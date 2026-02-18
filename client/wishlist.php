<?php
$pageTitle = 'Favorilerim';
$activePage = 'wishlist';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    Database::query("DELETE FROM wishlist WHERE id=? AND user_id=?", [intval($_POST['remove_id']), $_SESSION['user_id']]);
    flash('wishlist', 'Ürün favorilerden çıkarıldı.', 'success');
    redirect('/client/wishlist.php');
}

$wishlist = Database::fetchAll(
    "SELECT w.*, p.name, p.slug, p.price, p.discount_price, p.image, p.stock FROM wishlist w
     JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC",
    [$_SESSION['user_id']]
);
?>
<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="client-content">
            <?php showFlash('wishlist'); ?>
            <div class="card">
                <h3><i class="fas fa-heart" style="color:var(--danger)"></i> Favorilerim</h3>
                <?php if (empty($wishlist)): ?>
                    <div class="empty-state" style="padding:24px"><i class="fas fa-heart"></i>
                        <p>Favori listeniz boş.</p>
                        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-sm">Ürünleri İncele</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($wishlist as $item):
                            $product = [
                                'id' => $item['product_id'],
                                'name' => $item['name'],
                                'slug' => $item['slug'],
                                'price' => $item['price'],
                                'discount_price' => $item['discount_price'],
                                'image' => $item['image'],
                                'stock' => $item['stock'],
                                'category_name' => '',
                                'short_description' => '',
                                'created_at' => $item['created_at']
                            ];
                            include __DIR__ . '/../includes/product-card.php';
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>