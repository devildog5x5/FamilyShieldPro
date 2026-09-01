# Deploy Family Shield Pro

PHP app in `php/` → Hostinger `public_html`.

1. Zip the **contents** of `php/` (index.php, .htaccess, src, views, static, data).
2. hPanel → File Manager → unzip into `public_html`.
3. Copy `.env.example` to `.env`. Set:
   - `APP_SECRET` — long random string
   - `BASE_URL=https://yourdomain.com`
   - `CONTACT_PHONE` only if you have a real number (leave blank rather than XXX)
   - `OPERATOR_PASSWORD` for `/admin/login`
   - Resend or SMTP when you want invite/reset mail
4. PHP 8.2 or 8.3. Enable `pdo_sqlite`.
5. Backup `data/ourcircle.db` before replacing files on a live site.

Leave `SHOW_DEMO_LOGIN=0` on production. Sandbox may keep the demo circle.
