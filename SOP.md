# SOP

How we ship Family Shield Pro (OurCircle). Git and release steps stay in `CONTRIBUTING.md`. This file is the product standard.

## Main menu

Every page shows the same main menu. That includes the home page, guides, sign-in, forgot password, join, reset, settings (Account), a single check, two-factor setup, Plans, the operator console, and a missing page.

The menu always links to every site activity, in this order:

1. Home
2. Check
3. Circle
4. Trusted list
5. Report
6. Plans
7. Account
8. Look it up
9. Contact
10. Guides (the index at `/guides`)

The same menu then links to each public guide, using the short name on that guide. Those links are part of this menu, not a second menu.

When nobody is signed in, the same menu also links to Sign in and Start a 14-day trial. When someone is signed in, that slot is Sign out. Do not add a second, shorter menu on a “simple” screen.

No dead ends. From any page a person can open every activity without the browser back button. New pages use `Layout::start()`, which prints this menu. Do not build a page that omits it.

`/guides` is a real index of every public guide. Do not leave that address as a missing page. The footer repeats the same guide links.

## Source and releases

Never link to or serve source code or release zips from a live site.

Do not put GitHub, a repository, source code, an APK, an installer, or a release download in pages, the menu, the footer, admin, the sitemap, or structured data. The Hostinger zip is for upload into `public_html`. Visitors do not download it from the website.

`.htaccess` must keep denying `*.zip`, `*.ps1`, `*.sql`, `.env`, and similar dev files. The release zip itself must not contain other zips, build scripts, `.git`, or those dev files.
