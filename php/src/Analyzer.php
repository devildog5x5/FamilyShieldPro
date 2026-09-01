<?php
declare(strict_types=1);

final class Analyzer
{
    private const KNOWN = [
        'apple.com', 'icloud.com', 'google.com', 'microsoft.com', 'amazon.com',
        'paypal.com', 'chase.com', 'wellsfargo.com', 'bankofamerica.com', 'citibank.com',
        'usaa.com', 'capitalone.com', 'americanexpress.com', 'discover.com',
        'irs.gov', 'ssa.gov', 'usa.gov', 'ftc.gov', 'ic3.gov',
        'ups.com', 'fedex.com', 'usps.com', 'dhl.com',
        'facebook.com', 'whatsapp.com', 'verizon.com', 'att.com', 'tmobile.com',
    ];

    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function host(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $h = parse_url($url, PHP_URL_HOST);
        return strtolower((string) $h);
    }

    public static function extract(string $text): array
    {
        $phones = [];
        if (preg_match_all('/(?:\+?1[-.\s]*)?(?:\(?\d{3}\)?[-.\s]*)\d{3}[-.\s]*\d{4}/', $text, $m)) {
            foreach ($m[0] as $p) {
                $d = self::digits($p);
                if (strlen($d) >= 10) {
                    $phones[$d] = $d;
                }
            }
        }
        $urls = [];
        if (preg_match_all('#https?://[^\s<>"\']+#i', $text, $m2)) {
            foreach ($m2[0] as $u) {
                $urls[rtrim($u, '.,);')] = rtrim($u, '.,);');
            }
        }
        return ['phones' => array_values($phones), 'urls' => array_values($urls)];
    }

    public static function run(string $text, string $phone, string $url, array $trusted): array
    {
        $ex = self::extract($text);
        if ($phone !== '') {
            $d = self::digits($phone);
            if ($d !== '' && !in_array($d, $ex['phones'], true)) {
                $ex['phones'][] = $d;
            }
        }
        if ($url !== '' && !in_array($url, $ex['urls'], true)) {
            $ex['urls'][] = $url;
        }

        $low = strtolower($text);
        $signs = [];
        $level = 'unknown';
        $headline = 'We cannot confirm this. Ask your circle before you act.';

        $hardPay = preg_match('/gift\s*card|apple\s*card|google\s*play|steam\s*card|bitcoin|btc\b|crypto|wire transfer|western union|moneygram|zelle/i', $text);
        if ($hardPay) {
            $signs[] = 'This asks for a gift card, crypto, wire, or similar hard-to-reverse payment. Real banks, tax offices, and family emergencies almost never demand that.';
            $level = 'pause';
            $headline = 'Pause. Do not pay or share anything yet.';
        }
        if (preg_match('/don\'t tell|do not tell|keep (it |this )?secret|don\'t tell mom|do not tell dad|between us/i', $text)) {
            $signs[] = 'It pushes you to keep it secret or act before you can talk to anyone. Scams thrive on isolation and panic.';
            $level = 'pause';
            $headline = 'Pause. Do not pay or share anything yet.';
        }
        if (preg_match('/\bjail\b|arrest|bail|grandkid|grandson|granddaughter|grandma|crashed the car|in mexico|in trouble/i', $text)) {
            $signs[] = 'Family-emergency stories over text or email are a common trick. Call the person using a number you already have.';
            if ($level !== 'pause') {
                $level = 'pause';
                $headline = 'Pause. Do not pay or share anything yet.';
            }
        }
        if (preg_match('/urgent|right now|immediately|act now|limited time|account (is )?locked|suspended/i', $text)) {
            $signs[] = 'It rushes you. Legitimate organizations let you hang up and call back on a number you already trust.';
            if ($level === 'unknown') {
                $level = 'caution';
                $headline = 'Slow down. Verify with your circle before you act.';
            }
        }
        if (preg_match('/password|pin\b|ssn|social security|account number|routing number|one[- ]time code|otp\b/i', $text)) {
            $signs[] = 'It asks for passwords, PINs, or account information. Independently verify before anything like that moves.';
            $level = 'pause';
            $headline = 'Pause. Do not pay or share anything yet.';
        }

        $patterns = [];
        $official = [];
        foreach ($ex['urls'] as $u) {
            $host = self::host($u);
            if ($host === '') {
                continue;
            }
            $look = self::lookalike($host);
            if ($look) {
                $signs[] = "The website {$host} resembles {$look} but is not an exact match. Lookalike sites are a common trick.";
                $patterns[] = "Lookalike of {$look}";
                $level = 'pause';
                $headline = 'Pause. Do not pay or share anything yet.';
            } elseif (in_array($host, self::KNOWN, true) || self::endsWithKnown($host)) {
                $official[] = "{$host} is a well-known official domain — still call using a number you already have, not a number in the message.";
            }
            foreach ($trusted as $t) {
                $th = self::host((string) ($t['website'] ?? ''));
                if ($th !== '' && ($th === $host || str_ends_with($host, '.' . $th))) {
                    $official[] = "Domain matches a trusted contact: {$th}";
                }
            }
        }

        $phoneNotes = [];
        foreach ($ex['phones'] as $d) {
            $match = self::trustedPhone($d, $trusted);
            $pretty = self::prettyPhone($d);
            if ($match) {
                $phoneNotes[] = "{$pretty} is on your family's trusted list.";
            } else {
                $phoneNotes[] = "{$pretty} is not on your trusted list. Call the organization using a number from a statement, the back of your card, or a contact you already saved — not this one.";
            }
        }

        if ($signs === []) {
            $signs[] = 'No classic scam phrases jumped out, which does not mean it is genuine. Pause and verify with your circle anyway.';
        }

        $next = [
            'Do not send money, crypto, gift cards, passwords, or account details yet.',
            'Ask someone in your family circle to look at this with you.',
            'If they want you to pay, use a phone number you already trust — not a number in the message.',
            'If you already paid or shared information, open Report & recover for the next steps.',
        ];
        if ($level === 'pause') {
            array_splice($next, 1, 0, ['Send a "Please call me before I pay" alert to your circle so nobody is left alone with this.']);
        }

        return [
            'phones' => $ex['phones'],
            'urls' => $ex['urls'],
            'signs' => $signs,
            'patterns' => array_merge($patterns, $official, $phoneNotes),
            'next' => $next,
            'level' => $level,
            'headline' => $headline,
        ];
    }

    private static function endsWithKnown(string $host): bool
    {
        foreach (self::KNOWN as $k) {
            if ($host === $k || str_ends_with($host, '.' . $k)) {
                return true;
            }
        }
        return false;
    }

    private static function lookalike(string $host): ?string
    {
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        foreach (self::KNOWN as $k) {
            if ($host === $k || str_ends_with($host, '.' . $k)) {
                return null;
            }
            $base = explode('.', $k)[0];
            if ($base !== '' && str_contains($host, $base) && $host !== $k) {
                return $k;
            }
        }
        return null;
    }

    private static function trustedPhone(string $digits, array $trusted): bool
    {
        $a = substr($digits, -10);
        foreach ($trusted as $t) {
            $b = substr(self::digits((string) ($t['phone'] ?? '')), -10);
            if ($b !== '' && $a === $b) {
                return true;
            }
        }
        return false;
    }

    public static function prettyPhone(string $digits): string
    {
        $d = substr(self::digits($digits), -10);
        if (strlen($d) !== 10) {
            return $digits;
        }
        return '(' . substr($d, 0, 3) . ') ' . substr($d, 3, 3) . '-' . substr($d, 6);
    }
}
