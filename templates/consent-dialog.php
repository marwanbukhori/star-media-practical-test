<?php
/**
 * @var bool $showDialog    Rendered open (gate forced it, or ?consent=manage was requested).
 * @var bool $dismissible   Esc/backdrop-click close it. False while a choice is mandatory.
 */
$showDialog = $showDialog ?? false;
$dismissible = $dismissible ?? false;
$consentActionsClass = 'smg-consent-dialog__actions';
?>
<div
  class="smg-consent-overlay"
  data-smg-consent-overlay
  data-dismissible="<?php echo $dismissible ? '1' : '0'; ?>"
  <?php echo $showDialog ? '' : 'hidden'; ?>
>
  <div
    class="smg-consent-dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="smg-consent-title"
    data-smg-consent-dialog
  >
    <div class="smg-consent-dialog__body">
      <div class="smg-consent-dialog__chip">
        <span class="smg-consent-dialog__chip-mark" aria-hidden="true">★</span>
        <span>Cookie notice · v<?php echo (int) \Smg\Consent::CONSENT_VERSION; ?></span>
      </div>

      <h2 id="smg-consent-title">Before you continue</h2>

      <?php require __DIR__ . '/consent-form.php'; ?>
    </div>
    <div class="smg-consent-dialog__footer">
      <span>Manage this any time via "Cookie settings" in the footer.</span>
    </div>
  </div>
</div>
