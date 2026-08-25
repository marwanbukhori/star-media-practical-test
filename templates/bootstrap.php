<?php
declare(strict_types=1);

require __DIR__ . '/../src/Db.php';
require __DIR__ . '/../src/Csrf.php';
require __DIR__ . '/../src/Consent.php';

use Smg\Consent;

session_start();

['visible' => $showDialog, 'dismissible' => $dismissible] = Consent::dialogState();
$consentGateOpen = $showDialog && !$dismissible;
