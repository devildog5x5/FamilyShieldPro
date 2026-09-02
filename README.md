# Family Shield Pro (OurCircle)

Trusted family circle: pause, read warning signs, call someone you trust. **Guidance, not a guarantee.** Never a “this is safe” stamp.

Live sandbox: https://sandbox.familyshieldpro.com/

## What this repo is

Canonical PHP source for Hostinger (`public_html`). Version **1.3.5** adds an operator database page (browse / edit / SQL).

**Hostinger zip:** [FamilyShieldPro-PHP-1.3.5.zip](https://github.com/devildog5x5/FamilyShieldPro/releases/download/v1.3.5/FamilyShieldPro-PHP-1.3.5.zip) — unzip into `public_html`. Rebuild locally with `powershell -File .\build_php_zip.ps1` (filename includes the build number from `Db::VERSION`).

## Local run

Needs PHP 8.2+ with `pdo_sqlite`.

```bash
cd php
copy .env.example .env
php -S 127.0.0.1:8080
```

Open http://127.0.0.1:8080 — demo login `family@ourcircle.app` / `password123` when `SHOW_DEMO_LOGIN=1`.

## Deploy (Hostinger)

1. Download [FamilyShieldPro-PHP-1.3.5.zip](https://github.com/devildog5x5/FamilyShieldPro/releases/download/v1.3.5/FamilyShieldPro-PHP-1.3.5.zip) and unzip into `public_html` (not a nested folder).
2. Copy `.env.example` to `.env`. Set `APP_SECRET`, `BASE_URL`, `OPERATOR_EMAIL`, and `OPERATOR_PASSWORD`.
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
- Circle members reset passwords at `/forgot` (email, file fallback, or 2FA recovery code)
- Operators reset at `/admin/forgot` (same email/file flow; new password is stored so it survives `.env`)
- Operators can restore factory data from `/admin` (type FACTORY, re-enter operator password)
- Operators can browse, edit, delete, insert, and run SQL from `/admin/data`

## Support

CustomerService@FamilyShieldPro.com
