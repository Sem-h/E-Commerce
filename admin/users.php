<?php
$pageTitle = 'Kullanıcı Yönetimi';
$adminPage = 'users';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id']);
        $newStatus = intval($_POST['new_status']);
        Database::query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        flash('admin_users', 'Kullanıcı durumu güncellendi.', 'success');
        redirect('/admin/users.php');
    }
    if ($action === 'delete') {
        $userId = intval($_POST['user_id']);
        Database::query("DELETE FROM users WHERE id = ? AND id != ?", [$userId, $_SESSION['user_id']]);
        flash('admin_users', 'Kullanıcı silindi.', 'success');
        redirect('/admin/users.php');
    }
    if ($action === 'add_admin') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($username && $email && $pass) {
            $exists = Database::fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($exists) {
                flash('admin_users', 'Bu kullanıcı adı veya e-posta zaten kayıtlı.', 'error');
            } else {
                Database::query(
                    "INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES (?,?,?,?,?,'admin',1)",
                    [$username, $email, password_hash($pass, PASSWORD_DEFAULT), $first, $last]
                );
                flash('admin_users', 'Yeni yönetici eklendi.', 'success');
            }
        } else {
            flash('admin_users', 'Tüm alanları doldurun.', 'error');
        }
        redirect('/admin/users.php');
    }
}

$admins = Database::fetchAll("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
?>
<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center">
    <h1><i class="fas fa-user-shield" style="color:var(--admin-primary)"></i> Yönetici Kullanıcılar</h1>
    <button onclick="document.getElementById('addAdminModal').style.display='flex'" class="admin-btn admin-btn-primary">
        <i class="fas fa-plus"></i> Yeni Yönetici Ekle
    </button>
</div>
<?php showFlash('admin_users'); ?>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="icon purple"><i class="fas fa-user-shield"></i></div>
        <div>
            <h4><?= count($admins) ?></h4><span>Toplam Yönetici</span>
        </div>
    </div>
    <div class="admin-stat">
        <div class="icon green"><i class="fas fa-user-check"></i></div>
        <div>
            <h4><?= count(array_filter($admins, fn($u) => $u['status'])) ?></h4><span>Aktif</span>
        </div>
    </div>
</div>

<div class="admin-card" style="padding:0">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Yönetici</th>
                    <th>E-posta</th>
                    <th>Kayıt Tarihi</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $u): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div
                                    style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem">
                                    <?= strtoupper(mb_substr($u['first_name'] ?? 'A', 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><br>
                                    <span style="font-size:0.75rem;color:var(--admin-gray)">@<?= e($u['username']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?= $u['status'] ? '<span class="admin-badge admin-badge-green">Aktif</span>' : '<span class="admin-badge admin-badge-red">Pasif</span>' ?>
                        </td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline"><input type="hidden" name="action"
                                        value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $u['status'] ? 0 : 1 ?>">
                                    <button class="admin-btn admin-btn-<?= $u['status'] ? 'warning' : 'success' ?> admin-btn-sm"
                                        title="<?= $u['status'] ? 'Pasifleştir' : 'Aktifleştir' ?>">
                                        <i class="fas fa-<?= $u['status'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Bu yöneticiyi silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button class="admin-btn admin-btn-danger admin-btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:0.75rem;color:var(--admin-gray)"><i class="fas fa-lock"></i> Siz</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Yeni Yönetici Modal -->
<div id="addAdminModal"
    style="display:none;position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div
        style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 style="margin:0"><i class="fas fa-user-plus" style="color:var(--admin-primary)"></i> Yeni Yönetici</h3>
            <button onclick="document.getElementById('addAdminModal').style.display='none'"
                style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9ca3af">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_admin">
            <div style="display:grid;gap:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div><label style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:4px">Ad</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div><label style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:4px">Soyad</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div><label style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:4px">Kullanıcı
                        Adı</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div><label style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:4px">E-posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div><label style="display:block;font-size:0.8rem;font-weight:600;margin-bottom:4px">Şifre</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;margin-top:16px;padding:10px">
                <i class="fas fa-plus"></i> Yönetici Ekle
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>