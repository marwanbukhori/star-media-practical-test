<?php
/** @var bool $consentGateOpen True while the blocking consent dialog is forced open. */
$consentGateOpen = $consentGateOpen ?? false;
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$year = date('Y');
?>
<footer class="smg-footer" <?php echo $consentGateOpen ? 'inert' : ''; ?>>
  <div class="smg-footer__inner smg-container">
    <p class="smg-footer__copy">&copy; <?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?> Star Media Group Berhad (Reg. No. 197101000523 (10894-D)). All rights reserved.</p>
    <nav class="smg-footer__links" aria-label="Legal">
      <a href="privacy.php">Privacy Policy</a>
      <a href="terms.php">Terms &amp; Conditions</a>
      <a
        href="<?php echo htmlspecialchars($currentScript, ENT_QUOTES, 'UTF-8'); ?>?consent=manage"
        data-smg-reopen-consent
      >Cookie settings</a>
    </nav>
  </div>
</footer>
