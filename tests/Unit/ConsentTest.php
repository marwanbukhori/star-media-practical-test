<?php
declare(strict_types=1);

namespace Smg\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Smg\Consent;

final class ConsentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_COOKIE = [];
        $_GET = [];
        $_SERVER['HTTPS'] = 'on';
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        $_GET = [];
        unset($_SERVER['HTTPS'], $_SERVER['SCRIPT_NAME'], $_SERVER['QUERY_STRING']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- generateGuidV4

    public function testGenerateGuidV4ProducesARfc4122VersionFourGuid(): void
    {
        $method = new ReflectionMethod(Consent::class, 'generateGuidV4');

        $guid = $method->invoke(null);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $guid
        );
    }

    public function testGenerateGuidV4ProducesDistinctValuesAcrossCalls(): void
    {
        $method = new ReflectionMethod(Consent::class, 'generateGuidV4');

        $this->assertNotSame($method->invoke(null), $method->invoke(null));
    }

    // ---------------------------------------------------------------- sanitizeRedirect

    public function testSanitizeRedirectAllowsAWhitelistedPage(): void
    {
        $this->assertSame('about.php', Consent::sanitizeRedirect('about.php'));
    }

    public function testSanitizeRedirectFallsBackToIndexForAnUnknownPage(): void
    {
        $this->assertSame('index.php', Consent::sanitizeRedirect('admin/login.php'));
    }

    public function testSanitizeRedirectFallsBackToIndexForNull(): void
    {
        $this->assertSame('index.php', Consent::sanitizeRedirect(null));
    }

    public function testSanitizeRedirectStripsTheConsentQueryParamButKeepsOthers(): void
    {
        $this->assertSame(
            'privacy.php?ref=email',
            Consent::sanitizeRedirect('privacy.php?consent=manage&ref=email')
        );
    }

    public function testSanitizeRedirectDropsAnEmptyQueryStringEntirely(): void
    {
        $this->assertSame('terms.php', Consent::sanitizeRedirect('terms.php?consent=manage'));
    }

    // ---------------------------------------------------------------- currentPath

    public function testCurrentPathFallsBackToIndexButKeepsTheQueryString(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/consent.php';
        $_SERVER['QUERY_STRING'] = 'consent=manage&foo=bar';

        $this->assertSame('index.php?foo=bar', Consent::currentPath());
    }

    public function testCurrentPathKeepsAWhitelistedPageWithNoQueryString(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/about.php';
        $_SERVER['QUERY_STRING'] = '';

        $this->assertSame('about.php', Consent::currentPath());
    }

    // ---------------------------------------------------------------- shouldShowDialog / dialogState

    public function testShouldShowDialogIsTrueWithNoCookiesAtAll(): void
    {
        $this->assertTrue(Consent::shouldShowDialog());

        $state = Consent::dialogState();
        $this->assertTrue($state['visible']);
        $this->assertFalse($state['dismissible']);
    }

    public function testShouldShowDialogIsFalseWithAValidCurrentVersionAcceptCookie(): void
    {
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => '11111111-1111-4111-8111-111111111111',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => Consent::CONSENT_VERSION,
        ]);

        $this->assertFalse(Consent::shouldShowDialog());
    }

    public function testShouldShowDialogIsFalseWhenTheAcceptCookieVersionIsAheadOfCurrent(): void
    {
        // A cookie from a later CONSENT_VERSION than the one currently deployed (e.g. a
        // rollback) still satisfies the `>= CONSENT_VERSION` check.
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => '11111111-1111-4111-8111-111111111111',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => Consent::CONSENT_VERSION + 1,
        ]);

        $this->assertFalse(Consent::shouldShowDialog());
    }

    public function testShouldShowDialogIsFalseWithOnlyAValidDeclineCookie(): void
    {
        $_COOKIE[Consent::DECLINE_COOKIE] = '2026-01-01T00:00:00+08:00';

        $this->assertFalse(Consent::shouldShowDialog());
    }

    public function testShouldShowDialogTreatsAZeroVersionAcceptCookieAsAbsentSoTheDeclineCookieStillApplies(): void
    {
        // readAcceptCookie() rejects a cookie whose version is falsy (empty(0) === true),
        // so a version-0 cookie is indistinguishable from no cookie at all — the decline
        // cookie then takes over as if the accept cookie were never set.
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => '11111111-1111-4111-8111-111111111111',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => 0,
        ]);
        $_COOKIE[Consent::DECLINE_COOKIE] = '2026-01-01T00:00:00+08:00';

        $this->assertFalse(Consent::shouldShowDialog());
    }

    public function testDialogStateIsDismissibleWhenManageIsRequestedAndConsentIsAlreadyGiven(): void
    {
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => '11111111-1111-4111-8111-111111111111',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => Consent::CONSENT_VERSION,
        ]);
        $_GET['consent'] = 'manage';

        $state = Consent::dialogState();
        $this->assertTrue($state['visible']);
        $this->assertTrue($state['dismissible']);
    }

    public function testDialogStateIsNotDismissibleWhenManageIsRequestedButConsentIsStillForced(): void
    {
        $_GET['consent'] = 'manage';

        $state = Consent::dialogState();
        $this->assertTrue($state['visible']);
        $this->assertFalse($state['dismissible']);
    }

    // ---------------------------------------------------------------- currentRecord

    public function testCurrentRecordIsNullWithNoAcceptCookie(): void
    {
        $this->assertNull(Consent::currentRecord());
    }

    public function testCurrentRecordReturnsTheDecodedCookieFields(): void
    {
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => '11111111-1111-4111-8111-111111111111',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => Consent::CONSENT_VERSION,
        ]);

        $record = Consent::currentRecord();

        $this->assertSame('11111111-1111-4111-8111-111111111111', $record['guid']);
        $this->assertSame('2026-01-01T00:00:00+08:00', $record['accepted_at']);
        $this->assertSame(Consent::CONSENT_VERSION, $record['version']);
    }

    public function testCurrentRecordIsNullWhenTheGuidDoesNotMatchTheExpectedFormat(): void
    {
        $_COOKIE[Consent::ACCEPT_COOKIE] = json_encode([
            'guid' => 'not-a-real-guid',
            'accepted_at' => '2026-01-01T00:00:00+08:00',
            'version' => Consent::CONSENT_VERSION,
        ]);

        $this->assertNull(Consent::currentRecord());
    }

    public function testCurrentRecordIsNullForMalformedJson(): void
    {
        $_COOKIE[Consent::ACCEPT_COOKIE] = '{not json';

        $this->assertNull(Consent::currentRecord());
    }

    // ---------------------------------------------------------------- isSecureContext

    public function testIsSecureContextHonoursTheForceHttpsConfigValue(): void
    {
        // tests/config.test.php pins app.force_https to false, which must win even though
        // $_SERVER['HTTPS'] is set to 'on' in setUp().
        $this->assertFalse(Consent::isSecureContext());
    }
}
