<?php
// Admin login + write protection. Included at the top of api.php.
require __DIR__ . '/auth_config.php'; // defines $ADMIN_PASSWORD

session_name('inv_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

function auth_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$action  = $_GET['action'] ?? '';
$method  = $_SERVER['REQUEST_METHOD'];
$isAdmin = !empty($_SESSION['admin']);

if ($action === 'status') {
    auth_out(['admin' => $isAdmin]);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    auth_out(['admin' => false]);
}

if ($action === 'login') {
    if ($method !== 'POST') auth_out(['error' => 'Method not allowed'], 405);
    if (($ADMIN_PASSWORD ?? '') === '') auth_out(['error' => 'Set the admin password in auth_config.php first'], 500);

    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $pw = (string)($in['password'] ?? '');
    $stored = (string)$ADMIN_PASSWORD;
    // Works with a plain password or a bcrypt hash ($2y$...)
    $ok = strncmp($stored, '$2y$', 4) === 0 ? password_verify($pw, $stored) : hash_equals($stored, $pw);

    if (!$ok) { sleep(1); auth_out(['error' => 'Wrong password'], 401); }
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    auth_out(['admin' => true]);
}

// Anyone can read. Only a logged-in admin can add, edit or delete.
if ($method !== 'GET' && !$isAdmin) {
    auth_out(['error' => 'Please log in as admin to change products.'], 401);
}