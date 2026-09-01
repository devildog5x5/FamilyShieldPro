<?php
declare(strict_types=1);

final class Totp
{
    public static function secret(): string
    {
        return self::base32(random_bytes(20));
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $time = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $time + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function code(string $secret, int $counter): string
    {
        $key = self::base32decode($secret);
        $bin = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $truncated = ($unpacked[1] & 0x7fffffff) % 1000000;
        return str_pad((string) $truncated, 6, '0', STR_PAD_LEFT);
    }

    public static function uri(string $email, string $secret): string
    {
        return 'otpauth://totp/' . rawurlencode('OurCircle:' . $email)
            . '?secret=' . rawurlencode(str_replace(' ', '', $secret))
            . '&issuer=' . rawurlencode('OurCircle');
    }

    public static function grouped(string $secret): string
    {
        $s = strtoupper(str_replace(' ', '', $secret));
        return trim(chunk_split($s, 4, ' '));
    }

    public static function recoveryCodes(): array
    {
        $out = [];
        for ($i = 0; $i < 8; $i++) {
            $out[] = strtolower(bin2hex(random_bytes(4))) . '-' . strtolower(bin2hex(random_bytes(4)));
        }
        return $out;
    }

    private static function base32(string $raw): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($raw) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $out .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    private static function base32decode(string $b32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
        $bits = '';
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $val = strpos($alphabet, $b32[$i]);
            if ($val === false) {
                continue;
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
