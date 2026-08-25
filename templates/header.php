<?php
/**
 * @var string $activePage      'home'|'about'|'privacy'|'terms'
 * @var bool   $consentGateOpen True while the blocking consent dialog is forced open —
 *                              marks this chrome inert so it can't be tabbed into behind it.
 */
$activePage = $activePage ?? '';
$consentGateOpen = $consentGateOpen ?? false;

$navItems = [
    'home'    => ['label' => 'Home', 'href' => 'index.php'],
    'about'   => ['label' => 'About / Contact', 'href' => 'about.php'],
    'privacy' => ['label' => 'Privacy Policy', 'href' => 'privacy.php'],
    'terms'   => ['label' => 'Terms & Conditions', 'href' => 'terms.php'],
];
?>
<header class="smg-header<?php echo $activePage === 'home' ? ' smg-header--sticky' : ''; ?>" <?php echo $consentGateOpen ? 'inert' : ''; ?>>
  <div class="smg-header__inner smg-container">
    <a class="smg-logo" href="index.php">
      <span class="smg-logo__mark" aria-hidden="true">★</span>
      <span class="smg-logo__text">Star Media Group</span>
    </a>

    <input type="checkbox" id="smg-nav-toggle" class="smg-nav-toggle">
    <label for="smg-nav-toggle" class="smg-hamburger" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </label>

    <nav class="smg-nav" aria-label="Primary">
      <?php foreach ($navItems as $key => $item): ?>
        <a
          href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"
          class="smg-nav__link<?php echo $activePage === $key ? ' is-active' : ''; ?>"
          <?php echo $activePage === $key ? 'aria-current="page"' : ''; ?>
        ><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
