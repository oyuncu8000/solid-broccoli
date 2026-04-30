<?php
define('SECRET_KEY', 'ekdrk');
define('TOKEN_SURE', 900); // 15 dakika

$expires   = time() + TOKEN_SURE;
$sig       = hash_hmac('sha256', (string)$expires, SECRET_KEY);
$token     = urlencode(base64_encode($expires . ':' . $sig));

echo "Token: " . $token . "<br>";
echo "Geçerlilik: " . date('H:i:s', $expires) . " kadar<br>";
echo "<br>";
echo "<a href='stream.php?token=" . $token . "'>Stream'e Git →</a>";
?>