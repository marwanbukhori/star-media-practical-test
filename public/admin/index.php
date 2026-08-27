<?php
require __DIR__ . '/../../templates/admin-bootstrap.php';

use Smg\Auth;
use Smg\Consent;
use Smg\ConsentQuery;
use Smg\Db;

Auth::requireLogin();

$mstTz = new DateTimeZone('Asia/Kuala_Lumpur');
$utcTz = new DateTimeZone('UTC');

$pdo = Db::connection();

// ---------------------------------------------------------------- filters

$q = trim((string) ($_GET['q'] ?? ''));
$status = ConsentQuery::normalizeStatus((string) ($_GET['status'] ?? ''));
$dateFrom = (string) ($_GET['from'] ?? '');
$dateTo = (string) ($_GET['to'] ?? '');
$sort = ConsentQuery::normalizeSort((string) ($_GET['sort'] ?? ''));
$dir = ConsentQuery::normalizeDir((string) ($_GET['dir'] ?? ''));

[$whereSql, $params] = ConsentQuery::buildWhere($q, $status, $dateFrom, $dateTo, $mstTz, $utcTz);

/** @return array<string, string> */
function smg_active_filters(string $q, string $status, string $from, string $to, string $sort, string $dir): array
{
    return array_filter([
        'q' => $q,
        'status' => $status,
        'from' => $from,
        'to' => $to,
        'sort' => $sort,
        'dir' => strtolower($dir),
    ], static fn ($v) => $v !== '');
}

function smg_query_url(string $base, array $overrides, array $filters): string
{
    $merged = array_merge($filters, $overrides);
    $merged = array_filter($merged, static fn ($v) => $v !== '' && $v !== null);

    return $base . ($merged ? '?' . http_build_query($merged) : '');
}

$activeFilters = smg_active_filters($q, $status, $dateFrom, $dateTo, $sort, $dir);

// ---------------------------------------------------------------- table + pagination

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM consent_log WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listSql = "SELECT guid, action, consent_version, accepted_at, expires_at FROM consent_log
            WHERE {$whereSql} ORDER BY {$sort} {$dir} LIMIT :limit OFFSET :offset";
$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

// ---------------------------------------------------------------- stat cards

$acceptedStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM consent_log WHERE action = 'accepted'");
$acceptedStmt->execute();
$acceptedTotal = (int) $acceptedStmt->fetch()['c'];

$todayStartMst = new DateTimeImmutable('today', $mstTz);
$todayEndMst = $todayStartMst->modify('+1 day');
$todayStmt = $pdo->prepare(
    "SELECT COUNT(*) AS c FROM consent_log WHERE action = 'accepted' AND accepted_at >= :start AND accepted_at < :end"
);
$todayStmt->execute([
    ':start' => $todayStartMst->setTimezone($utcTz)->format('Y-m-d H:i:s'),
    ':end' => $todayEndMst->setTimezone($utcTz)->format('Y-m-d H:i:s'),
]);
$todayCount = (int) $todayStmt->fetch()['c'];

// ---------------------------------------------------------------- 14-day trend chart

$trendDays = 14;
$trendStartMst = $todayStartMst->modify('-' . ($trendDays - 1) . ' days');
$trendStmt = $pdo->prepare(
    "SELECT DATE(CONVERT_TZ(accepted_at, '+00:00', '+08:00')) AS day, action, COUNT(*) AS c
     FROM consent_log
     WHERE accepted_at >= :start
     GROUP BY day, action"
);
$trendStmt->execute([':start' => $trendStartMst->setTimezone($utcTz)->format('Y-m-d H:i:s')]);

$trendData = [];
foreach ($trendStmt->fetchAll() as $row) {
    $trendData[$row['day']][$row['action']] = (int) $row['c'];
}

$trend = [];
$trendMax = 1;
for ($i = 0; $i < $trendDays; $i++) {
    $day = $trendStartMst->modify("+{$i} days");
    $key = $day->format('Y-m-d');
    $accepted = $trendData[$key]['accepted'] ?? 0;
    $declined = $trendData[$key]['declined'] ?? 0;
    $trend[] = ['label' => $day->format('j M'), 'accepted' => $accepted, 'declined' => $declined];
    $trendMax = max($trendMax, $accepted + $declined);
}

// ---------------------------------------------------------------- display helpers

function smg_row_status(array $row, DateTimeZone $utcTz): string
{
    if ($row['action'] === 'declined') {
        return 'declined';
    }
    $expiresAt = new DateTimeImmutable($row['expires_at'], $utcTz);
    $now = new DateTimeImmutable('now', $utcTz);

    return $expiresAt < $now ? 'expired' : 'accepted';
}

function smg_format_mst(string $utcDatetime, DateTimeZone $utcTz, DateTimeZone $mstTz): string
{
    return (new DateTimeImmutable($utcDatetime, $utcTz))->setTimezone($mstTz)->format('j M Y, g:i A');
}

function smg_sort_href(string $column, string $currentSort, string $currentDir, array $filters): string
{
    $nextDir = ($currentSort === $column && $currentDir === 'ASC') ? 'desc' : 'asc';

    return smg_query_url('index.php', ['sort' => $column, 'dir' => $nextDir, 'page' => 1], $filters);
}

function smg_sort_indicator(string $column, string $currentSort, string $currentDir): string
{
    if ($currentSort !== $column) {
        return '';
    }

    return $currentDir === 'ASC' ? ' ↑' : ' ↓';
}

$statusLabels = ['accepted' => 'ACCEPTED', 'declined' => 'DECLINED', 'expired' => 'EXPIRED'];
$rangeStart = $total === 0 ? 0 : $offset + 1;
$rangeEnd = min($offset + $perPage, $total);

$exportHref = smg_query_url('export.php', [], $activeFilters);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consent acceptances — Consent Admin</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.png">
  <link rel="apple-touch-icon" href="../assets/images/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="../assets/css/tokens.css">
  <link rel="stylesheet" href="../assets/css/site.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="smg-admin-page">
  <header class="smg-admin-topbar">
    <div class="smg-admin-topbar__inner">
      <div class="smg-admin-login__brand">
        <img src="../assets/images/smg-logo-on-ink.png" alt="Star Media Group" class="smg-admin-login__brand-logo">
        <span class="smg-admin-login__brand-text">Consent Admin</span>
      </div>
      <div class="smg-admin-topbar__actions">
        <a href="audit.php" class="smg-admin-topbar__user-link">Audit log</a>
        <a href="change-password.php" class="smg-admin-topbar__user-link">Change password</a>
        <span class="smg-admin-topbar__user"><?php echo htmlspecialchars((string) Auth::currentUsername(), ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="logout.php" class="smg-btn smg-btn--ghost smg-btn--sm">Log out</a>
      </div>
    </div>
  </header>

  <main class="smg-admin-main">
    <div class="smg-admin-stats">
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Accepted</p>
        <p class="smg-stat-card__value"><?php echo number_format($acceptedTotal); ?></p>
      </div>
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Today</p>
        <p class="smg-stat-card__value"><?php echo number_format($todayCount); ?></p>
      </div>
      <div class="smg-stat-card">
        <p class="smg-eyebrow smg-eyebrow--muted">Notice version</p>
        <p class="smg-stat-card__value"><?php echo (int) Consent::CONSENT_VERSION; ?></p>
      </div>
    </div>

    <div class="smg-admin-trend-card">
      <div class="smg-admin-trend-card__head">
        <h2>Last 14 days</h2>
        <div class="smg-admin-trend-legend">
          <span class="smg-admin-trend-legend__item"><i class="smg-admin-trend-swatch smg-admin-trend-swatch--accepted"></i>Accepted</span>
          <span class="smg-admin-trend-legend__item"><i class="smg-admin-trend-swatch smg-admin-trend-swatch--declined"></i>Declined</span>
        </div>
      </div>
      <div class="smg-admin-trend-chart">
        <?php foreach ($trend as $day): ?>
          <div class="smg-admin-trend-bar" title="<?php echo htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8'); ?>: <?php echo $day['accepted']; ?> accepted, <?php echo $day['declined']; ?> declined">
            <div class="smg-admin-trend-bar__stack">
              <span class="smg-admin-trend-bar__segment smg-admin-trend-bar__segment--declined" style="height: <?php echo round($day['declined'] / $trendMax * 100); ?>%"></span>
              <span class="smg-admin-trend-bar__segment smg-admin-trend-bar__segment--accepted" style="height: <?php echo round($day['accepted'] / $trendMax * 100); ?>%"></span>
            </div>
            <span class="smg-admin-trend-bar__label"><?php echo htmlspecialchars($day['label'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="smg-admin-table-card">
      <div class="smg-admin-table-card__head">
        <h2>Consent acceptances</h2>
        <form method="get" action="index.php" class="smg-admin-table-card__controls">
          <input type="search" name="q" placeholder="Search GUID" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>">
          <select name="status">
            <option value="">All statuses</option>
            <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
            <option value="declined" <?php echo $status === 'declined' ? 'selected' : ''; ?>>Declined</option>
            <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
          </select>
          <label class="smg-admin-table-card__date-label">
            From
            <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
          </label>
          <label class="smg-admin-table-card__date-label">
            To
            <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
          </label>
          <button type="submit" class="smg-btn smg-btn--ghost smg-btn--sm">Filter</button>
          <a class="smg-btn smg-btn--ghost smg-btn--sm" href="<?php echo htmlspecialchars($exportHref, ENT_QUOTES, 'UTF-8'); ?>">Export CSV</a>
        </form>
      </div>

      <div class="smg-admin-table-scroll">
        <table>
          <thead>
            <tr>
              <th>GUID</th>
              <th><a href="<?php echo htmlspecialchars(smg_sort_href('accepted_at', $sort, $dir, $activeFilters), ENT_QUOTES, 'UTF-8'); ?>">Accepted at<?php echo smg_sort_indicator('accepted_at', $sort, $dir); ?></a></th>
              <th><a href="<?php echo htmlspecialchars(smg_sort_href('consent_version', $sort, $dir, $activeFilters), ENT_QUOTES, 'UTF-8'); ?>">Ver<?php echo smg_sort_indicator('consent_version', $sort, $dir); ?></a></th>
              <th><a href="<?php echo htmlspecialchars(smg_sort_href('expires_at', $sort, $dir, $activeFilters), ENT_QUOTES, 'UTF-8'); ?>">Expires<?php echo smg_sort_indicator('expires_at', $sort, $dir); ?></a></th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="5" class="smg-admin-table-empty">No consent records match this search.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
              <?php $status_ = smg_row_status($row, $utcTz); ?>
              <tr>
                <td class="smg-admin-table-guid">
                  <a href="record.php?guid=<?php echo urlencode($row['guid']); ?>"><?php echo htmlspecialchars($row['guid'], ENT_QUOTES, 'UTF-8'); ?></a>
                </td>
                <td><?php echo htmlspecialchars(smg_format_mst($row['accepted_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $row['consent_version']; ?></td>
                <td><?php echo htmlspecialchars(smg_format_mst($row['expires_at'], $utcTz, $mstTz), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="smg-pill smg-pill--<?php echo $status_; ?>"><?php echo $statusLabels[$status_]; ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="smg-admin-table-card__footer">
        <span>Showing <?php echo $rangeStart; ?>–<?php echo $rangeEnd; ?> of <?php echo number_format($total); ?></span>
        <nav class="smg-pager" aria-label="Pagination">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?php echo htmlspecialchars(smg_query_url('index.php', ['page' => $p], $activeFilters), ENT_QUOTES, 'UTF-8'); ?>"
               class="smg-pager__item<?php echo $p === $page ? ' is-current' : ''; ?>"><?php echo $p; ?></a>
          <?php endfor; ?>
        </nav>
      </div>
    </div>
  </main>
</body>
</html>
