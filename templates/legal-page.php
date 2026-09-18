<?php
/**
 * Shared chrome for privacy.php / terms.php. The including page must set:
 * $activePage, $pageTitle, $legalTitle, $effectiveDate, $readingMinutes, $tocItems, $articleBody
 */

use Smg\Consent;

$record = Consent::currentRecord();
$acceptedLabel = null;
$expiresLabel = null;
if ($record !== null) {
    try {
        $acceptedAt = new DateTimeImmutable($record['accepted_at']);
        $acceptedLabel = $acceptedAt->format('j M Y, g:i A \M\S\T');
        $expiresLabel = $acceptedAt->modify('+365 days')->format('j M Y');
    } catch (Exception $e) {
        $record = null;
    }
}
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'privacy.php');
?>
<!doctype html>
<html lang="en" class="<?php echo $showDialog ? 'smg-locked' : ''; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
  <?php require __DIR__ . '/header.php'; ?>

  <main <?php echo $consentGateOpen ? 'inert' : ''; ?>>
    <section class="smg-legal">
      <div class="smg-container smg-legal__grid">
        <nav class="smg-legal-toc" aria-label="On this page">
          <p class="smg-eyebrow smg-eyebrow--muted">On this page</p>
          <ul>
            <?php foreach ($tocItems as $item): ?>
              <li><a href="#<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>

        <article class="smg-legal-article">
          <p class="smg-eyebrow">Legal</p>
          <h1><?php echo htmlspecialchars($legalTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="smg-legal-article__meta">
            Version <?php echo (int) Consent::CONSENT_VERSION; ?> ·
            Effective <?php echo htmlspecialchars($effectiveDate, ENT_QUOTES, 'UTF-8'); ?> ·
            Reading time about <?php echo (int) $readingMinutes; ?> minutes
          </p>

          <?php echo $articleBody; ?>

          <div class="smg-consent-record">
            <p class="smg-eyebrow smg-eyebrow--muted">Your consent record</p>
            <?php if ($record !== null): ?>
              <p class="smg-consent-record__explainer">This is the consent record stored on this device. It identifies your browser only, never your name or email.</p>
              <div class="smg-consent-record__chips">
                <span class="smg-chip">guid: <?php echo htmlspecialchars($record['guid'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="smg-chip">accepted_at: <?php echo htmlspecialchars((string) $acceptedLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="smg-chip">consent_version = <?php echo (int) $record['version']; ?></span>
                <span class="smg-chip smg-chip--red">expires <?php echo htmlspecialchars((string) $expiresLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            <?php else: ?>
              <p class="smg-consent-record__explainer">
                No consent record is stored on this device yet.
                <a href="<?php echo htmlspecialchars($currentScript, ENT_QUOTES, 'UTF-8'); ?>?consent=manage" data-smg-reopen-consent>Review cookie settings</a>
                to create one.
              </p>
            <?php endif; ?>
          </div>
        </article>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/footer.php'; ?>
  <?php if ($showBanner): ?>
    <?php require __DIR__ . '/consent-banner.php'; ?>
  <?php endif; ?>
  <?php require __DIR__ . '/consent-dialog.php'; ?>
  <script src="assets/js/consent.js" defer></script>
</body>
</html>
