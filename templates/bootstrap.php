<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';

use Smg\Consent;

// A visitor should never see a raw PHP stack trace (file paths, DB error internals) — the
// default is display_errors=On, so without this an uncaught exception (e.g. Db::connection()
// exhausting its retries during a slow PaaS cold start) prints exactly that. Logs the real
// error server-side (visible via `railway logs`) and shows a plain, generic message instead.
set_exception_handler(function (\Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<title>Star Media Group</title></head><body style="font-family:sans-serif;'
        . 'max-width:32rem;margin:4rem auto;padding:0 1.5rem;color:#1a1a1a;">'
        . '<h1 style="font-size:1.25rem;">We hit a snag</h1>'
        . '<p>Something went wrong loading this page. Please try again in a moment.</p>'
        . '</body></html>';
});

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => Consent::isSecureContext(),
]);
session_start();

['visible' => $showDialog, 'dismissible' => $dismissible] = Consent::dialogState();
$consentGateOpen = $showDialog && !$dismissible;
