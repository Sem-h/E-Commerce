<?php
$pageTitle = 'Ana Sayfa';
require_once 'includes/header.php';

$featuredProducts = getFeaturedProducts(8);
$newProducts = getNewProducts(8);
$categories = getCategories();
$brands = Database::fetchAll("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND status = 1 ORDER BY brand LIMIT 50");
?>

<!-- Ana Sayfa Layout: Sidebar + İçerik -->
<div class="container">
    <div class="home-layout">
        <!-- SOL SIDEBAR: Kategori Ağacı -->
        <aside class="home-sidebar">
            <div class="sidebar-cat-header">
                <i class="fas fa-bars"></i> Kategoriler
            </div>
            <nav class="sidebar-cat-tree">
                <?php foreach ($categories as $cat):
                    $subCats = getSubCategories($cat['id']);
                    ?>
                    <div class="sidebar-cat-item <?= !empty($subCats) ? 'has-children' : '' ?>">
                        <?php if (!empty($subCats)): ?>
                            <div class="sidebar-cat-link" onclick="this.parentElement.classList.toggle('open')"
                                style="cursor:pointer">
                                <i class="<?= e($cat['icon']) ?>"></i>
                                <span><?= e($cat['name']) ?></span>
                                <i class="fas fa-chevron-down sidebar-cat-arrow"></i>
                            </div>
                            <div class="sidebar-cat-sub">
                                <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>"
                                    class="sidebar-sub-viewall">
                                    <i class="fas fa-th-large"></i> Tümünü Gör
                                </a>
                                <?php foreach ($subCats as $sub): ?>
                                    <a href="<?= BASE_URL ?>/products.php?category=<?= e($sub['slug']) ?>">
                                        <?= e($sub['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="sidebar-cat-link">
                                <i class="<?= e($cat['icon']) ?>"></i>
                                <span><?= e($cat['name']) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- SAĞ İÇERİK ALANI -->
        <div class="home-content">
            <!-- Hero Slider -->
            <div class="home-slider">
                <?php
                // Slider tablosu yoksa sessizce devam et
                try {
                    $sliders = Database::fetchAll("SELECT * FROM sliders WHERE status = 1 ORDER BY sort_order ASC, id ASC");
                } catch (Exception $e) {
                    $sliders = [];
                }
                if (empty($sliders)):
                    ?>
                    <div class="home-slide active" style="background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);">
                        <div class="slide-content">
                            <span class="slide-badge">🛒 V-Commerce</span>
                            <h2>Hoş Geldiniz</h2>
                            <p>Admin panelinden slider ekleyerek bu alanı özelleştirebilirsiniz.</p>
                            <a href="<?= BASE_URL ?>/products.php" class="btn btn-secondary">Alışverişe Başla</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($sliders as $si => $slide): ?>
                        <div class="home-slide <?= $si === 0 ? 'active' : '' ?>"
                            style="background: <?= $slide['image'] ? 'url(' . e($slide['image']) . ') center/cover no-repeat' : 'linear-gradient(135deg, ' . e($slide['gradient_start']) . ' 0%, ' . e($slide['gradient_end']) . ' 100%)' ?>;">
                            <div class="slide-content">
                                <?php if ($slide['badge']): ?><span
                                        class="slide-badge"><?= e($slide['badge']) ?></span><?php endif; ?>
                                <h2><?= e($slide['title']) ?></h2>
                                <?php if ($slide['description']): ?>
                                    <p><?= e($slide['description']) ?></p><?php endif; ?>
                                <a href="<?= BASE_URL . e($slide['button_url']) ?>"
                                    class="btn btn-secondary"><?= e($slide['button_text']) ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="slider-dots">
                        <?php foreach ($sliders as $si => $slide): ?>
                            <button class="slider-dot <?= $si === 0 ? 'active' : '' ?>" onclick="goSlide(<?= $si ?>)"></button>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-arrow slider-prev" onclick="prevSlide()"><i
                            class="fas fa-chevron-left"></i></button>
                    <button class="slider-arrow slider-next" onclick="nextSlide()"><i
                            class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>

            <!-- Promosyon Bannerları (Slider Altı) -->
            <?php if (!empty($sliders) && count($sliders) >= 2): ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px">
                    <?php foreach (array_slice($sliders, 0, 4) as $promo): ?>
                        <a href="<?= BASE_URL . e($promo['button_url']) ?>"
                            style="display:block;text-decoration:none;border-radius:12px;padding:24px 22px;color:#fff;overflow:hidden;background:linear-gradient(135deg, <?= e($promo['gradient_start']) ?>, <?= e($promo['gradient_end']) ?>);transition:transform 0.2s,box-shadow 0.2s"
                            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)'"
                            onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <?php if ($promo['badge']): ?><span
                                    style="display:inline-block;background:rgba(255,255,255,0.2);padding:3px 10px;border-radius:12px;font-size:0.7rem;margin-bottom:6px"><?= e($promo['badge']) ?></span><?php endif; ?>
                            <h3 style="margin:0 0 4px;font-size:1rem;font-weight:700"><?= e($promo['title']) ?></h3>
                            <p style="margin:0;opacity:0.85;font-size:0.8rem"><?= e($promo['description']) ?></p>
                            <span
                                style="display:inline-block;margin-top:10px;background:rgba(255,255,255,0.2);padding:5px 14px;border-radius:6px;font-size:0.75rem;font-weight:600"><?= e($promo['button_text']) ?>
                                →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Banner Row -->
            <div class="home-banners">
                <div class="home-banner" style="background:linear-gradient(135deg, #f97316, #ea580c);">
                    <i class="fas fa-truck"></i>
                    <div>
                        <strong>Ücretsiz Kargo</strong>
                        <span><?= formatPrice(floatval(getSetting('free_shipping_limit', 2000))) ?> üzeri</span>
                    </div>
                </div>
                <div class="home-banner" style="background:linear-gradient(135deg, #1a56db, #1e40af);">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>Güvenli Alışveriş</strong>
                        <span>256-bit SSL</span>
                    </div>
                </div>
                <div class="home-banner" style="background:linear-gradient(135deg, #059669, #047857);">
                    <i class="fas fa-undo"></i>
                    <div>
                        <strong>Kolay İade</strong>
                        <span>14 gün içinde</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Öne Çıkan Ürünler -->
<?php if (!empty($featuredProducts)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-star" style="color:var(--secondary)"></i> Öne Çıkan Ürünler</h2>
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

<!-- Kategoriler Grid -->
<section class="section" style="background:var(--white)">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-th-large" style="color:var(--primary)"></i> Popüler Kategoriler
            </h2>
            <a href="<?= BASE_URL ?>/products.php" class="section-link">Tümünü Gör <i
                    class="fas fa-arrow-right"></i></a>
        </div>
        <div class="categories-grid">
            <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="category-card">
                    <div class="category-icon"><i class="<?= e($cat['icon']) ?>"></i></div>
                    <h3><?= e($cat['name']) ?></h3>
                    <span><?= getCategoryProductCount($cat['id']) ?> ürün</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Yeni Ürünler -->
<?php if (!empty($newProducts)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-bolt" style="color:var(--warning)"></i> Yeni Eklenenler</h2>
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

<script>
    // Home Slider
    let currentSlide = 0;
    const slides = document.querySelectorAll('.home-slide');
    const dots = document.querySelectorAll('.slider-dot');

    function goSlide(n) {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        currentSlide = n;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }
    function nextSlide() { goSlide((currentSlide + 1) % slides.length); }
    function prevSlide() { goSlide((currentSlide - 1 + slides.length) % slides.length); }
    setInterval(nextSlide, 5000);

</script>

<?php require_once 'includes/footer.php'; ?>