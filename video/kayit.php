<?php
require 'db.php';

$hata  = '';
$adim  = 1;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sifre'])) {
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $sifre  = $_POST['sifre'] ?? '';
    $sifre2 = $_POST['sifre2'] ?? '';

    if (strlen($sifre) < 6) {
        $hata = 'Şifre en az 6 karakter olmalı.';
        $adim = 2;
    } elseif ($sifre !== $sifre2) {
        $hata = 'Şifreler eşleşmiyor.';
        $adim = 2;
    } else {
        $users = getUsers();
        if (isset($users[$email]['sifre'])) {
            header('Location: giris.php?zaten=1');
            exit;
        }
        // Şifreyi kaydet, ödemeyi bekle
        $users[$email] = [
            'email'      => $email,
            'sifre'      => password_hash($sifre, PASSWORD_DEFAULT),
            'expires'    => 0, // Ödeme gelince güncellenecek
            'created_at' => time(),
        ];
        saveUsers($users);

        // NOWPayments ödeme linki oluştur
        $ch = curl_init('https://api.nowpayments.io/v1/invoice');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . NOWPAYMENTS_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'price_amount'      => 5,        // Fiyat (USD)
            'price_currency'    => 'usd',
            'order_description' => $email,   // Webhook'ta email olarak gelecek
            'success_url'       => 'https://woxplus.tvx.org/giris.php?kayit=1',
            'cancel_url'        => 'https://woxplus.tvx.org/kayit.php',
            'ipn_callback_url'  => 'https://woxplus.tvx.org/webhook.php'
        ]));
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);

        if (!empty($data['invoice_url'])) {
            header('Location: ' . $data['invoice_url']);
            exit;
        } else {
            $hata = 'Ödeme sayfası oluşturulamadı. Tekrar deneyin.';
            $adim = 2;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['sifre'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = 'Geçerli bir email girin.';
    } else {
        $user = getUser($email);
        if ($user && isset($user['sifre']) && isActive($email)) {
            header('Location: giris.php?zaten=1');
            exit;
        }
        $adim = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Üyelik</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0a0a0a; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Helvetica Neue', sans-serif; }
  .box { background: #141414; border: 1px solid #222; border-radius: 8px; padding: 48px 40px; width: 100%; max-width: 420px; }
  h1 { color: #fff; font-size: 1.4rem; margin-bottom: 8px; }
  p  { color: rgba(255,255,255,.4); font-size: .85rem; margin-bottom: 28px; line-height: 1.6; }
  label { display: block; color: rgba(255,255,255,.6); font-size: .75rem; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; margin-top: 16px; }
  input { width: 100%; background: #1e1e1e; border: 1.5px solid #333; color: #fff; padding: 12px 16px; border-radius: 5px; font-size: .95rem; outline: none; transition: border .2s; }
  input:focus { border-color: #e50914; }
  button { width: 100%; background: #e50914; border: none; color: #fff; padding: 13px; border-radius: 5px; font-size: .95rem; font-weight: 700; cursor: pointer; margin-top: 20px; transition: background .15s; }
  button:hover { background: #f40612; }
  .hata { background: rgba(229,9,20,.12); border: 1px solid rgba(229,9,20,.3); color: #ff6b6b; padding: 12px; border-radius: 5px; font-size: .83rem; margin-bottom: 16px; }
  .fiyat { background: #1a1a1a; border-radius: 6px; padding: 16px; margin-bottom: 20px; text-align: center; color: #fff; }
  .fiyat span { font-size: 2rem; font-weight: 800; color: #e50914; }
  .fiyat small { display: block; color: rgba(255,255,255,.4); font-size: .75rem; margin-top: 4px; }
  .alt { text-align: center; margin-top: 20px; color: rgba(255,255,255,.3); font-size: .8rem; }
  .alt a { color: #e50914; text-decoration: none; }
</style>
</head>
<body>
<div class="box">

<?php if ($adim === 1): ?>
  <h1>📺 Üye Ol</h1>
  <p>Email adresini gir, şifreni belirle ve kripto ile ödeme yap.</p>

  <div class="fiyat">
    <span>$5</span>
    <small>/ aylık · Kripto ile öde</small>
  </div>

  <?php if ($hata): ?><div class="hata"><?= $hata ?></div><?php endif; ?>

  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" placeholder="ornek@email.com" required autofocus>
    <button type="submit">Devam Et →</button>
  </form>

  <div class="alt">Zaten hesabın var mı? <a href="giris.php">Giriş yap →</a></div>

<?php elseif ($adim === 2): ?>
  <h1>🔑 Şifre Belirle</h1>
  <p><b style="color:#fff"><?= htmlspecialchars($email) ?></b> için şifrenizi belirleyin. Ardından ödeme sayfasına yönlendirileceksiniz.</p>

  <?php if ($hata): ?><div class="hata"><?= $hata ?></div><?php endif; ?>

  <form method="POST">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <label>Şifre</label>
    <input type="password" name="sifre" placeholder="En az 6 karakter" required autofocus>
    <label>Şifre Tekrar</label>
    <input type="password" name="sifre2" placeholder="Şifreyi tekrar girin" required>
    <button type="submit">Ödemeye Geç →</button>
  </form>
<?php endif; ?>

</div>
</body>
</html>