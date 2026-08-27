<?php
require __DIR__ . '/../templates/bootstrap.php';
$activePage = 'home';
$pageTitle = 'Star Media Group — Malaysia\'s integrated media group';

$brands = [
    ['name' => 'The Star', 'file' => 'brands/thestar.png'],
    ['name' => 'The Star ePaper', 'file' => 'brands/starepaper.png'],
    ['name' => 'StarBiz7', 'file' => 'brands/starbiz7.png'],
    ['name' => 'Life Inspired', 'file' => 'brands/lifeinspired.png'],
    ['name' => 'Star ESG', 'file' => 'brands/staresg.png'],
    ['name' => 'Star Property', 'file' => 'brands/starproperty.png'],
    ['name' => 'The Star Online', 'file' => 'brands/tsol.png'],
    ['name' => 'R.AGE', 'file' => 'brands/rage.png'],
    ['name' => 'Mstar', 'file' => 'brands/mstar.png'],
    ['name' => 'Kuali.com', 'file' => 'brands/kuali.png'],
    ['name' => 'MyStarJob', 'file' => 'brands/mystarjob.png'],
    ['name' => 'Beli Lokal', 'file' => 'brands/belilokal.png'],
    ['name' => 'CarSifu', 'file' => 'brands/carsifu.png'],
    ['name' => 'Kuntum', 'file' => 'brands/kuntum.png'],
    ['name' => 'StarCherish', 'file' => 'brands/starcherish.png'],
    ['name' => '988', 'file' => 'brands/988.png'],
    ['name' => 'Suria', 'file' => 'brands/suria.png'],
];

$platforms = [
    ['index' => '01', 'title' => 'Print', 'image' => 'platform-print.jpg', 'type' => 'logo',
     'body' => 'Trusted daily journalism reaching households across Peninsular and East Malaysia.'],
    ['index' => '02', 'title' => 'Digital', 'image' => 'platform-digital.jpg', 'type' => 'logo',
     'body' => 'Breaking news, analysis and video across web and mobile, updated around the clock.'],
    ['index' => '03', 'title' => 'Broadcast', 'image' => 'platform-broadcast.jpg', 'type' => 'logo',
     'body' => 'Radio programming connecting communities nationwide.'],
    ['index' => '04', 'title' => 'Events & video', 'image' => 'platform-events.jpg', 'type' => 'photo',
     'body' => 'From the Star Outstanding Business Awards to large public activations, on stage and on screen.'],
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
  <link rel="icon" type="image/png" href="assets/images/favicon.png">
  <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
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
          <p class="smg-hero__lede">We're one newsroom across print, digital, radio and live events,
            reaching households all over Malaysia every day.</p>
          <div class="smg-hero__actions">
            <a href="#platforms" class="smg-btn smg-btn--primary">Our platforms</a>
            <a href="about.php" class="smg-btn smg-btn--ghost">About us</a>
          </div>
        </div>
        <div class="smg-hero__photo" data-reveal>
          <?php $heroPhoto = smg_asset_or_placeholder('hero.jpg'); ?>
          <?php if ($heroPhoto): ?>
            <img src="<?php echo htmlspecialchars($heroPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Star Media Group's headquarters in Petaling Jaya">
          <?php endif; ?>
          <span class="smg-badge">Est. 1971</span>
        </div>
      </div>
    </section>

    <section class="smg-marquee">
      <p class="smg-eyebrow smg-eyebrow--muted smg-marquee__label">Our family of brands</p>
      <div class="smg-marquee__viewport">
        <div class="smg-marquee__track">
          <?php foreach (array_merge($brands, $brands) as $brand): ?>
            <?php $logo = smg_asset_or_placeholder($brand['file']); ?>
            <?php if ($logo): ?>
              <img class="smg-marquee__item" src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
            <?php endif; ?>
          <?php endforeach; ?>
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

    <section class="smg-platforms" id="platforms">
      <div class="smg-container">
        <div class="smg-section-head" data-reveal>
          <h2>Our platforms</h2>
          <p class="smg-eyebrow smg-eyebrow--muted">Overview</p>
        </div>
        <div class="smg-carousel" data-smg-carousel data-reveal>
          <div class="smg-carousel__track" data-smg-carousel-track tabindex="0">
            <?php foreach ($platforms as $platform): ?>
              <div class="smg-carousel__panel smg-carousel__panel--<?php echo $platform['type']; ?>">
                <?php $photo = smg_asset_or_placeholder($platform['image']); ?>
                <?php if ($platform['type'] === 'logo'): ?>
                  <div class="smg-carousel__media">
                    <?php if ($photo): ?>
                      <img src="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($platform['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                  </div>
                <?php elseif ($photo): ?>
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
            <img src="<?php echo htmlspecialchars($aboutPhoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Star Education Fund scholarship recipients">
          <?php endif; ?>
        </div>
        <div class="smg-about-teaser__copy" data-reveal>
          <p class="smg-eyebrow">Our story</p>
          <h2>55 years of Malaysian journalism, still evolving</h2>
          <p>We started as a single newspaper back in 1971. More than five decades later, we're
            still growing: print, digital, radio, property, jobs, automotive and community
            programmes, all under the same roof.</p>
          <div class="smg-pull-quote">
            <p>&ldquo;Inform with integrity, inspire with meaning, and innovate for the future.&rdquo;</p>
            <cite>Star Media Group Berhad's stated purpose</cite>
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
          <p>Got an advertising or partnership idea? Send it over. Our team gets back to you within a business day.</p>
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
