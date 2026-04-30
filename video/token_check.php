<?php
// ═══════════════════════════════════════════════════════════════
//  WOXPLUS TOKEN SİSTEMİ — token_check.php
// ═══════════════════════════════════════════════════════════════

define('TOKEN_FILE',   __DIR__ . '/wox_tokens.json');
define('TOKEN_DAYS',   15);
define('TOKEN_PAGE',   'token_al.php');
define('TOKEN_SECRET', 'W0XPL4S_S3CR3T_2026'); // ← DEĞİŞTİR

// ── Dosyaya yazılabilir mi kontrol et ─────────────────────────
if (!file_exists(TOKEN_FILE)) {
    // Dosya yoksa oluşturmayı dene
    @file_put_contents(TOKEN_FILE, '{}', LOCK_EX);
}
if (!is_writable(TOKEN_FILE) && !is_writable(dirname(TOKEN_FILE))) {
    // Yazma izni yoksa sessizce devam et (token kontrolünü atla)
    // Bunu değiştirmek istersen aşağıdaki satırı aç:
    // die('HATA: wox_tokens.json dosyasına yazma izni yok. chmod 664 wox_tokens.json');
}

// ── JSON oku / yaz ────────────────────────────────────────────
function wox_read(): array {
    if (!file_exists(TOKEN_FILE)) return [];
    $raw = @file_get_contents(TOKEN_FILE);
    if ($raw === false || $raw === '') return [];
    $data = @json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function wox_write(array $d): void {
    @file_put_contents(TOKEN_FILE, json_encode($d, JSON_PRETTY_PRINT), LOCK_EX);
}

// ── Cihaz parmak izi ─────────────────────────────────────────
function wox_device(): string {
    $ip = '0.0.0.0';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    return md5($ip . '|' . $ua . '|' . TOKEN_SECRET);
}

// ── Token üret ───────────────────────────────────────────────
function wox_make_token(): string {
    return bin2hex(random_bytes(16)); // 32 hex karakter
}

// ── Token yayınla (token_al.php çağırır) ─────────────────────
// Cookie YAZMAZ — sadece link döndürür
function wox_issue(): array {
    $tokens = wox_read();
    $device = wox_device();
    $now    = time();

    // Bu cihazın geçerli tokeni var mı?
    if (isset($tokens[$device])) {
        $rec = $tokens[$device];
        $age = $now - (int)($rec['issued_at'] ?? 0);
        if ($age < TOKEN_DAYS * 86400) {
            return [
                'token'     => $rec['token'],
                'expires'   => date('d.m.Y', (int)$rec['issued_at'] + TOKEN_DAYS * 86400),
                'remaining' => TOKEN_DAYS - (int)($age / 86400),
                'link'      => 'darkwinds.php?' . $rec['token'],
                'is_new'    => false,
            ];
        }
        unset($tokens[$device]);
    }

    // Yeni token üret
    $tok     = wox_make_token();
    $now_exp = $now + TOKEN_DAYS * 86400;

    $tokens[$device] = [
        'token'     => $tok,
        'issued_at' => $now,
        'hits'      => 0,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 80),
        'created'   => date('Y-m-d H:i:s', $now),
    ];
    wox_write($tokens);

    return [
        'token'     => $tok,
        'expires'   => date('d.m.Y', $now_exp),
        'remaining' => TOKEN_DAYS,
        'link'      => 'darkwinds.php?' . $tok,
        'is_new'    => true,
    ];
}

// ── indeex.php'yi koru ───────────────────────────────────────
function wox_protect(): void {
    $tokens = wox_read();
    $device = wox_device();
    $now    = time();

    // ADIM 1: URL'de token var mı? → indeex.php?abc123... (32 hex, = yok)
    $qs        = $_SERVER['QUERY_STRING'] ?? '';
    $url_token = '';
    if ($qs !== '' && strpos($qs, '=') === false && preg_match('/^[a-f0-9]{32}$/i', $qs)) {
        $url_token = strtolower($qs);
    }

    if ($url_token !== '') {
        $found_dev = null;
        foreach ($tokens as $dev => $rec) {
            $stored = strtolower($rec['token'] ?? '');
            if ($stored !== '' && $stored === $url_token) {
                $found_dev = $dev;
                break;
            }
        }

        if ($found_dev === null) {
            wox_redir('Geçersiz token. Lütfen kendi tokenınızı alın.', 'invalid');
        }

        $rec = $tokens[$found_dev];

        // Süresi dolmuş mu?
        if (($now - (int)($rec['issued_at'] ?? 0)) >= TOKEN_DAYS * 86400) {
            unset($tokens[$found_dev]);
            wox_write($tokens);
            wox_redir('Token süresi doldu. Yeni token alın.', 'expired');
        }

        // ✅ Geçerli → cookie'ye kaydet, temiz URL'e yönlendir
        $exp = (int)$rec['issued_at'] + TOKEN_DAYS * 86400;
        setcookie('wox_access_token', $url_token, $exp, '/', '', false, true);

        $tokens[$found_dev]['hits']      = ($rec['hits'] ?? 0) + 1;
        $tokens[$found_dev]['last_seen'] = $now;
        wox_write($tokens);

        // URL'den tokeni temizle
        header('Location: darkwinds.php');
        exit;
    }

    // ADIM 2: Cookie'de token var mı?
    $cookie = $_COOKIE['wox_access_token'] ?? '';
    if ($cookie !== '') {
        $cookie = strtolower($cookie);
        foreach ($tokens as $dev => $rec) {
            $stored = strtolower($rec['token'] ?? '');
            if ($stored !== '' && $stored === $cookie) {
                // Farklı cihaz mı?
                if ($dev !== $device) {
                    setcookie('wox_access_token', '', time() - 3600, '/');
                    wox_redir('Bu token başka bir cihaza ait. Kendi tokenınızı alın.', 'device_mismatch');
                }
                // Süresi dolmuş mu?
                if (($now - (int)($rec['issued_at'] ?? 0)) >= TOKEN_DAYS * 86400) {
                    unset($tokens[$dev]);
                    wox_write($tokens);
                    setcookie('wox_access_token', '', time() - 3600, '/');
                    wox_redir('Token süresi doldu. Yeni token alın.', 'expired');
                }
                // ✅ Geçerli cookie → sayfayı aç
                $tokens[$dev]['hits']      = ($rec['hits'] ?? 0) + 1;
                $tokens[$dev]['last_seen'] = $now;
                wox_write($tokens);
                return;
            }
        }
        // Cookie var ama DB'de yok → temizle
        setcookie('wox_access_token', '', time() - 3600, '/');
    }

    // ADIM 3: Hiçbir şey yok → token_al.php'ye yönlendir
    wox_redir('Erişim için token gerekiyor.', 'no_token');
}

// ── Yönlendirme ──────────────────────────────────────────────
function wox_redir(string $msg, string $reason): void {
    header('Location: ' . TOKEN_PAGE . '?reason=' . urlencode($reason) . '&msg=' . urlencode($msg));
    exit;
}