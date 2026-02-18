<?php
$pageTitle = 'Siparişi Tamamla';
require_once 'includes/header.php';

requireLogin();

$cartItems = getCartItems();
if (empty($cartItems)) {
    redirect('/cart.php');
}

$subtotal = getCartTotal();
$kdvRate = 0.20;
$kdvAmount = round($subtotal * $kdvRate, 2);
$shippingCost = floatval(getSetting('shipping_cost', 49.90));
$freeShippingLimit = floatval(getSetting('free_shipping_limit', 2000));
$shipping = $subtotal >= $freeShippingLimit ? 0 : $shippingCost;
$total = $subtotal + $kdvAmount + $shipping;
$user = currentUser();

// Adresler
$addresses = Database::fetchAll("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC", [$_SESSION['user_id']]);

// Sipariş oluştur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNumber = generateOrderNumber();
    $paymentMethod = $_POST['payment_method'] ?? 'kapida_odeme';

    $shippingData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'district' => $_POST['district'] ?? '',
        'zip' => $_POST['zip_code'] ?? '',
    ];

    // Validasyon
    if (empty($shippingData['first_name']) || empty($shippingData['address']) || empty($shippingData['city']) || empty($shippingData['phone'])) {
        flash('checkout', 'Lütfen tüm zorunlu alanları doldurun.', 'error');
    } else {
        // Sipariş oluştur
        Database::query(
            "INSERT INTO orders (user_id, order_number, subtotal, shipping_cost, total, status, payment_method, payment_status,
             shipping_first_name, shipping_last_name, shipping_phone, shipping_address, shipping_city, shipping_district, shipping_zip, notes)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                $orderNumber,
                $subtotal,
                $shipping,
                $total,
                $paymentMethod,
                $shippingData['first_name'],
                $shippingData['last_name'],
                $shippingData['phone'],
                $shippingData['address'],
                $shippingData['city'],
                $shippingData['district'],
                $shippingData['zip'],
                $_POST['notes'] ?? ''
            ]
        );
        $orderId = Database::lastInsertId();

        // Sipariş kalemleri
        foreach ($cartItems as $item) {
            $itemPrice = $item['discount_price'] ?: $item['price'];
            Database::query(
                "INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$orderId, $item['product_id'], $item['name'], $item['image'], $item['quantity'], $itemPrice, $itemPrice * $item['quantity']]
            );
            // Stok düş
            Database::query("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?", [$item['quantity'], $item['product_id']]);
        }

        // Sepeti temizle
        clearCart();

        // PayTR yönlendirme
        if ($paymentMethod === 'paytr') {
            // PayTR token oluşturup yönlendir
            // Sonraki fazda implement edilecek
            flash('checkout', 'PayTR entegrasyonu yakında aktif olacak. Sipariş kapıda ödeme olarak oluşturuldu.', 'info');
        }

        flash('order_success', 'Sipariş #' . $orderNumber . ' başarıyla oluşturuldu!', 'success');
        redirect('/client/orders.php');
    }
}
?>

<div class="container" style="padding:32px 20px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <a href="<?= BASE_URL ?>/cart.php">Sepet</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current">Siparişi Tamamla</span>
    </div>

    <?php showFlash('checkout'); ?>

    <form method="POST">
        <div class="checkout-layout">
            <div>
                <!-- Teslimat Adresi -->
                <div class="checkout-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Teslimat Adresi</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ad *</label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= e($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Soyad *</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= e($user['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Telefon *</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Adres *</label>
                        <textarea name="address" class="form-control" rows="3"
                            required><?= e($addresses[0]['address_line'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>İl *</label>
                            <input type="text" name="city" class="form-control"
                                value="<?= e($addresses[0]['city'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>İlçe</label>
                            <input type="text" name="district" class="form-control"
                                value="<?= e($addresses[0]['district'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Posta Kodu</label>
                        <input type="text" name="zip_code" class="form-control"
                            value="<?= e($addresses[0]['zip_code'] ?? '') ?>">
                    </div>
                </div>

                <!-- Ödeme Yöntemi -->
                <div class="checkout-section">
                    <h3><i class="fas fa-credit-card"></i> Ödeme Yöntemi</h3>
                    <div class="payment-methods">
                        <label class="payment-method selected">
                            <input type="radio" name="payment_method" value="kapida_odeme" checked>
                            <div>
                                <h4><i class="fas fa-money-bill-wave" style="color:var(--success)"></i> Kapıda Ödeme
                                </h4>
                                <p style="font-size:0.8rem;color:var(--gray)">Siparişinizi teslim alırken nakit veya
                                    kart ile ödeme yapın.</p>
                            </div>
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="havale">
                            <div>
                                <h4><i class="fas fa-university" style="color:var(--info)"></i> Havale / EFT</h4>
                                <p style="font-size:0.8rem;color:var(--gray)">Banka havalesi ile ödeme yapın.</p>
                            </div>
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="paytr">
                            <div>
                                <h4><i class="fas fa-credit-card" style="color:var(--primary)"></i> Kredi / Banka Kartı
                                    (PayTR)</h4>
                                <p style="font-size:0.8rem;color:var(--gray)">Güvenli ödeme altyapısı ile online ödeme
                                    yapın.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Sipariş Notu -->
                <div class="checkout-section">
                    <h3><i class="fas fa-sticky-note"></i> Sipariş Notu</h3>
                    <textarea name="notes" class="form-control" rows="3"
                        placeholder="Siparişinizle ilgili not ekleyebilirsiniz..."></textarea>
                </div>
            </div>

            <!-- Sipariş Özeti -->
            <div>
                <div class="cart-summary" style="position:sticky;top:80px">
                    <h3><i class="fas fa-receipt"></i> Sipariş Özeti</h3>
                    <?php foreach ($cartItems as $item):
                        $itemPrice = $item['discount_price'] ?: $item['price'];
                        ?>
                        <div class="cart-summary-row">
                            <span>
                                <?= e(truncate($item['name'], 30)) ?> (x
                                <?= $item['quantity'] ?>)
                            </span>
                            <span>
                                <?= formatPrice($itemPrice * $item['quantity']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div class="cart-summary-row">
                        <span>Ara Toplam</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>KDV (%20)</span>
                        <span><?= formatPrice($kdvAmount) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Kargo</span>
                        <span>
                            <?= $shipping > 0 ? formatPrice($shipping) : '<span style="color:var(--success)">Ücretsiz</span>' ?>
                        </span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Genel Toplam <small style="font-weight:400;font-size:0.7rem;color:var(--gray)">(KDV
                                Dahil)</small></span>
                        <span><?= formatPrice($total) ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:16px">
                        <i class="fas fa-check"></i> Siparişi Onayla
                    </button>
                    <p style="text-align:center;font-size:0.75rem;color:var(--gray);margin-top:10px">
                        <i class="fas fa-lock"></i> 256-bit SSL ile güvenli alışveriş
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('.payment-method').forEach(pm => {
        pm.addEventListener('click', function () {
            document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>