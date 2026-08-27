<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';
require __DIR__ . '/../src/AuditLog.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/ConsentQuery.php';

use Smg\Consent;

// See templates/bootstrap.php for why this exists — same reasoning applies to admin pages.
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
