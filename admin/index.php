<?php
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$stats = getStats();
$recentOrders = Database::fetchAll("SELECT o.*, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$statuses = ['pending' => 'Beklemede', 'processing' => 'İşleniyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal'];
$statusBadge = ['pending' => 'yellow', 'processing' => 'blue', 'shipped' => 'purple', 'delivered' => 'green', 'cancelled' => 'red'];
?>

<div class="admin-header">
    <h1><i class="fas fa-home" style="color:var(--admin-primary)"></i> Dashboard</h1>
    <span style="font-size:0.875rem;color:var(--admin-gray)">Hoş geldiniz,
        <?= e($adminUser['first_name'] ?? 'Admin') ?>!
    </span>
</div>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="icon blue"><i class="fas fa-shopping-bag"></i></div>
        <div>
            <h4>
                <?= $stats['total_orders'] ?? 0 ?>
            </h4><span>Toplam Sipariş</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon green"><i class="fas fa-lira-sign"></i></div>
        <div>
            <h4>
                <?= formatPrice($stats['total_revenue'] ?? 0) ?>
            </h4><span>Toplam Gelir</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon orange"><i class="fas fa-box"></i></div>
        <div>
            <h4>
                <?= $stats['total_products'] ?? 0 ?>
            </h4><span>Toplam Ürün</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon purple"><i class="fas fa-users"></i></div>
        <div>
            <h4>
                <?= $stats['total_users'] ?? 0 ?>
            </h4><span>Toplam Kullanıcı</span>
        </div>
    </div>
</div>

<div class="admin-card">
    <h3><i class="fas fa-clock"></i> Son Siparişler</h3>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Tutar</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:var(--admin-gray)">Henüz sipariş
                            bulunmuyor.</td>
                    </tr>
                <?php else:
                    foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><strong>
                                    <?= e($o['order_number']) ?>
                                </strong></td>
                            <td>
                                <?= e($o['first_name'] . ' ' . $o['last_name']) ?>
                            </td>
                            <td><strong>
                                    <?= formatPrice($o['total']) ?>
                                </strong></td>
                            <td>
                                <?= $o['payment_method'] === 'kapida_odeme' ? 'Kapıda' : ($o['payment_method'] === 'havale' ? 'Havale' : 'PayTR') ?>
                            </td>
                            <td><span class="admin-badge admin-badge-<?= $statusBadge[$o['status']] ?? 'blue' ?>">
                                    <?= $statuses[$o['status']] ?? $o['status'] ?>
                                </span></td>
                            <td>
                                <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>