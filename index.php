<?php
$pageTitle = 'Ana Sayfa';
require_once 'includes/header.php';

$featuredProducts = getFeaturedProducts(8);
$newProducts = getNewProducts(8);
$categories = getCategories();
?>

<!-- Hero Slider -->
<div class="hero-slider">
    <div class="hero-slide active">
        <div class="hero-content">
            <span class="hero-badge">🔥 Özel Kampanya</span>
            <h1>Teknolojinin Gücünü Keşfedin</h1>
            <p>En yeni elektronik ürünleri en uygun fiyatlarla V-Commerce'de bulun. Hızlı kargo ve güvenli alışveriş.
            </p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-lg"><i
                        class="fas fa-shopping-bag"></i> Alışverişe Başla</a>
                <a href="<?= BASE_URL ?>/products.php?featured=1" class="btn btn-outline btn-lg"><i
                        class="fas fa-star"></i> Öne Çıkanlar</a>
            </div>
        </div>
    </div>
    <div class="hero-slide">
        <div class="hero-content">
            <span class="hero-badge">💻 Bilgisayar Dünyası</span>
            <h1>Laptop & PC'de Büyük İndirimler</h1>
            <p>İş ve oyun performansı bir arada. En güncel modelleri keşfedin.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/category.php?slug=bilgisayarlar" class="btn btn-primary btn-lg">Bilgisayarları
                    İncele</a>
                <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline btn-lg">Tüm Ürünler</a>
            </div>
        </div>
    </div>
    <div class="hero-slide">
        <div class="hero-content">
            <span class="hero-badge">🎧 Ses Deneyimi</span>
            <h1>Premium Kulaklık Koleksiyonu</h1>
            <p>Aktif gürültü engelleme ve üstün ses kalitesi. Müziğin tadını çıkarın.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/category.php?slug=kulakliklar" class="btn btn-secondary btn-lg">Kulaklıkları
                    Keşfet</a>
                <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline btn-lg">Tüm Ürünler</a>
            </div>
        </div>
    </div>
    <div class="hero-dots">
        <button class="hero-dot active"></button>
        <button class="hero-dot"></button>
        <button class="hero-dot"></button>
    </div>
</div>

<!-- Kategoriler -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Kategoriler</h2>
            <a href="<?= BASE_URL ?>/products.php" class="section-link">Tümünü Gör <i
                    class="fas fa-arrow-right"></i></a>
        </div>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/category.php?slug=<?= e($cat['slug']) ?>" class="category-card">
                    <div class="category-icon"><i class="<?= e($cat['icon']) ?>"></i></div>
                    <h3>
                        <?= e($cat['name']) ?>
                    </h3>
                    <span>
                        <?= getCategoryProductCount($cat['id']) ?> ürün
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Öne Çıkan Ürünler -->
<?php if (!empty($featuredProducts)): ?>
    <section class="section" style="background:var(--white)">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Öne Çıkan Ürünler</h2>
                <a href="<?= BASE_URL ?>/products.php?featured=1" class="section-link">Tümünü Gör <i
                        class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Promo Banner -->
<section class="section">
    <div class="container">
        <div class="promo-banner">
            <div>
                <h2><i class="fas fa-truck"></i> Ücretsiz Kargo</h2>
                <p>
                    <?= formatPrice(floatval(getSetting('free_shipping_limit', 2000))) ?> ve üzeri siparişlerde ücretsiz
                    kargo fırsatını kaçırmayın!
                </p>
            </div>
            <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline btn-lg">Alışverişe Başla</a>
        </div>
    </div>
</section>

<!-- Yeni Ürünler -->
<?php if (!empty($newProducts)): ?>
    <section class="section" style="background:var(--white)">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Yeni Eklenenler</h2>
                <a href="<?= BASE_URL ?>/products.php?sort=newest" class="section-link">Tümünü Gör <i
                        class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php foreach ($newProducts as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>