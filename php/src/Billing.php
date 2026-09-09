<?php
declare(strict_types=1);

final class Billing
{
    public const PLANS = [
        'monthly' => [
            'name' => 'Family monthly',
            'amount_label' => '$14.99/month',
            'amount' => 1499,
            'interval' => 'month',
            'env_price' => 'STRIPE_PRICE_MONTHLY',
            'env_link' => 'STRIPE_PAYMENT_LINK_MONTHLY',
        ],
        'yearly' => [
            'name' => 'Family yearly',
            'amount_label' => '$119.99/year',
            'amount' => 11999,
            'interval' => 'year',
            'env_price' => 'STRIPE_PRICE_YEARLY',
            'env_link' => 'STRIPE_PAYMENT_LINK_YEARLY',
        ],
    ];

    public static function configuredValue(string $value, string $prefix = '', int $minLen = 16): bool
    {
        $v = self::unquote(trim($value));
        if ($v === '' || str_contains($v, '...')) {
            return false;
        }
        if ($prefix !== '' && !str_starts_with($v, $prefix)) {
            return false;
        }
        return strlen($v) >= $minLen;
    }

    public static function unquote(string $v): string
    {
        $v = trim($v);
        if (
            (strlen($v) >= 2 && str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (strlen($v) >= 2 && str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            return substr($v, 1, -1);
        }
        return $v;
    }

    public static function config(): array
    {
        $prices = [];
        $links = [];
        foreach (self::PLANS as $key => $meta) {
            $prices[$key] = self::unquote(trim(Env::get($meta['env_price'])));
            $links[$key] = self::unquote(trim(Env::get($meta['env_link'])));
        }
        $secret = self::unquote(trim(Env::get('STRIPE_SECRET_KEY')));
        $hasSecret = self::configuredValue($secret, 'sk_', 20);
        $hasPrices = true;
        foreach ($prices as $pid) {
            $hasPrices = $hasPrices && self::configuredValue($pid, 'price_', 20);
        }
        $hasLinks = true;
        foreach ($links as $url) {
            $hasLinks = $hasLinks && self::isPaymentLink($url);
        }
        return [
            'secret_key' => $secret,
            'publishable_key' => trim(Env::get('STRIPE_PUBLISHABLE_KEY')),
            'webhook_secret' => trim(Env::get('STRIPE_WEBHOOK_SECRET')),
            'base_url' => rtrim(Env::get('BASE_URL', Http::baseUrl()), '/'),
            'prices' => $prices,
            'links' => $links,
            'checkout' => $hasSecret && $hasPrices,
            'payment_links' => $hasLinks,
            'ready' => ($hasSecret && $hasPrices) || $hasLinks,
            'test_mode' => str_starts_with($secret, 'sk_test_')
                || str_contains(implode(' ', $links), 'buy.stripe.com/test_'),
        ];
    }

    public static function ready(): bool
    {
        return self::config()['ready'];
    }

    public static function isPaymentLink(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || strlen($url) < 24) {
            return false;
        }
        return (bool) preg_match('#^https://buy\.stripe\.com/[A-Za-z0-9_]+#', $url);
    }

    public static function planFromPriceId(?string $priceId): ?string
    {
        if (!$priceId) {
            return null;
        }
        foreach (self::config()['prices'] as $plan => $pid) {
            if ($pid !== '' && $pid === $priceId) {
                return $plan;
            }
        }
        return null;
    }

    public static function label(string $plan): string
    {
        $meta = self::PLANS[$plan] ?? null;
        if (!$meta) {
            return $plan;
        }
        return $meta['name'] . ' (' . $meta['amount_label'] . ')';
    }

    public static function api(string $method, string $path, array $params = []): array
    {
        $cfg = self::config();
        if ($cfg['secret_key'] === '') {
            throw new RuntimeException('STRIPE_SECRET_KEY is not set');
        }
        $method = strtoupper($method);
        $url = 'https://api.stripe.com' . $path;
        if ($method === 'GET' && $params) {
            $url .= (str_contains($path, '?') ? '&' : '?') . http_build_query($params);
            $params = [];
        }
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $cfg['secret_key']];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Stripe request failed');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Stripe returned invalid JSON');
        }
        if ($code >= 400) {
            $msg = $data['error']['message'] ?? $raw;
            throw new RuntimeException((string) $msg);
        }
        return $data;
    }

    public static function createCheckout(array $args): array
    {
        $plan = (string) ($args['plan'] ?? '');
        if (!isset(self::PLANS[$plan])) {
            throw new InvalidArgumentException('Unknown plan');
        }
        $cfg = self::config();
        $price = $cfg['prices'][$plan] ?? '';
        if ($price === '') {
            throw new RuntimeException("Missing Stripe price for plan '{$plan}'");
        }
        $circleId = (string) $args['circle_id'];
        $meta = [
            'plan' => $plan,
            'circle_id' => $circleId,
            'user_id' => (string) $args['user_id'],
        ];
        $params = [
            'mode' => 'subscription',
            'line_items' => [['price' => $price, 'quantity' => 1]],
            'success_url' => $cfg['base_url'] . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cfg['base_url'] . '/billing',
            'client_reference_id' => 'c' . $circleId,
            'metadata' => $meta,
            'allow_promotion_codes' => 'true',
            'subscription_data' => ['metadata' => $meta],
        ];
        if (!empty($args['customer_id'])) {
            $params['customer'] = $args['customer_id'];
        } else {
            $params['customer_email'] = $args['customer_email'];
        }
        return self::api('POST', '/v1/checkout/sessions', $params);
    }

    public static function createPortal(string $customerId): array
    {
        $cfg = self::config();
        return self::api('POST', '/v1/billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => $cfg['base_url'] . '/billing',
        ]);
    }

    public static function retrieveCheckout(string $sessionId): array
    {
        return self::api(
            'GET',
            '/v1/checkout/sessions/' . rawurlencode($sessionId)
            . '?expand[]=subscription&expand[]=subscription.items.data.price'
        );
    }

    public static function startPayment(PDO $db, array $user, string $plan): string
    {
        if (!isset(self::PLANS[$plan])) {
            throw new InvalidArgumentException('Unknown plan');
        }
        $cfg = self::config();
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP curl is off. Enable curl in Hostinger PHP configuration so Checkout can reach Stripe.');
        }
        $st = $db->prepare('SELECT stripe_customer_id FROM circles WHERE id=?');
        $st->execute([(int) $user['circle_id']]);
        $customerId = trim((string) ($st->fetchColumn() ?: ''));
        if ($cfg['checkout']) {
            $args = [
                'plan' => $plan,
                'circle_id' => (int) $user['circle_id'],
                'user_id' => (int) $user['id'],
                'customer_email' => (string) $user['email'],
                'customer_id' => $customerId !== '' ? $customerId : null,
            ];
            try {
                $session = self::createCheckout($args);
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if ($customerId !== '' && stripos($msg, 'no such customer') !== false) {
                    $args['customer_id'] = null;
                    $session = self::createCheckout($args);
                } else {
                    throw $e;
                }
            }
            $url = (string) ($session['url'] ?? '');
            if ($url === '') {
                throw new RuntimeException('Stripe did not return a checkout URL');
            }
            return $url;
        }
        $link = $cfg['links'][$plan] ?? '';
        if (!self::isPaymentLink($link)) {
            throw new RuntimeException('Stripe is not ready for this plan');
        }
        $q = http_build_query([
            'client_reference_id' => 'c' . (int) $user['circle_id'],
            'prefilled_email' => (string) $user['email'],
        ]);
        return $link . (str_contains($link, '?') ? '&' : '?') . $q;
    }

    public static function constructEvent(string $payload, string $header, string $secret): array
    {
        $parts = [];
        foreach (explode(',', $header) as $item) {
            if (!str_contains($item, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $item, 2));
            $parts[$k][] = $v;
        }
        $timestamp = $parts['t'][0] ?? '';
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $ok = false;
        foreach ($parts['v1'] ?? [] as $sig) {
            if (hash_equals($expected, $sig)) {
                $ok = true;
            }
        }
        if (!$ok) {
            throw new RuntimeException('Invalid Stripe signature');
        }
        if (abs(time() - (int) $timestamp) > 300) {
            throw new RuntimeException('Stripe timestamp too old');
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new RuntimeException('Invalid Stripe event JSON');
        }
        return $event;
    }

    public static function circleIdFromCheckout(array $checkout): ?int
    {
        $meta = $checkout['metadata'] ?? [];
        if (is_array($meta) && isset($meta['circle_id']) && (int) $meta['circle_id'] > 0) {
            return (int) $meta['circle_id'];
        }
        $ref = (string) ($checkout['client_reference_id'] ?? '');
        if (preg_match('/^c(\d+)$/', $ref, $m)) {
            return (int) $m[1];
        }
        if (ctype_digit($ref) && (int) $ref > 0) {
            return (int) $ref;
        }
        return null;
    }

    public static function applyCheckout(PDO $db, array $checkout): void
    {
        $circleId = self::circleIdFromCheckout($checkout);
        $customer = $checkout['customer'] ?? null;
        $customerId = is_string($customer) ? $customer : null;
        if (!$circleId && $customerId) {
            $st = $db->prepare('SELECT id FROM circles WHERE stripe_customer_id=?');
            $st->execute([$customerId]);
            $circleId = (int) ($st->fetchColumn() ?: 0) ?: null;
        }
        if (!$circleId) {
            return;
        }
        $meta = is_array($checkout['metadata'] ?? null) ? $checkout['metadata'] : [];
        $plan = (string) ($meta['plan'] ?? '');
        $subscription = $checkout['subscription'] ?? null;
        $subId = is_string($subscription) ? $subscription : (is_array($subscription) ? ($subscription['id'] ?? null) : null);
        if (!isset(self::PLANS[$plan]) && is_array($subscription)) {
            $items = $subscription['items']['data'] ?? [];
            $priceId = $items[0]['price']['id'] ?? null;
            $plan = self::planFromPriceId(is_string($priceId) ? $priceId : null) ?? '';
        }
        $sets = [];
        $args = [];
        if (isset(self::PLANS[$plan])) {
            $sets[] = 'plan=?';
            $args[] = $plan;
        }
        if (is_string($customerId) && $customerId !== '') {
            $sets[] = 'stripe_customer_id=?';
            $args[] = $customerId;
        }
        if (is_string($subId) && $subId !== '') {
            $sets[] = 'stripe_subscription_id=?';
            $args[] = $subId;
        }
        if (!$sets) {
            return;
        }
        $args[] = $circleId;
        $db->prepare('UPDATE circles SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($args);
    }

    public static function applySubscription(PDO $db, array $sub): void
    {
        $customerId = is_string($sub['customer'] ?? null) ? (string) $sub['customer'] : '';
        $subId = (string) ($sub['id'] ?? '');
        if ($customerId === '') {
            return;
        }
        $st = $db->prepare('SELECT id FROM circles WHERE stripe_customer_id=? OR stripe_subscription_id=?');
        $st->execute([$customerId, $subId]);
        $circleId = (int) ($st->fetchColumn() ?: 0);
        $meta = is_array($sub['metadata'] ?? null) ? $sub['metadata'] : [];
        if (!$circleId && isset($meta['circle_id'])) {
            $circleId = (int) $meta['circle_id'];
        }
        if (!$circleId) {
            return;
        }
        $status = (string) ($sub['status'] ?? '');
        if ($status === 'canceled' || !empty($sub['canceled_at'])) {
            $db->prepare('UPDATE circles SET stripe_subscription_id=NULL WHERE id=?')->execute([$circleId]);
            return;
        }
        $items = $sub['items']['data'] ?? [];
        $priceId = $items[0]['price']['id'] ?? null;
        $plan = (string) ($meta['plan'] ?? '');
        if (!isset(self::PLANS[$plan])) {
            $plan = self::planFromPriceId(is_string($priceId) ? $priceId : null) ?? '';
        }
        $sets = ['stripe_customer_id=?', 'stripe_subscription_id=?'];
        $args = [$customerId, $subId];
        if (isset(self::PLANS[$plan])) {
            $sets[] = 'plan=?';
            $args[] = $plan;
        }
        $args[] = $circleId;
        $db->prepare('UPDATE circles SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($args);
    }

    public static function status(): array
    {
        $cfg = self::config();
        $secret = $cfg['secret_key'];
        return [
            'has_secret' => self::configuredValue($secret, 'sk_', 20),
            'test_key' => str_starts_with($secret, 'sk_test_'),
            'live_key' => str_starts_with($secret, 'sk_live_'),
            'has_publishable' => self::configuredValue($cfg['publishable_key'], 'pk_', 20),
            'has_monthly_price' => self::configuredValue($cfg['prices']['monthly'] ?? '', 'price_', 20),
            'has_yearly_price' => self::configuredValue($cfg['prices']['yearly'] ?? '', 'price_', 20),
            'has_links' => $cfg['payment_links'],
            'has_webhook' => self::configuredValue($cfg['webhook_secret'], 'whsec_', 16),
            'ready' => $cfg['ready'],
            'can_provision' => str_starts_with($secret, 'sk_test_'),
        ];
    }

    public static function safeMessage(string $msg): string
    {
        $msg = trim($msg);
        $msg = preg_replace('/sk_(?:live|test)_[A-Za-z0-9]+/', 'sk_***', $msg) ?? $msg;
        $msg = preg_replace('/pk_(?:live|test)_[A-Za-z0-9]+/', 'pk_***', $msg) ?? $msg;
        $msg = preg_replace('/whsec_[A-Za-z0-9]+/', 'whsec_***', $msg) ?? $msg;
        if (strlen($msg) > 400) {
            $msg = substr($msg, 0, 400) . '…';
        }
        return $msg;
    }

    public static function notReadyReason(): string
    {
        $cfg = self::config();
        $bits = [];
        if (!extension_loaded('curl')) {
            $bits[] = 'PHP curl is off (Hostinger must enable curl)';
        }
        if (!self::configuredValue($cfg['secret_key'], 'sk_', 20)) {
            $bits[] = 'STRIPE_SECRET_KEY is missing';
        }
        $monthly = self::priceProblem('STRIPE_PRICE_MONTHLY', $cfg['prices']['monthly'] ?? '');
        $yearly = self::priceProblem('STRIPE_PRICE_YEARLY', $cfg['prices']['yearly'] ?? '');
        if ($monthly) {
            $bits[] = $monthly;
        }
        if ($yearly) {
            $bits[] = $yearly;
        }
        if ($bits) {
            return implode('; ', $bits);
        }
        if (!$cfg['ready']) {
            return 'Stripe Checkout is not configured';
        }
        return '';
    }

    /** Why a price env value is rejected. Never returns the raw secret/id. */
    public static function priceProblem(string $name, string $raw): ?string
    {
        $v = trim($raw);
        if (
            (strlen($v) >= 2 && str_starts_with($v, '"') && str_ends_with($v, '"'))
            || (strlen($v) >= 2 && str_starts_with($v, "'") && str_ends_with($v, "'"))
        ) {
            $v = substr($v, 1, -1);
        }
        if ($v === '') {
            return $name . ' is empty';
        }
        if (str_contains($v, '...')) {
            return $name . ' is still the placeholder (price_...) — paste the real price id from Stripe';
        }
        if (str_starts_with($v, 'prod_')) {
            return $name . ' is a product id (prod_) — open the price and copy the id that starts with price_';
        }
        if (str_starts_with($v, 'https://') || str_starts_with($v, 'http://')) {
            return $name . ' is a URL — buy.stripe.com links go in STRIPE_PAYMENT_LINK_MONTHLY / _YEARLY, not the price fields';
        }
        if (!str_starts_with($v, 'price_')) {
            return $name . ' must start with price_ (API keys and product ids will not work here)';
        }
        if (strlen($v) < 20) {
            return $name . ' is too short to be a Stripe price id';
        }
        return null;
    }

    public static function logFailure(string $step, string $message): void
    {
        Db::writeStripePayLog($step . ': ' . self::safeMessage($message));
    }

    /**
     * Operator diagnostic. Never includes secret values.
     *
     * @return array{ready:bool,missing:list<string>,ok:list<string>,warn:list<string>,text:string}
     */
    public static function diagnose(): array
    {
        $cfg = self::config();
        $ok = [];
        $missing = [];
        $warn = [];
        $base = $cfg['base_url'];
        $webhookUrl = rtrim($base, '/') . '/billing/webhook';

        $ok[] = 'App version ' . Db::VERSION;

        if (!extension_loaded('curl')) {
            $missing[] = 'PHP curl extension (Checkout cannot call Stripe without it)';
        } else {
            $ok[] = 'PHP curl extension is on';
        }

        if ($base === '') {
            $missing[] = 'BASE_URL in .env';
        } elseif (str_contains($base, '127.0.0.1') || str_contains($base, 'localhost')) {
            $warn[] = 'BASE_URL is localhost. Stripe cannot send webhooks here. Sandbox should be https://sandbox.familyshieldpro.com';
        } elseif (!str_starts_with(strtolower($base), 'https://')) {
            $warn[] = 'BASE_URL is not https. Checkout return URLs should be https.';
        } else {
            $ok[] = 'BASE_URL is set (' . $base . ')';
        }

        $secret = $cfg['secret_key'];
        if (!self::configuredValue($secret, 'sk_', 20)) {
            $missing[] = 'STRIPE_SECRET_KEY (sk_test_… for sandbox)';
        } elseif (str_starts_with($secret, 'sk_live_')) {
            $warn[] = 'STRIPE_SECRET_KEY is a live key. Sandbox should use sk_test_.';
            $ok[] = 'STRIPE_SECRET_KEY is set (live)';
        } elseif (str_starts_with($secret, 'sk_test_')) {
            $ok[] = 'STRIPE_SECRET_KEY is set (test)';
        } else {
            $missing[] = 'STRIPE_SECRET_KEY does not look like a Stripe secret key';
        }

        if (!self::configuredValue($cfg['publishable_key'], 'pk_', 20)) {
            $warn[] = 'STRIPE_PUBLISHABLE_KEY is empty (Checkout still works; paste pk_test_ to match the secret)';
        } elseif (str_starts_with($cfg['publishable_key'], 'pk_test_')) {
            $ok[] = 'STRIPE_PUBLISHABLE_KEY is set (test)';
        } elseif (str_starts_with($cfg['publishable_key'], 'pk_live_')) {
            $warn[] = 'STRIPE_PUBLISHABLE_KEY is a live key';
        } else {
            $ok[] = 'STRIPE_PUBLISHABLE_KEY is set';
        }

        $monthly = $cfg['prices']['monthly'] ?? '';
        $yearly = $cfg['prices']['yearly'] ?? '';
        if (!self::configuredValue($monthly, 'price_', 20)) {
            $missing[] = self::priceProblem('STRIPE_PRICE_MONTHLY', $monthly)
                ?? 'STRIPE_PRICE_MONTHLY (price_… for $14.99/month)';
        } else {
            $ok[] = 'STRIPE_PRICE_MONTHLY is set (' . self::maskId($monthly) . ')';
        }
        if (!self::configuredValue($yearly, 'price_', 20)) {
            $missing[] = self::priceProblem('STRIPE_PRICE_YEARLY', $yearly)
                ?? 'STRIPE_PRICE_YEARLY (price_… for $119.99/year)';
        } else {
            $ok[] = 'STRIPE_PRICE_YEARLY is set (' . self::maskId($yearly) . ')';
        }

        if (self::configuredValue($cfg['webhook_secret'], 'whsec_', 16)) {
            $ok[] = 'STRIPE_WEBHOOK_SECRET is set';
        } else {
            $missing[] = 'STRIPE_WEBHOOK_SECRET (whsec_… from the sandbox webhook endpoint)';
        }

        if ($cfg['payment_links']) {
            $ok[] = 'Payment Links are set (optional fallback)';
        } else {
            $warn[] = 'Payment Links are empty. Not required when Checkout prices are set.';
        }

        if (self::configuredValue($secret, 'sk_', 20) && extension_loaded('curl')) {
            if (self::configuredValue($monthly, 'price_', 20)) {
                self::diagnosePrice($monthly, 1499, 'month', 'monthly', $ok, $missing, $warn);
            }
            if (self::configuredValue($yearly, 'price_', 20)) {
                self::diagnosePrice($yearly, 11999, 'year', 'yearly', $ok, $missing, $warn);
            }
            self::diagnoseWebhook($webhookUrl, $ok, $missing, $warn);
        } elseif (!self::configuredValue($secret, 'sk_', 20)) {
            $warn[] = 'Skipped live Stripe API checks until STRIPE_SECRET_KEY is set.';
        }

        if ($cfg['checkout']) {
            $ok[] = 'Checkout can start (secret key + both price IDs)';
        } elseif ($cfg['payment_links']) {
            $ok[] = 'Payment Links can start (no Checkout prices)';
        } else {
            $missing[] = 'Checkout is not ready. Need secret key and both price IDs, or both Payment Links.';
        }

        $ready = $missing === [];

        $lines = [];
        $lines[] = 'Family Shield Pro Stripe check';
        $lines[] = 'Generated: ' . Http::now();
        $lines[] = 'This file is next to the database and is blocked from the web.';
        $lines[] = 'No secret keys are written here.';
        $lines[] = '';
        $lines[] = $ready ? 'RESULT: Ready to test payments' : 'RESULT: Not ready — see MISSING';
        $lines[] = '';
        $lines[] = 'MISSING';
        if ($missing) {
            foreach ($missing as $m) {
                $lines[] = '- ' . $m;
            }
        } else {
            $lines[] = '- (none)';
        }
        $lines[] = '';
        $lines[] = 'OK';
        foreach ($ok as $m) {
            $lines[] = '- ' . $m;
        }
        $lines[] = '';
        $lines[] = 'NOTES';
        if ($warn) {
            foreach ($warn as $m) {
                $lines[] = '- ' . $m;
            }
        } else {
            $lines[] = '- (none)';
        }
        $lines[] = '';
        $lines[] = 'Webhook URL Stripe should call: ' . $webhookUrl;
        $lines[] = 'After deploy, Plans should say Pay Family monthly / yearly, not Choose.';
        $lines[] = 'Test card: 4242 4242 4242 4242, any future date, any CVC, any ZIP.';
        $pay = trim(Db::readStripePayLog());
        $lines[] = '';
        $lines[] = 'LAST CHECKOUT ERRORS (no secrets)';
        if ($pay === '') {
            $lines[] = '- (none yet)';
        } else {
            foreach (preg_split("/\r\n|\n|\r/", $pay) ?: [] as $pl) {
                if (trim($pl) !== '') {
                    $lines[] = '- ' . $pl;
                }
            }
        }
        $text = implode("\n", $lines) . "\n";

        return [
            'ready' => $ready,
            'missing' => $missing,
            'ok' => $ok,
            'warn' => $warn,
            'text' => $text,
        ];
    }

    private static function maskId(string $id): string
    {
        $id = trim($id);
        if (strlen($id) <= 10) {
            return '(set)';
        }
        return substr($id, 0, 8) . '…' . substr($id, -4);
    }

    private static function diagnosePrice(
        string $priceId,
        int $amount,
        string $interval,
        string $label,
        array &$ok,
        array &$missing,
        array &$warn
    ): void {
        try {
            $got = self::api('GET', '/v1/prices/' . rawurlencode($priceId));
        } catch (Throwable $e) {
            $missing[] = 'Stripe rejected STRIPE_PRICE_' . strtoupper($label) . ' (' . $e->getMessage() . ')';
            return;
        }
        $rec = is_array($got['recurring'] ?? null) ? $got['recurring'] : [];
        $gotAmount = (int) ($got['unit_amount'] ?? 0);
        $gotInterval = (string) ($rec['interval'] ?? '');
        $livemode = !empty($got['livemode']);
        if ($livemode) {
            $warn[] = $label . ' price is a live-mode price. Sandbox should use test mode.';
        }
        if ($gotAmount === $amount && $gotInterval === $interval) {
            $ok[] = $label . ' price matches $' . number_format($amount / 100, 2) . '/' . $interval
                . ' in Stripe (' . self::maskId($priceId) . ')';
            return;
        }
        $missing[] = $label . ' price in Stripe is $' . number_format($gotAmount / 100, 2)
            . '/' . ($gotInterval !== '' ? $gotInterval : 'unknown')
            . ' — Family Shield Pro needs $' . number_format($amount / 100, 2) . '/' . $interval;
    }

    private static function diagnoseWebhook(string $url, array &$ok, array &$missing, array &$warn): void
    {
        try {
            $list = self::api('GET', '/v1/webhook_endpoints', ['limit' => 20]);
        } catch (Throwable $e) {
            $warn[] = 'Could not list Stripe webhook endpoints (' . $e->getMessage() . ')';
            return;
        }
        foreach ($list['data'] ?? [] as $h) {
            if (!is_array($h)) {
                continue;
            }
            if (($h['url'] ?? '') === $url) {
                $status = (string) ($h['status'] ?? '');
                if ($status === 'disabled') {
                    $missing[] = 'Stripe webhook exists but is disabled: ' . $url;
                    return;
                }
                $ok[] = 'Stripe webhook endpoint exists for ' . $url;
                return;
            }
        }
        $missing[] = 'No Stripe webhook endpoint for ' . $url . ' (add it in Dashboard, or use Create prices on this page)';
    }

    /** Create Family Shield Pro products, prices, Payment Links, and webhook. Test keys only. */
    public static function provisionCatalog(): array
    {
        $cfg = self::config();
        if (!str_starts_with($cfg['secret_key'], 'sk_test_')) {
            throw new RuntimeException('Paste a test secret key (sk_test_…) into .env first. Live keys are not used here.');
        }
        $base = $cfg['base_url'];
        if ($base === '' || str_contains($base, '127.0.0.1') || str_contains($base, 'localhost')) {
            $base = 'https://sandbox.familyshieldpro.com';
        }
        $success = $base . '/billing/success?session_id={CHECKOUT_SESSION_ID}';
        $webhookUrl = $base . '/billing/webhook';

        $monthlyProd = self::ensureProduct('Family Shield Pro — Monthly');
        $yearlyProd = self::ensureProduct('Family Shield Pro — Yearly');
        $monthlyPrice = self::ensurePrice($monthlyProd, 1499, 'month', $cfg['prices']['monthly'] ?? '');
        $yearlyPrice = self::ensurePrice($yearlyProd, 11999, 'year', $cfg['prices']['yearly'] ?? '');
        $monthlyLink = self::ensurePaymentLink($monthlyPrice, $cfg['links']['monthly'] ?? '', $success);
        $yearlyLink = self::ensurePaymentLink($yearlyPrice, $cfg['links']['yearly'] ?? '', $success);
        $whsec = self::ensureWebhook($webhookUrl, $cfg['webhook_secret']);

        $out = [
            'STRIPE_PRICE_MONTHLY' => $monthlyPrice,
            'STRIPE_PRICE_YEARLY' => $yearlyPrice,
            'STRIPE_PAYMENT_LINK_MONTHLY' => $monthlyLink,
            'STRIPE_PAYMENT_LINK_YEARLY' => $yearlyLink,
        ];
        if (self::configuredValue($whsec, 'whsec_', 16)) {
            $out['STRIPE_WEBHOOK_SECRET'] = $whsec;
        }
        Env::putKeys($out);
        return [
            'monthly_price' => $monthlyPrice,
            'yearly_price' => $yearlyPrice,
            'monthly_link' => $monthlyLink,
            'yearly_link' => $yearlyLink,
            'webhook_url' => $webhookUrl,
            'webhook_secret_saved' => isset($out['STRIPE_WEBHOOK_SECRET']),
        ];
    }

    private static function ensureProduct(string $name): string
    {
        $list = self::api('GET', '/v1/products', ['limit' => 100, 'active' => 'true']);
        foreach ($list['data'] ?? [] as $p) {
            if (is_array($p) && ($p['name'] ?? '') === $name && !empty($p['id'])) {
                return (string) $p['id'];
            }
        }
        $created = self::api('POST', '/v1/products', ['name' => $name]);
        $id = (string) ($created['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Stripe did not return a product id');
        }
        return $id;
    }

    private static function ensurePrice(string $productId, int $amount, string $interval, string $existing): string
    {
        if (self::configuredValue($existing, 'price_', 20)) {
            try {
                $got = self::api('GET', '/v1/prices/' . rawurlencode($existing));
                if (!empty($got['id'])) {
                    return (string) $got['id'];
                }
            } catch (Throwable) {
            }
        }
        $list = self::api('GET', '/v1/prices', [
            'product' => $productId,
            'active' => 'true',
            'limit' => 20,
        ]);
        foreach ($list['data'] ?? [] as $pr) {
            if (!is_array($pr)) {
                continue;
            }
            $rec = is_array($pr['recurring'] ?? null) ? $pr['recurring'] : [];
            if ((int) ($pr['unit_amount'] ?? 0) === $amount
                && ($rec['interval'] ?? '') === $interval
                && ($pr['currency'] ?? '') === 'usd'
                && !empty($pr['id'])) {
                return (string) $pr['id'];
            }
        }
        $created = self::api('POST', '/v1/prices', [
            'product' => $productId,
            'currency' => 'usd',
            'unit_amount' => $amount,
            'recurring' => ['interval' => $interval],
        ]);
        $id = (string) ($created['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Stripe did not return a price id');
        }
        return $id;
    }

    private static function ensurePaymentLink(string $priceId, string $existing, string $successUrl): string
    {
        if (self::isPaymentLink($existing)) {
            return $existing;
        }
        $created = self::api('POST', '/v1/payment_links', [
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => ['url' => $successUrl],
            ],
        ]);
        $url = (string) ($created['url'] ?? '');
        if (!self::isPaymentLink($url)) {
            throw new RuntimeException('Stripe did not return a Payment Link');
        }
        return $url;
    }

    private static function ensureWebhook(string $url, string $existingSecret): string
    {
        $list = self::api('GET', '/v1/webhook_endpoints', ['limit' => 20]);
        foreach ($list['data'] ?? [] as $h) {
            if (is_array($h) && ($h['url'] ?? '') === $url) {
                return $existingSecret;
            }
        }
        $created = self::api('POST', '/v1/webhook_endpoints', [
            'url' => $url,
            'enabled_events' => [
                'checkout.session.completed',
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
            ],
        ]);
        return (string) ($created['secret'] ?? $existingSecret);
    }
}
