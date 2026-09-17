<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';
require __DIR__ . '/../src/AuditLog.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/ConsentQuery.php';
require __DIR__ . '/../src/ErrorPage.php';

use Smg\Consent;
use Smg\ErrorPage;

// See templates/bootstrap.php for why this exists — same reasoning applies to admin pages.
set_exception_handler(function (\Throwable $e): void {
    error_log((string) $e);
    ErrorPage::render(503);
});

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => Consent::isSecureContext(),
]);
session_start();
