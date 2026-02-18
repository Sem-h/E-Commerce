<?php
$pageTitle = 'Fiyat Uyarılarım';
$activePage = 'price_alerts';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    Database::query("DELETE FROM price_alerts WHERE id=? AND user_id=?", [intval($_POST['remove_id']), $_SESSION['user_id']]);
    flash('price_alert', 'Fiyat uyarısı kaldırıldı.', 'success');
    redirect('/client/price-alerts.php');
}

$alerts = [];
try {
    $alerts = Database::fetchAll(
        "SELECT pa.*, p.name, p.slug, p.price, p.discount_price, p.image, p.stock
         FROM price_alerts pa
         JOIN products p ON pa.product_id = p.id
         WHERE pa.user_id = ?
         ORDER BY pa.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
}
?>
<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="client-content">
            <?php showFlash('price_alert'); ?>
            <div class="card">
                <h3><i class="fas fa-bell" style="color:#f59e0b"></i> Fiyat Uyarılarım
                    <span style="font-size:0.8rem;color:var(--gray);font-weight:400;margin-left:8px">(
                        <?= count($alerts) ?> ürün)
                    </span>
                </h3>
                <?php if (empty($alerts)): ?>
                    <div class="empty-state" style="padding:24px"><i class="fas fa-bell-slash"></i>
                        <p>Henüz fiyat uyarısı eklemediniz.</p>
                        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-sm">Ürünleri İncele</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex;flex-direction:column;gap:12px">
                        <?php foreach ($alerts as $alert):
                            $currentPrice = $alert['discount_price'] ?: $alert['price'];
                            $priceChanged = $currentPrice < $alert['original_price'];
                            $priceDiff = $priceChanged ? $alert['original_price'] - $currentPrice : 0;
                            $diffPercent = $priceChanged ? round($priceDiff / $alert['original_price'] * 100) : 0;
                            ?>
                            <div
                                style="display:flex;align-items:center;gap:16px;padding:16px;border:1px solid <?= $priceChanged ? '#bbf7d0' : '#e5e7eb' ?>;border-radius:var(--radius);background:<?= $priceChanged ? '#f0fdf4' : '#fff' ?>">
                                <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($alert['slug']) ?>"
                                    style="flex-shrink:0">
                                    <img src="<?= e(getImageUrl($alert['image'])) ?>" alt=""
                                        style="width:64px;height:64px;object-fit:contain;border-radius:8px;border:1px solid #f3f4f6">
                                </a>
                                <div style="flex:1;min-width:0">
                                    <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($alert['slug']) ?>"
                                        style="color:var(--dark);font-weight:600;font-size:0.9rem;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?= e(html_entity_decode($alert['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                                    </a>
                                    <div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap">
                                        <span style="font-size:0.8rem;color:var(--gray)">
                                            Kayıt fiyatı: <b>
                                                <?= formatPrice($alert['original_price']) ?>
                                            </b>
                                        </span>
                                        <span style="font-size:0.8rem">→</span>
                                        <span
                                            style="font-size:0.9rem;font-weight:700;color:<?= $priceChanged ? '#059669' : 'var(--dark)' ?>">
                                            <?= formatPrice($currentPrice) ?>
                                        </span>
                                        <?php if ($priceChanged): ?>
                                            <span
                                                style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:0.7rem;font-weight:600">
                                                <i class="fas fa-arrow-down"></i> %
                                                <?= $diffPercent ?> düştü!
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--gray);margin-top:4px">
                                        <i class="fas fa-clock"></i>
                                        <?= date('d.m.Y H:i', strtotime($alert['created_at'])) ?>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;flex-shrink:0">
                                    <?php if ($priceChanged): ?>
                                        <a href="<?= BASE_URL ?>/product-detail.php?slug=<?= e($alert['slug']) ?>"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-cart-plus"></i> Satın Al
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="remove_id" value="<?= $alert['id'] ?>">
                                        <button class="btn btn-sm"
                                            style="background:none;color:var(--danger);border:1px solid #fecaca;padding:6px 10px;border-radius:6px"
                                            title="Uyarıyı Kaldır">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>