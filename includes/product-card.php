<?php
/**
 * Ürün Kartı Partial - product değişkeni gerektirir
 */
$price = $product['discount_price'] ?: $product['price'];
$hasDiscount = $product['discount_price'] && $product['discount_price'] < $product['price'];
$discountPercent = $hasDiscount ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
$imageUrl = getImageUrl($product['image']);
?>
<div class="product-card">
    <div class="product-image">
        <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <div class="product-badges">
            <?php if ($hasDiscount): ?>
                <span class="product-badge badge-sale">%
                    <?= $discountPercent ?>
                </span>
            <?php endif; ?>
            <?php if (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                <span class="product-badge badge-new">Yeni</span>
            <?php endif; ?>
        </div>
        <div class="product-actions-overlay">
            <button onclick="addToCart(<?= $product['id'] ?>)" title="Sepete Ekle"><i
                    class="fas fa-cart-plus"></i></button>
            <button onclick="toggleWishlist(<?= $product['id'] ?>)" title="Favorilere Ekle"><i
                    class="fas fa-heart"></i></button>
        </div>
    </div>
    <div class="product-info">
        <?php if (!empty($product['category_name'])): ?>
            <span class="product-category">
                <?= e($product['category_name']) ?>
            </span>
        <?php endif; ?>
        <h3 class="product-title">
            <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($product['slug']) ?>">
                <?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </h3>
        <?php if (!empty($product['short_description'])): ?>
            <p class="product-desc">
                <?= e(truncate($product['short_description'], 60)) ?>
            </p>
        <?php endif; ?>
        <div class="product-price">
            <span class="current-price">
                <?= formatPrice($price) ?>
            </span>
            <?php if ($hasDiscount): ?>
                <span class="old-price">
                    <?= formatPrice($product['price']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-footer">
        <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($product['slug']) ?>"
            class="btn btn-primary btn-sm btn-block">
            <i class="fas fa-eye"></i> İncele
        </a>
    </div>
</div>