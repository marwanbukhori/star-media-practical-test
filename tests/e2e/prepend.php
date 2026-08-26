<?php
declare(strict_types=1);

// Auto-prepended (php -d auto_prepend_file=...) to the built-in server Playwright starts,
// so E2E runs hit the isolated smg_consent_test database instead of local dev data — the
// same SMG_CONFIG_PATH mechanism tests/bootstrap.php uses for PHPUnit.
define('SMG_CONFIG_PATH', dirname(__DIR__) . '/config.test.php');
