<?php
require __DIR__ . '/../templates/bootstrap.php';

$activePage = 'privacy';
$pageTitle = 'Privacy Policy — Star Media Group';
$legalTitle = 'Privacy Policy';
$effectiveDate = '1 January 2026';
$readingMinutes = 6;

$tocItems = [
    ['id' => 'what-we-collect', 'label' => 'What we collect'],
    ['id' => 'cookies-and-consent', 'label' => 'Cookies and consent'],
    ['id' => 'how-long-we-keep-it', 'label' => 'How long we keep it'],
    ['id' => 'sharing-and-transfers', 'label' => 'Sharing and transfers'],
    ['id' => 'your-rights', 'label' => 'Your rights'],
    ['id' => 'contacting-us', 'label' => 'Contacting us'],
];

ob_start();
?>
<h2 id="what-we-collect">What we collect</h2>
<p>We collect information in two ways: directly, when you submit the contact form, and
automatically, through your use of this site.</p>
<ul>
  <li>Contact details you provide: full name, email address, and the content of your message.</li>
  <li>Technical data: your IP address, browser user agent, and cookie identifiers.</li>
  <li>Consent records: a randomly generated identifier (GUID), the date and time you accepted
    or declined cookies, and the notice version you responded to.</li>
</ul>

<h2 id="cookies-and-consent">Cookies and consent</h2>
<p>On your first visit, we ask you to accept or decline non-essential cookies via the consent
dialog. If you accept, we set a cookie named <code>smg_consent</code> containing a GUID, the
acceptance timestamp, and the notice version, valid for one year. If you decline, we set a
cookie named <code>smg_consent_declined</code> valid for one day, after which we'll ask again.</p>
<p>If we materially change what we collect or why, we increase the notice version, and the
dialog will reappear even if your existing cookie hasn't expired.</p>

<h2 id="how-long-we-keep-it">How long we keep it</h2>
<p>Consent records are retained for as long as they remain valid, plus a reasonable period for
audit purposes. Contact form submissions are retained only as long as needed to respond to your
enquiry and meet any applicable record-keeping obligations.</p>

<h2 id="sharing-and-transfers">Sharing and transfers</h2>
<p>We do not sell personal data. We share it only with service providers who help us operate
this site, such as our hosting and email delivery providers, under obligations to protect it.
Data is processed within Malaysia unless you're told otherwise.</p>

<h2 id="your-rights">Your rights</h2>
<p>You may ask to access, correct, or delete personal data we hold about you, or withdraw
consent at any time via "Cookie settings" in the footer. Contact our Data Protection Officer to
exercise these rights.</p>

<h2 id="contacting-us">Contacting us</h2>
<p>Questions about this policy can be sent to our Data Protection Officer at
<a href="mailto:dpo@starmediagroup.com.my">dpo@starmediagroup.com.my</a>, or via the
<a href="about.php">contact form</a>.</p>
<?php
$articleBody = ob_get_clean();

require __DIR__ . '/../templates/legal-page.php';
