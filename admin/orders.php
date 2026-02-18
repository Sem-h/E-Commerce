<?php
$pageTitle = 'Sipariş Yönetimi';
$adminPage = 'orders';
require_once __DIR__ . '/includes/header.php';

$statuses = ['pending' => 'Beklemede', 'processing' => 'İşleniyor', 'shipped' => 'Kargoda', 'delivered' => 'Teslim Edildi', 'cancelled' => 'İptal'];
$statusBadge = ['pending' => 'yellow', 'processing' => 'blue', 'shipped' => 'purple', 'delivered' => 'green', 'cancelled' => 'red'];

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    Database::query("UPDATE orders SET status = ? WHERE id = ?", [$_POST['new_status'], intval($_POST['order_id'])]);
    flash('admin_orders', 'Sipariş durumu güncellendi.', 'success');
    redirect('/admin/orders.php');
}

$filterStatus = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($filterStatus && array_key_exists($filterStatus, $statuses)) {
    $where .= ' AND o.status = ?';
    $params[] = $filterStatus;
}
$search = trim($_GET['q'] ?? '');
if ($search) {
    $where .= ' AND (o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$orders = Database::fetchAll("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.created_at DESC", $params);
?>

<div class="admin-header">
    <h1><i class="fas fa-shopping-bag" style="color:var(--admin-primary)"></i> Sipariş Yönetimi</h1>
</div>

<?php showFlash('admin_orders'); ?>

<div class="admin-toolbar">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="?status="
            class="admin-btn <?= !$filterStatus ? 'admin-btn-primary' : 'admin-btn-outline' ?> admin-btn-sm">Tümü</a>
        <?php foreach ($statuses as $k => $v):
            $cnt = Database::fetch("SELECT COUNT(*) as c FROM orders WHERE status = ?", [$k])['c'];
            ?>
            <a href="?status=<?= $k ?>"
                class="admin-btn <?= $filterStatus == $k ? 'admin-btn-primary' : 'admin-btn-outline' ?> admin-btn-sm">
                <?= $v ?> (
                <?= $cnt ?>)
            </a>
        <?php endforeach; ?>
    </div>
    <div class="admin-search"><i class="fas fa-search"></i>
        <form method="GET"><input type="text" name="q" placeholder="Sipariş ara..." value="<?= e($search) ?>">
            <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= e($filterStatus) ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="admin-card" style="padding:0">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Ürünler</th>
                    <th>Tutar</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:var(--admin-gray)">Sipariş bulunamadı.
                        </td>
                    </tr>
                <?php else:
                    foreach ($orders as $o):
                        $items = Database::fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$o['id']]);
                        ?>
                        <tr>
                            <td><strong>
                                    <?= e($o['order_number']) ?>
                                </strong></td>
                            <td>
                                <?= e($o['first_name'] . ' ' . $o['last_name']) ?><br><span
                                    style="font-size:0.75rem;color:var(--admin-gray)">
                                    <?= e($o['email']) ?>
                                </span>
                            </td>
                            <td>
                                <?php foreach ($items as $i): ?>
                                    <div style="font-size:0.8rem">
                                        <?= e(truncate($i['product_name'], 35)) ?> x
                                        <?= $i['quantity'] ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td><strong>
                                    <?= formatPrice($o['total']) ?>
                                </strong></td>
                            <td><span style="font-size:0.8rem">
                                    <?= $o['payment_method'] === 'kapida_odeme' ? 'Kapıda' : ($o['payment_method'] === 'havale' ? 'Havale' : 'PayTR') ?>
                                </span></td>
                            <td><span class="admin-badge admin-badge-<?= $statusBadge[$o['status']] ?? 'blue' ?>">
                                    <?= $statuses[$o['status']] ?? $o['status'] ?>
                                </span></td>
                            <td>
                                <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?>
                            </td>
                            <td>
                                <form method="POST" style="display:flex;gap:4px">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="new_status" class="form-control"
                                        style="padding:4px 8px;font-size:0.75rem;width:auto" onchange="this.form.submit()">
                                        <?php foreach ($statuses as $sk => $sv): ?>
                                            <option value="<?= $sk ?>" <?= $o['status'] == $sk ? 'selected' : '' ?>>
                                                <?= $sv ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>