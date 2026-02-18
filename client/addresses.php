<?php
$pageTitle = 'Adreslerim';
$activePage = 'addresses';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();

// Mahalle kolonunu ekle (yoksa)
try {
    Database::query("ALTER TABLE addresses ADD COLUMN neighborhood VARCHAR(100) DEFAULT '' AFTER district");
} catch (Exception $e) {
}

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
            trim($_POST['neighborhood'] ?? ''),
            trim($_POST['zip_code'])
        ];
        if ($action === 'add') {
            Database::query(
                "INSERT INTO addresses (user_id, title, first_name, last_name, phone, address_line, city, district, neighborhood, zip_code) VALUES (?,?,?,?,?,?,?,?,?,?)",
                array_merge([$_SESSION['user_id']], $data)
            );
            flash('address', 'Adres eklendi.', 'success');
        } else {
            Database::query(
                "UPDATE addresses SET title=?, first_name=?, last_name=?, phone=?, address_line=?, city=?, district=?, neighborhood=?, zip_code=? WHERE id=? AND user_id=?",
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
                        <div class="form-row">
                            <div class="form-group">
                                <label>İl *</label>
                                <select name="city" id="addCity" class="form-control" required>
                                    <option value="">İl seçiniz...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>İlçe *</label>
                                <select name="district" id="addDistrict" class="form-control" required>
                                    <option value="">Önce il seçiniz...</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Mahalle/Cadde</label>
                                <input type="text" name="neighborhood" class="form-control"
                                    placeholder="Mahalle veya cadde adı">
                            </div>
                            <div class="form-group"><label>Posta Kodu</label><input type="text" name="zip_code"
                                    class="form-control"></div>
                        </div>
                        <div class="form-group"><label>Adres Detayı *</label><textarea name="address_line"
                                class="form-control" placeholder="Sokak, cadde, bina no, daire no..."
                                required></textarea></div>
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
                    foreach ($addresses as $idx => $addr): ?>
                        <div
                            style="padding:16px;border:1px solid var(--gray-200);border-radius:var(--radius);margin-bottom:12px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                                <strong>
                                    <?= e($addr['title']) ?>
                                </strong>
                                <div style="display:flex;gap:6px;align-items:center">
                                    <button class="btn btn-sm" style="background:none;color:var(--primary);padding:6px"
                                        onclick="toggleEditForm(<?= $idx ?>)" title="Düzenle"><i
                                            class="fas fa-pen"></i></button>
                                    <form method="POST" style="display:inline"><input type="hidden" name="action"
                                            value="delete"><input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                        <button class="btn btn-sm" style="color:var(--danger);background:none;padding:6px"
                                            onclick="return confirm('Silmek istediğinize emin misiniz?')"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <p style="font-size:0.875rem;color:var(--dark-600)">
                                <?= e($addr['first_name']) ?>
                                <?= e($addr['last_name']) ?> -
                                <?= e($addr['phone']) ?>
                            </p>
                            <p style="font-size:0.875rem;color:var(--gray)">
                                <?= e($addr['address_line']) ?>,
                                <?php if (!empty($addr['neighborhood'])): ?>             <?= e($addr['neighborhood']) ?> Mah.,
                                <?php endif; ?>
                                <?= e($addr['district']) ?>/<?= e($addr['city']) ?>
                                <?= e($addr['zip_code']) ?>
                            </p>

                            <!-- Edit Form (hidden) -->
                            <div id="editForm_<?= $idx ?>"
                                style="display:none;margin-top:12px;padding:16px;background:var(--gray-50);border-radius:var(--radius)">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
                                    <div class="form-row">
                                        <div class="form-group"><label>Başlık</label><input type="text" name="title"
                                                class="form-control" value="<?= e($addr['title']) ?>" required></div>
                                        <div class="form-group"><label>Ad</label><input type="text" name="first_name"
                                                class="form-control" value="<?= e($addr['first_name']) ?>" required></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group"><label>Soyad</label><input type="text" name="last_name"
                                                class="form-control" value="<?= e($addr['last_name']) ?>" required></div>
                                        <div class="form-group"><label>Telefon</label><input type="tel" name="phone"
                                                class="form-control" value="<?= e($addr['phone']) ?>" required></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>İl *</label>
                                            <select name="city" id="editCity_<?= $idx ?>" class="form-control" required>
                                                <option value="">İl seçiniz...</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>İlçe *</label>
                                            <select name="district" id="editDistrict_<?= $idx ?>" class="form-control" required>
                                                <option value="">Önce il seçiniz...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Mahalle/Cadde</label>
                                            <input type="text" name="neighborhood" class="form-control"
                                                value="<?= e($addr['neighborhood'] ?? '') ?>">
                                        </div>
                                        <div class="form-group"><label>Posta Kodu</label><input type="text" name="zip_code"
                                                class="form-control" value="<?= e($addr['zip_code']) ?>"></div>
                                    </div>
                                    <div class="form-group"><label>Adres Detayı *</label><textarea name="address_line"
                                            class="form-control" required><?= e($addr['address_line']) ?></textarea></div>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i>
                                        Güncelle</button>
                                    <button type="button" onclick="toggleEditForm(<?= $idx ?>)"
                                        class="btn btn-outline-primary btn-sm">İptal</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/address-selector.js"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';

    // Add form selector
    initAddressSelector('addCity', 'addDistrict');

    // Edit form selectors
    <?php foreach ($addresses as $idx => $addr): ?>
        initAddressSelector('editCity_<?= $idx ?>', 'editDistrict_<?= $idx ?>', {
            city: '<?= e($addr['city']) ?>',
            district: '<?= e($addr['district']) ?>'
        });
    <?php endforeach; ?>

    function toggleEditForm(idx) {
        const el = document.getElementById('editForm_' + idx);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>