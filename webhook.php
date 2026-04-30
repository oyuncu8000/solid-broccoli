<?php
require 'db.php';

define('NOWPAYMENTS_IPN_SECRET', '7ysSQXeAfD4j9LPgOgl9wQ2o/OaFTJzn');

// IPN doğrula
$payload = file_get_contents('php://input');
$data    = json_decode($payload, true);

// İmza kontrolü
$sigHeader = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
ksort($data);
$expectedSig = hash_hmac('sha512', json_encode($data), NOWPAYMENTS_IPN_SECRET);

if ($sigHeader !== $expectedSig) {
    http_response_code(400);
    exit('Geçersiz imza');
}

// Ödeme tamamlandıysa
if (($data['payment_status'] === 'finished' || $data['payment_status'] === 'confirmed')) {
    $email   = strtolower($data['order_description'] ?? '');
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $users = getUsers();
        $users[$email] = [
            'email'      => $email,
            'expires'    => time() + (30 * 24 * 60 * 60), // 30 gün
            'created_at' => time(),
            'nowpayments_id' => $data['payment_id'] ?? ''
        ];
        saveUsers($users);
    }
}

http_response_code(200);
echo 'ok';