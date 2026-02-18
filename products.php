<?php
$pageTitle = 'Ürünler';
require_once 'includes/header.php';

// Filtreler
$categorySlug = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$featured = $_GET['featured'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

// Sorgu oluştur
$where = 'p.status = 1';
$params = [];

if ($categorySlug) {
    $cat = getCategoryBySlug($categorySlug);
    if ($cat) {
        // Ana kategori seçilmişse, alt kategorilerin ürünlerini de dahil et
        $catIds = [$cat['id']];
        $children = Database::fetchAll("SELECT id FROM categories WHERE parent_id = ?", [$cat['id']]);
        foreach ($children as $child) {
            $catIds[] = $child['id'];
        }
        $placeholders = implode(',', array_fill(0, count($catIds), '?'));
        $where .= " AND p.category_id IN ($placeholders)";
        $params = array_merge($params, $catIds);
        $pageTitle = $cat['name'];
    }
}

if ($brand) {
    $where .= ' AND p.brand = ?';
    $params[] = $brand;
}

if ($minPrice !== '') {
    $where .= ' AND (COALESCE(p.discount_price, p.price)) >= ?';
    $params[] = floatval($minPrice);
}

if ($maxPrice !== '') {
    $where .= ' AND (COALESCE(p.discount_price, p.price)) <= ?';
    $params[] = floatval($maxPrice);
}

if ($featured) {
    $where .= ' AND p.featured = 1';
    $pageTitle = 'Öne Çıkan Ürünler';
}

// Sıralama
$orderBy = 'p.created_at DESC';
switch ($sort) {
    case 'price_asc':
        $orderBy = 'COALESCE(p.discount_price, p.price) ASC';
        break;
    case 'price_desc':
        $orderBy = 'COALESCE(p.discount_price, p.price) DESC';
        break;
    case 'name_asc':
        $orderBy = 'p.name ASC';
        break;
    case 'popular':
        $orderBy = 'p.view_count DESC';
        break;
    default:
        $orderBy = 'p.created_at DESC';
}

// Toplam sayı
$countResult = Database::fetch("SELECT COUNT(*) as total FROM products p WHERE $where", $params);
$totalProducts = $countResult['total'];
$pagination = paginate($totalProducts, $perPage, $page);

// Ürünleri getir
$products = Database::fetchAll(
    "SELECT p.*, c.name as category_name FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET {$pagination['offset']}",
    $params
);

// Markalar
$brands = Database::fetchAll("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' AND status = 1 ORDER BY brand");
$categories = getCategories();
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current">
            <?= e($pageTitle) ?>
        </span>
    </div>

    <div class="shop-layout">
        <aside class="shop-sidebar">
            <!-- Kategori Ağacı (Ana sayfa ile aynı) -->
            <div class="home-sidebar" style="margin-bottom:16px">
                <div class="sidebar-cat-header">
                    <i class="fas fa-bars"></i> Kategoriler
                </div>
                <nav class="sidebar-cat-tree">
                    <?php
                    $allMainCats = getCategories();
                    foreach ($allMainCats as $mcat):
                        $subCats = getSubCategories($mcat['id']);
                        $isParentActive = ($categorySlug == $mcat['slug']);
                        $isChildActive = false;
                        foreach ($subCats as $sub) {
                            if ($categorySlug == $sub['slug']) {
                                $isChildActive = true;
                                break;
                            }
                        }
                        $isOpen = $isParentActive || $isChildActive;
                        ?>
                        <div
                            class="sidebar-cat-item <?= !empty($subCats) ? 'has-children' : '' ?> <?= $isOpen ? 'open' : '' ?>">
                            <?php if (!empty($subCats)): ?>
                                <div class="sidebar-cat-link <?= $isParentActive ? 'active-cat' : '' ?>"
                                    onclick="this.parentElement.classList.toggle('open')" style="cursor:pointer">
                                    <i class="<?= e($mcat['icon']) ?>"></i>
                                    <span><?= e($mcat['name']) ?></span>
                                    <i class="fas fa-chevron-down sidebar-cat-arrow"></i>
                                </div>
                                <div class="sidebar-cat-sub">
                                    <a href="<?= BASE_URL ?>/products.php?category=<?= e($mcat['slug']) ?>"
                                        class="sidebar-sub-viewall <?= $isParentActive ? 'active-cat' : '' ?>">
                                        <i class="fas fa-th-large"></i> Tümünü Gör
                                    </a>
                                    <?php foreach ($subCats as $sub): ?>
                                        <a href="<?= BASE_URL ?>/products.php?category=<?= e($sub['slug']) ?>"
                                            class="<?= $categorySlug == $sub['slug'] ? 'active-cat' : '' ?>">
                                            <?= e($sub['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/products.php?category=<?= e($mcat['slug']) ?>"
                                    class="sidebar-cat-link <?= $isParentActive ? 'active-cat' : '' ?>">
                                    <i class="<?= e($mcat['icon']) ?>"></i>
                                    <span><?= e($mcat['name']) ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </nav>
                <?php if ($categorySlug): ?>
                    <a href="<?= BASE_URL ?>/products.php"
                        style="display:block;text-align:center;padding:8px;font-size:0.75rem;color:var(--danger);border-top:1px solid var(--gray-200)">✕
                        Filtreyi Temizle</a>
                <?php endif; ?>
            </div>

            <!-- Fiyat & Marka Filtreleri -->
            <div
                style="background:var(--white);border-radius:var(--radius-lg);padding:16px;border:1px solid var(--gray-200)">
                <form method="GET" action="">
                    <?php if ($categorySlug): ?>
                        <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <?php if ($featured): ?><input type="hidden" name="featured" value="1"><?php endif; ?>

                    <div class="filter-group">
                        <h4><i class="fas fa-tag"></i> Fiyat Aralığı</h4>
                        <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" value="<?= e($minPrice) ?>">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="Max" value="<?= e($maxPrice) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm btn-block"
                            style="margin-top:10px">Uygula</button>
                    </div>

                    <?php if (!empty($brands)): ?>
                        <div class="filter-group">
                            <h4><i class="fas fa-building"></i> Markalar</h4>
                            <?php foreach (array_slice($brands, 0, 10) as $b): ?>
                                <label>
                                    <input type="radio" name="brand" value="<?= e($b['brand']) ?>" <?= $brand == $b['brand'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <?= e($b['brand']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Products -->
        <div>
            <div class="shop-toolbar">
                <span class="result-count">
                    <?= $totalProducts ?> ürün bulundu
                </span>
                <select onchange="location.href=this.value">
                    <option
                        value="<?= BASE_URL ?>/products.php?sort=newest<?= $categorySlug ? '&category=' . $categorySlug : '' ?>"
                        <?= $sort == 'newest' ? 'selected' : '' ?>>En Yeniler</option>
                    <option
                        value="<?= BASE_URL ?>/products.php?sort=price_asc<?= $categorySlug ? '&category=' . $categorySlug : '' ?>"
                        <?= $sort == 'price_asc' ? 'selected' : '' ?>>Fiyat: Düşükten Yükseğe</option>
                    <option
                        value="<?= BASE_URL ?>/products.php?sort=price_desc<?= $categorySlug ? '&category=' . $categorySlug : '' ?>"
                        <?= $sort == 'price_desc' ? 'selected' : '' ?>>Fiyat: Yüksekten Düşüğe</option>
                    <option
                        value="<?= BASE_URL ?>/products.php?sort=name_asc<?= $categorySlug ? '&category=' . $categorySlug : '' ?>"
                        <?= $sort == 'name_asc' ? 'selected' : '' ?>>İsme Göre (A-Z)</option>
                    <option
                        value="<?= BASE_URL ?>/products.php?sort=popular<?= $categorySlug ? '&category=' . $categorySlug : '' ?>"
                        <?= $sort == 'popular' ? 'selected' : '' ?>>Popülerlik</option>
                </select>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Ürün Bulunamadı</h3>
                    <p>Arama kriterlerinize uygun ürün bulunamadı.</p>
                    <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary">Tüm Ürünleri Göster</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php include __DIR__ . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <?php
                $baseUrl = '?sort=' . e($sort);
                if ($categorySlug)
                    $baseUrl .= '&category=' . e($categorySlug);
                if ($brand)
                    $baseUrl .= '&brand=' . e($brand);
                if ($featured)
                    $baseUrl .= '&featured=1';
                $baseUrl .= '&';
                renderPagination($pagination, $baseUrl);
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>