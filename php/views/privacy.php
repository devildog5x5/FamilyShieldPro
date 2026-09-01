<?php
Layout::start('Privacy · OurCircle');
Layout::publicNav();
$email = $email ?? Layout::supportEmail();
?>
<div class="wrap app-main">
  <h1>Privacy</h1>
  <p>Family Shield Pro (OurCircle) is a family pause tool. This page explains what we store and why. It is not legal advice.</p>
  <h2>What we collect</h2>
  <ul class="list">
    <li>Account details: name, email, optional mobile number, password hash, and optional authenticator secret.</li>
    <li>Circle data: who is in the household, invites, and the trusted list you save.</li>
    <li>Checks: pasted messages, extracted numbers and links, optional screenshots, and notes your circle leaves.</li>
    <li>Billing: which plan the circle chose. Card numbers are handled by the payment provider when connected — we do not store full card numbers.</li>
  </ul>
  <h2>How we use it</h2>
  <p>To run your circle, show warning signs, email join links and “please call” alerts when mail is connected, and improve the product. We do not sell your family messages.</p>
  <h2>Sharing</h2>
  <p>People you invite can see checks, notes, and the trusted list for that circle. We may use email and (when connected) SMS providers to deliver invites and alerts. We may disclose information if required by law.</p>
  <h2>Retention</h2>
  <p>We keep circle data while the account is open. You can ask us to delete a circle by emailing <a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a>.</p>
  <h2>Contact</h2>
  <p><a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a></p>
  <p><a href="/terms">Terms</a> · <a href="/">Home</a></p>
</div>
<?php Layout::end();
