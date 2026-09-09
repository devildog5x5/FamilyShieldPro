# Family Shield Pro — Stripe test payments

Sandbox return URL: `https://sandbox.familyshieldpro.com/billing/success?session_id={CHECKOUT_SESSION_ID}`  
Webhook (must stay public, no login): `https://sandbox.familyshieldpro.com/billing/webhook`

Plans: **Family monthly $14.99** and **Family yearly $119.99**.

## Check what is missing

Operator console `/admin` runs the check when you open the page and puts the full log in a text box (MISSING / OK / NOTES). It also writes `data/stripe-check.txt` next to the database (blocked from the web). Secret keys are never shown. Use **Run Stripe check again** to refresh.

On this PC:

```powershell
cd C:\Users\rober\Documents\GitHub\FamilyShieldPro
powershell -File .\deploy\check_stripe.ps1
```

That writes `stripe-check.log` (and `php/data/stripe-check.txt`) from local `php/.env`, then lists MISSING / OK / NOTES. It also reads the live sandbox version from `/healthz`.

You already have a Stripe account for another application. **Keep that account.** Do not reuse its products, prices, Payment Links, or webhook. Family Shield Pro adds its own catalog next to it. The **test API keys can be the same** `sk_test_` / `pk_test_` pair.

## What you paste (keys only)

1. [dashboard.stripe.com](https://dashboard.stripe.com) → **Test mode** on.
2. Developers → API keys → copy:
   - `STRIPE_SECRET_KEY=sk_test_…`
   - `STRIPE_PUBLISHABLE_KEY=pk_test_…`
3. Put those two lines in Hostinger `public_html/.env` (and local `php/.env` if you run the setup script). Do not put `sk_live_` on sandbox.

Do not create products, prices, Payment Links, or a webhook by hand unless the operator button cannot reach Stripe.

## What the app creates for you (v1.3.13+)

Deploy **v1.3.14+**, then operator console `/admin` → **Create Family Shield Pro prices in Stripe**.

That call uses the test secret key to create (or reuse) all of the following and writes the IDs into `.env`:

- Product `Family Shield Pro — Monthly` · recurring **$14.99 / month**
- Product `Family Shield Pro — Yearly` · recurring **$119.99 / year**
- Payment Links that return to `/billing/success?session_id={CHECKOUT_SESSION_ID}`
- Webhook `https://sandbox.familyshieldpro.com/billing/webhook` for  
  `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`

The other application’s webhook is left alone. Each endpoint has its own `whsec_`. If that sandbox webhook already existed, Stripe will not show the signing secret again — copy it from Developers → Webhooks into `STRIPE_WEBHOOK_SECRET`.

Same result from a machine that can reach Stripe:

```powershell
cd C:\Users\rober\Documents\GitHub\FamilyShieldPro
powershell -File .\deploy\setup_stripe_test.ps1
```

## What to click on the site

1. Sign in as the circle **owner** (demo `family@ourcircle.app` / `password123`, or a new signup).
2. **Plans** → **Pay Family monthly** or **Pay Family yearly**.
3. Test card: `4242 4242 4242 4242`, any future expiry, any CVC, any ZIP.
4. You return to `/billing`. The circle plan should show `monthly` or `yearly`.
5. **Manage card or cancel** opens the Stripe customer portal after the first successful Checkout.

Landing **Start monthly** / **Start yearly** signs up a new owner and then sends them to Stripe when checkout is ready.

## Failures

- Family **Plans** does not show Stripe env or checkout-debug lines. Open `/admin` → Stripe log and **Checkout errors**.
- Footer / `/healthz` still on an older build — unzip **v1.3.22+** into `public_html` and **keep the existing `.env`**.
- Buttons still say **Choose** (not **Pay**) — PHP is not seeing both `price_` IDs. `/admin` Stripe log reports whether `.env` is being read and whether those two lines are filled. Use `price_…` (open the $14.99 / $119.99 **price** row), not `prod_`.
- Hostinger **PHP Configuration → Environment variables** used to hide `.env` when the same key was already set (including placeholders). v1.3.20 lets a non-empty `.env` line win.
- Operator button says paste `sk_test_` — only a test secret key is used to create the catalog. If `.env` is writable, **Create Family Shield Pro prices in Stripe** writes the `price_` IDs for you.
- Checkout starts, then the plan does not change — webhook URL or `STRIPE_WEBHOOK_SECRET` is wrong. A success return with `session_id` still updates the circle if the secret key is present.
- Real cards are declined in test mode. Use `4242…` only.

Do not put live `sk_live_` keys on sandbox. Switch the Dashboard to live mode only when you are ready to charge families.
