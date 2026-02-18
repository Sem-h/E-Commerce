<?php
require_once __DIR__ . '/../config/config.php';
if (isLoggedIn()) {
    redirect('/client/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];

    $errors = [];
    if (empty($data['username']))
        $errors[] = 'Kullanıcı adı gerekli.';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Geçerli bir e-posta adresi girin.';
    if (strlen($data['password']) < 6)
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    if ($data['password'] !== $data['password_confirm'])
        $errors[] = 'Şifreler eşleşmiyor.';

    // Benzersizlik kontrolü
    if (Database::fetch("SELECT id FROM users WHERE username = ?", [$data['username']]))
        $errors[] = 'Bu kullanıcı adı zaten kullanılıyor.';
    if (Database::fetch("SELECT id FROM users WHERE email = ?", [$data['email']]))
        $errors[] = 'Bu e-posta adresi zaten kayıtlı.';

    if (!empty($errors)) {
        flash('register', implode('<br>', $errors), 'error');
    } else {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        Database::query(
            "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'customer', 1)",
            [$data['username'], $data['email'], $hashedPassword, $data['first_name'], $data['last_name'], $data['phone']]
        );
        flash('login', 'Hesabınız oluşturuldu! Giriş yapabilirsiniz.', 'success');
        redirect('/client/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - V-Commerce</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width:500px">
            <a href="<?= BASE_URL ?>/" class="logo">
                <div class="logo-icon">V</div>
                V-Commerce
            </a>
            <h2>Yeni Hesap Oluşturun</h2>

            <?php showFlash('register'); ?>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label>Ad *</label><input type="text" name="first_name" class="form-control"
                            required></div>
                    <div class="form-group"><label>Soyad *</label><input type="text" name="last_name"
                            class="form-control" required></div>
                </div>
                <div class="form-group"><label>Kullanıcı Adı *</label><input type="text" name="username"
                        class="form-control" required></div>
                <div class="form-group"><label>E-posta *</label><input type="email" name="email" class="form-control"
                        required></div>
                <div class="form-group"><label>Telefon</label><input type="tel" name="phone" class="form-control"
                        placeholder="05xx xxx xx xx"></div>
                <div class="form-row">
                    <div class="form-group"><label>Şifre *</label><input type="password" name="password"
                            class="form-control" minlength="6" required></div>
                    <div class="form-group"><label>Şifre Tekrar *</label><input type="password" name="password_confirm"
                            class="form-control" required></div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-user-plus"></i> Kayıt
                    Ol</button>
            </form>

            <div class="divider">Zaten hesabınız var mı?</div>
            <a href="<?= BASE_URL ?>/client/login.php" class="btn btn-outline-primary btn-block">Giriş Yap</a>
        </div>
    </div>
</body>

</html>