<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) {
    redirect('/client/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        flash('login', 'Lütfen tüm alanları doldurun.', 'error');
    } else {
        $user = Database::fetch("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 1", [$username, $username]);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                flash('login', 'Admin girişi için admin panelini kullanın.', 'error');
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                mergeCartOnLogin($user['id']);
                flash('dashboard', 'Hoş geldiniz, ' . $user['first_name'] . '!', 'success');
                redirect('/client/');
            }
        } else {
            flash('login', 'Hatalı kullanıcı adı veya şifre.', 'error');
        }
    }
}

$pageTitle = 'Giriş Yap';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - V-Commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <a href="<?= BASE_URL ?>/" class="logo">
                <div class="logo-icon">V</div>
                V-Commerce
            </a>
            <h2>Hesabınıza Giriş Yapın</h2>

            <?php showFlash('login'); ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Kullanıcı Adı veya E-posta</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-sign-in-alt"></i> Giriş
                    Yap</button>
            </form>

            <div class="divider">Hesabınız yok mu?</div>
            <a href="<?= BASE_URL ?>/client/register.php" class="btn btn-outline-primary btn-block">Kayıt Ol</a>
            <div style="text-align:center;margin-top:16px">
                <a href="<?= BASE_URL ?>/" style="font-size:0.875rem;color:var(--gray)"><i
                        class="fas fa-arrow-left"></i> Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>

</html>