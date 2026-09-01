<?php
Layout::start('Terms · OurCircle');
Layout::publicNav();
$email = $email ?? Layout::supportEmail();
?>
<div class="wrap app-main">
  <h1>Terms</h1>
  <p>By using Family Shield Pro (OurCircle) you agree that this application offers guidance, not a guarantee. We do not decide that a request is safe. You and your circle decide what to do.</p>
  <h2>The pause rule</h2>
  <p>Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified. OurCircle does not reverse payments, freeze cards, or contact banks for you.</p>
  <h2>Accounts</h2>
  <p>You must be old enough to form a contract in your state. Keep your password and recovery codes private. Up to five people share one family circle. The owner can remove members and cancel unused invites.</p>
  <h2>Plans</h2>
  <p>Family monthly is $14.99. Family yearly is $119.99. A paid plan is a family tool, not a stamp that a request is safe. If card payments are not connected, choosing a plan only records it on the circle.</p>
  <h2>Acceptable use</h2>
  <p>Do not use the service to harass anyone, upload illegal content, or attack the site. We may suspend accounts that abuse invites, mail, or storage.</p>
  <h2>Limitation</h2>
  <p>To the fullest extent allowed by law, Family Shield Pro is provided as-is. We are not liable for scams that succeed, payments you send, or advice you take or ignore. Some states do not allow certain limitations.</p>
  <h2>Contact</h2>
  <p><a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a></p>
  <p><a href="/privacy">Privacy</a> · <a href="/">Home</a></p>
</div>
<?php Layout::end();
