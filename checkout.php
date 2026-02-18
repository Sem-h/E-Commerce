<?php
$pageTitle = 'Siparişi Tamamla';
require_once 'includes/header.php';

requireLogin();

$cartItems = getCartItems();
if (empty($cartItems)) {
    redirect('/cart.php');
}

$subtotal = getCartTotal();

// Kupon indirimi
$couponDiscount = 0;
$appliedCoupon = $_SESSION['coupon'] ?? null;
$campaignId = null;
if ($appliedCoupon) {
    try {
        $campaign = Database::fetch("SELECT * FROM campaigns WHERE id = ? AND status = 1", [$appliedCoupon['campaign_id']]);
        if ($campaign) {
            $disc = 0;
            if ($campaign['discount_percent'] > 0) {
                $disc = round($subtotal * $campaign['discount_percent'] / 100, 2);
            } elseif ($campaign['discount_amount'] > 0) {
                $disc = $campaign['discount_amount'];
            }
            if ($campaign['max_discount'] > 0 && $disc > $campaign['max_discount'])
                $disc = $campaign['max_discount'];
            if ($disc > $subtotal)
                $disc = $subtotal;
            $couponDiscount = $disc;
            $campaignId = $campaign['id'];
        } else {
            unset($_SESSION['coupon']);
            $appliedCoupon = null;
        }
    } catch (Exception $e) {
    }
}

$kdvRate = 0.20;
$kdvAmount = round($subtotal * $kdvRate, 2);
$shippingCost = floatval(getSetting('shipping_cost', 49.90));
$freeShippingLimit = floatval(getSetting('free_shipping_limit', 2000));
$shipping = $subtotal >= $freeShippingLimit ? 0 : $shippingCost;
$total = $subtotal + $kdvAmount + $shipping - $couponDiscount;
if ($total < 0)
    $total = 0;
$user = currentUser();

// Adresler
$addresses = Database::fetchAll("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC", [$_SESSION['user_id']]);

// Mahalle ve ilçe alanlarını orders tablosuna ekle
try {
    Database::query("ALTER TABLE orders ADD COLUMN shipping_neighborhood VARCHAR(100) DEFAULT '' AFTER shipping_district");
} catch (Exception $e) {
}
try {
    Database::query("ALTER TABLE orders ADD COLUMN home_delivery TINYINT(1) DEFAULT 0 AFTER notes");
} catch (Exception $e) {
}
try {
    Database::query("ALTER TABLE orders ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0 AFTER home_delivery");
} catch (Exception $e) {
}

// Adrese teslim ayarları
$deliveryEnabled = getSetting('delivery_enabled', '0') === '1';
$deliveryCity = getSetting('delivery_city', 'Bursa');
$deliveryFee = floatval(getSetting('delivery_fee', 250));
$deliveryTitle = getSetting('delivery_title', 'Adresinize Teslim');
$deliveryDesc = getSetting('delivery_description', 'Gün içinde adresinize teslimat');
$deliveryDistricts = getSetting('delivery_districts', '');

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
        'neighborhood' => $_POST['neighborhood'] ?? '',
        'zip' => $_POST['zip_code'] ?? '',
    ];

    // Adrese teslim seçeneği
    $homeDelivery = isset($_POST['home_delivery']) ? 1 : 0;
    $orderDeliveryFee = 0;
    if ($homeDelivery && $deliveryEnabled && strtolower(trim($shippingData['city'])) === strtolower(trim($deliveryCity))) {
        $orderDeliveryFee = $deliveryFee;
        // İlçe kontrolü
        if (!empty($deliveryDistricts)) {
            $allowedDistricts = array_map('trim', array_map('mb_strtolower', explode(',', $deliveryDistricts)));
            if (!in_array(mb_strtolower(trim($shippingData['district'])), $allowedDistricts)) {
                $orderDeliveryFee = 0;
                $homeDelivery = 0;
            }
        }
        $total += $orderDeliveryFee;
    } else {
        $homeDelivery = 0;
    }

    // Validasyon
    if (empty($shippingData['first_name']) || empty($shippingData['address']) || empty($shippingData['city']) || empty($shippingData['phone'])) {
        flash('checkout', 'Lütfen tüm zorunlu alanları doldurun.', 'error');
    } else {
        // Sipariş oluştur
        Database::query(
            "INSERT INTO orders (user_id, order_number, subtotal, shipping_cost, discount_amount, campaign_id, total, status, payment_method, payment_status,
             shipping_first_name, shipping_last_name, shipping_phone, shipping_address, shipping_city, shipping_district, shipping_neighborhood, shipping_zip, notes, home_delivery, delivery_fee)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                $orderNumber,
                $subtotal,
                $shipping,
                $couponDiscount,
                $campaignId,
                $total,
                $paymentMethod,
                $shippingData['first_name'],
                $shippingData['last_name'],
                $shippingData['phone'],
                $shippingData['address'],
                $shippingData['city'],
                $shippingData['district'],
                $shippingData['neighborhood'],
                $shippingData['zip'],
                $_POST['notes'] ?? '',
                $homeDelivery,
                $orderDeliveryFee
            ]
        );
        $orderId = Database::lastInsertId();

        // Kampanya kullanım kaydı
        if ($campaignId && $couponDiscount > 0) {
            recordCampaignUsage($campaignId, $_SESSION['user_id'], $orderId, $couponDiscount);
        }

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
        unset($_SESSION['coupon']);

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
                            <select name="city" id="checkoutCity" class="form-control" required>
                                <option value="">İl seçiniz...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>İlçe *</label>
                            <select name="district" id="checkoutDistrict" class="form-control" required>
                                <option value="">Önce il seçiniz...</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mahalle/Cadde</label>
                            <input type="text" name="neighborhood" class="form-control"
                                value="<?= e($addresses[0]['neighborhood'] ?? '') ?>"
                                placeholder="Mahalle veya cadde adı">
                        </div>
                        <div class="form-group">
                            <label>Posta Kodu</label>
                            <input type="text" name="zip_code" class="form-control"
                                value="<?= e($addresses[0]['zip_code'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Adrese Teslim Seçeneği -->
                <?php if ($deliveryEnabled): ?>
                    <div class="checkout-section" id="deliverySection" style="display:none">
                        <h3><i class="fas fa-home" style="color:#3b82f6"></i> Teslimat Seçenekleri</h3>
                        <label class="payment-method" id="deliveryOption"
                            style="border:2px solid #93c5fd;background:linear-gradient(135deg,#eff6ff,#f0fdf4);cursor:pointer">
                            <input type="checkbox" name="home_delivery" value="1" id="homeDeliveryCheck"
                                style="width:20px;height:20px;accent-color:#3b82f6;flex-shrink:0">
                            <div style="flex:1">
                                <h4 style="display:flex;align-items:center;gap:8px">
                                    <i class="fas fa-shipping-fast" style="color:#3b82f6"></i>
                                    <?= e($deliveryTitle) ?>
                                    <span
                                        style="margin-left:auto;font-size:1rem;color:#059669;font-weight:700">+<?= formatPrice($deliveryFee) ?></span>
                                </h4>
                                <p style="font-size:0.8rem;color:var(--gray);margin:4px 0 0"><?= e($deliveryDesc) ?></p>
                            </div>
                        </label>
                    </div>
                <?php endif; ?>

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
                    <div class="cart-summary-row" id="shippingRow">
                        <span id="shippingLabel">Kargo</span>
                        <span id="shippingValue">
                            <?= $shipping > 0 ? formatPrice($shipping) : '<span style="color:var(--success)">Ücretsiz</span>' ?>
                        </span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Genel Toplam <small style="font-weight:400;font-size:0.7rem;color:var(--gray)">(KDV
                                Dahil)</small></span>
                        <span id="grandTotal"><?= formatPrice($total) ?></span>
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

<script src="<?= BASE_URL ?>/js/address-selector.js"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';

    // Checkout address selector
    initAddressSelector('checkoutCity', 'checkoutDistrict', {
        city: '<?= e($addresses[0]['city'] ?? '') ?>',
        district: '<?= e($addresses[0]['district'] ?? '') ?>'
    });

    document.querySelectorAll('.payment-method').forEach(pm => {
        pm.addEventListener('click', function () {
            document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    <?php if ($deliveryEnabled): ?>
        // Adrese teslim: şehir kontrolü
        const deliveryCity = '<?= e($deliveryCity) ?>';
        const deliveryFee = <?= $deliveryFee ?>;
        const deliveryDistricts = '<?= e($deliveryDistricts) ?>'.split(',').map(d => d.trim().toLowerCase()).filter(d => d);
        const baseTotal = <?= $total ?>;
        const deliverySection = document.getElementById('deliverySection');
        const homeDeliveryCheck = document.getElementById('homeDeliveryCheck');
        const grandTotal = document.getElementById('grandTotal');

        function checkDeliveryEligibility() {
            const selectedCity = document.getElementById('checkoutCity').value;
            const selectedDistrict = document.getElementById('checkoutDistrict').value;
            let eligible = selectedCity.toLowerCase() === deliveryCity.toLowerCase();

            // İlçe kontrolü
            if (eligible && deliveryDistricts.length > 0 && selectedDistrict) {
                eligible = deliveryDistricts.includes(selectedDistrict.toLowerCase());
            }

            if (deliverySection) {
                deliverySection.style.display = eligible ? 'block' : 'none';
                if (!eligible && homeDeliveryCheck) {
                    homeDeliveryCheck.checked = false;
                    updateDeliveryTotal();
                }
            }
        }

        function updateDeliveryTotal() {
            const checked = homeDeliveryCheck && homeDeliveryCheck.checked;
            const shippingLabel = document.getElementById('shippingLabel');
            const shippingValue = document.getElementById('shippingValue');
            const shippingRow = document.getElementById('shippingRow');
            const origShippingCost = <?= $shipping ?>;
            const origShippingHtml = origShippingCost > 0 ? new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(origShippingCost) + ' \u20BA' : '<span style="color:var(--success)">Ücretsiz</span>';

            if (checked) {
                shippingLabel.innerHTML = '<i class="fas fa-home" style="margin-right:4px"></i> Adrese Teslim';
                shippingValue.innerHTML = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(deliveryFee) + ' \u20BA';
                shippingRow.style.color = '#3b82f6';
                shippingRow.style.fontWeight = '600';
            } else {
                shippingLabel.textContent = 'Kargo';
                shippingValue.innerHTML = origShippingHtml;
                shippingRow.style.color = '';
                shippingRow.style.fontWeight = '';
            }

            const shippingCost = checked ? deliveryFee : origShippingCost;
            const newTotal = baseTotal - origShippingCost + shippingCost;
            if (grandTotal) grandTotal.textContent = new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(newTotal) + ' \u20BA';
        }

        document.getElementById('checkoutCity').addEventListener('change', () => {
            setTimeout(checkDeliveryEligibility, 100);
        });
        document.getElementById('checkoutDistrict').addEventListener('change', checkDeliveryEligibility);
        if (homeDeliveryCheck) homeDeliveryCheck.addEventListener('change', updateDeliveryTotal);

        // İlk yüklemede kontrol
        setTimeout(checkDeliveryEligibility, 800);
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>