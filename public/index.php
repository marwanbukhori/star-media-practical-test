<?php
require __DIR__ . '/../templates/bootstrap.php';
$activePage = 'home';
$pageTitle = 'Star Media Group — Malaysia\'s integrated media group';
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
    <section class="smg-hero">
      <div class="smg-container smg-hero__inner">
        <div class="smg-hero__lead">
          <p class="smg-eyebrow">Malaysia · Integrated media</p>
          <h1 class="smg-hero__h1">Stories that reach every screen, every household, every day.</h1>
          <p class="smg-hero__lede">Star Media Group brings print, digital, broadcast and events together
            under one integrated newsroom — trusted journalism at national scale.</p>
          <div class="smg-hero__actions">
            <a href="#platforms" class="smg-btn smg-btn--primary">Our platforms</a>
            <a href="about.php" class="smg-btn smg-btn--ghost">About us</a>
          </div>
        </div>
        <div class="smg-hero__stats">
          <div class="smg-stat">
            <p class="smg-stat__value">1.4m</p>
            <p class="smg-stat__label">Monthly readers</p>
          </div>
          <div class="smg-stat">
            <p class="smg-stat__value">54</p>
            <p class="smg-stat__label">Years in Malaysian media</p>
          </div>
          <div class="smg-stat">
            <p class="smg-stat__value">4</p>
            <p class="smg-stat__label">Integrated platforms</p>
          </div>
        </div>
      </div>
    </section>

    <section class="smg-platforms" id="platforms">
      <div class="smg-container">
        <div class="smg-section-head">
          <h2>Our platforms</h2>
          <p class="smg-eyebrow smg-eyebrow--muted">Overview</p>
        </div>
        <div class="smg-platforms__grid">
          <article class="smg-platform-card">
            <p class="smg-platform-card__index">01</p>
            <h3>Print</h3>
            <p>Trusted daily journalism reaching households across Peninsular and East Malaysia.</p>
          </article>
          <article class="smg-platform-card">
            <p class="smg-platform-card__index">02</p>
            <h3>Digital</h3>
            <p>Breaking news, analysis and video across web and mobile, updated around the clock.</p>
          </article>
          <article class="smg-platform-card">
            <p class="smg-platform-card__index">03</p>
            <h3>Broadcast</h3>
            <p>Radio and streaming audio programming connecting communities nationwide.</p>
          </article>
          <article class="smg-platform-card">
            <p class="smg-platform-card__index">04</p>
            <h3>Events &amp; video</h3>
            <p>Original documentary series alongside large-scale public events and activations.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="smg-cta-band">
      <div class="smg-container smg-cta-band__inner">
        <div>
          <h2>Let's build something together</h2>
          <p>Advertising, partnerships and editorial collaborations — our team responds within one business day.</p>
        </div>
        <a href="about.php" class="smg-btn smg-btn--primary">Contact us</a>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/../templates/footer.php'; ?>
  <?php require __DIR__ . '/../templates/consent-dialog.php'; ?>
  <script src="assets/js/consent.js" defer></script>
</body>
</html>
