<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';
require __DIR__ . '/../src/ErrorPage.php';

use Smg\Consent;
use Smg\ErrorPage;

// A visitor should never see a raw PHP stack trace (file paths, DB error internals) — the
// default is display_errors=On, so without this an uncaught exception (e.g. Db::connection()
// exhausting its retries during a slow PaaS cold start) prints exactly that. Logs the real
// error server-side (visible via `railway logs`) and shows a plain, generic message instead.
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

['visible' => $showDialog, 'dismissible' => $dismissible, 'banner' => $showBanner] = Consent::dialogState();
$consentGateOpen = $showDialog && !$dismissible;
