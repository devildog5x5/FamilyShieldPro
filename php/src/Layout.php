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
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />';
        echo '<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet" />';
        echo '<link rel="stylesheet" href="/static/css/app.css?v=' . Http::e($v) . '" />';
        echo '</head><body class="' . Http::e($bodyClass) . '">';
        if ($user) {
            self::appHeader($user);
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
        echo '<div class="wrap"><p class="disclaimer">This application offers guidance, not a guarantee.</p></div>';
        self::chat();
        echo '<script src="/static/js/fsp-chat.js?v=' . Http::e(self::asset()) . '"></script>';
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
        echo '<a href="#contact">Contact</a>';
        echo '<a href="/login">Sign in</a>';
        echo '<a class="btn sm" href="/signup">Start a circle</a>';
        echo '</nav></header></div>';
    }
}
