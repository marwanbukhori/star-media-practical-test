<?php
/**
 * Non-modal consent bar for the gate-exempt legal pages (privacy.php, terms.php): the visitor can
 * read the page and make their choice here instead of being blocked by the dialog.
 */
$consentActionsClass = 'smg-consent-banner__actions';
?>
<section class="smg-consent-banner" aria-label="Cookie consent" data-smg-consent-banner>
  <div class="smg-container">
    <?php require __DIR__ . '/consent-form.php'; ?>
  </div>
</section>
