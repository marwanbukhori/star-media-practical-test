<?php
/**
 * The consent copy (verbatim from the requirements PDF — never reword it) and the Accept/Decline
 * form. Shared by consent-dialog.php and consent-banner.php so the copy exists in one place.
 *
 * @var string $consentActionsClass Class for the button row (differs between dialog and bar).
 */
$redirectTo = \Smg\Consent::currentPath();
?>
<div class="smg-consent-choice" data-smg-consent-choice>
  <div class="smg-consent-choice__copy">
    <p>Cookies are necessary for this website to function properly, for performance measurement, and to provide you with the best experience.</p>
    <p>By continuing to access or use this site, you acknowledge and consent to our use of cookies in accordance with our <a href="terms.php">Terms &amp; Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>
  </div>

  <form method="post" action="consent.php" class="<?php echo htmlspecialchars($consentActionsClass, ENT_QUOTES, 'UTF-8'); ?>" data-smg-consent-form>
    <?php echo \Smg\Csrf::field(); ?>
    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit" name="action" value="accept" class="smg-btn smg-btn--primary" data-smg-consent-accept>Accept</button>
    <button type="submit" name="action" value="decline" class="smg-btn smg-btn--ghost">Decline</button>
  </form>
</div>
