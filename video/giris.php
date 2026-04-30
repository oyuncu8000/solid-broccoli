<?php
require 'db.php';
session_start();

$hata = '';

// Bilgi mesajları
$bilgi = '';
if (isset($_GET['kayit']))  $bilgi = '✅ Hesabınız oluşturuldu! Giriş yapabilirsiniz.';
if (isset($_GET['zaten']))  $bilgi = 'ℹ️ Hesabınız zaten mevcut, giriş yapın.';
if (isset($_GET['expired'])) $hata = 'Üyeliğiniz sona erdi. Lütfen yenileyin.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $sifre = $_POST['sifre'] ?? '';
    $user  = getUser($email);

    if (!$user || !isset($user['sifre'])) {
        $hata = 'Email bulunamadı. <a href="kayit.php">Kayıt olun →</a>';
    } elseif (!password_verify($sifre, $user['sifre'])) {
        $hata = 'Şifre yanlış.';
    } elseif (!isActive($email)) {
        $hata = 'Üyeliğiniz sona erdi. <a href="https://buy.stripe.com/test_5kQbJ0afq9d49KebbM7wA0x" target="_blank">Yenileyin →</a>';
    } else {
        $_SESSION['email'] = $email;
        header('Location: stream.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giriş</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0a0a0a; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Helvetica Neue', sans-serif; }
  .box { background: #141414; border: 1px solid #222; border-radius: 8px; padding: 48px 40px; width: 100%; max-width: 400px; }
  h1 { color: #fff; font-size: 1.6rem; margin-bottom: 8px; }
  p  { color: rgba(255,255,255,.4); font-size: .85rem; margin-bottom: 32px; }
  label { display: block; color: rgba(255,255,255,.6); font-size: .75rem; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 8px; margin-top: 16px; }
  input { width: 100%; background: #1e1e1e; border: 1.5px solid #333; color: #fff; padding: 12px 16px; border-radius: 5px; font-size: .95rem; outline: none; transition: border .2s; }
  input:focus { border-color: #e50914; }
  button { width: 100%; background: #e50914; border: none; color: #fff; padding: 13px; border-radius: 5px; font-size: .95rem; font-weight: 700; cursor: pointer; margin-top: 16px; transition: background .15s; }
  button:hover { background: #f40612; }
  .hata  { background: rgba(229,9,20,.12); border: 1px solid rgba(229,9,20,.3); color: #ff6b6b; padding: 12px 16px; border-radius: 5px; font-size: .83rem; margin-bottom: 20px; }
  .hata a { color: #e50914; }
  .bilgi { background: rgba(0,200,0,.08); border: 1px solid rgba(0,200,0,.25); color: #6f6; padding: 12px 16px; border-radius: 5px; font-size: .83rem; margin-bottom: 20px; }
  .alt { text-align: center; margin-top: 24px; color: rgba(255,255,255,.3); font-size: .8rem; }
  .alt a { color: #e50914; text-decoration: none; }
</style>
</head>
<body>
<div class="box">
  <h1>📺 Giriş Yap</h1>
  <p>İzlemeye devam etmek için giriş yapın.</p>

  <?php if ($hata):  ?><div class="hata"><?= $hata ?></div><?php endif; ?>
  <?php if ($bilgi): ?><div class="bilgi"><?= $bilgi ?></div><?php endif; ?>

  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" placeholder="ornek@email.com" required autofocus>
    <label>Şifre</label>
    <input type="password" name="sifre" placeholder="Şifreniz" required>
    <button type="submit">Giriş Yap →</button>
  </form>

  <div class="alt">
    Hesabınız yok mu? <a href="kayit.php">Kayıt olun →</a>
  </div>
</div>
</body>
</html>