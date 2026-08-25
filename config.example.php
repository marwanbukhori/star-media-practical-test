<?php
declare(strict_types=1);

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'smg_consent',
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
        'session_name'     => 'smg_admin_session',
        // Max login attempts allowed per IP per 60 seconds.
        'login_rate_limit' => 5,
    ],

    'app' => [
        // Leave null to auto-detect via $_SERVER['HTTPS']; set true/false to force
        // (controls whether cookies get the Secure flag — useful for local HTTP testing).
        'force_https' => null,
        // Display timezone (MST / UTC+8). The DB always stores UTC.
        'timezone'    => 'Asia/Kuala_Lumpur',
    ],
];
