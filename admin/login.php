<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn() && isAdmin()) {
    redirect('/admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = Database::fetch("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin' AND status = 1", [$username, $username]);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        redirect('/admin/');
    } else {
        $error = 'Hatalı kullanıcı adı veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - V-Commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
</head>

<body>
    <div class="auth-wrapper" style="background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#1a56db 100%)">
        <div class="auth-card">
            <div class="logo" style="justify-content:center">
                <div class="logo-icon">V</div> V-Commerce
            </div>
            <h2><i class="fas fa-shield-alt" style="color:var(--primary)"></i> Admin Paneli</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group"><label><i class="fas fa-user"></i> Kullanıcı Adı</label><input type="text"
                        name="username" class="form-control" required autofocus></div>
                <div class="form-group"><label><i class="fas fa-lock"></i> Şifre</label><input type="password"
                        name="password" class="form-control" required></div>
                <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-sign-in-alt"></i> Giriş
                    Yap</button>
            </form>
            <div style="text-align:center;margin-top:16px"><a href="<?= BASE_URL ?>/"
                    style="font-size:0.875rem;color:var(--gray)"><i class="fas fa-arrow-left"></i> Siteye Dön</a></div>
        </div>
    </div>
</body>

</html>