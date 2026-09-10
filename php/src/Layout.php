<?php
declare(strict_types=1);

final class Layout
{
    public static function asset(): string
    {
        return Db::VERSION;
    }

    public static function supportEmail(): string
    {
        return Env::get('SUPPORT_EMAIL', 'CustomerService@FamilyShieldPro.com');
    }

    public static function contactPhone(): string
    {
        $p = trim(Env::get('CONTACT_PHONE'));
        if ($p === '' || str_contains($p, 'XXX')) {
            return '';
        }
        return $p;
    }

    public static function theme(?array $user = null): string
    {
        $u = strtolower(trim((string) ($user['theme'] ?? '')));
        if ($u === 'dark' || $u === 'light') {
            return $u;
        }
        $s = strtolower(trim((string) ($_SESSION['theme'] ?? '')));
        if ($s === 'dark' || $s === 'light') {
            return $s;
        }
        return 'light';
    }

    public static function start(string $title, ?array $user = null, string $bodyClass = ''): void
    {
        $title = $title !== '' ? $title : 'OurCircle';
        $v = self::asset();
        $base = Http::baseUrl();
        $mode = self::theme($user);
        $htmlClass = $mode === 'dark' ? ' class="dark"' : '';
        echo '<!DOCTYPE html><html lang="en"' . $htmlClass . '><head><meta charset="UTF-8" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        echo '<title>' . Http::e($title) . '</title>';
        echo '<meta name="application-name" content="Family Shield Pro ' . Http::e($v) . '" />';
        echo '<link rel="canonical" href="' . Http::e($base . Http::path()) . '" />';
        echo '<link rel="icon" type="image/png" href="/static/img/logo.png" />';
        echo '<meta name="color-scheme" content="' . ($mode === 'dark' ? 'dark' : 'light') . '" />';
        echo '<meta name="csrf-token" content="' . Http::e(Http::csrfToken()) . '" />';
        echo '<script src="/static/js/fsp-theme.js?v=' . Http::e($v) . '"></script>';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />';
        echo '<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet" />';
        echo '<link rel="stylesheet" href="/static/css/app.css?v=' . Http::e($v) . '" />';
        echo '</head><body class="' . Http::e($bodyClass) . '">';
        if ($user) {
            self::appHeader($user);
        } elseif ($bodyClass === 'auth-page' || $bodyClass === 'app-bare') {
            echo '<div class="theme-toggle-wrap">' . self::themeToggle() . '</div>';
        }
    }

    public static function brand(): void
    {
        echo '<a class="brand" href="/">';
        echo '<img src="/static/img/logo.png" alt="OurCircle" />';
        echo '<strong>OurCircle</strong>';
        echo '</a>';
    }

    public static function appHeader(array $user): void
    {
        echo '<div class="wrap"><header class="app-header">';
        echo '<div class="brand-lockup">';
        self::brand();
        $who = trim((string) ($user['name'] ?? ''));
        if ($who !== '') {
            echo '<span class="brand-who">' . Http::e($who) . '</span>';
        }
        echo '</div>';
        echo '<nav class="nav">';
        foreach ([
            '/home' => 'Check',
            '/circle' => 'Circle',
            '/trusted' => 'Trusted list',
            '/report' => 'Report',
            '/billing' => 'Plans',
            '/account' => 'Account',
            '/logout' => 'Sign out',
        ] as $href => $label) {
            echo '<a href="' . $href . '">' . $label . '</a>';
        }
        echo self::themeToggle();
        echo '</nav></header></div>';
        echo '<div class="wrap"><p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p></div>';
        self::trialBanner($user);
    }

    public static function trialBanner(array $user): void
    {
        $t = $user['trial'] ?? [];
        if ($t === [] || !empty($t['demo']) || !empty($t['paid'])) {
            return;
        }
        if (!empty($t['active_trial'])) {
            $left = !empty($t['ends_today'])
                ? 'Your 14-day trial ends today (' . ($t['ends_label'] ?? '') . ').'
                : '14-day trial · ' . (int) ($t['days_left'] ?? 0) . ' day'
                    . ((int) ($t['days_left'] ?? 0) === 1 ? '' : 's')
                    . ' left · ends ' . ($t['ends_label'] ?? '') . '.';
            echo '<div class="wrap"><div class="trial-banner">' . Http::e($left)
                . ' Then the circle owner pays to keep checking new requests. <a href="/billing">View plans</a></div></div>';
            return;
        }
        if (empty($t['expired'])) {
            return;
        }
        $msg = !empty($t['is_owner'])
            ? 'Your 14-day trial has ended. You can still view the trusted list and past checks. Pay to check new requests, invite family, and use call-me.'
            : 'This circle’s 14-day trial has ended. You can still view the trusted list and past checks. Ask the owner to continue Family Shield Pro.';
        echo '<div class="wrap"><div class="trial-banner ended">' . Http::e($msg)
            . ' <a href="/billing">Plans</a></div></div>';
    }

    public static function flash(): void
    {
        $f = Http::flash();
        if (!$f) {
            return;
        }
        $cls = $f['type'] === 'error' ? 'flash error' : 'flash ok';
        echo '<div class="' . $cls . '">' . Http::e($f['text']) . '</div>';
    }

    public static function end(?array $user = null): void
    {
        echo '<div class="wrap"><p class="disclaimer"><span class="copy">© 2026 Family Shield Pro. All rights reserved.</span> This application offers guidance, not a guarantee. '
            . self::legalLinks()
            . ' <span class="build">' . Http::e(self::asset()) . '</span></p></div>';
        self::chat();
        echo '<script src="/static/js/fsp-chat.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '<script src="/static/js/fsp-password.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '<script src="/static/js/fsp-focus.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '<script src="/static/js/fsp-video.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '</body></html>';
    }

    public static function legalLinks(): string
    {
        return '<a href="/privacy">Privacy</a> · <a href="/terms">Terms &amp; Conditions</a>';
    }

    public static function agreeCheckbox(): void
    {
        echo '<label class="check-row agree-row"><input type="checkbox" name="agree" value="1" required />';
        echo '<span>I agree to the <a href="/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a> and <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.</span></label>';
    }

    public static function agreed(): bool
    {
        $v = strtolower(trim((string) ($_POST['agree'] ?? '')));
        return in_array($v, ['1', 'on', 'true', 'yes'], true);
    }

    public static function chat(): void
    {
        $em = self::supportEmail();
        echo '<div class="fsp-chat" id="fsp-chat">';
        echo '<button type="button" class="fsp-chat-tab" id="fsp-chat-toggle" aria-expanded="false" aria-controls="fsp-chat-panel">Help</button>';
        echo '<div class="fsp-chat-panel" id="fsp-chat-panel" hidden>';
        echo '<header class="fsp-chat-head"><strong>Family Shield Pro help</strong>';
        echo '<button type="button" class="fsp-chat-hide" id="fsp-chat-close" aria-label="Hide help">Hide</button></header>';
        echo '<div class="fsp-chat-log" id="fsp-chat-log"></div>';
        echo '<form class="fsp-chat-form" id="fsp-chat-form">';
        echo '<label class="sr-only" for="fsp-chat-input">Message</label>';
        echo '<input id="fsp-chat-input" maxlength="800" placeholder="Ask about plans, login, or the pause rule." autocomplete="off" />';
        echo '<button class="btn" type="submit">Send</button></form>';
        echo '<p class="fsp-chat-mail">Email <a href="mailto:' . Http::e($em) . '">' . Http::e($em) . '</a></p>';
        echo '</div></div>';
    }

    public static function publicNav(): void
    {
        echo '<div class="wrap"><header class="site-header">';
        self::brand();
        echo '<nav class="nav">';
        echo '<a href="#lookup">Look it up</a>';
        echo '<a href="#contact">Contact</a>';
        echo '<a href="/login">Sign in</a>';
        echo '<a class="btn sm" href="/signup">Start a 14-day trial</a>';
        echo self::themeToggle();
        echo '</nav></header></div>';
    }

    public static function themeToggle(): string
    {
        return '<button type="button" class="theme-toggle" id="fsp-theme-toggle" aria-pressed="false" title="Switch to dark appearance">Dark</button>';
    }

    public static function scamRefs(string $heading = 'h3'): void
    {
        $h = $heading === 'h2' ? 'h2' : 'h3';
        echo '<' . $h . '>Look it up yourself</' . $h . '>';
        echo '<p class="muted">Search the claim on these sites. Do not tap links inside the suspicious message. A match or a missing article is not a stamp that something is safe.</p>';
        echo '<ul class="list scam-refs">';
        foreach ([
            ['https://www.snopes.com/', 'Snopes', 'Search a prize, news story, or viral claim.'],
            ['https://consumer.ftc.gov/features/scam-alerts', 'FTC Scam Alerts', 'Current scam patterns the FTC is warning about.'],
            ['https://www.bbb.org/scamtracker', 'BBB Scam Tracker', 'See if others reported the same ask.'],
            ['https://reportfraud.ftc.gov', 'ReportFraud.ftc.gov', 'Official U.S. fraud report.'],
            ['https://www.ic3.gov', 'IC3.gov', 'FBI internet-crime reports.'],
        ] as $row) {
            echo '<li><a href="' . Http::e($row[0]) . '" rel="noopener noreferrer" target="_blank">' . Http::e($row[1]) . '</a> — ' . Http::e($row[2]) . '</li>';
        }
        echo '</ul>';
    }
}
