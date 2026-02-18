<?php
$q = trim($_GET['q'] ?? '');
$pageTitle = $q ? 'Arama: ' . $q : 'Arama';
require_once 'includes/header.php';

$products = [];
if ($q) {
    $searchTerm = '%' . $q . '%';
    $products = Database::fetchAll(
        "SELECT p.*, c.name as category_name FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.status = 1 AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ? OR p.sku LIKE ?)
         ORDER BY p.name ASC LIMIT 50",
        [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
    );
}
?>

<div class="container" style="padding:32px 20px;">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current">Arama
            <?= $q ? ': ' . e($q) : '' ?>
        </span>
    </div>

    <?php if ($q): ?>
        <div style="margin-bottom:24px">
            <h2 style="font-size:1.25rem;font-weight:700">"
                <?= e($q) ?>" için
                <?= count($products) ?> sonuç bulundu
            </h2>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>
                <?= $q ? 'Sonuç Bulunamadı' : 'Ürün Arayın' ?>
            </h3>
            <p>
                <?= $q ? '"' . e($q) . '" için sonuç bulunamadı.' : 'Arama kutusunu kullanarak ürün arayabilirsiniz.' ?>
            </p>
            <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary">Tüm Ürünleri Göster</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>