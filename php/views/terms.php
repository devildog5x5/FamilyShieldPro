<?php
Layout::start('Terms & Conditions · OurCircle');
Layout::publicNav();
$email = $email ?? Layout::supportEmail();
?>
<div class="wrap app-main legal-doc">
  <h1>Terms &amp; Conditions</h1>
  <p class="muted">Effective September 10, 2026. These Terms are the agreement for using Family Shield Pro (OurCircle). They are not legal advice about scams, and they are not a stamp that any request is safe.</p>

  <h2>1. The service</h2>
  <p>Family Shield Pro is a family pause tool. It helps a household stop, look at warning signs in a text, call, prize, or “urgent” payment ask, check a trusted list, and call someone they trust — then the family decides. We do not decide that a request is safe, real, or a scam. We do not reverse payments, freeze cards, contact banks, or report fraud for you.</p>
  <p>This application offers guidance, not a guarantee. Paying for a plan does not make a request safe.</p>

  <h2>2. The pause rule</h2>
  <p>Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified. Independently verified means you confirmed it through a number or site you already trust — not a number or link inside the suspicious message.</p>

  <h2>3. Who may use it</h2>
  <p>You must be old enough to form a contract in your state (usually 18). A parent or guardian may start a circle for a household. You are responsible for the people you invite and for how your circle uses the service.</p>
  <p>Starting a circle, joining a circle, or continuing to use the service after these Terms are posted means you agree to them and to the <a href="/privacy">Privacy Policy</a>.</p>

  <h2>4. Accounts and circles</h2>
  <p>Up to five people share one family circle. The owner can invite people, cancel unused invites, remove members, and choose the household plan. Keep your password and recovery codes private. You are responsible for activity under your login.</p>
  <p>Do not share pasted messages, screenshots, or trusted-list numbers with people outside your circle unless you have a lawful reason. Circle members can see checks, notes, and the trusted list for that circle.</p>

  <h2>5. Trial, plans, and billing</h2>
  <p>Every new circle includes a 14-day trial. Family monthly is $14.99. Family yearly is $119.99. After the trial the owner pays to keep checking new requests, inviting family, and using call-me. If the trial ends or a subscription lapses, we do not lock you out of the personal information you entered — the trusted list, past checks, and account details stay readable.</p>
  <p>When card payments are connected, the circle owner pays through Stripe. We do not store full card numbers. If payments are not connected, choosing a plan only records it on the circle and nothing is charged. Subscriptions renew until the owner cancels through the card-management portal (when available) or by emailing us. Fees already paid are not refunded except where the law requires it.</p>
  <p>Prices may change for later billing periods. We will describe the current plans on the site. Taxes, if any, are extra where required.</p>

  <h2>6. What you paste and upload</h2>
  <p>You may paste messages, phone numbers, websites, and screenshots so your circle can pause together. You represent that you have the right to share that material with your circle for that purpose. Do not upload illegal content. We may remove material that appears to abuse the service, but we are not obligated to review everything you paste.</p>

  <h2>7. Acceptable use</h2>
  <p>Do not use Family Shield Pro to harass anyone, impersonate someone, attack the site, scrape accounts, send spam, or overload mail or storage. Do not try to bypass trial or payment limits. We may suspend or close a circle that abuses invites, mail, SMS, or the product.</p>

  <h2>8. Privacy</h2>
  <p>How we collect, use, and share information is described in the <a href="/privacy">Privacy Policy</a>. We do not sell people’s information. Email and (when connected) SMS providers may deliver invites and alerts. We may disclose information if required by law.</p>

  <h2>9. Texts and email</h2>
  <p>Password reset uses your email. Optional mobile numbers are for circle invites and “Please call me before I pay” alerts when SMS is turned on — not a customer-service hotline. Reply STOP to opt out of texts. Delivery of mail or SMS is not guaranteed.</p>

  <h2>10. Changes and availability</h2>
  <p>We may update these Terms. The effective date at the top will change. Continued use after an update means you accept the revised Terms. We may change, pause, or discontinue features. We do not promise uninterrupted service.</p>
  <p>You may stop using the service at any time. To delete a circle, email <a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a>. We may close an account that violates these Terms.</p>

  <h2>11. Our materials</h2>
  <p>Family Shield Pro, OurCircle, the site, and related marks are ours. You may use the service for your household. You may not copy the product, reverse-engineer it, or present it as your own.</p>

  <h2>12. No warranty</h2>
  <p>The service is provided as-is. We do not warrant that warning signs are complete, that a number or site is genuine, that a request is or is not a scam, or that using Family Shield Pro will prevent loss. Third-party sites we link to (for example Snopes, the FTC, BBB, or IC3) are not part of our service.</p>

  <h2>13. Limitation of liability</h2>
  <p>To the fullest extent allowed by law, Family Shield Pro and the people who operate it are not liable for scams that succeed, payments or information you send, advice you take or ignore, or lost profits. If we are ever liable, our total liability for a claim is limited to the amount the circle paid us in the three months before the claim, or $50 if the circle has not paid. Some states do not allow certain limitations; in those states our liability is limited to the maximum the law allows. Nothing here limits liability that the law does not let us limit, including for fraud or willful misconduct.</p>

  <h2>14. You keep responsibility</h2>
  <p>You remain responsible for independently verifying requests and for any money, crypto, gift cards, passwords, or account information you send. If you use the service in a way that causes claims against us (other than our own misconduct), you agree to cover reasonable costs we incur as a result, to the extent the law allows.</p>

  <h2>15. Law</h2>
  <p>These Terms are governed by the laws of the United States. If you are a consumer, nothing here takes away rights your state does not let you waive. If a part of these Terms cannot be enforced, the rest still applies.</p>

  <h2>16. Contact</h2>
  <p>Questions about these Terms, your circle, or billing: <a href="mailto:<?= Http::e($email) ?>"><?= Http::e($email) ?></a>.</p>
  <p><a href="/privacy">Privacy Policy</a> · <a href="/">Home</a></p>
</div>
<?php Layout::end();
