<?php
declare(strict_types=1);

require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';
require __DIR__ . '/../src/Auth.php';

use Smg\Consent;

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => Consent::isSecureContext(),
]);
session_start();
