<?php
declare(strict_types=1);

namespace Smg;

use RuntimeException;

final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /**
     * Loads config once and caches it. Local dev (php -S) and PHPUnit both use a checked-out
     * file — config.php or, when tests define SMG_CONFIG_PATH, tests/config.test.php.
     *
     * A container (docker-compose, Railway) can't ship real secrets baked into an image, so
     * when MYSQLHOST is set — Railway's own MySQL plugin sets it automatically, and
     * docker-compose.yml sets the same name for the local `db` service — the whole config is
     * built from environment variables instead, and config.php is never read.
     *
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        if (self::$config === null) {
            self::$config = getenv('MYSQLHOST') !== false ? self::fromEnv() : self::fromFile();
        }

        return self::$config;
    }

    /** @return array<string, mixed> */
    private static function fromFile(): array
    {
        $path = defined('SMG_CONFIG_PATH') ? SMG_CONFIG_PATH : dirname(__DIR__) . '/config.php';

        // A bare `require` of a missing file is a fatal error the exception handler can't catch.
        if (!is_file($path)) {
            throw new RuntimeException("Config file not found at {$path} — copy config.example.php to config.php.");
        }

        return require $path;
    }

    /** @return array<string, mixed> */
    private static function fromEnv(): array
    {
        return [
            'db' => [
                'host'    => (string) getenv('MYSQLHOST'),
                'port'    => (int) (getenv('MYSQLPORT') ?: 3306),
                'name'    => (string) getenv('MYSQLDATABASE'),
                'user'    => (string) getenv('MYSQLUSER'),
                'pass'    => (string) getenv('MYSQLPASSWORD'),
                'charset' => 'utf8mb4',
            ],
            'mail' => [
                'from_address' => (string) (getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@starmediagroup.example'),
                'from_name'    => (string) (getenv('MAIL_FROM_NAME') ?: 'Star Media Group'),
                'to_address'   => (string) (getenv('MAIL_TO_ADDRESS') ?: 'contact@starmediagroup.example'),
            ],
            'admin' => [
                'session_name'     => (string) (getenv('ADMIN_SESSION_NAME') ?: 'smg_admin_session'),
                'login_rate_limit' => (int) (getenv('ADMIN_LOGIN_RATE_LIMIT') ?: 5),
            ],
            'app' => [
                // Left null (auto-detect) rather than forced true: docker-compose's local `db`
                // service sets these same env var names but is plain HTTP with no reverse
                // proxy, so forcing Secure here would silently break cookies in local testing.
                // Consent::isSecureContext() auto-detects Railway's real HTTPS via the
                // X-Forwarded-Proto header its edge proxy sets, which docker-compose never has.
                'force_https' => null,
                'timezone'    => (string) (getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur'),
            ],
        ];
    }
}
