<?php
Layout::start('OurCircle — Pause. Ask family. Then pay.');
Layout::publicNav();
$email = $email ?? Layout::supportEmail();
$phone = $phone ?? Layout::contactPhone();
?>
<div class="wrap">
  <p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p>
  <section class="hero">
    <div>
      <h1>A family pause before you send a dime.</h1>
      <p class="lede">Family Shield Pro (OurCircle) is a trusted circle for the text, call, prize, or “urgent” payment that feels a little off. We are not an AI that stamps a request as safe. We help you stop, read the warning signs in plain language, and get someone you trust on the phone — then you decide.</p>
      <p><a class="btn" href="/signup">Start a family circle</a></p>
      <p class="muted">Family yearly $119.99, or $14.99/month on <a href="#plans">Plans</a>.</p>
    </div>
    <div class="hero-card panel">
      <p>OurCircle cannot tell you that something is safe. We help you pause, look for warning signs, check your family's trusted list, and ask someone you trust before you act.</p>
      <p class="disclaimer">This application offers guidance, not a guarantee.</p>
    </div>
  </section>

  <h2>Strategy and Tactics</h2>
  <p class="story">We offer Strategy and Tactics to help you and your family prevent being scammed. Not a guarantee. Your circle and us help prevent you from being taken advantage of.</p>

  <h2>Why we built this</h2>
  <p class="story">We are invested in this because we have personally been the victim of so many scams over the years. They keep getting better and more believable. Fake banks. “Grandkid in trouble.” Prize emails. Refunds that are not refunds. You really have to be on your toes.</p>
  <p class="story">So we built a place for a household to park the screenshot, look at the warning signs together, and call before anyone pays. Up to five people. A protected list of the real numbers for banks, doctors, and family. A “Please call me before I pay” button when it is urgent.</p>
  <p class="rule-restated"><strong>Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</strong></p>

  <div class="too-good">
    <h2>If it sounds too good to be true, it usually is.</h2>
    <p class="really">Really! Really! Really!</p>
  </div>

  <h2>What you can do</h2>
  <div class="grid-3">
    <div class="panel step"><strong>1. Bring the request in</strong> Paste the email or text, upload a screenshot, or enter a phone number, website, offer, or payment ask.</div>
    <div class="panel step"><strong>2. Read the warning signs</strong> See why it might be a scam, whether a number or site resembles a known trick, and what to do next — never a “this is safe” stamp.</div>
    <div class="panel step"><strong>3. Involve your circle</strong> Ask a family member to look. Tap “Please call me before I pay” when it is urgent. Independently verify before money, crypto, gift cards, passwords, or account information moves.</div>
  </div>

  <h3>Protected trusted list</h3>
  <p>Save the real numbers for banks, doctors, insurers, utilities, and family. Checks compare incoming numbers and websites to that list — not to a stranger in the message.</p>
  <h3>Text with your circle</h3>
  <p>When SMS is set up, invites and “Please call me before I pay” can go by text. Save your mobile on Account, then forward a sketchy message to the Family Shield Pro number. We never say a request is safe. Reply STOP to opt out.</p>
  <h3>If something already went wrong</h3>
  <p>Get calm instructions to report fraud, freeze cards, and tell the people who can actually stop a payment. Speed matters more than shame.</p>

  <h2 id="plans">Family plans</h2>
  <div class="plans">
    <div class="panel">
      <h3>Family monthly</h3>
      <p><strong>$14.99/month</strong></p>
      <p>Up to five people in one circle. Pause, trusted list, and call-me-before-I-pay.</p>
      <p><a class="btn wide" href="/signup">Start a circle</a></p>
    </div>
    <div class="panel featured">
      <h3>Family yearly</h3>
      <p><strong>$119.99/year</strong></p>
      <p>Same circle. Pay once a year — about $10 a month.</p>
      <p><a class="btn gold wide" href="/signup">Start a circle</a></p>
    </div>
  </div>
  <p class="disclaimer">This application offers guidance, not a guarantee. A paid plan is a family tool, not a stamp that a request is safe.</p>
  <p>Churches, senior centers, and veterans groups: ask us about a shared license. Credit unions and insurers: per-member partnership pricing.</p>

  <section class="support-contact" id="contact">
    <h2>Customer service</h2>
    <p>Questions about your circle, billing, login, or this site? Email us. A person reads every message.</p>
    <p><a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a></p>
    <?php if ($phone !== ''): ?>
      <p class="support-phone"><a href="tel:<?= Http::e(preg_replace('/\D+/', '', $phone) ?? '') ?>"><?= Http::e($phone) ?></a></p>
    <?php endif; ?>
    <p class="muted">OurCircle is built for families — including parents, adult children, and grandparents — who want a second set of eyes.</p>
    <p><a href="/privacy">Privacy</a> · <a href="/terms">Terms</a></p>
  </section>
</div>
<?php Layout::end();
