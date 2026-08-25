<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;

Auth::logout();

header('Location: login.php', true, 303);
exit;
