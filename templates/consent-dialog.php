<?php
/**
 * @var bool $showDialog    Rendered open (gate forced it, or ?consent=manage was requested).
 * @var bool $dismissible   Esc/backdrop-click close it. False while a choice is mandatory.
 */
$showDialog = $showDialog ?? false;
$dismissible = $dismissible ?? false;
$redirectTo = \Smg\Consent::currentPath();
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

      <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you with the best experience.</p>
      <p>By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with our <a href="terms.php">Terms &amp; Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>

      <form method="post" action="consent.php" class="smg-consent-dialog__actions" data-smg-consent-form>
        <?php echo \Smg\Csrf::field(); ?>
        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" name="action" value="accept" class="smg-btn smg-btn--primary" data-smg-consent-accept>Accept</button>
        <button type="submit" name="action" value="decline" class="smg-btn smg-btn--ghost">Decline</button>
      </form>
    </div>
    <div class="smg-consent-dialog__footer">
      <span>Manage this any time via "Cookie settings" in the footer.</span>
    </div>
  </div>
</div>
