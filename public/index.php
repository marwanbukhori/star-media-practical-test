<?php
require __DIR__ . '/../templates/bootstrap.php';
$activePage = 'home';
$pageTitle = 'Star Media Group — Malaysia\'s integrated media group';

$brands = [
    'The Star', 'The Star Online', 'StarBiz7', 'Life Inspired', 'R.AGE', 'Mstar',
    'Star Property', 'Star ESG', 'Kuali.com', 'MyStarJob', 'Beli Lokal', 'CarSifu',
    'Kuntum', 'StarCherish', '988FM', 'Suria FM',
];

$platforms = [
    ['index' => '01', 'title' => 'Print', 'image' => 'platform-print.jpg',
     'body' => 'Trusted daily journalism reaching households across Peninsular and East Malaysia.'],
    ['index' => '02', 'title' => 'Digital', 'image' => 'platform-digital.jpg',
     'body' => 'Breaking news, analysis and video across web and mobile, updated around the clock.'],
    ['index' => '03', 'title' => 'Broadcast', 'image' => 'platform-broadcast.jpg',
     'body' => 'Radio and streaming audio programming connecting communities nationwide.'],
    ['index' => '04', 'title' => 'Events & video', 'image' => 'platform-events.jpg',
     'body' => 'Original documentary series alongside large-scale public events and activations.'],
];

$recognition = [
    ['value' => '55 years', 'label' => 'Serving Malaysian readers since our founding in 1971.', 'source' => 'Company registration 197101000523 (10894-D)'],
    ['value' => 'Trusted outlet', 'label' => 'Named among Malaysia\'s trusted media outlets.', 'source' => 'Reuters Institute Digital News Report 2025'],
    ['value' => '17+ brands', 'label' => 'Publications, platforms and stations across print, digital, radio and property.', 'source' => 'Star Media Group Berhad portfolio'],
    ['value' => 'Inform. Inspire. Innovate.', 'label' => 'Our purpose, in three words.', 'source' => 'Star Media Group Berhad'],
];

function smg_asset_or_placeholder(string $filename): ?string
{
    $path = __DIR__ . '/assets/images/' . $filename;
    return file_exists($path) ? 'assets/images/' . $filename : null;
}
?>
<!doctype html>
<html lang="en" class="<?php echo $showDialog ? 'smg-locked' : ''; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <script>document.documentElement.classList.add('js');</script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="assets/css/tokens.css">
  <link rel="stylesheet" href="assets/css/site.css">
  <link rel="stylesheet" href="assets/css/home.css">
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
        <div class="smg-hero__photo" data-reveal>
          <?php $heroPhoto = smg_asset_or_placeholder('hero.jpg'); ?>
          <?php if ($heroPhoto): ?>
            <img src="<?php echo htmlspecialchars($heroPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Star Media Group newsroom">
          <?php endif; ?>
          <span class="smg-badge">Est. 1971</span>
        </div>
      </div>
    </section>

    <section class="smg-stats-strip">
      <div class="smg-container smg-stats-strip__grid">
        <div class="smg-stat">
          <p class="smg-stat__value" data-count-up>1.4m</p>
          <p class="smg-stat__label">Monthly readers</p>
        </div>
        <div class="smg-stat">
          <p class="smg-stat__value" data-count-up>55</p>
          <p class="smg-stat__label">Years in Malaysian media</p>
        </div>
        <div class="smg-stat">
          <p class="smg-stat__value" data-count-up>4</p>
          <p class="smg-stat__label">Integrated platforms</p>
        </div>
      </div>
    </section>

    <section class="smg-marquee">
      <p class="smg-eyebrow smg-eyebrow--muted smg-marquee__label">Our family of brands</p>
      <div class="smg-marquee__viewport">
        <div class="smg-marquee__track">
          <?php foreach (array_merge($brands, $brands) as $brand): ?>
            <span class="smg-marquee__item"><?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="smg-platforms" id="platforms">
      <div class="smg-container">
        <div class="smg-section-head" data-reveal>
          <h2>Our platforms</h2>
          <p class="smg-eyebrow smg-eyebrow--muted">Overview</p>
        </div>
        <div class="smg-carousel" data-smg-carousel data-reveal>
          <div class="smg-carousel__track" data-smg-carousel-track tabindex="0">
            <?php foreach ($platforms as $platform): ?>
              <div class="smg-carousel__panel">
                <?php $photo = smg_asset_or_placeholder($platform['image']); ?>
                <?php if ($photo): ?>
                  <img src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($platform['title'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <div class="smg-carousel__caption">
                  <p class="smg-carousel__index"><?php echo htmlspecialchars($platform['index'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <h3><?php echo htmlspecialchars($platform['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars($platform['body'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="smg-about-teaser">
      <div class="smg-container smg-about-teaser__grid">
        <div class="smg-about-teaser__photo" data-reveal>
          <?php $aboutPhoto = smg_asset_or_placeholder('about.jpg'); ?>
          <?php if ($aboutPhoto): ?>
            <img src="<?php echo htmlspecialchars($aboutPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Star Media Group team">
          <?php endif; ?>
        </div>
        <div class="smg-about-teaser__copy" data-reveal>
          <p class="smg-eyebrow">Our story</p>
          <h2>55 years of Malaysian journalism, still evolving</h2>
          <p>Since 1971, Star Media Group Berhad has grown from a single newspaper into one of
            Malaysia's most established integrated media companies — spanning print, digital,
            radio, property, jobs, automotive and community platforms under one roof.</p>
          <div class="smg-pull-quote">
            <p>&ldquo;Inform with integrity, inspire with meaning, and innovate for the future.&rdquo;</p>
            <cite>— Star Media Group Berhad's stated purpose</cite>
          </div>
          <a href="about.php" class="smg-btn smg-btn--ghost">More about us</a>
        </div>
      </div>
    </section>

    <section class="smg-recognition">
      <div class="smg-container">
        <div class="smg-section-head" data-reveal>
          <h2>Why Malaysia trusts us</h2>
          <p class="smg-eyebrow smg-eyebrow--muted">Recognition</p>
        </div>
        <div class="smg-carousel" data-smg-carousel data-smg-auto-carousel data-reveal>
          <div class="smg-carousel__track" data-smg-carousel-track tabindex="0">
            <?php foreach ($recognition as $item): ?>
              <div class="smg-recognition-card">
                <p class="smg-recognition-card__value"><?php echo htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="smg-recognition-card__label"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                <span class="smg-recognition-card__source"><?php echo htmlspecialchars($item['source'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
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
  <script src="assets/js/home.js" defer></script>
</body>
</html>
