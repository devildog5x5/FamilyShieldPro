# Family Shield Pro (OurCircle)

Trusted family circle: pause, read warning signs, call someone you trust. **Guidance, not a guarantee.** Never a “this is safe” stamp.

Live sandbox: https://sandbox.familyshieldpro.com/

## What this repo is

Canonical PHP source for Hostinger (`public_html`). Version **1.3.0** fixes isolation, invites, call-me attribution, billing permissions, legal pages, and security headers found in sandbox testing.

## Local run

Needs PHP 8.2+ with `pdo_sqlite`.

```bash
cd php
copy .env.example .env
php -S 127.0.0.1:8080
```

Open http://127.0.0.1:8080 — demo login `family@ourcircle.app` / `password123` when `SHOW_DEMO_LOGIN=1`.

## Deploy (Hostinger)

1. Upload everything inside `php/` into `public_html` (not a nested folder).
2. Copy `.env.example` to `.env`. Set `APP_SECRET` and `BASE_URL`.
3. PHP 8.2/8.3 with `pdo_sqlite`.
4. Database file: `public_html/data/ourcircle.db` (blocked by `.htaccess`).

If you already have a live SQLite file, **back it up first**. This schema is not a drop-in ALTER of every 1.2.x table name; restore from backup if you need the old data, then migrate carefully.

## Product rules baked in

- Never stamp a request as safe
- Call-me names the person who **pasted the check**, not whoever tapped the button
- Screenshots are served only to the same circle
- Invites cannot duplicate an existing member or a waiting invite
- Owner can cancel invites and remove members
- Only the owner can change the household plan
- Empty circle notes are rejected

## Support

CustomerService@FamilyShieldPro.com
