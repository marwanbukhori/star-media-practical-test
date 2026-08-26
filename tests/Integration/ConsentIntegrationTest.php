<?php
declare(strict_types=1);

namespace Smg\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Smg\Consent;
use Smg\Db;

/**
 * Exercises Consent::accept()/decline() against the real smg_consent_test database —
 * the parts CsrfTest/ConsentTest can't cover because they touch consent_log.
 */
final class ConsentIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Db::connection();
        $this->pdo->exec('TRUNCATE TABLE consent_log');

        $_COOKIE = [];
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit-integration';
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('TRUNCATE TABLE consent_log');
        $_COOKIE = [];
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        parent::tearDown();
    }

    public function testAcceptInsertsAConsentLogRowMatchingTheReturnedGuid(): void
    {
        $result = Consent::accept();

        $row = $this->fetchByGuid($result['guid']);

        $this->assertNotNull($row);
        $this->assertSame('accepted', $row['action']);
        $this->assertSame(Consent::CONSENT_VERSION, (int) $row['consent_version']);
        $this->assertSame($result['accepted_at']->format('Y-m-d H:i:s'), $row['accepted_at']);
        $this->assertSame($result['expires_at']->format('Y-m-d H:i:s'), $row['expires_at']);
    }

    public function testAcceptTwiceWithTheSameCookieUpdatesTheExistingRowInsteadOfDuplicating(): void
    {
        $first = Consent::accept();

        // accept() re-reads $_COOKIE for an existing GUID; writeAcceptCookie() already put
        // the encoded cookie back into $_COOKIE as a side effect, simulating a real repeat
        // visit within the same request-response cycle.
        $second = Consent::accept();

        $this->assertSame($first['guid'], $second['guid']);
        $this->assertSame(1, $this->countRows());

        $row = $this->fetchByGuid($first['guid']);
        $this->assertSame($second['accepted_at']->format('Y-m-d H:i:s'), $row['accepted_at']);
    }

    public function testDeclineInsertsADeclinedRowWithItsOwnGuid(): void
    {
        Consent::decline();

        $this->assertSame(1, $this->countRows());

        $stmt = $this->pdo->query('SELECT action FROM consent_log LIMIT 1');
        $row = $stmt->fetch();
        $this->assertSame('declined', $row['action']);
    }

    public function testAcceptAfterDeclineWritesASeparateRowRatherThanReusingTheDeclinedGuid(): void
    {
        Consent::decline();
        Consent::accept();

        $this->assertSame(2, $this->countRows());
    }

    private function countRows(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM consent_log')->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    private function fetchByGuid(string $guid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM consent_log WHERE guid = :guid');
        $stmt->execute([':guid' => $guid]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
