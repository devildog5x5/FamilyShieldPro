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

    public static function start(string $title, ?array $user = null, string $bodyClass = ''): void
    {
        $title = $title !== '' ? $title : 'OurCircle';
        $v = self::asset();
        $base = Http::baseUrl();
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        echo '<title>' . Http::e($title) . '</title>';
        echo '<link rel="canonical" href="' . Http::e($base . Http::path()) . '" />';
        echo '<link rel="icon" type="image/png" href="/static/img/logo.png" />';
        echo '<meta name="color-scheme" content="light dark" />';
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

    public static function appHeader(array $user): void
    {
        echo '<div class="wrap"><header class="app-header">';
        echo '<a class="brand" href="' . Http::e(Http::baseUrl()) . '"><img src="/static/img/logo.png" alt="Family Shield Pro" /><div><strong>OurCircle</strong><span>' . Http::e($user['name'] ?? '') . '</span></div></a>';
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
        echo '<div class="wrap"><p class="disclaimer">This application offers guidance, not a guarantee.<a class="op-hatch" href="/admin/login" tabindex="-1" aria-hidden="true"></a></p></div>';
        self::chat();
        echo '<script src="/static/js/fsp-chat.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '<script src="/static/js/fsp-password.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '<script src="/static/js/fsp-focus.js?v=' . Http::e(self::asset()) . '"></script>';
        echo '</body></html>';
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
        echo '<a class="brand" href="' . Http::e(Http::baseUrl()) . '"><img src="/static/img/logo.png" alt="Family Shield Pro" /><div><strong>OurCircle</strong><span>Family Shield Pro</span></div></a>';
        echo '<nav class="nav">';
        echo '<a href="#lookup">Look it up</a>';
        echo '<a href="#contact">Contact</a>';
        echo '<a href="/login">Sign in</a>';
        echo '<a class="btn sm" href="/signup">Start a circle</a>';
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
