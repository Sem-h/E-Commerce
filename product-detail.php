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

$pageTitle = $product['name'];
$related = Database::fetchAll(
    "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.category_id = ? AND p.id != ? AND p.status = 1 ORDER BY RAND() LIMIT 4",
    [$product['category_id'], $product['id']]
);

$price = $product['discount_price'] ?: $product['price'];
$hasDiscount = $product['discount_price'] && $product['discount_price'] < $product['price'];
$discountPercent = $hasDiscount ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;

require_once 'includes/header.php';
?>

<div class="container" style="padding-top:16px; padding-bottom:40px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php if (!empty($product['category_name'])): ?>
            <a href="<?= BASE_URL ?>/category.php?slug=<?= e($product['category_slug'] ?? '') ?>">
                <?= e($product['category_name']) ?>
            </a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <?php endif; ?>
        <span class="current">
            <?= e(truncate($product['name'], 50)) ?>
        </span>
    </div>

    <div class="product-detail">
        <!-- Gallery -->
        <div class="product-gallery">
            <img src="<?= e(getImageUrl($product['image'])) ?>" alt="<?= e($product['name']) ?>" class="main-image">
        </div>

        <!-- Info -->
        <div class="product-detail-info">
            <?php if (!empty($product['brand'])): ?>
                <span class="product-category">
                    <?= e($product['brand']) ?>
                </span>
            <?php endif; ?>

            <h1>
                <?= e($product['name']) ?>
            </h1>

            <div class="product-meta">
                <span><i class="fas fa-barcode"></i> SKU:
                    <?= e($product['sku'] ?: 'N/A') ?>
                </span>
                <span><i class="fas fa-eye"></i>
                    <?= number_format($product['view_count']) ?> görüntülenme
                </span>
                <span>
                    <i class="fas fa-circle"
                        style="color:<?= $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;font-size:8px"></i>
                    <?= $product['stock'] > 0 ? 'Stokta (' . $product['stock'] . ' adet)' : 'Tükendi' ?>
                </span>
            </div>

            <div class="detail-price">
                <span class="current-price">
                    <?= formatPrice($price) ?>
                </span>
                <?php if ($hasDiscount): ?>
                    <span class="old-price">
                        <?= formatPrice($product['price']) ?>
                    </span>
                    <span class="discount-percent">%
                        <?= $discountPercent ?> İndirim
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <div class="detail-short-desc">
                    <?= e($product['short_description']) ?>
                </div>
            <?php endif; ?>

            <?php if ($product['stock'] > 0): ?>
                <div class="quantity-selector">
                    <button type="button" class="qty-minus">−</button>
                    <input type="number" id="qty" value="1" min="1" max="<?= $product['stock'] ?>">
                    <button type="button" class="qty-plus">+</button>
                </div>

                <div class="detail-actions">
                    <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('qty').value)"
                        class="btn btn-primary btn-lg">
                        <i class="fas fa-cart-plus"></i> Sepete Ekle
                    </button>
                    <button onclick="toggleWishlist(<?= $product['id'] ?>)" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Bu ürün şu anda stokta bulunmamaktadır.
                </div>
            <?php endif; ?>

            <div class="detail-features">
                <div class="detail-feature">
                    <i class="fas fa-truck"></i>
                    <span>Hızlı Kargo</span>
                </div>
                <div class="detail-feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Güvenli Ödeme</span>
                </div>
                <div class="detail-feature">
                    <i class="fas fa-undo"></i>
                    <span>Kolay İade</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Açıklama -->
    <?php if (!empty($product['description'])): ?>
        <div
            style="background:var(--white); border-radius:var(--radius-lg); padding:32px; border:1px solid var(--gray-200); margin-top:24px;">
            <h3
                style="font-size:1.25rem; font-weight:700; margin-bottom:16px; padding-bottom:12px; border-bottom:2px solid var(--gray-200);">
                <i class="fas fa-info-circle" style="color:var(--primary)"></i> Ürün Açıklaması
            </h3>
            <div style="font-size:0.9375rem; line-height:1.8; color:var(--dark-600);">
                <?= nl2br(e($product['description'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Benzer Ürünler -->
    <?php if (!empty($related)): ?>
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Benzer Ürünler</h2>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>