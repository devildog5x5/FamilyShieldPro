<?php
Layout::start('OurCircle — Pause. Ask family. Then pay.');
?>
<div class="wrap home">
  <section class="hero">
    <div>
      <h1>A family pause before you send a dime.</h1>
      <p class="lede">OurCircle is a trusted circle for the text, call, prize, or “urgent” payment that feels off. We do not stamp a request as safe. We help you stop, read the warning signs, and get someone you trust on the phone — then you decide.</p>
      <p class="hero-cta"><a class="btn" href="/signup">Start a 14-day trial</a></p>
      <p class="muted">Start with a 14-day trial. Then Family yearly is $119.99 or $14.99/month. You keep what you entered. We do not sell it.</p>
    </div>
    <figure class="hero-video">
      <div class="hero-video-box">
        <video playsinline preload="none" poster="/static/video/ourcircle-pause.webp?v=<?= Http::e(Layout::asset()) ?>" width="1280" height="720">
          <source src="/static/video/ourcircle-pause.mp4?v=<?= Http::e(Layout::asset()) ?>" type="video/mp4">
        </video>
        <button type="button" class="hero-video-start">
          <img class="hero-video-poster" src="/static/video/ourcircle-pause.webp?v=<?= Http::e(Layout::asset()) ?>" width="1280" height="720" alt="Grandma with short white hair and glasses, in a pink cardigan, sits on a living-room couch and looks at a smartphone." fetchpriority="high" />
          <span class="hero-video-cue">
            <img class="hero-video-who" src="/static/video/grandma-still.webp?v=<?= Http::e(Layout::asset()) ?>" width="160" height="160" alt="Close-up of the grandma in the story, with short white hair, glasses, and a pink cardigan." />
          </span>
          <span class="hero-video-play"><span class="hero-video-play-icon" aria-hidden="true"></span> Watch the story</span>
        </button>
      </div>
      <figcaption>This short story is about a grandma who pauses and calls family before she pays. Click the picture to watch. She tells it in her own words. OurCircle never stamps a request as safe or fake.</figcaption>
    </figure>
  </section>

  <div class="too-good">
    <h2>If it sounds too good to be true, it usually is.</h2>
    <p>Pause and call someone you trust before you pay.</p>
  </div>

  <h2>Three steps before anyone pays</h2>
  <div class="grid-3">
    <div class="panel step"><strong>1. Bring the request in</strong> Paste the email or text, upload a screenshot, or enter a phone number, website, offer, or payment ask.</div>
    <div class="panel step"><strong>2. Read the warning signs</strong> See why it might be a scam, whether a number or site resembles a known trick, and what to do next — never a “this is safe” stamp.</div>
    <div class="panel step"><strong>3. Involve your circle</strong> Ask a family member to look. Tap “Please call me before I pay” when it is urgent. Independently verify before money, crypto, gift cards, passwords, or account information moves.</div>
  </div>

  <p class="story landing-why">We built this because we have been scammed ourselves. Fake banks. “Grandkid in trouble.” Prize emails. OurCircle is a place for a household to park the screenshot, look at the warning signs together, and call before anyone pays — up to five people, a protected list of real numbers, and a call-me-before-I-pay button when it is urgent.</p>

  <div class="feature-strip">
    <div class="panel">
      <h3>Protected trusted list</h3>
      <p>Save the real numbers for banks, doctors, insurers, utilities, and family. Checks compare incoming numbers and websites to that list — not to a stranger in the message.</p>
    </div>
    <div class="panel">
      <h3>Text with your circle</h3>
      <p>Invites and “Please call me before I pay” can go by text when texting is connected. Paste a sketchy message into the circle and call someone on your trusted list. We never say a request is safe. Reply STOP to opt out of texts.</p>
    </div>
    <div class="panel">
      <h3>If something already went wrong</h3>
      <p>Calm steps to report fraud, freeze cards, and tell the people who can actually stop a payment. Speed matters more than shame.</p>
    </div>
  </div>

  <section id="lookup" class="panel lookup-card">
    <?php Layout::scamRefs('h2'); ?>
    <p><a href="/report">If money or passwords already went out → Report &amp; recover</a></p>
  </section>

  <h2 id="plans">Family plans</h2>
  <div class="plans">
    <div class="panel">
      <h3>Family monthly</h3>
      <p><strong>$14.99/month</strong></p>
      <p>Up to five people. Pause, trusted list, and call-me-before-I-pay. <strong>14-day trial</strong>, then this plan. You keep what you entered. We do not sell people’s information.</p>
      <p><a class="btn wide" href="<?= !empty($user) ? '/billing' : '/signup?plan=monthly' ?>"><?= !empty($user) ? 'Go to plans' : 'Start monthly' ?></a></p>
    </div>
    <div class="panel featured">
      <h3>Family yearly</h3>
      <p><strong>$119.99/year</strong></p>
      <p>Same circle. Pay once a year — about $10 a month. <strong>14-day trial</strong> first. You keep what you entered. We do not sell people’s information.</p>
      <p><a class="btn gold wide" href="<?= !empty($user) ? '/billing' : '/signup?plan=yearly' ?>"><?= !empty($user) ? 'Go to plans' : 'Start yearly' ?></a></p>
    </div>
  </div>
  <?php Layout::faq(); ?>
  <p class="disclaimer">This application offers guidance, not a guarantee. A paid plan is a family tool, not a stamp that a request is safe.</p>
  <p class="muted partner-line">Churches, senior centers, and veterans groups: ask about a shared license. Credit unions and insurers: per-member partnership pricing. <?php echo Layout::supportNote(); ?></p>

  <section class="support-contact" id="contact">
    <h2>Customer service</h2>
    <p>Questions about your circle, billing, login, or this site? <?php echo Layout::supportNote(); ?> A person reads every message.</p>
    <p class="muted">OurCircle is built for families — including parents, adult children, and grandparents — who want a second set of eyes.</p>
    <p><a href="/privacy">Privacy</a> · <a href="/terms">Terms &amp; Conditions</a></p>
  </section>
</div>
<?php Layout::end();
