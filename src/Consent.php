<?php
declare(strict_types=1);

namespace Smg;

use DateTimeImmutable;
use DateTimeZone;

final class Consent
{
    /** Bump this to force the dialog to reappear for everyone, even holders of a valid cookie. */
    public const CONSENT_VERSION = 1;

    public const ACCEPT_COOKIE = 'smg_consent';
    public const DECLINE_COOKIE = 'smg_consent_declined';

    private const ACCEPT_TTL_SECONDS = 365 * 24 * 60 * 60;
    private const DECLINE_TTL_SECONDS = 24 * 60 * 60;

    private const DISPLAY_TIMEZONE = 'Asia/Kuala_Lumpur';

    private const ALLOWED_REDIRECT_PAGES = ['index.php', 'about.php', 'privacy.php', 'terms.php'];

    /** Pages the blocking gate never covers: the documents its own copy asks visitors to read. */
    private const GATE_EXEMPT_PAGES = ['privacy.php', 'terms.php'];

    private const GUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    /**
     * Whether the blocking gate must appear: no valid accept cookie at (or above) the current
     * consent version, and no valid decline cookie either.
     */
    public static function shouldShowDialog(): bool
    {
        $accepted = self::readAcceptCookie();
        if ($accepted !== null && $accepted['version'] >= self::CONSENT_VERSION) {
            return false;
        }

        if ($accepted === null && self::hasValidDeclineCookie()) {
            return false;
        }

        return true;
    }

    public static function isManageRequested(): bool
    {
        return isset($_GET['consent']) && $_GET['consent'] === 'manage';
    }

    public static function isGateExemptPage(): bool
    {
        return in_array(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'), self::GATE_EXEMPT_PAGES, true);
    }

    /**
     * The visitor's own accepted-consent record, for display (e.g. the "Your consent record"
     * callout on the legal pages). Null if they've never accepted or their cookie is gone.
     *
     * @return array{guid: string, accepted_at: string, version: int}|null
     */
    public static function currentRecord(): ?array
    {
        return self::readAcceptCookie();
    }

    /**
     * How the consent prompt is presented on the current page:
     *  - visible:     the modal dialog is rendered open
     *  - dismissible: Esc/backdrop may close it (false while a choice is still mandatory)
     *  - banner:      the non-modal consent bar is rendered instead of forcing the dialog —
     *                 legal pages only, while a choice is still pending
     *
     * @return array{visible: bool, dismissible: bool, banner: bool}
     */
    public static function dialogState(): array
    {
        $pending = self::shouldShowDialog();
        $exempt = self::isGateExemptPage();
        $blocking = $pending && !$exempt;
        $visible = $blocking || self::isManageRequested();

        return [
            'visible' => $visible,
            'dismissible' => $visible && !$blocking,
            'banner' => $pending && $exempt,
        ];
    }

    /**
     * @return array{guid: string, accepted_at: DateTimeImmutable, expires_at: DateTimeImmutable}
     */
    public static function accept(): array
    {
        $existing = self::readAcceptCookie();
        $guid = $existing['guid'] ?? self::generateGuidV4();

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt = $now->modify('+365 days');

        self::writeAcceptCookie($guid, $now);
        self::clearDeclineCookie();
        self::logConsent($guid, 'accepted', $now, $expiresAt);

        return ['guid' => $guid, 'accepted_at' => $now, 'expires_at' => $expiresAt];
    }

    public static function decline(): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt = $now->modify('+1 day');

        self::writeDeclineCookie($now);
        self::clearAcceptCookie();
        self::logConsent(self::generateGuidV4(), 'declined', $now, $expiresAt);
    }

    /**
     * Validates a redirect target against the known public pages and strips the `consent`
     * query param, preventing an open redirect via a tampered `redirect_to` field.
     */
    public static function sanitizeRedirect(?string $path): string
    {
        $parts = parse_url((string) $path);
        $base = isset($parts['path']) ? basename($parts['path']) : 'index.php';

        if (!in_array($base, self::ALLOWED_REDIRECT_PAGES, true)) {
            $base = 'index.php';
        }

        $query = '';
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
            unset($params['consent']);
            if ($params !== []) {
                $query = '?' . http_build_query($params);
            }
        }

        return $base . $query;
    }

    public static function currentPath(): string
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        $queryString = $_SERVER['QUERY_STRING'] ?? '';

        return self::sanitizeRedirect($script . ($queryString !== '' ? '?' . $queryString : ''));
    }

    // ---------------------------------------------------------------- cookies

    /**
     * @return array{guid: string, accepted_at: string, version: int}|null
     */
    private static function readAcceptCookie(): ?array
    {
        $raw = $_COOKIE[self::ACCEPT_COOKIE] ?? null;
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['guid']) || empty($data['version']) || empty($data['accepted_at'])) {
            return null;
        }

        $guid = (string) $data['guid'];
        if (!preg_match(self::GUID_PATTERN, $guid)) {
            return null;
        }

        return [
            'guid' => $guid,
            'accepted_at' => (string) $data['accepted_at'],
            'version' => (int) $data['version'],
        ];
    }

    private static function hasValidDeclineCookie(): bool
    {
        return !empty($_COOKIE[self::DECLINE_COOKIE]);
    }

    private static function writeAcceptCookie(string $guid, DateTimeImmutable $nowUtc): void
    {
        $displayTime = $nowUtc->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));

        $payload = json_encode([
            'guid' => $guid,
            'accepted_at' => $displayTime->format(DATE_ATOM),
            'version' => self::CONSENT_VERSION,
        ], JSON_THROW_ON_ERROR);

        self::setCookie(self::ACCEPT_COOKIE, $payload, self::ACCEPT_TTL_SECONDS);
        $_COOKIE[self::ACCEPT_COOKIE] = $payload;
    }

    private static function writeDeclineCookie(DateTimeImmutable $nowUtc): void
    {
        $displayTime = $nowUtc->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE));
        $value = $displayTime->format(DATE_ATOM);

        self::setCookie(self::DECLINE_COOKIE, $value, self::DECLINE_TTL_SECONDS);
        $_COOKIE[self::DECLINE_COOKIE] = $value;
    }

    private static function clearAcceptCookie(): void
    {
        self::setCookie(self::ACCEPT_COOKIE, '', -3600);
        unset($_COOKIE[self::ACCEPT_COOKIE]);
    }

    private static function clearDeclineCookie(): void
    {
        self::setCookie(self::DECLINE_COOKIE, '', -3600);
        unset($_COOKIE[self::DECLINE_COOKIE]);
    }

    private static function setCookie(string $name, string $value, int $ttlSeconds): void
    {
        setcookie($name, $value, [
            'expires' => time() + $ttlSeconds,
            'path' => '/',
            'secure' => self::isSecureContext(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Whether cookies should get the Secure flag — used for the session cookie too. */
    public static function isSecureContext(): bool
    {
        $config = Config::get();
        $forced = $config['app']['force_https'] ?? null;
        if ($forced !== null) {
            return (bool) $forced;
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Railway (and most PaaS reverse proxies) terminates TLS at the edge and forwards to
        // the container over plain HTTP, so $_SERVER['HTTPS'] is never set even for a real
        // HTTPS visitor — the proxy's own header is the only signal. Only trust it when a
        // proxy is actually in front (this app has no other reverse-proxy deployment target).
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    // ---------------------------------------------------------------- persistence

    private static function logConsent(string $guid, string $action, DateTimeImmutable $whenUtc, DateTimeImmutable $expiresUtc): void
    {
        $pdo = Db::connection();

        $sql = 'INSERT INTO consent_log (guid, action, consent_version, accepted_at, expires_at, ip_address, user_agent)
                VALUES (:guid, :action, :version, :accepted_at, :expires_at, INET6_ATON(:ip), :ua)
                ON DUPLICATE KEY UPDATE
                    action = VALUES(action),
                    consent_version = VALUES(consent_version),
                    accepted_at = VALUES(accepted_at),
                    expires_at = VALUES(expires_at),
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':guid' => $guid,
            ':action' => $action,
            ':version' => self::CONSENT_VERSION,
            ':accepted_at' => $whenUtc->format('Y-m-d H:i:s'),
            ':expires_at' => $expiresUtc->format('Y-m-d H:i:s'),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private static function generateGuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        $hex = bin2hex($data);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

}
