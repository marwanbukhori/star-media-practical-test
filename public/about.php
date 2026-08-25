<?php
require __DIR__ . '/../templates/bootstrap.php';
require __DIR__ . '/../src/Mailer.php';

use Smg\Csrf;
use Smg\Db;
use Smg\Mailer;

$activePage = 'about';
$pageTitle = 'About / Contact — Star Media Group';

$subjectOptions = [
    'advertising_partnerships' => 'Advertising and partnerships',
    'editorial_enquiry' => 'Editorial enquiry',
    'data_privacy_request' => 'Data and privacy request',
];

$errors = [];
$values = ['full_name' => '', 'email' => '', 'subject' => '', 'message' => '', 'consent_privacy' => false];
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $values['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $values['email'] = trim((string) ($_POST['email'] ?? ''));
    $values['subject'] = (string) ($_POST['subject'] ?? '');
    $values['message'] = trim((string) ($_POST['message'] ?? ''));
    $values['consent_privacy'] = isset($_POST['consent_privacy']);

    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify(is_string($csrfToken) ? $csrfToken : null)) {
        $errors['form'] = 'Your session expired — please try again.';
    }
    if ($values['full_name'] === '' || mb_strlen($values['full_name']) > 120) {
        $errors['full_name'] = 'Enter your full name.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!array_key_exists($values['subject'], $subjectOptions)) {
        $errors['subject'] = 'Choose a subject.';
    }
    if ($values['message'] === '' || mb_strlen($values['message']) < 10) {
        $errors['message'] = 'Enter a message (at least 10 characters).';
    }
    if (!$values['consent_privacy']) {
        $errors['consent_privacy'] = 'Please confirm you agree to the Privacy Policy.';
    }

    if (!$errors) {
        $emailSent = Mailer::sendContactMessage($values['full_name'], $values['email'], $values['subject'], $values['message']);

        $stmt = Db::connection()->prepare(
            'INSERT INTO contact_messages (full_name, email, subject, message, consent_privacy, ip_address, email_sent)
             VALUES (:name, :email, :subject, :message, 1, INET6_ATON(:ip), :sent)'
        );
        $stmt->execute([
            ':name' => $values['full_name'],
            ':email' => $values['email'],
            ':subject' => $values['subject'],
            ':message' => $values['message'],
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':sent' => $emailSent ? 1 : 0,
        ]);

        header('Location: about.php?sent=1', true, 303);
        exit;
    }
}
?>
<!doctype html>
<html lang="en" class="<?php echo $showDialog ? 'smg-locked' : ''; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
  <?php require __DIR__ . '/../templates/header.php'; ?>

  <main <?php echo $consentGateOpen ? 'inert' : ''; ?>>
    <section class="smg-title-band">
      <div class="smg-container">
        <p class="smg-eyebrow">About us · Contact us</p>
        <h1 class="smg-title-band__h1">Get to know Star Media Group, and get in touch.</h1>
      </div>
    </section>

    <section class="smg-about-body">
      <div class="smg-container smg-about-body__grid">
        <div class="smg-about-copy">
          <p class="smg-lede">We're an integrated media company operating across print, digital, broadcast
            and events, built around one newsroom and one set of editorial standards.</p>
          <p>Founded to serve readers across Malaysia, we've grown alongside the audiences we cover —
            from daily print to on-demand video, always grounded in accountable journalism.</p>

          <div class="smg-facts">
            <div class="smg-fact">
              <p class="smg-fact__label">Head office</p>
              <p class="smg-fact__value">Menara Star, 15 Jalan 16/11, 46350 Petaling Jaya, Selangor, Malaysia</p>
            </div>
            <div class="smg-fact">
              <p class="smg-fact__label">General line</p>
              <p class="smg-fact__value">+60 3-7967 1388</p>
            </div>
            <div class="smg-fact">
              <p class="smg-fact__label">Data protection officer</p>
              <p class="smg-fact__value">dpo@starmediagroup.com.my</p>
            </div>
          </div>
        </div>

        <div class="smg-form-card">
          <h2>Send us a message</h2>
          <p class="smg-form-card__note">We typically respond within one business day.</p>

          <?php if ($sent): ?>
            <p class="smg-form-banner smg-form-banner--success">Thanks — your message has been sent. We'll be in touch soon.</p>
          <?php endif; ?>
          <?php if (!empty($errors['form'])): ?>
            <p class="smg-form-banner smg-form-banner--error"><?php echo htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <form method="post" action="about.php" class="smg-form" novalidate>
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="contact_submit" value="1">

            <div class="smg-field<?php echo isset($errors['full_name']) ? ' has-error' : ''; ?>">
              <label for="full_name">Full name*</label>
              <input type="text" id="full_name" name="full_name" required maxlength="120"
                value="<?php echo htmlspecialchars($values['full_name'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php if (isset($errors['full_name'])): ?><p class="smg-field__error"><?php echo htmlspecialchars($errors['full_name'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </div>

            <div class="smg-field<?php echo isset($errors['email']) ? ' has-error' : ''; ?>">
              <label for="email">Email*</label>
              <input type="email" id="email" name="email" required
                value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php if (isset($errors['email'])): ?><p class="smg-field__error"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </div>

            <div class="smg-field<?php echo isset($errors['subject']) ? ' has-error' : ''; ?>">
              <label for="subject">Subject</label>
              <select id="subject" name="subject" required>
                <option value="" disabled <?php echo $values['subject'] === '' ? 'selected' : ''; ?>>Choose one</option>
                <?php foreach ($subjectOptions as $key => $label): ?>
                  <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $values['subject'] === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if (isset($errors['subject'])): ?><p class="smg-field__error"><?php echo htmlspecialchars($errors['subject'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </div>

            <div class="smg-field<?php echo isset($errors['message']) ? ' has-error' : ''; ?>">
              <label for="message">Message*</label>
              <textarea id="message" name="message" rows="4" required><?php echo htmlspecialchars($values['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              <?php if (isset($errors['message'])): ?><p class="smg-field__error"><?php echo htmlspecialchars($errors['message'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </div>

            <div class="smg-field smg-field--checkbox<?php echo isset($errors['consent_privacy']) ? ' has-error' : ''; ?>">
              <label class="smg-checkbox-label">
                <input type="checkbox" name="consent_privacy" value="1" <?php echo $values['consent_privacy'] ? 'checked' : ''; ?>>
                <span>I agree to the <a href="privacy.php">Privacy Policy</a>.</span>
              </label>
              <?php if (isset($errors['consent_privacy'])): ?><p class="smg-field__error"><?php echo htmlspecialchars($errors['consent_privacy'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </div>

            <button type="submit" class="smg-btn smg-btn--primary">Send message</button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/../templates/footer.php'; ?>
  <?php require __DIR__ . '/../templates/consent-dialog.php'; ?>
  <script src="assets/js/consent.js" defer></script>
</body>
</html>
