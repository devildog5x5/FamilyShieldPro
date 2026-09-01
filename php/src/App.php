<?php
declare(strict_types=1);

final class App
{
    private const MAX_CIRCLE = 5;
    private const IMAGE_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(private PDO $db)
    {
    }

    public function run(): void
    {
        $user = $this->loadUser();
        $method = Http::method();
        $path = Http::path();

        if ($method === 'GET' && $path === '/') {
            $this->landing();
        } elseif ($path === '/login') {
            $this->login($user);
        } elseif ($path === '/signup') {
            $this->signup($user);
        } elseif ($method === 'GET' && $path === '/logout') {
            $_SESSION = [];
            session_destroy();
            Http::redirect('/');
        } elseif ($path === '/forgot') {
            $this->forgot();
        } elseif ($method === 'POST' && $path === '/forgot/code') {
            $this->forgotCode();
        } elseif ($method === 'GET' && $path === '/privacy') {
            $this->legal('privacy');
        } elseif ($method === 'GET' && $path === '/terms') {
            $this->legal('terms');
        } elseif ($method === 'GET' && $path === '/robots.txt') {
            $this->robots();
        } elseif ($method === 'GET' && $path === '/sitemap.xml') {
            $this->sitemap();
        } elseif ($method === 'GET' && $path === '/healthz') {
            $this->healthz();
        } elseif ($method === 'POST' && $path === '/support/chat') {
            $this->chat();
        } elseif ($path === '/admin/login' || $path === '/admin') {
            $this->admin($path, $method);
        } elseif (preg_match('#^/join/([A-Za-z0-9_-]{8,})$#', $path, $m)) {
            $this->join($m[1], $user);
        } elseif (preg_match('#^/uploads/([A-Za-z0-9_-]+)$#', $path, $m)) {
            $this->serveUpload($m[1], $user);
        } elseif (in_array($path, ['/home', '/circle', '/trusted', '/billing', '/report', '/account', '/account/2fa/setup'], true)
            || str_starts_with($path, '/check')
            || str_starts_with($path, '/account/')
            || str_starts_with($path, '/trusted/')
            || str_starts_with($path, '/billing/')
            || str_starts_with($path, '/circle/')) {
            $user = $this->requireUser($user);
            $this->authed($method, $path, $user);
        } else {
            http_response_code(404);
            echo 'Not found';
        }
    }

    private function loadUser(): ?array
    {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) {
            return null;
        }
        $st = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([(int) $uid]);
        $row = $st->fetch();
        if (!$row) {
            unset($_SESSION['user_id']);
            return null;
        }
        $this->db->prepare('UPDATE users SET last_seen_at = ?, status = ? WHERE id = ?')
            ->execute([Http::now(), 'access', $row['id']]);
        $row['status'] = 'access';
        return $row;
    }

    private function requireUser(?array $user): array
    {
        if ($user) {
            if (!empty($user['totp_enabled']) && empty($_SESSION['totp_ok'])) {
                if (Http::path() !== '/login') {
                    Http::redirect('/login');
                }
            }
            return $user;
        }
        $next = Http::path();
        Http::redirect('/login?next=' . rawurlencode($next));
    }

    private function requireOwner(array $user): void
    {
        if (($user['role'] ?? '') !== 'owner') {
            Http::flash('Only the circle owner can do that.', 'error');
            Http::redirect('/home');
        }
    }

    private function authed(string $method, string $path, array $user): void
    {
        if ($method === 'GET' && $path === '/home') {
            $this->home($user);
        } elseif ($method === 'POST' && $path === '/check') {
            Http::csrfCheck();
            $this->createCheck($user);
        } elseif (preg_match('#^/checks/(\d+)$#', $path, $m) && $method === 'GET') {
            $this->showCheck($user, (int) $m[1]);
        } elseif (preg_match('#^/checks/(\d+)/alert$#', $path, $m) && $method === 'POST') {
            Http::csrfCheck();
            $this->alert($user, (int) $m[1]);
        } elseif (preg_match('#^/checks/(\d+)/review$#', $path, $m) && $method === 'POST') {
            Http::csrfCheck();
            $this->review($user, (int) $m[1]);
        } elseif (preg_match('#^/checks/(\d+)/review/reply$#', $path, $m) && $method === 'POST') {
            Http::csrfCheck();
            $this->reply($user, (int) $m[1]);
        } elseif ($path === '/circle' && $method === 'GET') {
            $this->circle($user);
        } elseif ($path === '/circle' && $method === 'POST') {
            Http::csrfCheck();
            $this->invite($user);
        } elseif ($path === '/circle/resend' && $method === 'POST') {
            Http::csrfCheck();
            $this->resendInvite($user);
        } elseif (preg_match('#^/circle/invite/(\d+)/cancel$#', $path, $m) && $method === 'POST') {
            Http::csrfCheck();
            $this->cancelInvite($user, (int) $m[1]);
        } elseif ($path === '/circle/remove' && $method === 'POST') {
            Http::csrfCheck();
            $this->removeMember($user);
        } elseif ($path === '/trusted' && $method === 'GET') {
            $this->trusted($user);
        } elseif ($path === '/trusted' && $method === 'POST') {
            Http::csrfCheck();
            $this->addTrusted($user);
        } elseif (preg_match('#^/trusted/(\d+)/delete$#', $path, $m) && $method === 'POST') {
            Http::csrfCheck();
            $this->deleteTrusted($user, (int) $m[1]);
        } elseif ($path === '/billing' && $method === 'GET') {
            $this->billing($user);
        } elseif ($path === '/billing/choose' && $method === 'POST') {
            Http::csrfCheck();
            $this->choosePlan($user);
        } elseif ($path === '/report' && $method === 'GET') {
            $this->report($user);
        } elseif ($path === '/account' && $method === 'GET') {
            $this->account($user);
        } elseif ($path === '/account/password' && $method === 'POST') {
            Http::csrfCheck();
            $this->changePassword($user);
        } elseif ($path === '/account/phone' && $method === 'POST') {
            Http::csrfCheck();
            $this->savePhone($user);
        } elseif ($path === '/account/2fa/setup') {
            $this->totpSetup($user, $method);
        } else {
            http_response_code(404);
            echo 'Not found';
        }
    }

    private function view(string $name, array $vars = []): never
    {
        extract($vars, EXTR_SKIP);
        require dirname(__DIR__) . '/views/' . $name . '.php';
        exit;
    }

    private function landing(): never
    {
        $this->view('landing', [
            'phone' => Layout::contactPhone(),
            'email' => Layout::supportEmail(),
        ]);
    }

    private function login(?array $user): void
    {
        if ($user && Http::method() === 'GET') {
            Http::redirect('/home');
        }
        if (Http::method() === 'POST') {
            Http::csrfCheck();
            $email = strtolower(trim((string) ($_POST['email'] ?? $_SESSION['pending_email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $next = Http::safeNext($_POST['next'] ?? '/home');
            if ($password === '' && !empty($_SESSION['pending_user_id'])) {
                $st = $this->db->prepare('SELECT * FROM users WHERE id=?');
                $st->execute([(int) $_SESSION['pending_user_id']]);
                $pending = $st->fetch();
                if ($pending) {
                    $email = (string) $pending['email'];
                    $row = $pending;
                    $code = preg_replace('/\s+/', '', (string) ($_POST['otp'] ?? '')) ?? '';
                    if (!Totp::verify((string) $row['totp_secret'], $code)) {
                        Http::flash('That sign-in code did not match.', 'error');
                        $this->view('login', ['next' => $next, 'needOtp' => true, 'email' => $email, 'showDemo' => false]);
                    }
                    unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['totp_ok'] = 1;
                    $this->db->prepare('UPDATE users SET status=?, last_seen_at=? WHERE id=?')
                        ->execute(['access', Http::now(), $row['id']]);
                    Http::redirect($next);
                }
            }
            $st = $this->db->prepare('SELECT * FROM users WHERE lower(email) = ?');
            $st->execute([$email]);
            $row = $st->fetch();
            if (!$row || !password_verify($password, $row['password_hash'])) {
                Http::flash('Email or password did not match.', 'error');
                $this->view('login', ['next' => $next, 'showDemo' => Env::truthy('SHOW_DEMO_LOGIN')]);
            }
            if (!empty($row['totp_enabled'])) {
                $code = preg_replace('/\s+/', '', (string) ($_POST['otp'] ?? '')) ?? '';
                if ($code === '') {
                    $_SESSION['pending_user_id'] = $row['id'];
                    $_SESSION['pending_email'] = $email;
                    $this->view('login', ['next' => $next, 'needOtp' => true, 'email' => $email, 'showDemo' => false]);
                }
                if (!Totp::verify((string) $row['totp_secret'], $code)) {
                    Http::flash('That sign-in code did not match.', 'error');
                    $this->view('login', ['next' => $next, 'needOtp' => true, 'email' => $email, 'showDemo' => false]);
                }
                $_SESSION['totp_ok'] = 1;
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['totp_ok'] = 1;
            $this->db->prepare('UPDATE users SET status=?, last_seen_at=? WHERE id=?')
                ->execute(['access', Http::now(), $row['id']]);
            Http::redirect($next);
        }
        $next = Http::safeNext($_GET['next'] ?? '/home');
        $this->view('login', ['next' => $next, 'showDemo' => Env::truthy('SHOW_DEMO_LOGIN')]);
    }

    private function signup(?array $user): void
    {
        if ($user) {
            Http::redirect('/home');
        }
        if (Http::method() !== 'POST') {
            $this->view('signup');
        }
        Http::csrfCheck();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if ($name === '' || $email === '' || !str_contains($email, '@') || strlen($password) < 8) {
            Http::flash('Name, email, and an 8+ character password are required.', 'error');
            $this->view('signup');
        }
        $exists = $this->db->prepare('SELECT id FROM users WHERE lower(email)=?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            Http::flash('That email already has a login. Sign in instead.', 'error');
            Http::redirect('/login');
        }
        $now = Http::now();
        $this->db->prepare('INSERT INTO circles (name, plan, created_at) VALUES (?,?,?)')
            ->execute([$name . "'s circle", 'yearly', $now]);
        $cid = (int) $this->db->lastInsertId();
        $this->db->prepare(
            'INSERT INTO users (circle_id, email, name, password_hash, phone, role, status, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$cid, $email, $name, password_hash($password, PASSWORD_DEFAULT), $phone, 'owner', 'access', $now]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $this->db->lastInsertId();
        $_SESSION['totp_ok'] = 1;
        Http::flash('Welcome. Paste anything odd below, or invite family from the right.');
        Http::redirect('/home');
    }

    private function forgot(): void
    {
        if (Http::method() === 'POST') {
            Http::csrfCheck();
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $st = $this->db->prepare('SELECT * FROM users WHERE lower(email)=?');
            $st->execute([$email]);
            $row = $st->fetch();
            if ($row && Mailer::configured()) {
                $token = bin2hex(random_bytes(16));
                $_SESSION['reset_' . $email] = ['t' => $token, 'exp' => time() + 3600];
                $link = Http::baseUrl() . '/forgot?email=' . rawurlencode($email) . '&token=' . $token;
                try {
                    Mailer::send($email, 'Reset your OurCircle password', "Use this one-hour link:\n{$link}\n");
                } catch (Throwable $e) {
                    // same public message either way
                }
            }
            Http::flash('If that email is on a circle, we sent reset instructions. Check spam. You can also use a recovery code on this page.');
            Http::redirect('/forgot');
        }
        $this->view('forgot');
    }

    private function forgotCode(): void
    {
        Http::csrfCheck();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $code = strtolower(trim((string) ($_POST['recovery_code'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $st = $this->db->prepare('SELECT * FROM users WHERE lower(email)=?');
        $st->execute([$email]);
        $row = $st->fetch();
        $ok = false;
        if ($row && strlen($password) >= 8 && $code !== '') {
            $codes = json_decode((string) $row['recovery_codes'], true) ?: [];
            foreach ($codes as $i => $stored) {
                if (hash_equals((string) $stored, $code)) {
                    unset($codes[$i]);
                    $ok = true;
                    $this->db->prepare('UPDATE users SET password_hash=?, recovery_codes=? WHERE id=?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), json_encode(array_values($codes)), $row['id']]);
                    break;
                }
            }
        }
        unset($ok);
        Http::flash('If that email and recovery code matched, the password is updated. Sign in.');
        Http::redirect('/login');
    }

    private function home(array $user): never
    {
        $members = $this->members((int) $user['circle_id']);
        $pending = $this->pendingInvites((int) $user['circle_id']);
        $trusted = $this->trustedRows((int) $user['circle_id']);
        $checks = $this->recentChecks((int) $user['circle_id']);
        $alert = $this->latestAlert((int) $user['circle_id']);
        $this->view('home', compact('user', 'members', 'pending', 'trusted', 'checks', 'alert'));
    }

    private function createCheck(array $user): void
    {
        $text = trim((string) ($_POST['text'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $token = $this->storeUpload($user);
        if ($text === '' && $phone === '' && $url === '' && $token === null) {
            Http::flash('Paste the message, a phone number, a website, or upload a screenshot.', 'error');
            Http::redirect('/home');
        }
        if ($text === '' && $token) {
            $text = '(Screenshot uploaded — describe what it says if you can.)';
        }
        if (strlen($text) > 20000) {
            $text = substr($text, 0, 20000);
        }
        $trusted = $this->trustedRows((int) $user['circle_id']);
        $analysis = Analyzer::run($text, $phone, $url, $trusted);
        $this->db->prepare(
            'INSERT INTO checks (circle_id, user_id, text, phone, url, screenshot_token, headline, level, analysis_json, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $user['circle_id'], $user['id'], $text, $phone, $url, $token,
            $analysis['headline'], $analysis['level'], json_encode($analysis, JSON_UNESCAPED_SLASHES), Http::now(),
        ]);
        $id = (int) $this->db->lastInsertId();
        if ($token) {
            $this->db->prepare('UPDATE uploads SET check_id=? WHERE token=? AND circle_id=?')
                ->execute([$id, $token, $user['circle_id']]);
        }
        Http::redirect('/checks/' . $id);
    }

    private function storeUpload(array $user): ?string
    {
        if (empty($_FILES['screenshot']) || (int) ($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $f = $_FILES['screenshot'];
        if ((int) $f['error'] !== UPLOAD_ERR_OK) {
            Http::flash('The screenshot could not be uploaded.', 'error');
            Http::redirect('/home');
        }
        $tmp = (string) $f['tmp_name'];
        $info = @getimagesize($tmp);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        if (!isset(self::IMAGE_TYPES[$mime])) {
            Http::flash('Please upload a PNG, JPG, WEBP, or GIF screenshot.', 'error');
            Http::redirect('/home');
        }
        if ((int) $f['size'] > 8 * 1024 * 1024) {
            Http::flash('That screenshot is too large (8 MB max).', 'error');
            Http::redirect('/home');
        }
        $token = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        $dir = dirname(__DIR__) . '/data/uploads/' . (int) $user['circle_id'];
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $ext = self::IMAGE_TYPES[$mime];
        $abs = $dir . '/' . $token . '.' . $ext;
        if (!move_uploaded_file($tmp, $abs)) {
            Http::flash('The screenshot could not be saved.', 'error');
            Http::redirect('/home');
        }
        $orig = basename((string) ($f['name'] ?? 'screenshot.' . $ext));
        $this->db->prepare(
            'INSERT INTO uploads (circle_id, token, mime, original_name, abs_path, created_at) VALUES (?,?,?,?,?,?)'
        )->execute([$user['circle_id'], $token, $mime, $orig, $abs, Http::now()]);
        return $token;
    }

    private function serveUpload(string $token, ?array $user): never
    {
        $user = $this->requireUser($user);
        $st = $this->db->prepare('SELECT * FROM uploads WHERE token=? AND circle_id=?');
        $st->execute([$token, $user['circle_id']]);
        $row = $st->fetch();
        if (!$row || !is_file((string) $row['abs_path'])) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        header('Content-Type: ' . $row['mime']);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . basename((string) $row['original_name']) . '"');
        readfile((string) $row['abs_path']);
        exit;
    }

    private function loadCheck(array $user, int $id): array
    {
        $st = $this->db->prepare('SELECT * FROM checks WHERE id=? AND circle_id=?');
        $st->execute([$id, $user['circle_id']]);
        $row = $st->fetch();
        if (!$row) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        return $row;
    }

    private function showCheck(array $user, int $id): never
    {
        $check = $this->loadCheck($user, $id);
        $analysis = json_decode((string) $check['analysis_json'], true) ?: [];
        $notes = $this->db->prepare(
            'SELECT n.*, u.name FROM check_notes n JOIN users u ON u.id=n.user_id WHERE n.check_id=? ORDER BY n.id DESC'
        );
        $notes->execute([$id]);
        $author = $this->db->prepare('SELECT name FROM users WHERE id=?');
        $author->execute([$check['user_id']]);
        $this->view('check', [
            'user' => $user,
            'check' => $check,
            'analysis' => $analysis,
            'notes' => $notes->fetchAll(),
            'authorName' => (string) ($author->fetchColumn() ?: ''),
        ]);
    }

    private function alert(array $user, int $id): void
    {
        $check = $this->loadCheck($user, $id);
        $payerId = (int) $check['user_id'];
        $this->db->prepare(
            'INSERT INTO alerts (check_id, circle_id, payer_user_id, triggered_by, created_at) VALUES (?,?,?,?,?)'
        )->execute([$id, $user['circle_id'], $payerId, $user['id'], Http::now()]);
        $n = $this->mailCircle($user, $id, 'call-me');
        $msg = 'Urgent alert is on the circle home. Call them by voice if you can — do not rely on a banner alone.';
        if ($n > 0) {
            $msg .= ' Emailed ' . $n . ' circle members.';
        }
        Http::flash($msg);
        Http::redirect('/checks/' . $id);
    }

    private function review(array $user, int $id): void
    {
        $this->loadCheck($user, $id);
        $this->db->prepare(
            'INSERT INTO check_notes (check_id, user_id, kind, body, created_at) VALUES (?,?,?,?,?)'
        )->execute([$id, $user['id'], 'asked', 'Please look at this with me before I do anything.', Http::now()]);
        $n = $this->mailCircle($user, $id, 'review');
        $msg = 'Asked the circle to look.';
        if ($n > 0) {
            $msg .= ' Emailed ' . $n . ' circle members.';
        }
        Http::flash($msg);
        Http::redirect('/checks/' . $id);
    }

    private function reply(array $user, int $id): void
    {
        $this->loadCheck($user, $id);
        $body = trim((string) ($_POST['reply'] ?? ''));
        if ($body === '') {
            Http::flash('Write a note before sending.', 'error');
            Http::redirect('/checks/' . $id);
        }
        $this->db->prepare(
            'INSERT INTO check_notes (check_id, user_id, kind, body, created_at) VALUES (?,?,?,?,?)'
        )->execute([$id, $user['id'], 'looked', substr($body, 0, 2000), Http::now()]);
        Http::flash('Your note is on this check for the whole circle.');
        Http::redirect('/checks/' . $id);
    }

    private function circle(array $user): never
    {
        $members = $this->members((int) $user['circle_id']);
        $pending = $this->pendingInvites((int) $user['circle_id']);
        $alert = $this->latestAlert((int) $user['circle_id']);
        $this->view('circle', compact('user', 'members', 'pending', 'alert'));
    }

    private function circleSize(int $circleId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM users WHERE circle_id=?');
        $st->execute([$circleId]);
        $people = (int) $st->fetchColumn();
        $st = $this->db->prepare("SELECT COUNT(*) FROM invites WHERE circle_id=? AND status='sent'");
        $st->execute([$circleId]);
        return $people + (int) $st->fetchColumn();
    }

    private function invite(array $user): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $return = (($_POST['return'] ?? '') === 'home') ? '/home' : '/circle';
        if ($email === '' || !str_contains($email, '@')) {
            Http::flash('Need an email address to invite.', 'error');
            Http::redirect($return);
        }
        $in = $this->db->prepare('SELECT id FROM users WHERE circle_id=? AND lower(email)=?');
        $in->execute([$user['circle_id'], $email]);
        if ($in->fetch()) {
            Http::flash('That person is already in this circle.', 'error');
            Http::redirect($return);
        }
        $pend = $this->db->prepare("SELECT * FROM invites WHERE circle_id=? AND lower(email)=? AND status='sent'");
        $pend->execute([$user['circle_id'], $email]);
        $existing = $pend->fetch();
        if ($existing) {
            $link = Http::baseUrl() . '/join/' . $existing['token'];
            $this->sendInviteMail($email, $link, $user);
            Http::flash('Invite already waiting. Share this join link: ' . $link);
            Http::redirect($return);
        }
        if ($this->circleSize((int) $user['circle_id']) >= self::MAX_CIRCLE) {
            Http::flash('The family plan includes up to five people. Remove someone or upgrade with us later.', 'error');
            Http::redirect($return);
        }
        $token = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $this->db->prepare(
            'INSERT INTO invites (circle_id, email, name, phone, token, invited_by, status, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([$user['circle_id'], $email, $name, $phone, $token, $user['id'], 'sent', Http::now()]);
        $link = Http::baseUrl() . '/join/' . $token;
        $this->sendInviteMail($email, $link, $user);
        Http::flash('Invite emailed to ' . $email . '. If they do not see it, share this join link: ' . $link);
        Http::redirect($return);
    }

    private function sendInviteMail(string $email, string $link, array $from): void
    {
        if (!Mailer::configured()) {
            return;
        }
        try {
            Mailer::send(
                $email,
                $from['name'] . ' invited you to an OurCircle',
                "Open this join link on a device you trust:\n{$link}\n\nWe will never tell you a payment request is safe.\n"
            );
        } catch (Throwable $e) {
        }
    }

    private function resendInvite(array $user): void
    {
        $id = (int) ($_POST['invite_id'] ?? 0);
        $st = $this->db->prepare("SELECT * FROM invites WHERE id=? AND circle_id=? AND status='sent'");
        $st->execute([$id, $user['circle_id']]);
        $row = $st->fetch();
        if (!$row) {
            Http::flash('That invite is not waiting anymore.', 'error');
            Http::redirect('/circle');
        }
        $link = Http::baseUrl() . '/join/' . $row['token'];
        $this->sendInviteMail((string) $row['email'], $link, $user);
        Http::flash('Invite resent. Join link: ' . $link);
        Http::redirect('/circle');
    }

    private function cancelInvite(array $user, int $id): void
    {
        $this->requireOwner($user);
        $st = $this->db->prepare("SELECT id FROM invites WHERE id=? AND circle_id=? AND status='sent'");
        $st->execute([$id, $user['circle_id']]);
        if (!$st->fetch()) {
            Http::flash('That invite is not waiting anymore.', 'error');
            Http::redirect('/circle');
        }
        $this->db->prepare("UPDATE invites SET status='cancelled' WHERE id=?")->execute([$id]);
        Http::flash('Invite cancelled. That seat is free again.');
        Http::redirect('/circle');
    }

    private function removeMember(array $user): void
    {
        $this->requireOwner($user);
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id === (int) $user['id']) {
            Http::flash('The owner cannot remove themselves. Transfer the circle first.', 'error');
            Http::redirect('/circle');
        }
        $st = $this->db->prepare('SELECT id FROM users WHERE id=? AND circle_id=? AND role != ?');
        $st->execute([$id, $user['circle_id'], 'owner']);
        if (!$st->fetch()) {
            Http::flash('That person is not a removable member of this circle.', 'error');
            Http::redirect('/circle');
        }
        $this->db->prepare('DELETE FROM users WHERE id=? AND circle_id=?')->execute([$id, $user['circle_id']]);
        Http::flash('Removed from the circle. That seat is free again.');
        Http::redirect('/circle');
    }

    private function join(string $token, ?array $user): void
    {
        $st = $this->db->prepare("SELECT * FROM invites WHERE token=? AND status='sent'");
        $st->execute([$token]);
        $inv = $st->fetch();
        if (!$inv) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        if (Http::method() !== 'POST') {
            $this->view('join', ['invite' => $inv]);
        }
        Http::csrfCheck();
        $password = (string) ($_POST['password'] ?? '');
        $name = trim((string) ($_POST['name'] ?? $inv['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? $inv['phone'] ?? ''));
        if ($name === '' || strlen($password) < 8) {
            Http::flash('Name and an 8+ character password are required.', 'error');
            $this->view('join', ['invite' => $inv]);
        }
        $exists = $this->db->prepare('SELECT id, circle_id FROM users WHERE lower(email)=?');
        $exists->execute([strtolower((string) $inv['email'])]);
        if ($exists->fetch()) {
            Http::flash('That email already has a login. Sign in instead.', 'error');
            Http::redirect('/login');
        }
        if ($this->circleSize((int) $inv['circle_id']) >= self::MAX_CIRCLE) {
            Http::flash('This circle is full.', 'error');
            Http::redirect('/');
        }
        $this->db->prepare(
            'INSERT INTO users (circle_id, email, name, password_hash, phone, role, status, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $inv['circle_id'], strtolower((string) $inv['email']), $name,
            password_hash($password, PASSWORD_DEFAULT), $phone, 'member', 'accepted', Http::now(),
        ]);
        $uid = (int) $this->db->lastInsertId();
        $this->db->prepare("UPDATE invites SET status='accepted' WHERE id=?")->execute([$inv['id']]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid;
        $_SESSION['totp_ok'] = 1;
        Http::flash('You are in the circle. If someone asks you to look, pause with them — do not rush.');
        Http::redirect('/home');
    }

    private function trusted(array $user): never
    {
        $rows = $this->trustedRows((int) $user['circle_id']);
        $this->view('trusted', ['user' => $user, 'rows' => $rows]);
    }

    private function addTrusted(array $user): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Http::flash('Give this contact a name you will recognize.', 'error');
            Http::redirect('/trusted');
        }
        $this->db->prepare(
            'INSERT INTO trusted (circle_id, name, phone, website, kind, notes, added_by, created_at)
             VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $user['circle_id'], $name,
            Analyzer::digits((string) ($_POST['phone'] ?? '')),
            trim((string) ($_POST['website'] ?? '')),
            (string) ($_POST['kind'] ?? 'other'),
            trim((string) ($_POST['notes'] ?? '')),
            $user['id'], Http::now(),
        ]);
        Http::flash('Saved on your protected list. Prefer numbers from statements and cards, not from unexpected texts.');
        Http::redirect('/trusted');
    }

    private function deleteTrusted(array $user, int $id): void
    {
        $st = $this->db->prepare('SELECT id FROM trusted WHERE id=? AND circle_id=?');
        $st->execute([$id, $user['circle_id']]);
        if (!$st->fetch()) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        $this->db->prepare('DELETE FROM trusted WHERE id=? AND circle_id=?')->execute([$id, $user['circle_id']]);
        Http::flash('Removed from the trusted list.');
        Http::redirect('/trusted');
    }

    private function billing(array $user): never
    {
        $st = $this->db->prepare('SELECT plan FROM circles WHERE id=?');
        $st->execute([$user['circle_id']]);
        $plan = (string) ($st->fetchColumn() ?: 'yearly');
        $stripe = trim(Env::get('STRIPE_SECRET_KEY')) !== '';
        $this->view('billing', ['user' => $user, 'plan' => $plan, 'stripe' => $stripe, 'isOwner' => $user['role'] === 'owner']);
    }

    private function choosePlan(array $user): void
    {
        $this->requireOwner($user);
        $plan = (string) ($_POST['plan'] ?? '');
        if (!in_array($plan, ['monthly', 'yearly'], true)) {
            Http::flash('Choose Family monthly or Family yearly.', 'error');
            Http::redirect('/billing');
        }
        $this->db->prepare('UPDATE circles SET plan=? WHERE id=?')->execute([$plan, $user['circle_id']]);
        $label = $plan === 'monthly' ? 'Family monthly ($14.99/month)' : 'Family yearly ($119.99/year)';
        if (trim(Env::get('STRIPE_SECRET_KEY')) === '') {
            Http::flash("This circle is on {$label}. Card payments are not connected yet, so nothing was charged.");
        } else {
            Http::flash("This circle is on {$label}.");
        }
        Http::redirect('/billing');
    }

    private function report(array $user): never
    {
        $this->view('report', ['user' => $user]);
    }

    private function account(array $user): never
    {
        $this->view('account', ['user' => $user]);
    }

    private function changePassword(array $user): void
    {
        $cur = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        if (!password_verify($cur, $user['password_hash'])) {
            Http::flash('Current password did not match.', 'error');
            Http::redirect('/account');
        }
        if (strlen($new) < 8) {
            Http::flash('Use at least 8 characters.', 'error');
            Http::redirect('/account');
        }
        $this->db->prepare('UPDATE users SET password_hash=? WHERE id=?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        Http::flash('Password updated.');
        Http::redirect('/account');
    }

    private function savePhone(array $user): void
    {
        $phone = Analyzer::digits((string) ($_POST['phone'] ?? ''));
        $opt = isset($_POST['sms_opt_out']) ? 1 : 0;
        $this->db->prepare('UPDATE users SET phone=?, sms_opt_out=? WHERE id=?')->execute([$phone, $opt, $user['id']]);
        Http::flash('Mobile number saved.');
        Http::redirect('/account');
    }

    private function totpSetup(array $user, string $method): void
    {
        if ($method === 'POST') {
            Http::csrfCheck();
            $code = (string) ($_POST['otp'] ?? '');
            $secret = (string) ($user['totp_pending'] ?: $user['totp_secret']);
            if (!Totp::verify($secret, $code)) {
                Http::flash('That 6-digit code did not match. Try again.', 'error');
                Http::redirect('/account/2fa/setup');
            }
            $codes = Totp::recoveryCodes();
            $this->db->prepare(
                'UPDATE users SET totp_secret=?, totp_pending=NULL, totp_enabled=1, recovery_codes=? WHERE id=?'
            )->execute([$secret, json_encode($codes), $user['id']]);
            $_SESSION['totp_ok'] = 1;
            $this->view('totp-done', ['user' => $user, 'codes' => $codes]);
        }
        $secret = Totp::secret();
        $this->db->prepare('UPDATE users SET totp_pending=? WHERE id=?')->execute([$secret, $user['id']]);
        $this->view('totp-setup', [
            'user' => $user,
            'secret' => Totp::grouped($secret),
            'uri' => Totp::uri($user['email'], $secret),
        ]);
    }

    private function chat(): never
    {
        $body = Http::bodyJson();
        $msg = trim((string) ($body['message'] ?? ''));
        $reply = $this->chatReply($msg);
        Http::json(['reply' => $reply]);
    }

    private function chatReply(string $msg): string
    {
        $em = Layout::supportEmail();
        if ($msg === '') {
            return 'Ask me about plans, login, or how the circle works. For a person, email ' . $em . '.';
        }
        $low = strtolower($msg);
        if (preg_match('/safe|legit|real|scam or not/', $low)) {
            return 'OurCircle cannot tell you that a request is safe. Pause, read the warning signs, check your trusted list, and call someone in your circle. Then you decide.';
        }
        if (preg_match('/year|annual|119/', $low)) {
            return 'Family Shield Pro is $14.99 per month or $119.99 per year for one circle of up to five people. Yearly is the better family value. Start at /signup. Paying does not make a request safe.';
        }
        if (preg_match('/price|cost|plan|month|14\.99/', $low)) {
            return 'Family Shield Pro is $14.99 per month or $119.99 per year for one circle of up to five people. Yearly is the better family value. Start at /signup.';
        }
        if (preg_match('/login|password|forgot|sign in/', $low)) {
            return 'Use /login with the email on your circle. Forgot password sends a one-hour link if mail is set up, or use a recovery code from 2FA. Demo circle (sandbox): family@ourcircle.app / password123.';
        }
        if (preg_match('/sms|text|twilio|forward/', $low)) {
            return 'Save your mobile on Account. When texting is connected, invites and “Please call me before I pay” can go by SMS. Reply STOP to opt out. This is not a customer-service hotline.';
        }
        return 'Family Shield Pro (OurCircle) is a trusted family circle for sketchy texts, calls, prizes, and urgent payment asks. It is not an AI stamp of safety. Paste the request, read the warning signs, and call someone you trust. For a person, email ' . $em . '.';
    }

    private function legal(string $which): never
    {
        $this->view($which, ['email' => Layout::supportEmail()]);
    }

    private function robots(): never
    {
        header('Content-Type: text/plain; charset=utf-8');
        $host = parse_url(Http::baseUrl(), PHP_URL_HOST) ?: 'familyshieldpro.com';
        echo "User-agent: *\nAllow: /\nAllow: /signup\nAllow: /login\nAllow: /forgot\nAllow: /privacy\nAllow: /terms\n";
        echo "Disallow: /home\nDisallow: /circle\nDisallow: /trusted\nDisallow: /checks\nDisallow: /uploads\nDisallow: /join\nDisallow: /billing\nDisallow: /report\nDisallow: /account\nDisallow: /logout\nDisallow: /admin\n\n";
        echo 'Host: ' . $host . "\n";
        echo 'Sitemap: ' . Http::baseUrl() . "/sitemap.xml\n";
        exit;
    }

    private function sitemap(): never
    {
        header('Content-Type: application/xml; charset=utf-8');
        $b = Http::baseUrl();
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (['/', '/signup', '/login', '/forgot', '/privacy', '/terms'] as $p) {
            echo '<url><loc>' . Http::e($b . $p) . '</loc><changefreq>weekly</changefreq></url>';
        }
        echo '</urlset>';
        exit;
    }

    private function healthz(): never
    {
        $payload = [
            'ok' => true,
            'service' => 'familyshieldpro',
            'product' => 'Family Shield Pro',
            'app' => 'OurCircle',
            'version' => Db::VERSION,
        ];
        if (Env::truthy('HEALTHZ_DETAILS') || !empty($_SESSION['admin'])) {
            $payload['mail'] = Mailer::configured();
            $payload['stripe'] = trim(Env::get('STRIPE_SECRET_KEY')) !== '';
            $payload['sms'] = trim(Env::get('TWILIO_AUTH_TOKEN')) !== '';
            $payload['admin'] = trim(Env::get('OPERATOR_PASSWORD')) !== '';
        }
        Http::json($payload);
    }

    private function admin(string $path, string $method): void
    {
        $need = Env::get('OPERATOR_PASSWORD');
        if ($path === '/admin') {
            if (empty($_SESSION['admin'])) {
                Http::redirect('/admin/login');
            }
            $circles = $this->db->query(
                'SELECT c.id, c.name, c.plan, c.created_at, (SELECT COUNT(*) FROM users u WHERE u.circle_id=c.id) AS people
                 FROM circles c ORDER BY c.id DESC LIMIT 100'
            )->fetchAll();
            $this->view('admin', ['circles' => $circles]);
        }
        if ($method === 'POST') {
            Http::csrfCheck();
            $pw = (string) ($_POST['password'] ?? '');
            if ($need === '' || !hash_equals($need, $pw)) {
                Http::flash('Operator password did not match.', 'error');
                $this->view('admin-login');
            }
            $_SESSION['admin'] = 1;
            Http::redirect('/admin');
        }
        $this->view('admin-login');
    }

    private function mailCircle(array $user, int $checkId, string $kind): int
    {
        if (!Mailer::configured()) {
            return 0;
        }
        $st = $this->db->prepare('SELECT email, name FROM users WHERE circle_id=? AND id!=?');
        $st->execute([$user['circle_id'], $user['id']]);
        $n = 0;
        foreach ($st->fetchAll() as $m) {
            $n++;
            $link = Http::baseUrl() . '/checks/' . $checkId;
            $subj = $kind === 'call-me'
                ? 'Please call before a payment — OurCircle'
                : 'Look at this with your circle — OurCircle';
            try {
                Mailer::send((string) $m['email'], $subj, "Open this check:\n{$link}\n");
            } catch (Throwable $e) {
            }
        }
        return $n;
    }

    private function members(int $circleId): array
    {
        $st = $this->db->prepare('SELECT * FROM users WHERE circle_id=? ORDER BY role DESC, id');
        $st->execute([$circleId]);
        return $st->fetchAll();
    }

    private function pendingInvites(int $circleId): array
    {
        $st = $this->db->prepare("SELECT * FROM invites WHERE circle_id=? AND status='sent' ORDER BY id");
        $st->execute([$circleId]);
        return $st->fetchAll();
    }

    private function trustedRows(int $circleId): array
    {
        $st = $this->db->prepare('SELECT * FROM trusted WHERE circle_id=? ORDER BY id');
        $st->execute([$circleId]);
        return $st->fetchAll();
    }

    private function recentChecks(int $circleId): array
    {
        $st = $this->db->prepare('SELECT * FROM checks WHERE circle_id=? ORDER BY id DESC LIMIT 8');
        $st->execute([$circleId]);
        return $st->fetchAll();
    }

    private function latestAlert(int $circleId): ?array
    {
        $st = $this->db->prepare(
            'SELECT a.*, p.name AS payer_name, t.name AS trigger_name
             FROM alerts a
             JOIN users p ON p.id=a.payer_user_id
             JOIN users t ON t.id=a.triggered_by
             WHERE a.circle_id=? ORDER BY a.id DESC LIMIT 1'
        );
        $st->execute([$circleId]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
