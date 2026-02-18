<?php
$pageTitle = 'Kullanıcı Yönetimi';
$adminPage = 'users';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id']);
        $newStatus = intval($_POST['new_status']);
        Database::query("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'", [$newStatus, $userId]);
        flash('admin_users', 'Kullanıcı durumu güncellendi.', 'success');
        redirect('/admin/users.php');
    }
    if ($action === 'delete') {
        $userId = intval($_POST['user_id']);
        Database::query("DELETE FROM users WHERE id = ? AND role != 'admin'", [$userId]);
        flash('admin_users', 'Kullanıcı silindi.', 'success');
        redirect('/admin/users.php');
    }
}

$users = Database::fetchAll("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count FROM users u ORDER BY u.created_at DESC");
?>
<div class="admin-header">
    <h1><i class="fas fa-users" style="color:var(--admin-primary)"></i> Kullanıcılar</h1>
</div>
<?php showFlash('admin_users'); ?>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="icon blue"><i class="fas fa-users"></i></div>
        <div>
            <h4>
                <?= count($users) ?>
            </h4><span>Toplam Kullanıcı</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon green"><i class="fas fa-user-check"></i></div>
        <div>
            <h4>
                <?= count(array_filter($users, fn($u) => $u['role'] === 'customer')) ?>
            </h4><span>Müşteri</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon purple"><i class="fas fa-user-shield"></i></div>
        <div>
            <h4>
                <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
            </h4><span>Admin</span>
        </div>
    </div>
</div>

<div class="admin-card" style="padding:0">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>E-posta</th>
                    <th>Rol</th>
                    <th>Siparişler</th>
                    <th>Kayıt</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong>
                                <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                            </strong><br><span style="font-size:0.75rem;color:var(--admin-gray)">@
                                <?= e($u['username']) ?>
                            </span></td>
                        <td>
                            <?= e($u['email']) ?>
                        </td>
                        <td><span class="admin-badge <?= $u['role'] === 'admin' ? 'admin-badge-purple' : 'admin-badge-blue' ?>">
                                <?= $u['role'] === 'admin' ? 'Admin' : 'Müşteri' ?>
                            </span></td>
                        <td>
                            <?= $u['order_count'] ?>
                        </td>
                        <td>
                            <?= date('d.m.Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td>
                            <?= $u['status'] ? '<span class="admin-badge admin-badge-green">Aktif</span>' : '<span class="admin-badge admin-badge-red">Pasif</span>' ?>
                        </td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" style="display:inline"><input type="hidden" name="action"
                                        value="toggle_status"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $u['status'] ? 0 : 1 ?>">
                                    <button class="admin-btn admin-btn-<?= $u['status'] ? 'warning' : 'success' ?> admin-btn-sm"><i
                                            class="fas fa-<?= $u['status'] ? 'ban' : 'check' ?>"></i></button>
                                </form>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('UYARI: Bu kullanıcıyı silmek istediğinize emin misiniz?')"><input
                                        type="hidden" name="action" value="delete"><input type="hidden" name="user_id"
                                        value="<?= $u['id'] ?>">
                                    <button class="admin-btn admin-btn-danger admin-btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            <?php else: ?><span style="font-size:0.75rem;color:var(--admin-gray)">Korumalı</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>