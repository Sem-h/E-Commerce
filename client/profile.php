<?php
$pageTitle = 'Profil';
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';
    if ($action === 'profile') {
        Database::query(
            "UPDATE users SET first_name=?, last_name=?, phone=?, email=? WHERE id=?",
            [trim($_POST['first_name']), trim($_POST['last_name']), trim($_POST['phone']), trim($_POST['email']), $_SESSION['user_id']]
        );
        flash('profile', 'Profil güncellendi.', 'success');
        redirect('/client/profile.php');
    }
    if ($action === 'password') {
        if (!password_verify($_POST['current_password'], $user['password'])) {
            flash('profile', 'Mevcut şifre hatalı.', 'error');
        } elseif ($_POST['new_password'] !== $_POST['new_password_confirm']) {
            flash('profile', 'Yeni şifreler eşleşmiyor.', 'error');
        } elseif (strlen($_POST['new_password']) < 6) {
            flash('profile', 'Yeni şifre en az 6 karakter olmalı.', 'error');
        } else {
            Database::query("UPDATE users SET password=? WHERE id=?", [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
            flash('profile', 'Şifre başarıyla değiştirildi.', 'success');
        }
        redirect('/client/profile.php');
    }
}
?>
<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="client-content">
            <?php showFlash('profile'); ?>
            <div class="card">
                <h3><i class="fas fa-user-cog" style="color:var(--primary)"></i> Profil Bilgileri</h3>
                <form method="POST"><input type="hidden" name="action" value="profile">
                    <div class="form-row">
                        <div class="form-group"><label>Ad</label><input type="text" name="first_name"
                                class="form-control" value="<?= e($user['first_name']) ?>" required></div>
                        <div class="form-group"><label>Soyad</label><input type="text" name="last_name"
                                class="form-control" value="<?= e($user['last_name']) ?>" required></div>
                    </div>
                    <div class="form-group"><label>E-posta</label><input type="email" name="email" class="form-control"
                            value="<?= e($user['email']) ?>" required></div>
                    <div class="form-group"><label>Telefon</label><input type="tel" name="phone" class="form-control"
                            value="<?= e($user['phone']) ?>"></div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                </form>
            </div>
            <div class="card">
                <h3><i class="fas fa-lock" style="color:var(--primary)"></i> Şifre Değiştir</h3>
                <form method="POST"><input type="hidden" name="action" value="password">
                    <div class="form-group"><label>Mevcut Şifre</label><input type="password" name="current_password"
                            class="form-control" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>Yeni Şifre</label><input type="password" name="new_password"
                                class="form-control" minlength="6" required></div>
                        <div class="form-group"><label>Yeni Şifre Tekrar</label><input type="password"
                                name="new_password_confirm" class="form-control" required></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Şifreyi Değiştir</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>