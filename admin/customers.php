<?php
$pageTitle = 'Müşteri Yönetimi';
$adminPage = 'customers';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id']);
        $newStatus = intval($_POST['new_status']);
        Database::query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        flash('admin_customers', 'Müşteri durumu güncellendi.', 'success');
        redirect('/admin/customers.php');
    }
    if ($action === 'delete') {
        $userId = intval($_POST['user_id']);
        Database::query("DELETE FROM users WHERE id = ? AND role = 'customer'", [$userId]);
        flash('admin_customers', 'Müşteri silindi.', 'success');
        redirect('/admin/customers.php');
    }
}

$customers = Database::fetchAll(
    "SELECT u.*,
        (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
        (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id) as total_spent,
        (SELECT MAX(created_at) FROM orders WHERE user_id = u.id) as last_order_date
     FROM users u WHERE u.role = 'customer' ORDER BY u.created_at DESC"
);

$totalCustomers = count($customers);
$activeCustomers = count(array_filter($customers, fn($c) => $c['status']));
$totalRevenue = array_sum(array_column($customers, 'total_spent'));
$withOrders = count(array_filter($customers, fn($c) => $c['order_count'] > 0));
?>
<div class="admin-header">
    <h1><i class="fas fa-users" style="color:var(--admin-primary)"></i> Müşteriler</h1>
</div>
<?php showFlash('admin_customers'); ?>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="icon blue"><i class="fas fa-users"></i></div>
        <div><h4><?= $totalCustomers ?></h4><span>Toplam Müşteri</span></div>
    </div>
    <div class="admin-stat">
        <div class="icon green"><i class="fas fa-user-check"></i></div>
        <div><h4><?= $activeCustomers ?></h4><span>Aktif</span></div>
    </div>
    <div class="admin-stat">
        <div class="icon orange"><i class="fas fa-shopping-bag"></i></div>
        <div><h4><?= $withOrders ?></h4><span>Sipariş Veren</span></div>
    </div>
    <div class="admin-stat">
        <div class="icon purple"><i class="fas fa-lira-sign"></i></div>
        <div><h4><?= formatPrice($totalRevenue) ?></h4><span>Toplam Ciro</span></div>
    </div>
</div>

<!-- Arama -->
<div class="admin-card" style="padding:12px 16px;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:10px">
        <i class="fas fa-search" style="color:var(--admin-gray)"></i>
        <input type="text" id="customerSearch" class="form-control" placeholder="Müşteri ara (ad, e-posta, kullanıcı adı)..." style="border:none;box-shadow:none;padding:6px 0;flex:1" oninput="filterCustomers(this.value)">
    </div>
</div>

<div class="admin-card" style="padding:0">
    <div class="admin-table-wrapper">
        <table class="admin-table" id="customerTable">
            <thead>
                <tr>
                    <th>Müşteri</th>
                    <th>E-posta</th>
                    <th>Siparişler</th>
                    <th>Toplam Harcama</th>
                    <th>Son Sipariş</th>
                    <th>Kayıt</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--admin-gray)">
                        <i class="fas fa-users" style="font-size:2rem;margin-bottom:8px;display:block;opacity:.3"></i>
                        Henüz müşteri bulunmuyor.
                    </td></tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr class="customer-row" data-search="<?= e(strtolower($c['first_name'] . ' ' . $c['last_name'] . ' ' . $c['email'] . ' ' . $c['username'])) ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#06b6d4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem">
                                    <?= strtoupper(mb_substr($c['first_name'] ?? 'M', 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong><br>
                                    <span style="font-size:0.75rem;color:var(--admin-gray)">@<?= e($c['username']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:0.85rem"><?= e($c['email']) ?></td>
                        <td>
                            <?php if ($c['order_count'] > 0): ?>
                                <span class="admin-badge admin-badge-blue"><?= $c['order_count'] ?> sipariş</span>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--admin-gray)">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['total_spent'] > 0): ?>
                                <strong style="color:#059669"><?= formatPrice($c['total_spent']) ?></strong>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--admin-gray)">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8rem">
                            <?= $c['last_order_date'] ? date('d.m.Y', strtotime($c['last_order_date'])) : '<span style="color:var(--admin-gray)">—</span>' ?>
                        </td>
                        <td style="font-size:0.8rem"><?= date('d.m.Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <?= $c['status'] ? '<span class="admin-badge admin-badge-green">Aktif</span>' : '<span class="admin-badge admin-badge-red">Pasif</span>' ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $c['status'] ? 0 : 1 ?>">
                                <button class="admin-btn admin-btn-<?= $c['status'] ? 'warning' : 'success' ?> admin-btn-sm" title="<?= $c['status'] ? 'Pasifleştir' : 'Aktifleştir' ?>">
                                    <i class="fas fa-<?= $c['status'] ? 'ban' : 'check' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Bu müşteriyi silmek istediğinize emin misiniz?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                <button class="admin-btn admin-btn-danger admin-btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterCustomers(query) {
    query = query.toLowerCase().trim();
    document.querySelectorAll('.customer-row').forEach(row => {
        const searchData = row.getAttribute('data-search');
        row.style.display = !query || searchData.includes(query) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
