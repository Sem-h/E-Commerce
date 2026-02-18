<?php
$pageTitle = 'Adreslerim';
$activePage = 'addresses';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $data = [
            trim($_POST['title']),
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['phone']),
            trim($_POST['address_line']),
            trim($_POST['city']),
            trim($_POST['district']),
            trim($_POST['zip_code'])
        ];
        if ($action === 'add') {
            Database::query(
                "INSERT INTO addresses (user_id, title, first_name, last_name, phone, address_line, city, district, zip_code) VALUES (?,?,?,?,?,?,?,?,?)",
                array_merge([$_SESSION['user_id']], $data)
            );
            flash('address', 'Adres eklendi.', 'success');
        } else {
            Database::query(
                "UPDATE addresses SET title=?, first_name=?, last_name=?, phone=?, address_line=?, city=?, district=?, zip_code=? WHERE id=? AND user_id=?",
                array_merge($data, [intval($_POST['address_id']), $_SESSION['user_id']])
            );
            flash('address', 'Adres güncellendi.', 'success');
        }
        redirect('/client/addresses.php');
    }
    if ($action === 'delete') {
        Database::query("DELETE FROM addresses WHERE id=? AND user_id=?", [intval($_POST['address_id']), $_SESSION['user_id']]);
        flash('address', 'Adres silindi.', 'success');
        redirect('/client/addresses.php');
    }
}
$addresses = Database::fetchAll("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC", [$_SESSION['user_id']]);
?>
<div class="container">
    <div class="client-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <div class="client-content">
            <?php showFlash('address'); ?>
            <div class="card">
                <h3><i class="fas fa-map-marker-alt" style="color:var(--primary)"></i> Adreslerim
                    <button onclick="document.getElementById('addForm').style.display='block'"
                        class="btn btn-primary btn-sm" style="margin-left:auto;float:right"><i class="fas fa-plus"></i>
                        Yeni Adres</button>
                </h3>
                <!-- Add Form -->
                <div id="addForm"
                    style="display:none;margin-bottom:20px;padding:20px;background:var(--gray-50);border-radius:var(--radius)">
                    <form method="POST"><input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group"><label>Başlık</label><input type="text" name="title"
                                    class="form-control" placeholder="Ev, İş vb." required></div>
                            <div class="form-group"><label>Ad</label><input type="text" name="first_name"
                                    class="form-control" required></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Soyad</label><input type="text" name="last_name"
                                    class="form-control" required></div>
                            <div class="form-group"><label>Telefon</label><input type="tel" name="phone"
                                    class="form-control" required></div>
                        </div>
                        <div class="form-group"><label>Adres</label><textarea name="address_line" class="form-control"
                                required></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>İl</label><input type="text" name="city" class="form-control"
                                    required></div>
                            <div class="form-group"><label>İlçe</label><input type="text" name="district"
                                    class="form-control"></div>
                        </div>
                        <div class="form-group"><label>Posta Kodu</label><input type="text" name="zip_code"
                                class="form-control"></div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
                        <button type="button" onclick="document.getElementById('addForm').style.display='none'"
                            class="btn btn-outline-primary">İptal</button>
                    </form>
                </div>

                <?php if (empty($addresses)): ?>
                    <div class="empty-state" style="padding:24px"><i class="fas fa-map-marker-alt"></i>
                        <p>Henüz kayıtlı adresiniz yok.</p>
                    </div>
                <?php else:
                    foreach ($addresses as $addr): ?>
                        <div
                            style="padding:16px;border:1px solid var(--gray-200);border-radius:var(--radius);margin-bottom:12px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                                <strong>
                                    <?= e($addr['title']) ?>
                                </strong>
                                <form method="POST" style="display:inline"><input type="hidden" name="action"
                                        value="delete"><input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                    <button class="btn btn-sm" style="color:var(--danger);background:none"
                                        onclick="return confirm('Silmek istediğinize emin misiniz?')"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </div>
                            <p style="font-size:0.875rem;color:var(--dark-600)">
                                <?= e($addr['first_name']) ?>
                                <?= e($addr['last_name']) ?> -
                                <?= e($addr['phone']) ?>
                            </p>
                            <p style="font-size:0.875rem;color:var(--gray)">
                                <?= e($addr['address_line']) ?>,
                                <?= e($addr['district']) ?>
                                <?= e($addr['city']) ?>
                                <?= e($addr['zip_code']) ?>
                            </p>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>