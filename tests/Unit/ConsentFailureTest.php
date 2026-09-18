<?php
declare(strict_types=1);

namespace Smg\Tests;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Smg\Consent;
use Smg\Db;

/**
 * What Consent does when the database is unavailable: accept must fail closed (no cookie without a
 * consent_log row), decline must still be honoured (its row is optional).
 */
final class ConsentFailureTest extends TestCase
{
    private string|false $previousErrorLog = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is required to simulate a failing database.');
        }

        $_COOKIE = [];
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        // decline() error_log()s the failure by design; keep it out of the test output.
        $this->previousErrorLog = ini_set('error_log', '/dev/null');

        // An empty in-memory SQLite database has no consent_log table, so every insert throws a
        // real PDOException — the same code path as an unreachable MySQL server.
        $this->setDbInstance(new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]));
    }

    protected function tearDown(): void
    {
        $this->setDbInstance(null);
        if ($this->previousErrorLog !== false) {
            ini_set('error_log', $this->previousErrorLog);
        }
        $_COOKIE = [];
        unset($_SERVER['HTTPS'], $_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    public function testAcceptThrowsAndSetsNoAcceptCookieWhenTheConsentLogInsertFails(): void
    {
        try {
            Consent::accept();
            $this->fail('Consent::accept() should throw when consent_log cannot be written.');
        } catch (PDOException) {
            $this->assertArrayNotHasKey(Consent::ACCEPT_COOKIE, $_COOKIE);
        }
    }

    public function testAcceptLeavesAnExistingDeclineCookieInPlaceWhenTheInsertFails(): void
    {
        $_COOKIE[Consent::DECLINE_COOKIE] = '2026-01-01T00:00:00+08:00';

        try {
            Consent::accept();
            $this->fail('Consent::accept() should throw when consent_log cannot be written.');
        } catch (PDOException) {
            $this->assertArrayHasKey(Consent::DECLINE_COOKIE, $_COOKIE);
        }
    }

    public function testDeclineStillSetsTheDeclineCookieWhenTheConsentLogInsertFails(): void
    {
        Consent::decline();

        $this->assertArrayHasKey(Consent::DECLINE_COOKIE, $_COOKIE);
        $this->assertArrayNotHasKey(Consent::ACCEPT_COOKIE, $_COOKIE);
    }

    private function setDbInstance(?PDO $pdo): void
    {
        (new ReflectionProperty(Db::class, 'instance'))->setValue(null, $pdo);
    }
}
