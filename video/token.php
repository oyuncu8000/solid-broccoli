<?php
require 'db.php';
session_start();

header('Content-Type: application/json');

$email = $_SESSION['email'] ?? '';

if (!$email || !isActive($email)) {
    http_response_code(403);
    echo json_encode(['error' => 'Üyelik geçersiz']);
    exit;
}

$expires = time() + TOKEN_SURE;
$sig     = hash_hmac('sha256', $email . ':' . $expires, SECRET_KEY);
$token   = urlencode(base64_encode($email . ':' . $expires . ':' . $sig));

echo json_encode([
    'token'   => $token,
    'expires' => $expires
]);