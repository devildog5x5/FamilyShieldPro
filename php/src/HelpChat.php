<?php
declare(strict_types=1);

final class HelpChat
{
    private const MAX_MSG = 800;
    private const MAX_HISTORY = 8;
    private const MAX_REPLY = 900;
    private const RATE_WINDOW = 600;
    private const RATE_MAX_SESSION = 16;
    private const RATE_MAX_IP = 40;

    public static function configured(): bool
    {
        return self::apiKey() !== '' && extension_loaded('curl');
    }

    public static function apiKey(): string
    {
        $k = trim(Env::get('XAI_API_KEY'));
        if ($k === '') {
            $k = trim(Env::get('GROK_API_KEY'));
        }
        return $k;
    }

    public static function model(): string
    {
        $m = trim(Env::get('GROK_MODEL', 'grok-4.6'));
        return $m !== '' ? $m : 'grok-4.6';
    }

    /**
     * @param list<array{role?:string,content?:string}> $history
     */
    public static function reply(string $msg, array $history = []): string
    {
        $msg = trim($msg);
        if (strlen($msg) > self::MAX_MSG) {
            $msg = substr($msg, 0, self::MAX_MSG);
        }
        if ($msg === '') {
            return self::fallback('');
        }
        if (!self::allow()) {
            return 'Please wait a minute, then try Help again — or email ' . Layout::supportEmail() . '.';
        }
        if (!self::configured()) {
            return self::fallback($msg);
        }
        try {
            $text = self::grok($msg, $history);
            $text = self::guard($text);
            if ($text === '') {
                return self::fallback($msg);
            }
            return $text;
        } catch (Throwable $e) {
            self::log('error', $e->getMessage());
            return self::fallback($msg);
        }
    }

    public static function logPath(): string
    {
        $dir = dirname(Db::stripePayLogPath());
        return $dir . DIRECTORY_SEPARATOR . 'help-chat.txt';
    }

    public static function readLog(): string
    {
        $path = self::logPath();
        if (!is_file($path)) {
            return '';
        }
        $raw = file_get_contents($path);
        return is_string($raw) ? $raw : '';
    }

    /** @param list<array{role?:string,content?:string}> $history */
    private static function grok(string $msg, array $history): string
    {
        $messages = [['role' => 'system', 'content' => self::systemPrompt()]];
        $n = 0;
        foreach ($history as $row) {
            if ($n >= self::MAX_HISTORY) {
                break;
            }
            if (!is_array($row)) {
                continue;
            }
            $role = strtolower(trim((string) ($row['role'] ?? '')));
            $content = trim((string) ($row['content'] ?? ''));
            if ($content === '' || ($role !== 'user' && $role !== 'assistant')) {
                continue;
            }
            if (strlen($content) > self::MAX_MSG) {
                $content = substr($content, 0, self::MAX_MSG);
            }
            $messages[] = ['role' => $role, 'content' => $content];
            $n++;
        }
        $last = $messages[count($messages) - 1] ?? null;
        if (!is_array($last) || ($last['role'] ?? '') !== 'user' || ($last['content'] ?? '') !== $msg) {
            $messages[] = ['role' => 'user', 'content' => $msg];
        }
        $payload = json_encode([
            'model' => self::model(),
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 400,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            throw new RuntimeException('Could not build Grok request');
        }
        $ch = curl_init('https://api.x.ai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . self::apiKey(),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 18,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Grok request failed' . ($err !== '' ? ': connection' : ''));
        }
        $data = json_decode($raw, true);
        if ($code >= 400) {
            $hint = '';
            if (is_array($data)) {
                $err = $data['error'] ?? null;
                if (is_array($err)) {
                    $hint = (string) ($err['message'] ?? '');
                } elseif (is_string($err)) {
                    $hint = $err;
                }
            }
            $hint = preg_replace('/xai-[A-Za-z0-9_-]+/', 'xai_***', $hint) ?? $hint;
            throw new RuntimeException('Grok HTTP ' . $code . ($hint !== '' ? ': ' . substr($hint, 0, 180) : ''));
        }
        $text = '';
        if (is_array($data)) {
            $text = (string) ($data['choices'][0]['message']['content'] ?? '');
        }
        $text = trim($text);
        if (strlen($text) > self::MAX_REPLY) {
            $text = rtrim(substr($text, 0, self::MAX_REPLY)) . '…';
        }
        return $text;
    }

    private static function guard(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (preg_match('/\b(this is safe|that is safe|it is safe|not a scam|isn\'t a scam|is legitimate|is legit|go ahead and (pay|send)|you (can|should) (pay|send|wire))\b/i', $text)) {
            $em = Layout::supportEmail();
            return 'OurCircle cannot tell you that a request is safe. Pause. Check your trusted list. Call someone in your circle. Search the claim on Snopes, FTC Scam Alerts, or BBB Scam Tracker — do not tap links in the message. For a person, email ' . $em . '.';
        }
        return $text;
    }

    private static function systemPrompt(): string
    {
        $em = Layout::supportEmail();
        return <<<TXT
You are the Help assistant for Family Shield Pro (OurCircle), a family pause tool at familyshieldpro.com.
Speak in short, plain sentences. Do not role-play a lawyer or a bank.

Hard rules:
- Never say a text, call, prize, number, website, or payment request is safe, real, legitimate, or “not a scam.”
- Never tell someone to send money, crypto, gift cards, passwords, or account information.
- The pause rule: never send those until the request is independently verified through a number or site the family already trusts — not a number or link inside the suspicious message.
- Paying for a plan does not make a request safe. This application offers guidance, not a guarantee.

Product facts (do not invent others):
- New circles get a 14-day trial. Then Family monthly is \$14.99 or Family yearly is \$119.99 for up to five people.
- After the trial, new checks, invites, and call-me need the owner to pay. We do not lock people out of personal information they entered. Trusted list, past checks, and account details stay readable. We do not sell people’s information.
- Sign up at /signup. Sign in at /login. Forgot password at /forgot. Plans at /billing. Terms at /terms. Privacy at /privacy.
- Card payments go through Stripe when connected. Test cards only work in Stripe test mode.
- For a person, email {$em}. Help is not a customer-service hotline and not an emergency line.

If the visitor pastes a suspicious message, do not analyze it as safe or unsafe in a verdict. Tell them to paste it into Check on OurCircle, call someone they trust, and use the lookup links (Snopes, FTC, BBB, IC3).
If you do not know, say so and give the email. Do not invent prices, phone numbers, or features.
TXT;
    }

    public static function fallback(string $msg): string
    {
        $em = Layout::supportEmail();
        if ($msg === '') {
            return 'Ask me about plans, login, or how the circle works. For a person, email ' . $em . '.';
        }
        $low = strtolower($msg);
        if (preg_match('/terms|privacy|legal|conditions|t&c|\bt and c\b|t\'s and c/', $low)) {
            return 'The Terms & Conditions are at /terms. Privacy is at /privacy. Starting or joining a circle means you agree to both. We do not sell people’s information, and we do not lock you out of what you entered if a trial ends.';
        }
        if (preg_match('/safe|legit|real|scam or not|snopes|ftc|ic3|bbb/', $low)) {
            return 'OurCircle cannot tell you that a request is safe. Search the claim on Snopes, FTC Scam Alerts, or BBB Scam Tracker — do not tap links in the message. Official reports: ReportFraud.ftc.gov and IC3.gov. Then call someone in your circle.';
        }
        if (preg_match('/trial|14.day|paywall|expired/', $low)) {
            return 'Every new circle includes a 14-day trial. After that the owner pays $14.99/month or $119.99/year to keep checking new requests. We do not lock you out of what you entered, and we do not sell people’s information. You can still view the trusted list and past checks. Pay on /billing. Paying does not make a request safe.';
        }
        if (preg_match('/year|annual|119|price|cost|plan|month|14\.99/', $low)) {
            return 'Family Shield Pro is $14.99 per month or $119.99 per year for one circle of up to five people. Yearly is the better family value. Start at /signup. Paying does not make a request safe.';
        }
        if (preg_match('/login|password|forgot|sign in/', $low)) {
            return 'Use /login with the email on your circle. Forgot password sends a one-hour link, or saves it next to the database if mail is not connected. 2FA recovery codes also work. For a person, email ' . $em . '.';
        }
        if (preg_match('/sms|text|twilio|forward/', $low)) {
            return 'Save your mobile on Account. When texting is connected, invites and “Please call me before I pay” can go by SMS. Reply STOP to opt out. This is not a customer-service hotline.';
        }
        return 'Family Shield Pro (OurCircle) is a trusted family circle for sketchy texts, calls, prizes, and urgent payment asks. It is not an AI stamp of safety. Paste the request, read the warning signs, and call someone you trust. For a person, email ' . $em . '.';
    }

    private static function allow(): bool
    {
        $now = time();
        if (!isset($_SESSION['help_chat_hits']) || !is_array($_SESSION['help_chat_hits'])) {
            $_SESSION['help_chat_hits'] = [];
        }
        $hits = array_values(array_filter(
            $_SESSION['help_chat_hits'],
            static fn ($t): bool => is_int($t) && $t > $now - self::RATE_WINDOW
        ));
        if (count($hits) >= self::RATE_MAX_SESSION) {
            $_SESSION['help_chat_hits'] = $hits;
            return false;
        }
        $hits[] = $now;
        $_SESSION['help_chat_hits'] = $hits;
        return self::allowIp($now);
    }

    private static function allowIp(int $now): bool
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $key = hash_hmac('sha256', $ip, Env::get('APP_SECRET', 'fsp-dev-change-me'));
        $path = dirname(self::logPath()) . DIRECTORY_SEPARATOR . 'help-rate.json';
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $fh = fopen($path, 'c+');
        if ($fh === false) {
            return true;
        }
        flock($fh, LOCK_EX);
        $raw = stream_get_contents($fh);
        $map = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($map)) {
            $map = [];
        }
        $list = [];
        if (isset($map[$key]) && is_array($map[$key])) {
            foreach ($map[$key] as $t) {
                if (is_int($t) && $t > $now - self::RATE_WINDOW) {
                    $list[] = $t;
                }
            }
        }
        $ok = count($list) < self::RATE_MAX_IP;
        if ($ok) {
            $list[] = $now;
        }
        $map[$key] = $list;
        if (count($map) > 400) {
            $map = array_slice($map, -300, null, true);
        }
        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, json_encode($map, JSON_UNESCAPED_SLASHES) ?: '{}');
        flock($fh, LOCK_UN);
        fclose($fh);
        return $ok;
    }

    private static function log(string $step, string $message): void
    {
        $path = self::logPath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $safe = trim($message);
        $safe = preg_replace('/xai-[A-Za-z0-9_-]+/', 'xai_***', $safe) ?? $safe;
        $safe = preg_replace('/sk_(?:live|test)_[A-Za-z0-9]+/', 'sk_***', $safe) ?? $safe;
        if (strlen($safe) > 240) {
            $safe = substr($safe, 0, 240) . '…';
        }
        $line = Http::now() . ' ' . $step . ': ' . $safe . "\n";
        $prev = is_file($path) ? (string) file_get_contents($path) : '';
        $all = $line . $prev;
        $lines = preg_split("/\r\n|\n|\r/", trim($all)) ?: [];
        $lines = array_slice($lines, 0, 30);
        file_put_contents($path, implode("\n", $lines) . "\n");
    }
}
