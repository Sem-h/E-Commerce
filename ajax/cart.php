<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Geçersiz istek.'];

switch ($action) {
    case 'add':
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));

        $product = getProduct($productId);
        if (!$product) {
            $response = ['success' => false, 'message' => 'Ürün bulunamadı.'];
            break;
        }
        if ($product['stock'] <= 0) {
            $response = ['success' => false, 'message' => 'Ürün stokta yok.'];
            break;
        }

        addToCart($productId, $quantity);
        $response = [
            'success' => true,
            'message' => $product['name'] . ' sepete eklendi!',
            'cart_count' => getCartCount()
        ];
        break;

    case 'remove':
        $cartId = intval($_POST['cart_id'] ?? 0);
        removeFromCart($cartId);
        $response = ['success' => true, 'message' => 'Ürün sepetten kaldırıldı.', 'cart_count' => getCartCount()];
        break;

    case 'update':
        $cartId = intval($_POST['cart_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        updateCartQuantity($cartId, $quantity);
        $response = ['success' => true, 'message' => 'Sepet güncellendi.', 'cart_count' => getCartCount()];
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
