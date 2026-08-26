#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Db.php';

use Smg\Db;

fwrite(STDOUT, "Admin username: ");
$username = trim((string) fgets(STDIN));

fwrite(STDOUT, "Admin password (min 8 chars): ");
$hasStty = (bool) shell_exec('command -v stty');
if ($hasStty) {
    shell_exec('stty -echo');
}
$password = trim((string) fgets(STDIN));
if ($hasStty) {
    shell_exec('stty echo');
}
fwrite(STDOUT, "\n");

if ($username === '' || $password === '') {
    fwrite(STDERR, "Username and password are required.\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = Db::connection()->prepare(
    'INSERT INTO admin_users (username, password_hash) VALUES (:username, :hash)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
);
$stmt->execute([':username' => $username, ':hash' => $hash]);

fwrite(STDOUT, "Admin user '{$username}' created (or password updated).\n");
