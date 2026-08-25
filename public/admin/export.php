<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\Db;

Auth::requireLogin();

$utcTz = new DateTimeZone('UTC');
$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');

$q = trim((string) ($_GET['q'] ?? ''));
$escapedQ = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
$likePattern = '%' . $escapedQ . '%';

$pdo = Db::connection();
$stmt = $pdo->prepare(
    "SELECT guid, action, consent_version, accepted_at, expires_at FROM consent_log
     WHERE guid LIKE :q ESCAPE '\\\\' ORDER BY accepted_at DESC"
);
$stmt->execute([':q' => $likePattern]);

$filename = 'consent-export-' . (new DateTimeImmutable('now', $mstTz))->format('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['GUID', 'Action', 'Consent Version', 'Accepted At (MST)', 'Expires At (MST)', 'Status'], ',', '"', '\\');

while ($row = $stmt->fetch()) {
    $expiresAt = new DateTimeImmutable($row['expires_at'], $utcTz);
    $status = $row['action'] === 'declined'
        ? 'DECLINED'
        : ($expiresAt < new DateTimeImmutable('now', $utcTz) ? 'EXPIRED' : 'ACCEPTED');

    fputcsv($out, [
        $row['guid'],
        $row['action'],
        $row['consent_version'],
        (new DateTimeImmutable($row['accepted_at'], $utcTz))->setTimezone($mstTz)->format('Y-m-d H:i:s'),
        $expiresAt->setTimezone($mstTz)->format('Y-m-d H:i:s'),
        $status,
    ], ',', '"', '\\');
}

fclose($out);
exit;
