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
        $img = $base . '/static/img/og-card.jpg';
        $alt = 'OurCircle logo beside the words Pause. Ask family. Then pay.';
        $nonce = Http::cspNonce();
        echo '<!DOCTYPE html><html lang="en"' . $htmlClass . '><head><meta charset="UTF-8" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        echo '<title>' . Http::e($title) . '</title>';
        echo '<meta name="description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta name="keywords" content="' . Http::e($seo['keywords']) . '" />';
        echo '<meta name="robots" content="' . Http::e($seo['robots']) . '" />';
        echo '<meta name="author" content="Family Shield Pro" />';
        echo '<meta name="application-name" content="Family Shield Pro ' . Http::e($v) . '" />';
        echo '<meta name="theme-color" content="#0f6f6a" />';
        self::verificationTags();
        echo '<link rel="canonical" href="' . Http::e($url) . '" />';
        echo '<link rel="icon" href="/favicon.ico" sizes="any" />';
        echo '<link rel="icon" type="image/png" sizes="32x32" href="/static/img/favicon-32.png" />';
        echo '<link rel="icon" type="image/png" sizes="16x16" href="/static/img/favicon-16.png" />';
        echo '<link rel="apple-touch-icon" href="/apple-touch-icon.png" />';
        echo '<link rel="manifest" href="/site.webmanifest" />';
        echo '<meta property="og:site_name" content="OurCircle" />';
        echo '<meta property="og:type" content="' . Http::e($seo['og_type']) . '" />';
        echo '<meta property="og:locale" content="en_US" />';
        echo '<meta property="og:title" content="' . Http::e($title) . '" />';
        echo '<meta property="og:description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta property="og:url" content="' . Http::e($url) . '" />';
        echo '<meta property="og:image" content="' . Http::e($img) . '" />';
        if (str_starts_with(strtolower($base), 'https://')) {
            echo '<meta property="og:image:secure_url" content="' . Http::e($img) . '" />';
        }
        echo '<meta property="og:image:type" content="image/jpeg" />';
        echo '<meta property="og:image:width" content="1200" />';
        echo '<meta property="og:image:height" content="630" />';
        echo '<meta property="og:image:alt" content="' . Http::e($alt) . '" />';
        echo '<meta name="twitter:card" content="summary_large_image" />';
        echo '<meta name="twitter:title" content="' . Http::e($title) . '" />';
        echo '<meta name="twitter:description" content="' . Http::e($seo['description']) . '" />';
        echo '<meta name="twitter:image" content="' . Http::e($img) . '" />';
        echo '<meta name="twitter:image:alt" content="' . Http::e($alt) . '" />';
        echo '<meta name="color-scheme" content="' . ($mode === 'dark' ? 'dark' : 'light') . '" />';
        echo '<meta name="csrf-token" content="' . Http::e(Http::csrfToken()) . '" />';
        if (!empty($seo['jsonld'])) {
            echo '<script type="application/ld+json" nonce="' . Http::e($nonce) . '">' . self::jsonLd($base) . '</script>';
        }
        if (Http::path() === '/') {
            echo '<link rel="preload" as="image" href="/static/video/ourcircle-pause.webp?v=' . Http::e($v) . '" />';
        }
        echo '<script src="/static/js/fsp-theme.js?v=' . Http::e($v) . '" defer></script>';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />';
        echo '<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet" />';
        echo '<link rel="stylesheet" href="/static/css/app.css?v=' . Http::e($v) . '" />';
        echo '</head><body class="' . Http::e($bodyClass) . '">';
        self::mainNav($user);
    }

    /** Same activity links on every page. See SOP.md. */
    public static function activities(): array
    {
        return [
            '/' => 'Home',
            '/home' => 'Check',
            '/circle' => 'Circle',
            '/trusted' => 'Trusted list',
            '/report' => 'Report',
            '/billing' => 'Plans',
            '/account' => 'Account',
            '/#lookup' => 'Look it up',
            '/#contact' => 'Contact',
            '/guides' => 'Guides',
        ];
    }

    /** @return list<string> */
    public static function publicPaths(): array
    {
        return array_merge(['/', '/signup', '/privacy', '/terms', '/guides'], Guides::paths());
    }

    /** @return list<array{q:string,a:string}> */
    public static function faqItems(): array
    {
        return [
            [
                'q' => 'What is OurCircle?',
                'a' => 'A family pause before you send money, a gift card, or crypto. You bring in the text, call, or screenshot. You read the warning signs with up to five people. You call someone you trust. We do not stamp a request as safe.',
            ],
            [
                'q' => 'How much does it cost?',
                'a' => 'Start with a 14-day trial. Then Family yearly is $119.99 or $14.99 a month. The circle owner pays. You keep what you entered. We do not sell people’s information.',
            ],
            [
                'q' => 'What is included?',
                'a' => 'A household of up to five people, a trusted list of real phone numbers and sites, warning signs on a pasted message, and “Please call me before I pay.” It does not watch a phone by itself, freeze a card, or reverse a payment.',
            ],
            [
                'q' => 'What if the trial ends?',
                'a' => 'The trusted list and past checks stay readable. New checks, invites, and call-me wait until the owner pays. Paying is a family tool, not a stamp that a request is safe.',
            ],
        ];
    }

    public static function mainNav(?array $user = null): void
    {
        echo '<div class="wrap"><header class="site-header">';
        echo '<div class="brand-lockup">';
        self::brand();
        $who = trim((string) ($user['name'] ?? ''));
        if ($user && $who !== '') {
            echo '<span class="brand-who">' . Http::e($who) . '</span>';
        }
        echo '</div>';
        echo '<nav class="nav" aria-label="Main">';
        foreach (self::activities() as $href => $label) {
            $current = self::navCurrent($href) ? ' aria-current="page"' : '';
            echo '<a href="' . Http::e($href) . '"' . $current . '>' . Http::e($label) . '</a>';
        }
        $onGuide = str_starts_with(Http::path(), '/guides/');
        echo '<details class="nav-guides"' . ($onGuide ? ' open' : '') . '>';
        echo '<summary>Guide topics</summary><div class="nav-guides-panel">';
        foreach (Guides::all() as $guide) {
            $href = (string) $guide['path'];
            $current = Http::path() === $href ? ' aria-current="page"' : '';
            echo '<a class="nav-guide" href="' . Http::e($href) . '"' . $current . '>' . Http::e((string) $guide['nav']) . '</a>';
        }
        echo '</div></details>';
        if ($user || !empty($_SESSION['user_id'])) {
            echo '<a href="/logout">Sign out</a>';
        } else {
            $sign = self::navCurrent('/login') ? ' aria-current="page"' : '';
            $trial = self::navCurrent('/signup') ? ' aria-current="page"' : '';
            echo '<a href="/login"' . $sign . '>Sign in</a>';
            echo '<a class="btn sm" href="/signup"' . $trial . '>Start a 14-day trial</a>';
        }
        echo self::themeToggle();
        echo '</nav></header></div>';
        echo '<div class="wrap"><p class="core-rule">Never send money, cryptocurrency, gift cards, passwords, or account information until the request is independently verified.</p></div>';
        if ($user) {
            self::trialBanner($user);
        }
    }

    private static function navCurrent(string $href): bool
    {
        $path = Http::path();
        if ($href === '/' || $href === '/guides') {
            return $path === $href;
        }
        if (str_contains($href, '#')) {
            return false;
        }
        $prefixes = [
            '/home' => ['/home', '/check', '/checks'],
            '/account' => ['/account'],
            '/circle' => ['/circle'],
            '/trusted' => ['/trusted'],
            '/billing' => ['/billing'],
            '/report' => ['/report'],
            '/login' => ['/login'],
            '/signup' => ['/signup'],
        ];
        foreach ($prefixes[$href] ?? [$href] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    public static function brand(): void
    {
        echo '<a class="brand" href="/">';
        echo '<img src="/static/img/logo-mark.webp?v=' . Http::e(self::asset()) . '" width="56" height="56" alt="OurCircle logo" />';
        echo '<strong>OurCircle</strong>';
        echo '</a>';
    }

    private static function seo(): array
    {
        $path = Http::path();
        $guide = Guides::byPath($path);
        if ($guide) {
            return [
                'description' => (string) $guide['description'],
                'keywords' => (string) $guide['keywords'],
                'robots' => 'index, follow',
                'og_type' => 'article',
                'jsonld' => true,
            ];
        }
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
        $index = in_array($key, self::publicPaths(), true);
        $baseKw = 'Family Shield Pro, OurCircle, family scam protection, scam text pause, gift card scam, crypto scam, trusted list, call me before I pay';
        $copy = [
            '/' => 'OurCircle by Family Shield Pro: a household pause before money, gift cards, or crypto. Call someone you trust. Not a guarantee.',
            '/guides' => 'OurCircle guides: older parents, asking family before you pay, gift cards, trusted numbers, and emergency texts. Not a guarantee.',
            '/signup' => 'Start a 14-day OurCircle trial for up to five people. Trusted numbers, warning signs, and call-me-before-I-pay. Not a safe stamp.',
            '/login' => 'Sign in to Family Shield Pro OurCircle to pause with your household before anyone sends money. Guidance, not a guarantee.',
            '/forgot' => 'Request a one-hour Family Shield Pro OurCircle reset link by email. We never say whether that address is already on a circle.',
            '/privacy' => 'Privacy Policy for Family Shield Pro (OurCircle). How circle data, trusted lists, and checks are handled. We do not sell it.',
            '/terms' => 'Terms for Family Shield Pro OurCircle, a family pause tool. Guidance, not a guarantee. Never a stamp that a request is safe.',
            '/join' => 'Join a Family Shield Pro OurCircle household. Pause together on texts, prizes, and payment asks before anyone sends money.',
            '/reset' => 'Choose a new Family Shield Pro OurCircle password with your one-hour reset link, or the saved file when email is not connected.',
            '/home' => 'Family Shield Pro check inbox: paste a scam text, call, prize, or urgent payment ask. Read warning signs with your OurCircle family. Not a safe-or-fake stamp.',
            '/check' => 'Review this Family Shield Pro OurCircle check with your family: warning signs, trusted-list compare, notes, and call-me. Never a verdict that it is safe.',
            '/circle' => 'Manage your Family Shield Pro OurCircle household: invite up to five people, send call-me alerts, and pause together before anyone pays.',
            '/trusted' => 'Family Shield Pro trusted list: save real bank, doctor, and family numbers. OurCircle compares messages to this list, not to links in the text.',
            '/report' => 'Family Shield Pro report and recover: next steps if money, gift cards, or passwords already went out — freeze cards, report fraud, and stop further payments.',
            '/billing' => 'Family Shield Pro plans: Family monthly $14.99 or yearly $119.99 after a 14-day OurCircle trial. Paying does not make a request safe. You keep what you entered.',
            '/account' => 'Family Shield Pro OurCircle account settings: name, email, mobile for call-me SMS, appearance, password, and optional two-factor authentication.',
            '/trial' => 'Your Family Shield Pro 14-day OurCircle trial ended. Trusted list and past checks stay readable. Pay to check new scam texts, invite family, and use call-me.',
            '/admin' => 'Family Shield Pro operator console for site owners: manage circles, billing overrides, and help tools. Not a public family login.',
        ];
        $keywords = [
            '/' => $baseKw . ', elder fraud prevention, family circle app, pause before you pay',
            '/guides' => $baseKw . ', family guides, older parents, gift card scam, emergency text, Cybersecurity Awareness Month',
            '/signup' => $baseKw . ', OurCircle signup, 14-day trial, start a family circle',
            '/login' => $baseKw . ', OurCircle login, sign in, family account',
            '/forgot' => $baseKw . ', password reset, forgot password',
            '/privacy' => $baseKw . ', privacy policy, data protection',
            '/terms' => $baseKw . ', terms and conditions, terms of use',
            '/join' => $baseKw . ', join family circle, invite link',
            '/reset' => $baseKw . ', reset password, new password',
            '/home' => $baseKw . ', check a text, scam warning signs, paste suspicious message',
            '/check' => $baseKw . ', review request, warning signs, call-me alert',
            '/circle' => $baseKw . ', family members, household invite, call-me',
            '/trusted' => $baseKw . ', trusted contacts, verified numbers, bank phone list',
            '/report' => $baseKw . ', report fraud, recover from scam, freeze cards',
            '/billing' => $baseKw . ', pricing, Family monthly, Family yearly, subscribe',
            '/account' => $baseKw . ', account settings, 2FA, profile',
            '/trial' => $baseKw . ', trial ended, subscribe, continue OurCircle',
            '/admin' => 'Family Shield Pro, operator console, admin, site owner',
        ];
        return [
            'description' => $copy[$key] ?? 'That Family Shield Pro page is not here. Use the menu to open Home, Check, Circle, or another OurCircle activity. Guidance, not a guarantee.',
            'keywords' => $keywords[$key] ?? $baseKw,
            'robots' => $index ? 'index, follow' : 'noindex, nofollow',
            'og_type' => 'website',
            'jsonld' => $index,
        ];
    }

    private static function verificationTags(): void
    {
        $google = self::verificationCode('GOOGLE_SITE_VERIFICATION');
        $bing = self::verificationCode('BING_SITE_VERIFICATION');
        if ($google !== '') {
            echo '<meta name="google-site-verification" content="' . Http::e($google) . '" />';
        }
        if ($bing !== '') {
            echo '<meta name="msvalidate.01" content="' . Http::e($bing) . '" />';
        }
    }

    private static function verificationCode(string $key): string
    {
        $v = trim(Env::get($key));
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $v, $m)) {
            $v = trim($m[1]);
        }
        if ($v === '' || strlen($v) > 128 || !preg_match('/^[A-Za-z0-9_-]+$/', $v)) {
            return '';
        }
        return $v;
    }

    private static function jsonLd(string $base): string
    {
        $org = $base . '/#organization';
        $email = self::supportEmail();
        $organization = [
            '@type' => 'Organization',
            '@id' => $org,
            'name' => 'Family Shield Pro',
            'alternateName' => 'OurCircle',
            'url' => $base . '/',
            'logo' => $base . '/static/img/logo-mark.webp',
            'email' => $email,
            'description' => 'Family pause tool for scam texts, prizes, and urgent payment asks. Guidance, not a guarantee.',
        ];
        $phone = self::contactPhone();
        if ($phone !== '') {
            $organization['telephone'] = $phone;
        }
        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
                [
                    '@type' => 'WebSite',
                    '@id' => $base . '/#website',
                    'name' => 'OurCircle',
                    'alternateName' => 'Family Shield Pro',
                    'url' => $base . '/',
                    'description' => 'A household pause before money, gift cards, or crypto. Guidance, not a guarantee.',
                    'inLanguage' => 'en-US',
                    'publisher' => ['@id' => $org],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    'name' => 'OurCircle',
                    'alternateName' => 'Family Shield Pro',
                    'applicationCategory' => 'LifestyleApplication',
                    'operatingSystem' => 'Web',
                    'url' => $base . '/',
                    'description' => 'A family circle to pause on a text, call, prize, or urgent payment ask, read warning signs, and call someone you trust. It does not stamp a request as safe.',
                    'publisher' => ['@id' => $org],
                    'offers' => [
                        [
                            '@type' => 'Offer',
                            'name' => 'Family monthly',
                            'price' => '14.99',
                            'priceCurrency' => 'USD',
                            'url' => $base . '/signup?plan=monthly',
                            'description' => 'Up to five people. 14-day trial, then $14.99 per month.',
                            'priceSpecification' => [
                                '@type' => 'UnitPriceSpecification',
                                'price' => '14.99',
                                'priceCurrency' => 'USD',
                                'referenceQuantity' => [
                                    '@type' => 'QuantitativeValue',
                                    'value' => 1,
                                    'unitCode' => 'MON',
                                ],
                            ],
                        ],
                        [
                            '@type' => 'Offer',
                            'name' => 'Family yearly',
                            'price' => '119.99',
                            'priceCurrency' => 'USD',
                            'url' => $base . '/signup?plan=yearly',
                            'description' => 'Up to five people. 14-day trial, then $119.99 per year.',
                            'priceSpecification' => [
                                '@type' => 'UnitPriceSpecification',
                                'price' => '119.99',
                                'priceCurrency' => 'USD',
                                'referenceQuantity' => [
                                    '@type' => 'QuantitativeValue',
                                    'value' => 1,
                                    'unitCode' => 'ANN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        if (Http::path() === '/') {
            $entities = [];
            foreach (self::faqItems() as $item) {
                $entities[] = [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['a'],
                    ],
                ];
            }
            $graph['@graph'][] = [
                '@type' => 'FAQPage',
                '@id' => $base . '/#faq',
                'url' => $base . '/',
                'mainEntity' => $entities,
            ];
        }
        return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?: '{}';
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
        echo '<div class="wrap site-foot">';
        echo '<nav class="guide-links" aria-label="Guides">';
        $guideIndex = Http::path() === '/guides' ? ' aria-current="page"' : '';
        echo '<a href="/guides"' . $guideIndex . '>All guides</a>';
        foreach (Guides::all() as $guide) {
            $current = Http::path() === $guide['path'] ? ' aria-current="page"' : '';
            echo '<a href="' . Http::e((string) $guide['path']) . '"' . $current . '>' . Http::e((string) $guide['nav']) . '</a>';
        }
        echo '</nav>';
        echo '<p class="disclaimer"><span class="copy">© 2026 Family Shield Pro. All rights reserved.</span> This application offers guidance, not a guarantee. '
            . self::legalLinks()
            . ' <span class="build">' . Http::e(self::asset()) . '</span></p></div>';
        self::chat();
        echo '<script src="/static/js/fsp-chat.js?v=' . Http::e(self::asset()) . '" defer></script>';
        echo '<script src="/static/js/fsp-password.js?v=' . Http::e(self::asset()) . '" defer></script>';
        echo '<script src="/static/js/fsp-focus.js?v=' . Http::e(self::asset()) . '" defer></script>';
        echo '<script src="/static/js/fsp-video.js?v=' . Http::e(self::asset()) . '" defer></script>';
        echo '</body></html>';
    }

    public static function faq(): void
    {
        echo '<section class="faq" id="faq"><h2>Questions families ask</h2>';
        foreach (self::faqItems() as $item) {
            echo '<details class="faq-item"><summary>' . Http::e($item['q']) . '</summary>';
            echo '<p>' . Http::e($item['a']) . '</p></details>';
        }
        echo '</section>';
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
