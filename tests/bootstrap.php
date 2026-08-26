<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Redirects Smg\Config::get() at every class (Db, Consent, Auth, Mailer) to the test
// config instead of the real config.php — defined once, here, before anything else runs.
define('SMG_CONFIG_PATH', __DIR__ . '/config.test.php');
