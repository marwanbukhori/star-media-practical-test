<?php
declare(strict_types=1);

namespace Smg;

final class Csrf
{
    private const SESSION_KEY = 'smg_csrf_token';

    public static function token(): string
    {
        self::ensureSession();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function verify(?string $submitted): bool
    {
        self::ensureSession();

        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($submitted) || !is_string($expected) || $submitted === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
