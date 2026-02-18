<?php
// Admin Header - requires $adminPage variable
require_once __DIR__ . '/../../config/config.php';
requireAdmin();
$adminUser = currentUser();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Admin | V-Commerce
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>

<body>
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <div class="icon">V</div> V-Commerce
        </div>
        <nav class="admin-nav">
            <span class="admin-nav-section">Ana Menü</span>
            <a href="<?= BASE_URL ?>/admin/" class="<?= ($adminPage ?? '') === 'dashboard' ? 'active' : '' ?>"><i
                    class="fas fa-home"></i> Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="<?= ($adminPage ?? '') === 'orders' ? 'active' : '' ?>"><i
                    class="fas fa-shopping-bag"></i> Siparişler</a>
            <a href="<?= BASE_URL ?>/admin/products.php" class="<?= ($adminPage ?? '') === 'products' ? 'active' : '' ?>"><i
                    class="fas fa-box"></i> Ürünler</a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= ($adminPage ?? '') === 'categories' ? 'active' : '' ?>"><i
                    class="fas fa-th-list"></i> Kategoriler</a>
            <span class="admin-nav-section">Yönetim</span>
            <a href="<?= BASE_URL ?>/admin/users.php" class="<?= ($adminPage ?? '') === 'users' ? 'active' : '' ?>"><i
                    class="fas fa-users"></i> Kullanıcılar</a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= ($adminPage ?? '') === 'settings' ? 'active' : '' ?>"><i
                    class="fas fa-cog"></i> Ayarlar</a>
            <a href="<?= BASE_URL ?>/admin/xml-import.php" class="<?= ($adminPage ?? '') === 'xml' ? 'active' : '' ?>"><i
                    class="fas fa-file-code"></i> XML Import</a>
        </nav>
        <div class="admin-nav-footer">
            <a href="<?= BASE_URL ?>/" target="_blank"><i class="fas fa-external-link-alt"></i> Siteyi Görüntüle</a>
            <a href="<?= BASE_URL ?>/admin/logout.php" style="margin-top:8px;color:#f87171"><i
                    class="fas fa-sign-out-alt"></i> Çıkış</a>
        </div>
    </aside>
    <div class="admin-content">