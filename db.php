<?php
define('SECRET_KEY', 'ekdrk');
define('TOKEN_SURE', 300); // 5 dakika
define('USERS_FILE', __DIR__ . '/data/users.json');
define('NOWPAYMENTS_API_KEY', 'K6PAYG7-VJDMVFN-N0FZA6Q-V2EBX0D');
define('NOWPAYMENTS_IPN_SECRET', 'V7WZ0VP-TJ4MWKX-NNQX3N1-0KSRP0K');

if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);

function getUsers() {
    if (!file_exists(USERS_FILE)) return [];
    return json_decode(file_get_contents(USERS_FILE), true) ?? [];
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function getUser($email) {
    $users = getUsers();
    return $users[strtolower($email)] ?? null;
}

function isActive($email) {
    $user = getUser($email);
    if (!$user) return false;
    if ($user['expires'] === 'lifetime') return true;
    return time() < $user['expires'];
}