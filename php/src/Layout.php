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
        $s = strtolower(trim((string) ($_SESSION['theme'] ?? '')));
        if ($s === 'dark' || $s === 'light') {
            return $s;
        }
        return 'light';
    }

    public static function start(string $title, ?array $user = null, string $bodyClass = ''): void
    {
        $title = $title !== '' ? $title : 'OurCircle';
        if (!str_contains($title, 'OurCircle') && !str_contains($title, 'Family Shield Pro')) {
            $title .= ' · OurCircle';
        }
        $v = self::asset();
        $base = Http::baseUrl();
        $url = $base . Http::path();
        $mode = self::theme($user);
        $htmlClass = $mode === 'dark' ? ' class="dark"' : '';
        $seo = self::seo();
        $img = $base . '/static/video/ourcircle-pause.jpg';
        echo '<!DOCTYPE html><html lang="en"' . $htmlClass . '><head><meta charset="UTF-8" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        echo '<title>' . Http::e($title) . '</title>';
        echo '<meta name="description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta name="robots" content="' . Http::e($seo['robots']) . '" />';
        echo '<meta name="author" content="Family Shield Pro" />';
        echo '<meta name="application-name" content="Family Shield Pro ' . Http::e($v) . '" />';
        echo '<meta name="theme-color" content="#0f6f6a" />';
        echo '<link rel="canonical" href="' . Http::e($url) . '" />';
        echo '<link rel="icon" type="image/png" href="/static/img/logo.png" />';
        echo '<meta property="og:site_name" content="OurCircle" />';
        echo '<meta property="og:type" content="' . Http::e($seo['og_type']) . '" />';
        echo '<meta property="og:locale" content="en_US" />';
        echo '<meta property="og:title" content="' . Http::e($title) . '" />';
        echo '<meta property="og:description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta property="og:url" content="' . Http::e($url) . '" />';
        echo '<meta property="og:image" content="' . Http::e($img) . '" />';
        echo '<meta property="og:image:alt" content="OurCircle — Pause. Ask family. Then pay." />';
        echo '<meta name="twitter:card" content="summary_large_image" />';
        echo '<meta name="twitter:title" content="' . Http::e($title) . '" />';
        echo '<meta name="twitter:description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta name="twitter:image" content="' . Http::e($img) . '" />';
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

    private static function seo(): array
    {
        $path = Http::path();
        $key = '/';
        if ($path !== '/' && $path !== '') {
            $key = $path;
            if (str_starts_with($path, '/join/')) {
                $key = '/join';
            } elseif (str_starts_with($path, '/check')) {
                $key = '/check';
            } elseif (str_starts_with($path, '/admin')) {
                $key = '/admin';
            } elseif (str_starts_with($path, '/reset')) {
                $key = '/reset';
            } elseif (str_starts_with($path, '/account')) {
                $key = '/account';
            } elseif (str_starts_with($path, '/trusted')) {
                $key = '/trusted';
            } elseif (str_starts_with($path, '/billing')) {
                $key = '/billing';
            } elseif (str_starts_with($path, '/circle')) {
                $key = '/circle';
            } elseif (str_starts_with($path, '/trial')) {
                $key = '/trial';
            }
        }
        $index = in_array($key, ['/', '/signup', '/login', '/forgot', '/privacy', '/terms'], true);
        $copy = [
            '/' => 'Family Shield Pro OurCircle: family scam pause before money, gift cards, or crypto. Paste a sketchy text, read warning signs, check your trusted list, and call someone you trust. Guidance, not a guarantee.',
            '/signup' => 'Start a Family Shield Pro OurCircle 14-day trial. Family circle of up to five, trusted contacts list, and call-me-before-I-pay for scam texts and urgent payment asks. Not a safe stamp.',
            '/login' => 'Sign in to Family Shield Pro OurCircle. Pause with your household, review scam warning signs, and call trusted family before anyone pays. Guidance, not a guarantee.',
            '/forgot' => 'Forgot password for Family Shield Pro OurCircle. Request a one-hour reset link by email. We never confirm whether that address is on file.',
            '/privacy' => 'Privacy Policy for Family Shield Pro (OurCircle). How we handle family circle data, trusted lists, and checks. We do not sell people’s information.',
            '/terms' => 'Terms & Conditions for Family Shield Pro OurCircle. Family pause tool for scam texts and payment asks. Guidance, not a guarantee. Never a stamp that a request is safe.',
            '/join' => 'Join a Family Shield Pro OurCircle household. Pause together on sketchy texts, prizes, and payment requests before anyone sends money or gift cards.',
            '/reset' => 'Choose a new Family Shield Pro OurCircle password with your one-hour reset link, or the local reset file when email is not connected.',
            '/home' => 'Family Shield Pro check inbox: paste a scam text, call, prize, or urgent payment ask. Read warning signs with your OurCircle family. Not a safe-or-fake stamp.',
            '/check' => 'Review this Family Shield Pro OurCircle check with your family: warning signs, trusted-list compare, notes, and call-me. Never a verdict that it is safe.',
            '/circle' => 'Manage your Family Shield Pro OurCircle household: invite up to five people, send call-me alerts, and pause together before anyone pays.',
            '/trusted' => 'Family Shield Pro trusted list: save real bank, doctor, and family numbers and websites. OurCircle compares sketchy messages to this list, not to links in the text.',
            '/report' => 'Family Shield Pro report and recover: next steps if money, gift cards, or passwords already went out — freeze cards, report fraud, and stop further payments.',
            '/billing' => 'Family Shield Pro plans: Family monthly $14.99 or yearly $119.99 after a 14-day OurCircle trial. Paying does not make a request safe. You keep what you entered.',
            '/account' => 'Family Shield Pro OurCircle account settings: name, email, mobile for call-me SMS, appearance, password, and optional two-factor authentication.',
            '/trial' => 'Your Family Shield Pro 14-day OurCircle trial ended. Trusted list and past checks stay readable. Pay to check new scam texts, invite family, and use call-me.',
            '/admin' => 'Family Shield Pro operator console for site owners: manage circles, billing overrides, and help tools. Not a public family login.',
        ];
        return [
            'description' => $copy[$key] ?? 'Family Shield Pro OurCircle is a trusted family circle for scam texts, prizes, and urgent payment asks: pause, read warning signs, call someone you trust. Guidance, not a guarantee.',
            'robots' => $index ? 'index, follow' : 'noindex, nofollow',
            'og_type' => $key === '/' ? 'website' : 'article',
        ];
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
