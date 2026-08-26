<?php
declare(strict_types=1);

// Test-only config — no secrets, safe to commit (same spirit as config.example.php).
// Points at a separate database so tests never touch local dev data.
return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'smg_consent_test',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'from_address' => 'no-reply@starmediagroup.example',
        'from_name'    => 'Star Media Group',
        'to_address'   => 'contact@starmediagroup.example',
    ],
    'admin' => [
        'session_name'     => 'smg_admin_session_test',
        'login_rate_limit' => 5,
    ],
    'app' => [
        'force_https' => false,
        'timezone'    => 'Asia/Kuala_Lumpur',
    ],
];
