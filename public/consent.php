<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';

use Smg\Consent;
use Smg\Csrf;

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => Consent::isSecureContext(),
]);
session_start();

$action = $_POST['action'] ?? null;
$csrfToken = $_POST['csrf_token'] ?? null;
$redirectTo = $_POST['redirect_to'] ?? 'index.php';
$isFetch = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

$valid = $_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($action, ['accept', 'decline'], true)
    && Csrf::verify(is_string($csrfToken) ? $csrfToken : null);

if (!$valid) {
    http_response_code(400);
    if ($isFetch) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'invalid_request']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid consent request.';
    }
    exit;
}

if ($action === 'accept') {
    Consent::accept();
} else {
    Consent::decline();
}

if ($isFetch) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'action' => $action]);
    exit;
}

header('Location: ' . Consent::sanitizeRedirect(is_string($redirectTo) ? $redirectTo : null), true, 303);
exit;
