<?php
$pageTitle = 'Ürün Yönetimi';
$adminPage = 'products';
require_once __DIR__ . '/includes/header.php';

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'name' => trim($_POST['name']),
            'slug' => slugify($_POST['name']),
            'category_id' => intval($_POST['category_id']),
            'brand' => trim($_POST['brand'] ?? ''),
            'sku' => trim($_POST['sku'] ?? ''),
            'price' => floatval($_POST['price']),
            'discount_price' => floatval($_POST['discount_price'] ?? 0) ?: null,
            'stock' => intval($_POST['stock']),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];

        // Resim yükleme
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImage($_FILES['image'], 'products');
        }

        if ($action === 'add') {
            Database::query(
                "INSERT INTO products (name, slug, category_id, brand, sku, price, discount_price, stock, short_description, description, image, featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$data['name'], $data['slug'], $data['category_id'], $data['brand'], $data['sku'], $data['price'], $data['discount_price'], $data['stock'], $data['short_description'], $data['description'], $image, $data['featured'], $data['status']]
            );
            flash('admin_products', 'Ürün eklendi.', 'success');
        } else {
            $productId = intval($_POST['product_id']);
            $sql = "UPDATE products SET name=?, slug=?, category_id=?, brand=?, sku=?, price=?, discount_price=?, stock=?, short_description=?, description=?, featured=?, status=?";
            $params = [$data['name'], $data['slug'], $data['category_id'], $data['brand'], $data['sku'], $data['price'], $data['discount_price'], $data['stock'], $data['short_description'], $data['description'], $data['featured'], $data['status']];
            if ($image) {
                $sql .= ", image=?";
                $params[] = $image;
            }
            $sql .= " WHERE id=?";
            $params[] = $productId;
            Database::query($sql, $params);
            flash('admin_products', 'Ürün güncellendi.', 'success');
        }
        redirect('/admin/products.php');
    }

    if ($action === 'delete') {
        Database::query("DELETE FROM products WHERE id = ?", [intval($_POST['product_id'])]);
        flash('admin_products', 'Ürün silindi.', 'success');
        redirect('/admin/products.php');
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            Database::query("DELETE FROM products WHERE id IN ($placeholders)", $ids);
            flash('admin_products', count($ids) . ' ürün silindi.', 'success');
        }
        redirect('/admin/products.php');
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$search = trim($_GET['q'] ?? '');
$where = '1=1';
$params = [];
if ($search) {
    $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
    $params = ["%$search%", "%$search%"];
}
$total = Database::fetch("SELECT COUNT(*) as c FROM products p WHERE $where", $params)['c'];
$pagination = paginate($total, $perPage, $page);
$products = Database::fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where ORDER BY p.id DESC LIMIT $perPage OFFSET {$pagination['offset']}", $params);
$categories = getCategories();
?>

<div class="admin-header">
    <h1><i class="fas fa-box" style="color:var(--admin-primary)"></i> Ürün Yönetimi</h1>
    <button onclick="document.getElementById('addModal').classList.add('active')" class="admin-btn admin-btn-primary"><i
            class="fas fa-plus"></i> Yeni Ürün</button>
</div>

<?php showFlash('admin_products'); ?>

<div class="admin-toolbar">
    <div class="admin-search">
        <i class="fas fa-search"></i>
        <form method="GET"><input type="text" name="q" placeholder="Ürün ara..." value="<?= e($search) ?>"></form>
    </div>
    <span style="font-size:0.875rem;color:var(--admin-gray)">
        <?= $total ?> ürün
    </span>
</div>

<!-- Toplu İşlem Barı -->
<form id="bulkForm" method="POST">
    <input type="hidden" name="action" value="bulk_delete">
    <div id="bulkBar"
        style="display:none;padding:10px 16px;background:var(--admin-danger, #e74c3c);color:#fff;border-radius:8px;margin-bottom:12px;align-items:center;gap:12px;justify-content:space-between">
        <span><strong id="selectedCount">0</strong> ürün seçildi</span>
        <button type="submit" class="admin-btn admin-btn-sm"
            style="background:#fff;color:var(--admin-danger, #e74c3c);font-weight:600"
            onclick="return confirm('Seçili ürünleri silmek istediğinize emin misiniz?')">
            <i class="fas fa-trash"></i> Seçilenleri Sil
        </button>
    </div>

    <div class="admin-card" style="padding:0">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="selectAll" title="Tümünü Seç"></th>
                        <th>Resim</th>
                        <th>Ürün</th>
                        <th>Kategori</th>
                        <th>Fiyat</th>
                        <th>Stok</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><input type="checkbox" class="product-check" name="ids[]" value="<?= $p['id'] ?>"></td>
                            <td><img src="<?= e(getImageUrl($p['image'])) ?>" class="admin-product-img" alt=""></td>
                            <td><strong>
                                    <?= e(truncate($p['name'], 50)) ?>
                                </strong><br><span style="font-size:0.75rem;color:var(--admin-gray)">SKU:
                                    <?= e($p['sku'] ?: '-') ?>
                                </span></td>
                            <td>
                                <?= e($p['category_name'] ?: '-') ?>
                            </td>
                            <td>
                                <?php if ($p['discount_price']): ?>
                                    <span style="text-decoration:line-through;color:var(--admin-gray);font-size:0.75rem">
                                        <?= formatPrice($p['price']) ?>
                                    </span><br>
                                <?php endif; ?>
                                <strong>
                                    <?= formatPrice($p['discount_price'] ?: $p['price']) ?>
                                </strong>
                            </td>
                            <td>
                                <?= $p['stock'] <= 0 ? '<span class="admin-badge admin-badge-red">Tükendi</span>' : $p['stock'] ?>
                            </td>
                            <td>
                                <?= $p['status'] ? '<span class="admin-badge admin-badge-green">Aktif</span>' : '<span class="admin-badge admin-badge-red">Pasif</span>' ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($p['slug']) ?>" target="_blank"
                                    class="admin-btn admin-btn-outline admin-btn-sm" title="Görüntüle"><i
                                        class="fas fa-eye"></i></a>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="product_id"
                                        value="<?= $p['id'] ?>">
                                    <button class="admin-btn admin-btn-danger admin-btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form><!-- /bulkForm -->

<script>
    const selectAll = document.getElementById('selectAll');
    const bulkBar = document.getElementById('bulkBar');
    const selectedCount = document.getElementById('selectedCount');
    const checkboxes = () => document.querySelectorAll('.product-check');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.product-check:checked').length;
        selectedCount.textContent = checked;
        bulkBar.style.display = checked > 0 ? 'flex' : 'none';
        selectAll.checked = checked > 0 && checked === checkboxes().length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes().forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('product-check')) updateBulkBar();
    });
</script>

<!-- Add Product Modal -->
<div id="addModal" class="admin-modal-bg" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3><i class="fas fa-plus" style="color:var(--admin-primary)"></i> Yeni Ürün</h3><button
                class="admin-modal-close"
                onclick="document.getElementById('addModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="admin-modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group"><label>Ürün Adı *</label><input type="text" name="name" class="form-control"
                        required></div>
                <div class="form-row">
                    <div class="form-group"><label>Kategori *</label><select name="category_id" class="form-control"
                            required>
                            <option value="">Seçin</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label>Marka</label><input type="text" name="brand" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Fiyat *</label><input type="number" name="price" class="form-control"
                            step="0.01" required></div>
                    <div class="form-group"><label>İndirimli Fiyat</label><input type="number" name="discount_price"
                            class="form-control" step="0.01"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Stok *</label><input type="number" name="stock" class="form-control"
                            value="0" required></div>
                    <div class="form-group"><label>SKU</label><input type="text" name="sku" class="form-control"></div>
                </div>
                <div class="form-group"><label>Kısa Açıklama</label><input type="text" name="short_description"
                        class="form-control"></div>
                <div class="form-group"><label>Açıklama</label><textarea name="description"
                        class="form-control"></textarea></div>
                <div class="form-group"><label>Ürün Resmi</label><input type="file" name="image" class="form-control"
                        accept="image/*"></div>
                <div style="display:flex;gap:16px">
                    <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="featured"> Öne
                        Çıkan</label>
                    <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="status" checked>
                        Aktif</label>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="admin-btn admin-btn-outline"
                    onclick="document.getElementById('addModal').classList.remove('active')">İptal</button>
                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>