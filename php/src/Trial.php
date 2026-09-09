<?php
declare(strict_types=1);

final class Trial
{
    public const DAYS = 14;

    public static function endsAt(?string $fromIso = null): string
    {
        $ts = $fromIso ? strtotime($fromIso) : time();
        if (!$ts) {
            $ts = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $ts + self::DAYS * 86400);
    }

    public static function forCircle(PDO $db, int $circleId, string $role = 'member'): array
    {
        return self::state($db, ['circle_id' => $circleId, 'role' => $role]);
    }

    public static function state(PDO $db, array $user): array
    {
        $cid = (int) ($user['circle_id'] ?? 0);
        $st = $db->prepare(
            'SELECT plan, stripe_subscription_id, trial_ends_at, created_at FROM circles WHERE id=?'
        );
        $st->execute([$cid]);
        $c = $st->fetch() ?: [];
        $demo = self::isDemoCircle($db, $cid);
        $paid = trim((string) ($c['stripe_subscription_id'] ?? '')) !== '';
        $ends = trim((string) ($c['trial_ends_at'] ?? ''));
        if ($ends === '') {
            $ends = self::endsAt((string) ($c['created_at'] ?? ''));
        }
        $endTs = strtotime($ends) ?: (time() + self::DAYS * 86400);
        $now = time();
        $open = $now < $endTs;
        $canWrite = $demo || $paid || $open;
        $expired = !$demo && !$paid && !$open;
        $activeTrial = !$demo && !$paid && $open;
        $secondsLeft = max(0, $endTs - $now);
        $daysLeft = (int) ceil($secondsLeft / 86400);
        return [
            'demo' => $demo,
            'paid' => $paid || $demo,
            'active_trial' => $activeTrial,
            'expired' => $expired,
            'can_write' => $canWrite,
            'ends_at' => $ends,
            'ends_label' => gmdate('F j, Y', $endTs),
            'days_left' => $activeTrial ? max(0, $daysLeft) : 0,
            'ends_today' => $activeTrial && $secondsLeft > 0 && $secondsLeft < 86400,
            'is_owner' => ($user['role'] ?? '') === 'owner',
            'days' => self::DAYS,
        ];
    }

    public static function isDemoCircle(PDO $db, int $circleId): bool
    {
        if ($circleId < 1) {
            return false;
        }
        $st = $db->prepare('SELECT 1 FROM users WHERE circle_id=? AND lower(email)=? LIMIT 1');
        $st->execute([$circleId, Db::DEMO_EMAIL]);
        return (bool) $st->fetchColumn();
    }

    public static function writeBlockedMessage(array $trial): string
    {
        if (!empty($trial['is_owner'])) {
            return 'Your 14-day trial has ended. Pay on Plans to keep checking new requests, inviting family, and using call-me. You can still view the trusted list and past checks.';
        }
        return 'This circle’s 14-day trial has ended. Ask the owner to continue Family Shield Pro on Plans. You can still view the trusted list and past checks.';
    }
}
