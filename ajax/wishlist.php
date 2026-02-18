<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapın.']);
    exit;
}

$productId = intval($_POST['product_id'] ?? 0);
$userId = $_SESSION['user_id'];

$existing = Database::fetch("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
if ($existing) {
    Database::query("DELETE FROM wishlist WHERE id = ?", [$existing['id']]);
    echo json_encode(['success' => true, 'message' => 'Ürün favorilerden çıkarıldı.', 'action' => 'removed']);
} else {
    Database::query("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)", [$userId, $productId]);
    echo json_encode(['success' => true, 'message' => 'Ürün favorilere eklendi!', 'action' => 'added']);
}
