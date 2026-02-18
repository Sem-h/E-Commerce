<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>
        <?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>
        <?= e(getSetting('site_name', 'V-Commerce')) ?>
    </title>
    <meta name="description"
        content="<?= isset($pageDesc) ? e($pageDesc) : e(getSetting('site_description', 'Elektronik Ürünlerde Güvenilir Alışveriş')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
</head>

<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <a href="tel:<?= e(getSetting('site_phone')) ?>"><i class="fas fa-phone"></i>
                    <?= e(getSetting('site_phone', '+90 555 000 00 00')) ?>
                </a>
                <a href="mailto:<?= e(getSetting('site_email')) ?>"><i class="fas fa-envelope"></i>
                    <?= e(getSetting('site_email', 'info@vcommerce.com')) ?>
                </a>
            </div>
            <div class="top-bar-right">
                <span><i class="fas fa-truck"></i>
                    <?= formatPrice(floatval(getSetting('free_shipping_limit', 2000))) ?> üzeri ücretsiz kargo
                </span>
                <div class="social-links">
                    <a href="<?= e(getSetting('instagram', '#')) ?>" target="_blank"><i
                            class="fab fa-instagram"></i></a>
                    <a href="<?= e(getSetting('facebook', '#')) ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="<?= e(getSetting('twitter', '#')) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="main-header">
        <div class="header-inner">
            <a href="<?= BASE_URL ?>/" class="logo">
                <div class="logo-icon">V</div>
                V-Commerce
            </a>

            <div class="search-bar">
                <form action="<?= BASE_URL ?>/search.php" method="GET">
                    <input type="text" name="q" placeholder="Ürün, kategori veya marka ara..."
                        value="<?= e($_GET['q'] ?? '') ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="header-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/client/" class="header-action">
                        <i class="fas fa-user"></i>
                        <span>Hesabım</span>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/client/login.php" class="header-action">
                        <i class="fas fa-user"></i>
                        <span>Giriş Yap</span>
                    </a>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/cart.php" class="header-action">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sepet</span>
                    <?php $cartCount = getCartCount(); ?>
                    <span class="badge cart-badge-count" style="<?= $cartCount == 0 ? 'display:none' : '' ?>">
                        <?= $cartCount ?>
                    </span>
                </a>
            </div>

            <button class="mobile-toggle"><i class="fas fa-bars"></i></button>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <ul class="nav-list">
                <li><a href="<?= BASE_URL ?>/"
                        class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['page']) ? 'active' : '' ?>"><i
                            class="fas fa-home"></i> Ana Sayfa</a></li>
                <li class="has-mega">
                    <a href="<?= BASE_URL ?>/products.php"><i class="fas fa-th-large"></i> Kategoriler <i
                            class="fas fa-chevron-down" style="font-size:10px"></i></a>
                    <div class="mega-dropdown">
                        <div class="mega-grid">
                            <?php
                            $navCategories = getCategories();
                            foreach ($navCategories as $cat):
                                $subCats = getSubCategories($cat['id']);
                                ?>
                                <div class="mega-col">
                                    <a href="<?= BASE_URL ?>/products.php?category=<?= e($cat['slug']) ?>"
                                        class="mega-parent">
                                        <i class="<?= e($cat['icon']) ?>"></i> <?= e($cat['name']) ?>
                                    </a>
                                    <?php if (!empty($subCats)): ?>
                                        <ul class="mega-subs">
                                            <?php foreach ($subCats as $sub): ?>
                                                <li><a
                                                        href="<?= BASE_URL ?>/products.php?category=<?= e($sub['slug']) ?>"><?= e($sub['name']) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
                <li><a href="<?= BASE_URL ?>/products.php"><i class="fas fa-box-open"></i> Tüm Ürünler</a></li>
                <li><a href="<?= BASE_URL ?>/products.php?featured=1"><i class="fas fa-star"></i> Öne Çıkanlar</a></li>
                <li><a href="<?= BASE_URL ?>/products.php?sort=newest"><i class="fas fa-bolt"></i> Yeni Ürünler</a></li>
            </ul>
        </nav>
    </header>

    <main>