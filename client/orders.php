<?php
$pageTitle = 'Siparişlerim';
$activePage = 'orders';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();
$orders = Database::fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$_SESSION['user_id']]);
$statuses = ['pending' => 'Beklemede', 'processing' => 'İşleniyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal'];
?>
<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="client-content">
            <?php showFlash('order_success'); ?>
            <div class="card">
                <h3><i class="fas fa-shopping-bag" style="color:var(--primary)"></i> Siparişlerim</h3>
                <?php if (empty($orders)): ?>
                    <div class="empty-state" style="padding:24px"><i class="fas fa-shopping-bag"></i>
                        <p>Henüz siparişiniz yok.</p>
                        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-sm">Alışverişe Başla</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Ürünler</th>
                                    <th>Tutar</th>
                                    <th>Ödeme</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order):
                                    $items = Database::fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
                                    ?>
                                    <tr>
                                        <td><strong>
                                                <?= e($order['order_number']) ?>
                                            </strong></td>
                                        <td>
                                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php foreach ($items as $i): ?>
                                                <div style="font-size:0.8rem">
                                                    <?= e(truncate($i['product_name'], 40)) ?> x
                                                    <?= $i['quantity'] ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><strong>
                                                <?= formatPrice($order['total']) ?>
                                            </strong></td>
                                        <td><span style="font-size:0.8rem">
                                                <?= $order['payment_method'] === 'kapida_odeme' ? 'Kapıda Ödeme' : ($order['payment_method'] === 'havale' ? 'Havale/EFT' : 'PayTR') ?>
                                            </span></td>
                                        <td><span class="status status-<?= $order['status'] ?>">
                                                <?= $statuses[$order['status']] ?? $order['status'] ?>
                                            </span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>