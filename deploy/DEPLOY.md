# Deploy Family Shield Pro

PHP app in `php/` → Hostinger `public_html`.

**Easiest:** download [FamilyShieldPro-PHP-1.3.7.zip](https://github.com/devildog5x5/FamilyShieldPro/releases/download/v1.3.7/FamilyShieldPro-PHP-1.3.7.zip) and unzip into `public_html`. Rebuild locally with `powershell -File .\build_php_zip.ps1` (the zip name includes the build number).

Or by hand:

1. Zip the **contents** of `php/` (index.php, .htaccess, src, views, static, data).
2. hPanel → File Manager → unzip into `public_html`.
3. Copy `.env.example` to `.env`. Set:
   - `APP_SECRET` — long random string
   - `BASE_URL=https://yourdomain.com`
   - `CONTACT_PHONE` only if you have a real number (leave blank rather than XXX)
   - `OPERATOR_EMAIL` — where operator password-reset mail goes (defaults to `SUPPORT_EMAIL`)
   - `OPERATOR_PASSWORD` for `/admin/login` (hashed into the database on first load so forgot-password can replace it)
   - Resend or SMTP when you want invite/reset mail. If mail is off, circle and operator reset links are written to `data/password-reset.txt` (blocked from the web).
4. PHP 8.2 or 8.3. Enable `pdo_sqlite`.
5. Backup `data/ourcircle.db` before replacing files on a live site.
6. To wipe sandbox data: operator console → Factory reset (type `FACTORY` and the operator password). This reseeds the Foster demo and restores `OPERATOR_PASSWORD` from `.env`.

Leave `SHOW_DEMO_LOGIN=0` on production. Sandbox may keep the demo circle.
