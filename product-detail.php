<?php
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
$product = getProductBySlug($slug);
if (!$product) {
    header('Location: ' . BASE_URL . '/products.php');
    exit;
}

// Görüntülenme sayısı artır
Database::query("UPDATE products SET view_count = view_count + 1 WHERE id = ?", [$product['id']]);

$pageTitle = html_entity_decode($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$related = Database::fetchAll(
    "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.category_id = ? AND p.id != ? AND p.status = 1 ORDER BY RAND() LIMIT 4",
    [$product['category_id'], $product['id']]
);

$price = $product['discount_price'] ?: $product['price'];
$hasDiscount = $product['discount_price'] && $product['discount_price'] < $product['price'];
$discountPercent = $hasDiscount ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
$decodedName = html_entity_decode($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$decodedDesc = html_entity_decode($product['description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
$decodedShort = html_entity_decode($product['short_description'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

require_once 'includes/header.php';

// Fiyat uyarısı kontrolü
$hasPriceAlert = false;
if (isLoggedIn()) {
    try {
        $alertCheck = Database::fetch("SELECT id FROM price_alerts WHERE user_id = ? AND product_id = ?", [$_SESSION['user_id'], $product['id']]);
        $hasPriceAlert = !!$alertCheck;
    } catch (Exception $e) {
    }
}
?>

<div class="container" style="padding-top:16px; padding-bottom:40px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php if (!empty($product['category_name'])): ?>
            <a href="<?= BASE_URL ?>/products.php?category=<?= e($product['category_slug'] ?? '') ?>">
                <?= e($product['category_name']) ?>
            </a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
        <span class="current">
            <?= e(truncate($decodedName, 50)) ?>
        </span>
    </div>

    <div class="pd-grid">
        <!-- Sol: Ürün Görseli -->
        <div class="pd-gallery">
            <div class="pd-image-main">
                <?php if ($hasDiscount): ?>
                    <span class="pd-badge-sale">%<?= $discountPercent ?> İNDİRİM</span>
                <?php endif; ?>
                <?php if (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                    <span class="pd-badge-new">YENİ</span>
                <?php endif; ?>
                <img src="<?= e(getImageUrl($product['image'])) ?>" alt="<?= e($decodedName) ?>" id="mainImg">
            </div>
        </div>

        <!-- Sağ: Ürün Bilgileri -->
        <div class="pd-info">
            <?php if (!empty($product['brand'])): ?>
                <a href="<?= BASE_URL ?>/products.php?brand=<?= urlencode($product['brand']) ?>" class="pd-brand">
                    <?= e($product['brand']) ?>
                </a>
            <?php endif; ?>

            <h1 class="pd-title"><?= htmlspecialchars($decodedName, ENT_QUOTES, 'UTF-8') ?></h1>

            <div class="pd-meta">
                <div class="pd-meta-item">
                    <i class="fas fa-barcode"></i>
                    <span><?= e($product['sku'] ?: 'N/A') ?></span>
                </div>
                <div class="pd-meta-item">
                    <i class="fas fa-eye"></i>
                    <span><?= number_format($product['view_count']) ?> görüntülenme</span>
                </div>
                <div class="pd-meta-item pd-stock <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <i class="fas fa-<?= $product['stock'] > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                    <span><?= $product['stock'] > 0 ? 'Stokta (' . $product['stock'] . ' adet)' : 'Tükendi' ?></span>
                </div>
            </div>

            <!-- Fiyat Alanı -->
            <div class="pd-price-box">
                <div class="pd-price-row">
                    <span class="pd-current-price"><?= formatPrice($price) ?></span>
                    <?php if ($hasDiscount): ?>
                        <span class="pd-old-price"><?= formatPrice($product['price']) ?></span>
                        <span class="pd-discount-tag">%<?= $discountPercent ?> İndirim</span>
                    <?php endif; ?>
                </div>
                <?php if ($hasDiscount): ?>
                    <div class="pd-savings">
                        <i class="fas fa-piggy-bank"></i>
                        Bu üründe <strong><?= formatPrice($product['price'] - $price) ?></strong> tasarruf edin
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($decodedShort)): ?>
                <div class="pd-short-desc">
                    <p><?= strip_tags($decodedShort) ?></p>
                </div>
            <?php endif; ?>

            <!-- Sepete Ekle -->
            <?php if ($product['stock'] > 0): ?>
                <div class="pd-actions">
                    <div class="pd-qty">
                        <button type="button" class="qty-minus" onclick="changeQty(-1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="qty" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                        <button type="button" class="qty-plus" onclick="changeQty(1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('qty').value)"
                        class="btn btn-primary btn-lg pd-add-cart">
                        <i class="fas fa-cart-plus"></i> Sepete Ekle
                    </button>
                    <button onclick="toggleWishlist(<?= $product['id'] ?>)" class="pd-wishlist-btn" title="Favorilere Ekle">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <!-- Fiyat Düşünce Haber Ver -->
                <button onclick="togglePriceAlert(<?= $product['id'] ?>)"
                    class="pd-price-alert-btn <?= $hasPriceAlert ? 'active' : '' ?>"
                    style="margin-top:10px;width:100%;padding:10px 16px;border:2px solid #f59e0b;background:<?= $hasPriceAlert ? '#fef3c7' : '#fff' ?>;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:8px;justify-content:center;font-size:0.85rem;font-weight:600;color:#92400e;transition:all .2s">
                    <i class="fas fa-<?= $hasPriceAlert ? 'bell-slash' : 'bell' ?>"></i>
                    <?= $hasPriceAlert ? 'Uyarı Aktif' : 'Fiyat Düşünce Haber Ver' ?>
                </button>
            <?php else: ?>
                <div class="pd-out-of-stock-msg">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Bu ürün şu anda stokta yok</strong>
                        <p>Tekrar stoka girdiğinde sizi bilgilendirmemizi ister misiniz?</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Güvence -->
            <div class="pd-guarantees">
                <div class="pd-guarantee">
                    <i class="fas fa-truck-fast"></i>
                    <div>
                        <strong>Ücretsiz Kargo</strong>
                        <span>150₺ üzeri siparişlerde</span>
                    </div>
                </div>
                <div class="pd-guarantee">
                    <i class="fas fa-shield-halved"></i>
                    <div>
                        <strong>Güvenli Ödeme</strong>
                        <span>256-bit SSL şifreleme</span>
                    </div>
                </div>
                <div class="pd-guarantee">
                    <i class="fas fa-rotate-left"></i>
                    <div>
                        <strong>Kolay İade</strong>
                        <span>14 gün içinde ücretsiz</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Açıklama Tabları -->
    <?php if (!empty($decodedDesc)): ?>
        <div class="pd-tabs-section">
            <div class="pd-tabs-header">
                <button class="pd-tab active" onclick="switchTab(this, 'tab-desc')">
                    <i class="fas fa-file-alt"></i> Ürün Açıklaması
                </button>
                <button class="pd-tab" onclick="switchTab(this, 'tab-specs')">
                    <i class="fas fa-list-check"></i> Teknik Özellikler
                </button>
            </div>
            <div class="pd-tab-content" id="tab-desc" style="display:block">
                <div class="pd-description-content">
                    <?= $decodedDesc ?>
                </div>
            </div>
            <div class="pd-tab-content" id="tab-specs" style="display:none">
                <div class="pd-description-content">
                    <?= $decodedDesc ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Benzer Ürünler -->
    <?php if (!empty($related)): ?>
        <section class="pd-related">
            <div class="section-header">
                <h2 class="section-title">Benzer Ürünler</h2>
                <a href="<?= BASE_URL ?>/products.php?category=<?= e($product['category_slug'] ?? '') ?>"
                    class="section-link">
                    Tümünü Gör <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    function changeQty(delta) {
        const input = document.getElementById('qty');
        let val = parseInt(input.value) + delta;
        const max = parseInt(input.max);
        if (val < 1) val = 1;
        if (val > max) val = max;
        input.value = val;
    }

    function switchTab(btn, tabId) {
        document.querySelectorAll('.pd-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.pd-tab-content').forEach(c => c.style.display = 'none');
        btn.classList.add('active');
        document.getElementById(tabId).style.display = 'block';
    }
</script>

<?php require_once 'includes/footer.php'; ?>