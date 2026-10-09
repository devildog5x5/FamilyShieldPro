# Family Shield Pro (OurCircle)

Trusted family circle: pause, read warning signs, call someone you trust. **Guidance, not a guarantee.** Never a “this is safe” stamp.

Live site: https://familyshieldpro.com/

`sandbox.familyshieldpro.com` now returns 404. Do not use it for `BASE_URL`, Stripe return URLs, or webhooks. Point those at `https://familyshieldpro.com`.

## What this repo is

Canonical PHP source for Hostinger (`public_html`). Version **1.4.4**.

**Hostinger zip:** `familyshieldpro-v1.4.4.zip` — unzip into `public_html`. Rebuild locally with `powershell -File .\build_php_zip.ps1` (filename is `familyshieldpro-v` plus the build number from `Db::VERSION`). The zip stores paths with forward slashes so it opens on Linux and on Hostinger. Do not link that zip, this repo, or any installer from the live site. See `SOP.md`. Keep the live `.env` and database. Do not overwrite them.

## Local run

Needs PHP 8.2+ with `pdo_sqlite`.

```bash
cd php
copy .env.example .env
php -S 127.0.0.1:8080
```

Open http://127.0.0.1:8080 — demo login `family@ourcircle.app` / `password123` only when `SHOW_DEMO_LOGIN=1` on a host other than familyshieldpro.com. That demo circle can change data. The public site never prints the password. If the live `.env` still has `SHOW_DEMO_LOGIN=1`, set it to `0` when you next edit that file. Do not replace the live `.env` with `.env.example`.

## Deploy (Hostinger)

1. Download the latest [familyshieldpro zip](https://github.com/devildog5x5/FamilyShieldPro/releases/latest) (`familyshieldpro-v<version>.zip`) and unzip into `public_html` (not a nested folder).
2. Copy `.env.example` to `.env`. Set `APP_SECRET`, `BASE_URL`, `OPERATOR_EMAIL`, and `OPERATOR_PASSWORD`. Stripe test keys: see `STRIPE.md`.
3. PHP 8.2/8.3 with `pdo_sqlite`.
4. Database file: `public_html/data/ourcircle.db` (blocked by `.htaccess`).

If you already have a live SQLite file, **back it up first**. This schema is not a drop-in ALTER of every 1.2.x table name; restore from backup if you need the old data, then migrate carefully.

## Product rules baked in

- Never stamp a request as safe
- Call-me names the person who **pasted the check**, not whoever tapped the button
- Screenshots are served only to the same circle
- Invites cannot duplicate an existing member or a waiting invite
- Owner can cancel invites and remove members
- Only the owner can change the household plan (Stripe Checkout when keys are set; otherwise the plan is saved with no charge)
- Signup and join require agreement to the Terms & Conditions (`/terms`) and Privacy Policy (`/privacy`)
- Help button uses xAI Grok when `XAI_API_KEY` is set (https://console.x.ai); otherwise short built-in answers. Cursor login is not that key.
- Every new circle includes a 14-day trial; after that, new checks/invites/call-me need the owner to pay. Trusted list, past checks, and other entered personal information stay readable. We do not sell people’s information.
- Empty circle notes are rejected
- Circle members reset passwords at `/forgot` (email, file fallback, or 2FA recovery code)
- Operators reset at `/admin/forgot` (same email/file flow; new password is stored so it survives `.env`)
- Operators can restore factory data from `/admin` (type FACTORY, re-enter operator password)
- Operators can browse, edit, delete, insert, and run SQL from `/admin/data`

## Search consoles

Paste verification codes into `public_html/.env`. Leave a line blank to omit that tag. Paste the code only, or the whole meta tag — the site reads the `content` value either way. No code edit.

```
GOOGLE_SITE_VERIFICATION=
BING_SITE_VERIFICATION=
```

- Google Search Console → Ownership verification → HTML tag → the `content` value of `google-site-verification`.
- Bing Webmaster Tools → HTML meta tag → the `content` value of `msvalidate.01`.

IndexNow key file (the file name is the key, and the file body is the same key):

https://familyshieldpro.com/f05145da9377e3bf6466de8db09bede7.txt

Sitemap: https://familyshieldpro.com/sitemap.xml  
Robots: https://familyshieldpro.com/robots.txt

Sign-in, forgot password, reset, join, and signed-in app pages send `noindex` and are left out of the sitemap. Home, trial signup, privacy, terms, `/guides`, and each public guide stay indexable. `www`, `/index.php`, and a trailing slash 301 to `https://familyshieldpro.com` with no trailing slash. `http` is already redirected by the host.

The same main menu is on every page. See `SOP.md`.

## Support

Inquiries Text First Then Call: 801.319.1061. `SUPPORT_EMAIL` in `.env` is for outbound mail only and is not shown on the site.

FamilyShieldPro v1.4.4 · © 2026 REKKY Consulting LLC · Inquiries Text First Then Call: 801.319.1061
