<?php
$pageTitle = 'Sepetim';
require_once 'includes/header.php';

// Sepet işlemleri (form post)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $cartId = intval($_POST['cart_id']);
        $qty = intval($_POST['quantity']);
        updateCartQuantity($cartId, $qty);
        flash('cart', 'Sepet güncellendi.', 'success');
        redirect('/cart.php');
    }
    if ($action === 'remove') {
        removeFromCart(intval($_POST['cart_id']));
        flash('cart', 'Ürün sepetten kaldırıldı.', 'success');
        redirect('/cart.php');
    }
    if ($action === 'clear') {
        clearCart();
        unset($_SESSION['coupon']);
        flash('cart', 'Sepet temizlendi.', 'success');
        redirect('/cart.php');
    }
    if ($action === 'apply_coupon') {
        $code = trim($_POST['coupon_code'] ?? '');
        $userId = $_SESSION['user_id'] ?? null;
        $tempSubtotal = getCartTotal();
        $result = applyCoupon($code, $userId, $tempSubtotal);
        if ($result['success']) {
            $_SESSION['coupon'] = [
                'campaign_id' => $result['campaign']['id'],
                'code' => $result['campaign']['code'],
                'name' => $result['campaign']['name'],
                'discount' => $result['discount'],
                'type' => $result['campaign']['type'],
            ];
            flash('cart', $result['message'], 'success');
        } else {
            flash('cart', $result['message'], 'error');
        }
        redirect('/cart.php');
    }
    if ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        flash('cart', 'İndirim kodu kaldırıldı.', 'success');
        redirect('/cart.php');
    }
}

$cartItems = getCartItems();
$subtotal = getCartTotal();

// Müşteriye özel kampanya otomatik uygulama
$userId = $_SESSION['user_id'] ?? null;
if ($userId && !isset($_SESSION['coupon'])) {
    try {
        $userCampaigns = getActiveCampaignsForUser($userId);
        if (!empty($userCampaigns)) {
            $uc = $userCampaigns[0];
            $discount = 0;
            if ($uc['discount_percent'] > 0) {
                $discount = round($subtotal * $uc['discount_percent'] / 100, 2);
            } elseif ($uc['discount_amount'] > 0) {
                $discount = $uc['discount_amount'];
            }
            if ($uc['max_discount'] > 0 && $discount > $uc['max_discount'])
                $discount = $uc['max_discount'];
            if ($discount > 0 && ($uc['min_order_amount'] <= 0 || $subtotal >= $uc['min_order_amount'])) {
                $_SESSION['coupon'] = [
                    'campaign_id' => $uc['id'],
                    'code' => $uc['code'],
                    'name' => $uc['name'],
                    'discount' => $discount,
                    'type' => $uc['type'],
                ];
            }
        }
    } catch (Exception $e) {
    }
}

// Kupon indirimi (session'dan)
$couponDiscount = 0;
$appliedCoupon = $_SESSION['coupon'] ?? null;
if ($appliedCoupon) {
    // Subtotal değişince indirimi yeniden hesapla
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
            $_SESSION['coupon']['discount'] = $disc;
        } else {
            unset($_SESSION['coupon']);
            $appliedCoupon = null;
        }
    } catch (Exception $e) {
    }
}

$kdvRate = 0.20; // %20 KDV
$kdvAmount = round($subtotal * $kdvRate, 2);
$shippingCost = floatval(getSetting('shipping_cost', 49.90));
$freeShippingLimit = floatval(getSetting('free_shipping_limit', 2000));
$shipping = $subtotal >= $freeShippingLimit ? 0 : $shippingCost;
$total = $subtotal + $kdvAmount + $shipping - $couponDiscount;
if ($total < 0)
    $total = 0;
?>

<div class="container" style="padding:32px 20px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current">Sepetim</span>
    </div>

    <?php showFlash('cart'); ?>

    <?php if (empty($cartItems)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-cart"></i>
            <h3>Sepetiniz Boş</h3>
            <p>Henüz sepetinize ürün eklemediniz.</p>
            <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-lg"><i class="fas fa-shopping-bag"></i>
                Alışverişe Başla</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Fiyat</th>
                            <th>Adet</th>
                            <th>Toplam</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item):
                            $itemPrice = $item['discount_price'] ?: $item['price'];
                            $itemTotal = $itemPrice * $item['quantity'];
                            ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <img src="<?= e(getImageUrl($item['image'])) ?>" alt="<?= e($item['name']) ?>">
                                        <div class="cart-product-info">
                                            <h4><a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($item['slug']) ?>">
                                                    <?= e($item['name']) ?>
                                                </a></h4>
                                        </div>
                                    </div>
                                </td>
                                <td><strong>
                                        <?= formatPrice($itemPrice) ?>
                                    </strong></td>
                                <td>
                                    <form method="POST" style="display:flex;gap:4px;align-items:center">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                            max="<?= $item['stock'] ?>" class="form-control"
                                            style="width:60px;padding:6px;text-align:center" onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td><strong>
                                        <?= formatPrice($itemTotal) ?>
                                    </strong></td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm"
                                            style="color:var(--danger);background:none;padding:6px" title="Kaldır">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top:16px;display:flex;justify-content:space-between">
                    <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i>
                        Alışverişe Devam</a>
                    <form method="POST"><input type="hidden" name="action" value="clear"><button class="btn btn-sm"
                            style="color:var(--danger)"><i class="fas fa-trash"></i> Sepeti Temizle</button></form>
                </div>
            </div>

            <div class="cart-summary">
                <h3><i class="fas fa-receipt"></i> Sipariş Özeti</h3>
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
                        <?= $shipping > 0 ? formatPrice($shipping) : '<span style="color:var(--success);font-weight:600">Ücretsiz</span>' ?>
                    </span>
                </div>
                <?php if ($shipping > 0): ?>
                    <div style="font-size:0.75rem;color:var(--gray);padding:4px 0">
                        <?= formatPrice($freeShippingLimit - $subtotal) ?> daha ekleyin, kargo ücretsiz!
                    </div>
                <?php endif; ?>

                <!-- İndirim Kodu -->
                <div style="border-top:1px dashed #e5e7eb;padding-top:12px;margin-top:8px">
                    <?php if ($appliedCoupon): ?>
                        <div class="cart-summary-row" style="color:var(--success)">
                            <span><i class="fas fa-tag"></i> <?= e($appliedCoupon['name']) ?></span>
                            <span>-<?= formatPrice($couponDiscount) ?></span>
                        </div>
                        <form method="POST" style="margin-top:6px">
                            <input type="hidden" name="action" value="remove_coupon">
                            <button type="submit"
                                style="background:none;border:none;color:var(--danger);font-size:0.75rem;cursor:pointer;padding:0">
                                <i class="fas fa-times"></i> Kodu Kaldır
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:flex;gap:6px">
                            <input type="hidden" name="action" value="apply_coupon">
                            <input type="text" name="coupon_code" class="form-control" placeholder="İndirim kodu"
                                style="flex:1;padding:8px 12px;text-transform:uppercase;letter-spacing:1px;font-size:0.8rem">
                            <button type="submit" class="btn btn-primary"
                                style="padding:8px 14px;white-space:nowrap;font-size:0.8rem">
                                <i class="fas fa-check"></i> Uygula
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="cart-summary-row total">
                    <span>Genel Toplam <small style="font-weight:400;font-size:0.7rem;color:var(--gray)">(KDV
                            Dahil)</small></span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-primary btn-lg btn-block" style="margin-top:16px">
                    <i class="fas fa-lock"></i> Siparişi Tamamla
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>