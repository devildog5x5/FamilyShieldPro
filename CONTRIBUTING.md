# Contributing

Canonical clone: `C:\Users\rober\Documents\GitHub\FamilyShieldPro`

1. `git checkout main` and `git pull`.
2. Branch: `feature/…`, `fix/…`, or `chore/…` — one concern.
3. Commit with a short “why” message. Push the branch (`git push -u origin HEAD`).
4. Open a pull request into `main`. Merge, then delete the branch.
5. For product/PHP changes: bump `php/src/Db.php` `VERSION`, run `powershell -File .\build_php_zip.ps1`, attach the zip to a GitHub Release. Unzip into Hostinger `public_html` and keep the live `.env`.

Do not commit `.env`, SQLite files, or `installers/`. Do not force-push `main`. Never stamp a request as safe.
