<?php
$pageTitle = 'Hesabım';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();
$recentOrders = Database::fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$_SESSION['user_id']]);
$orderCount = Database::fetch("SELECT COUNT(*) as c FROM orders WHERE user_id = ?", [$_SESSION['user_id']])['c'];
$wishlistCount = Database::fetch("SELECT COUNT(*) as c FROM wishlist WHERE user_id = ?", [$_SESSION['user_id']])['c'];
$activePage = 'dashboard';
?>

<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="client-content">
            <?php showFlash('dashboard');
            showFlash('order_success'); ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info">
                        <h4>
                            <?= $orderCount ?>
                        </h4><span>Toplam Sipariş</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-heart"></i></div>
                    <div class="stat-info">
                        <h4>
                            <?= $wishlistCount ?>
                        </h4><span>Favoriler</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-clock" style="color:var(--primary)"></i> Son Siparişler</h3>
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state" style="padding:24px">
                        <i class="fas fa-shopping-bag"></i>
                        <p>Henüz siparişiniz bulunmuyor.</p>
                        <a href="<?= BASE_URL ?>/products.php" class="btn btn-primary btn-sm">Alışverişe Başla</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong>
                                                <?= e($order['order_number']) ?>
                                            </strong></td>
                                        <td>
                                            <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                        </td>
                                        <td><strong>
                                                <?= formatPrice($order['total']) ?>
                                            </strong></td>
                                        <td><span class="status status-<?= $order['status'] ?>">
                                                <?php
                                                $statuses = ['pending' => 'Beklemede', 'processing' => 'İşleniyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal'];
                                                echo $statuses[$order['status']] ?? $order['status'];
                                                ?>
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