<?php
declare(strict_types=1);

namespace Smg\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Smg\Auth;
use Smg\Db;

/**
 * Exercises Auth::isRateLimited()/retryAfterSeconds() against the real login_attempts
 * table — tests/config.test.php pins admin.login_rate_limit to 5, matching production.
 */
final class AuthIntegrationTest extends TestCase
{
    private const IP = '203.0.113.20';

    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Db::connection();
        $this->pdo->exec('TRUNCATE TABLE login_attempts');

        $_SERVER['REMOTE_ADDR'] = self::IP;
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('TRUNCATE TABLE login_attempts');
        unset($_SERVER['REMOTE_ADDR']);
        parent::tearDown();
    }

    public function testIsRateLimitedIsFalseBelowTheThreshold(): void
    {
        $this->seedAttempts(4);

        $this->assertFalse(Auth::isRateLimited());
    }

    public function testIsRateLimitedIsTrueAtTheThreshold(): void
    {
        $this->seedAttempts(5);

        $this->assertTrue(Auth::isRateLimited());
    }

    public function testIsRateLimitedIgnoresAttemptsFromADifferentIp(): void
    {
        $this->seedAttempts(5);
        $this->pdo->exec("UPDATE login_attempts SET ip_address = INET6_ATON('198.51.100.1')");

        $this->assertFalse(Auth::isRateLimited());
    }

    public function testIsRateLimitedIgnoresAttemptsOutsideTheOneMinuteWindow(): void
    {
        $this->seedAttempts(5);
        $this->pdo->exec(
            "UPDATE login_attempts SET attempted_at = UTC_TIMESTAMP() - INTERVAL 90 SECOND"
        );

        $this->assertFalse(Auth::isRateLimited());
    }

    public function testRetryAfterSecondsIsZeroWithNoAttempts(): void
    {
        $this->assertSame(0, Auth::retryAfterSeconds());
    }

    public function testRetryAfterSecondsCountsDownFromTheOldestAttemptInTheWindow(): void
    {
        $this->seedAttempts(1);
        $this->pdo->exec(
            "UPDATE login_attempts SET attempted_at = UTC_TIMESTAMP() - INTERVAL 20 SECOND"
        );

        // 60s window minus the 20s already elapsed, allowing a couple of seconds of slack
        // for the time it takes this test itself to run.
        $retryAfter = Auth::retryAfterSeconds();
        $this->assertGreaterThanOrEqual(38, $retryAfter);
        $this->assertLessThanOrEqual(40, $retryAfter);
    }

    private function seedAttempts(int $count): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (ip_address, username, succeeded) VALUES (INET6_ATON(:ip), :username, 0)'
        );
        for ($i = 0; $i < $count; $i++) {
            $stmt->execute([':ip' => self::IP, ':username' => 'attacker']);
        }
    }
}
