<?php
declare(strict_types=1);

namespace Smg;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class Auth
{
    private const SESSION_USER_ID = 'smg_admin_user_id';
    private const SESSION_USERNAME = 'smg_admin_username';
    private const RATE_LIMIT_WINDOW_SECONDS = 60;

    public static function attemptLogin(string $username, string $password): bool
    {
        if (self::isRateLimited()) {
            return false;
        }

        $pdo = Db::connection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        $ok = $admin !== false && password_verify($password, $admin['password_hash']);

        self::recordAttempt($username, $ok);

        if (!$ok) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_ID] = (int) $admin['id'];
        $_SESSION[self::SESSION_USERNAME] = $admin['username'];

        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $update = $pdo->prepare('UPDATE admin_users SET last_login_at = :now WHERE id = :id');
        $update->execute([':now' => $now, ':id' => $admin['id']]);

        return true;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php', true, 303);
            exit;
        }
    }

    public static function currentUsername(): ?string
    {
        return $_SESSION[self::SESSION_USERNAME] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?: 'Lax',
            ]);
        }

        session_destroy();
    }

    private static function isRateLimited(): bool
    {
        $config = self::config();
        $limit = (int) ($config['admin']['login_rate_limit'] ?? 5);

        $pdo = Db::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS attempts FROM login_attempts
             WHERE ip_address = INET6_ATON(:ip) AND attempted_at >= (UTC_TIMESTAMP() - INTERVAL :window SECOND)'
        );
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bindValue(':window', self::RATE_LIMIT_WINDOW_SECONDS, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return ((int) $row['attempts']) >= $limit;
    }

    private static function recordAttempt(string $username, bool $succeeded): void
    {
        $pdo = Db::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (ip_address, username, succeeded) VALUES (INET6_ATON(:ip), :username, :succeeded)'
        );
        $stmt->execute([
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':username' => substr($username, 0, 64),
            ':succeeded' => $succeeded ? 1 : 0,
        ]);
    }

    /** @return array<string, mixed> */
    private static function config(): array
    {
        static $config = null;
        if ($config === null) {
            $config = require dirname(__DIR__) . '/config.php';
        }

        return $config;
    }
}
