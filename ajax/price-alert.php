<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

// Tablo oluştur
try {
    Database::query("CREATE TABLE IF NOT EXISTS price_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        target_price DECIMAL(10,2) NOT NULL,
        original_price DECIMAL(10,2) NOT NULL,
        notified TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_alert (user_id, product_id)
    )");
} catch (Exception $e) {
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapın.']);
    exit;
}

$action = $_POST['action'] ?? 'toggle';
$productId = intval($_POST['product_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($action === 'toggle') {
    $existing = Database::fetch("SELECT id FROM price_alerts WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
    if ($existing) {
        Database::query("DELETE FROM price_alerts WHERE id = ?", [$existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Fiyat uyarısı kaldırıldı.', 'action' => 'removed']);
    } else {
        // Mevcut fiyatı al
        $product = Database::fetch("SELECT price, discount_price FROM products WHERE id = ?", [$productId]);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
            exit;
        }
        $currentPrice = $product['discount_price'] ?: $product['price'];
        Database::query(
            "INSERT INTO price_alerts (user_id, product_id, target_price, original_price) VALUES (?, ?, ?, ?)",
            [$userId, $productId, $currentPrice, $currentPrice]
        );
        echo json_encode(['success' => true, 'message' => 'Fiyat düşünce haber verilecek!', 'action' => 'added']);
    }
    exit;
}

if ($action === 'remove') {
    Database::query("DELETE FROM price_alerts WHERE id = ? AND user_id = ?", [intval($_POST['alert_id'] ?? 0), $userId]);
    echo json_encode(['success' => true, 'message' => 'Fiyat uyarısı kaldırıldı.']);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
