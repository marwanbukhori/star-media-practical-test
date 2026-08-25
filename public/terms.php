<?php
require __DIR__ . '/../templates/bootstrap.php';

$activePage = 'terms';
$pageTitle = 'Terms & Conditions — Star Media Group';
$legalTitle = 'Terms & Conditions';
$effectiveDate = '1 January 2026';
$readingMinutes = 5;

$tocItems = [
    ['id' => 'acceptable-use', 'label' => 'Acceptable use'],
    ['id' => 'cookies-and-consent', 'label' => 'Cookies and consent'],
    ['id' => 'intellectual-property', 'label' => 'Intellectual property'],
    ['id' => 'liability', 'label' => 'Liability'],
    ['id' => 'governing-law', 'label' => 'Governing law'],
    ['id' => 'contacting-us', 'label' => 'Contacting us'],
];

ob_start();
?>
<h2 id="acceptable-use">Acceptable use</h2>
<p>By using this site, you agree not to misuse it. That means no trying to disrupt how it
operates, no accessing data you're not authorised to see, and no submitting false or malicious
content through the contact form.</p>

<h2 id="cookies-and-consent">Cookies and consent</h2>
<p>Cookies are necessary for this website to function properly, for performance measurement,
and to provide you with the best experience. By continuing to access or use this site, you
acknowledge and consent to our use of cookies in accordance with our
<a href="terms.php">Terms &amp; Conditions</a> and <a href="privacy.php">Privacy Statement</a>.</p>
<p>You may decline non-essential cookies at any time via "Cookie settings" in the footer;
declining doesn't prevent you from browsing the site, though your choice is only remembered for
one day before we ask again.</p>

<h2 id="intellectual-property">Intellectual property</h2>
<p>All content on this site, including articles, graphics, and the Star Media Group name and mark,
is owned by or licensed to Star Media Group Berhad and may not be reproduced without permission.</p>

<h2 id="liability">Liability</h2>
<p>This site is provided as-is. While we aim for accuracy, we make no warranty that content is
error-free, and we're not liable for any loss arising from your use of it, to the extent
permitted by law.</p>

<h2 id="governing-law">Governing law</h2>
<p>These terms are governed by the laws of Malaysia, and any disputes are subject to the
exclusive jurisdiction of the Malaysian courts.</p>

<h2 id="contacting-us">Contacting us</h2>
<p>Questions about these terms can be sent via the <a href="about.php">contact form</a>, or to
our general line listed on the About page.</p>
<?php
$articleBody = ob_get_clean();

require __DIR__ . '/../templates/legal-page.php';
