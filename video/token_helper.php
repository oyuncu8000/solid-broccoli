<?php
// ═══════════════════════════════════════════════════════════════
//  Bu kodu giris.php'de başarılı giriş sonrasına ekle
//  (header('Location: stream.php') yerine bunu kullan)
// ═══════════════════════════════════════════════════════════════

// giris.php içinde başarılı giriş kontrolünden SONRA:
//
//   $_SESSION['email'] = $email;
//   header('Location: ' . generatePlayerToken($email));
//   exit;

require 'db.php';

function generatePlayerToken(string $email): string {
    $expires = time() + 3600; // 1 saat geçerli
    $sig     = hash_hmac('sha256', $email . ':' . $expires, SECRET_KEY);
    $raw     = $email . ':' . $expires . ':' . $sig;
    $token   = urlencode(base64_encode($raw));
    return 'player.php?token=' . $token;
}

// ─── TEST: Bu dosyayı doğrudan açarsan örnek token üretir ─────
if (php_sapi_name() !== 'cli' && isset($_GET['test_email'])) {
    $email = $_GET['test_email'];
    $url   = generatePlayerToken($email);
    echo '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>';
    exit;
}