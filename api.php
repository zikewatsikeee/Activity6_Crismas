<?php
// JSON API for the inventory (MySQL). Import database.sql first.
header('Content-Type: application/json');
require __DIR__ . '/auth.php';
// XAMPP defaults. Change these if your MySQL settings are different.
$DB_HOST = 'sql310.infinityfree.com';
$DB_NAME = 'if0_43413442_group3';
$DB_USER = 'if0_43413442';
$DB_PASS = 'L8SCCtSNXZum7';

try {
    $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $method = $_SERVER['REQUEST_METHOD'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $in = json_decode(file_get_contents('php://input'), true) ?: [];

    $clean = fn($in) => [
        trim($in['name'] ?? ''),
        trim($in['category'] ?? ''),
        max(0, (float)($in['price'] ?? 0)),
        max(0, (int)($in['qty'] ?? 0)),
        (string)($in['image'] ?? ''),
    ];

    if ($method === 'GET') {
        $rows = $db->query('SELECT id, name, category, price, qty, image FROM products ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['price'] = (float)$r['price']; $r['qty'] = (int)$r['qty']; }
        echo json_encode($rows);
    } elseif ($method === 'POST') {
        [$name, $cat, $price, $qty, $img] = $clean($in);
        if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Name is required']); exit; }
        $db->prepare('INSERT INTO products (name, category, price, qty, image) VALUES (?, ?, ?, ?, ?)')
           ->execute([$name, $cat, $price, $qty, $img]);
        echo json_encode(['id' => (int)$db->lastInsertId()]);
    } elseif ($method === 'PUT' && $id) {
        [$name, $cat, $price, $qty, $img] = $clean($in);
        if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Name is required']); exit; }
        $db->prepare('UPDATE products SET name=?, category=?, price=?, qty=?, image=? WHERE id=?')
           ->execute([$name, $cat, $price, $qty, $img, $id]);
        echo json_encode(['ok' => true]);
    } elseif ($method === 'DELETE' && $id) {
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}