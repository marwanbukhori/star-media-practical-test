<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\AuditLog;
use Smg\ConsentQuery;
use Smg\Db;

Auth::requireLogin();

$utcTz = new DateTimeZone('UTC');
$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');

$q = trim((string) ($_GET['q'] ?? ''));
$status = ConsentQuery::normalizeStatus((string) ($_GET['status'] ?? ''));
$dateFrom = (string) ($_GET['from'] ?? '');
$dateTo = (string) ($_GET['to'] ?? '');

[$whereSql, $params] = ConsentQuery::buildWhere($q, $status, $dateFrom, $dateTo, $mstTz, $utcTz);

$filterSummary = trim(implode(' ', array_filter([
    $q !== '' ? "q={$q}" : '',
    $status !== '' ? "status={$status}" : '',
    $dateFrom !== '' ? "from={$dateFrom}" : '',
    $dateTo !== '' ? "to={$dateTo}" : '',
]))) ?: 'no filters';
AuditLog::record('export', Auth::currentUserId(), Auth::currentUsername(), $filterSummary);

$pdo = Db::connection();
$stmt = $pdo->prepare(
    "SELECT guid, action, consent_version, accepted_at, expires_at FROM consent_log
     WHERE {$whereSql} ORDER BY accepted_at DESC"
);
$stmt->execute($params);

$filename = 'consent-export-' . (new DateTimeImmutable('now', $mstTz))->format('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['GUID', 'Action', 'Consent Version', 'Accepted At (MST)', 'Expires At (MST)', 'Status'], ',', '"', '\\');

while ($row = $stmt->fetch()) {
    $expiresAt = new DateTimeImmutable($row['expires_at'], $utcTz);
    $status_ = $row['action'] === 'declined'
        ? 'DECLINED'
        : ($expiresAt < new DateTimeImmutable('now', $utcTz) ? 'EXPIRED' : 'ACCEPTED');

    fputcsv($out, [
        $row['guid'],
        $row['action'],
        $row['consent_version'],
        (new DateTimeImmutable($row['accepted_at'], $utcTz))->setTimezone($mstTz)->format('Y-m-d H:i:s'),
        $expiresAt->setTimezone($mstTz)->format('Y-m-d H:i:s'),
        $status_,
    ], ',', '"', '\\');
}

fclose($out);
exit;
