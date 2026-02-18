<?php
$adminPage = 'campaigns';
$pageTitle = 'Kampanya Yönetimi';
require_once __DIR__ . '/includes/header.php';

// Tabloları oluştur
Database::query("CREATE TABLE IF NOT EXISTS `campaigns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('percentage','gift_voucher','discount_code','customer_specific') NOT NULL DEFAULT 'discount_code',
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) DEFAULT NULL,
    `discount_percent` DECIMAL(5,2) DEFAULT 0,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `min_order_amount` DECIMAL(10,2) DEFAULT 0,
    `max_discount` DECIMAL(10,2) DEFAULT 0,
    `user_id` INT DEFAULT NULL,
    `usage_limit` INT DEFAULT 0,
    `used_count` INT DEFAULT 0,
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `campaign_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

Database::query("CREATE TABLE IF NOT EXISTS `campaign_usage` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `order_id` INT DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

// Orders tablosuna discount alanları ekle
try {
    Database::query("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER shipping_cost");
} catch (Exception $e) {
}
try {
    Database::query("ALTER TABLE orders ADD COLUMN campaign_id INT DEFAULT NULL AFTER discount_amount");
} catch (Exception $e) {
}

// Form İşlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'type' => $_POST['type'] ?? 'discount_code',
            'name' => trim($_POST['name'] ?? ''),
            'code' => strtoupper(trim($_POST['code'] ?? '')) ?: null,
            'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'min_order_amount' => floatval($_POST['min_order_amount'] ?? 0),
            'max_discount' => floatval($_POST['max_discount'] ?? 0),
            'user_id' => !empty($_POST['user_id']) ? intval($_POST['user_id']) : null,
            'usage_limit' => intval($_POST['usage_limit'] ?? 0),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'status' => isset($_POST['status']) ? 1 : 0,
        ];

        if (empty($data['name'])) {
            flash('admin_campaign', 'Kampanya adı zorunludur.', 'error');
        } else {
            if ($action === 'add') {
                Database::query(
                    "INSERT INTO campaigns (type,name,code,discount_percent,discount_amount,min_order_amount,max_discount,user_id,usage_limit,start_date,end_date,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$data['type'], $data['name'], $data['code'], $data['discount_percent'], $data['discount_amount'], $data['min_order_amount'], $data['max_discount'], $data['user_id'], $data['usage_limit'], $data['start_date'], $data['end_date'], $data['status']]
                );
                flash('admin_campaign', 'Kampanya oluşturuldu!', 'success');
            } else {
                $id = intval($_POST['id']);
                Database::query(
                    "UPDATE campaigns SET type=?,name=?,code=?,discount_percent=?,discount_amount=?,min_order_amount=?,max_discount=?,user_id=?,usage_limit=?,start_date=?,end_date=?,status=? WHERE id=?",
                    [$data['type'], $data['name'], $data['code'], $data['discount_percent'], $data['discount_amount'], $data['min_order_amount'], $data['max_discount'], $data['user_id'], $data['usage_limit'], $data['start_date'], $data['end_date'], $data['status'], $id]
                );
                flash('admin_campaign', 'Kampanya güncellendi!', 'success');
            }
            redirect('/admin/campaigns.php');
        }
    }

    if ($action === 'delete') {
        Database::query("DELETE FROM campaigns WHERE id = ?", [intval($_POST['id'])]);
        flash('admin_campaign', 'Kampanya silindi.', 'success');
        redirect('/admin/campaigns.php');
    }
}

$campaigns = Database::fetchAll("SELECT c.*, u.username as user_name FROM campaigns c LEFT JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
$users = Database::fetchAll("SELECT id, username, first_name, last_name FROM users WHERE role='customer' AND status=1 ORDER BY first_name");
$editCampaign = null;
if (isset($_GET['edit'])) {
    $editCampaign = Database::fetch("SELECT * FROM campaigns WHERE id = ?", [intval($_GET['edit'])]);
}

// İstatistikler
$totalActive = 0;
$totalUsed = 0;
$totalDiscount = 0;
foreach ($campaigns as $c) {
    if ($c['status'])
        $totalActive++;
    $totalUsed += $c['used_count'];
}
$totalDiscount = Database::fetch("SELECT COALESCE(SUM(discount_amount),0) as total FROM campaign_usage")['total'] ?? 0;

$typeIcons = ['percentage' => 'percent', 'gift_voucher' => 'gift', 'discount_code' => 'ticket-alt', 'customer_specific' => 'user-tag'];
$typeNames = ['percentage' => '% İndirim', 'gift_voucher' => 'Hediye Çeki', 'discount_code' => 'İndirim Kodu', 'customer_specific' => 'Müşteriye Özel'];
$typeColors = ['percentage' => '#6366f1', 'gift_voucher' => '#f59e0b', 'discount_code' => '#10b981', 'customer_specific' => '#ec4899'];
?>

<style>
    .camp-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px
    }

    .camp-header h2 {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px
    }

    .camp-header p {
        color: var(--admin-gray);
        margin: 4px 0 0;
        font-size: 0.85rem
    }

    .camp-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 24px
    }

    .camp-stat {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px
    }

    .camp-stat .si {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem
    }

    .camp-stat h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700
    }

    .camp-stat span {
        font-size: 0.75rem;
        color: var(--admin-gray)
    }

    .camp-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px
    }

    .camp-card h3 {
        margin: 0 0 20px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f3f4f6
    }

    .camp-form .fr {
        display: grid;
        gap: 16px;
        margin-bottom: 16px
    }

    .camp-form .fr.c2 {
        grid-template-columns: 1fr 1fr
    }

    .camp-form .fr.c3 {
        grid-template-columns: 1fr 1fr 1fr
    }

    .camp-form .fr.c4 {
        grid-template-columns: 1fr 1fr 1fr 1fr
    }

    .camp-form .fg {
        display: flex;
        flex-direction: column;
        gap: 6px
    }

    .camp-form .fg label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 5px
    }

    .camp-form .fg .form-control {
        padding: 9px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.85rem;
        transition: border-color .2s
    }

    .camp-form .fg .form-control:focus {
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .1)
    }

    .camp-form .fg small {
        font-size: 0.7rem;
        color: var(--admin-gray)
    }

    .type-tabs {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-bottom: 20px
    }

    .type-tab {
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: #fff
    }

    .type-tab:hover {
        border-color: #c7d2fe;
        background: #f8f9ff
    }

    .type-tab.active {
        border-color: #6366f1;
        background: #eef2ff
    }

    .type-tab i {
        font-size: 1.2rem;
        display: block;
        margin-bottom: 4px
    }

    .type-tab span {
        font-size: 0.75rem;
        font-weight: 600
    }

    .camp-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        transition: border-color .2s, box-shadow .2s
    }

    .camp-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 2px 12px rgba(99, 102, 241, .08)
    }

    .camp-item.inactive {
        opacity: .55;
        background: #fafafa
    }

    .camp-type-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0
    }

    .camp-info {
        flex: 1;
        min-width: 0
    }

    .camp-info-top {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
        flex-wrap: wrap
    }

    .camp-info-top strong {
        font-size: 0.95rem
    }

    .camp-code {
        font-family: monospace;
        background: #f3f4f6;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        letter-spacing: 1px
    }

    .camp-meta {
        display: flex;
        gap: 12px;
        font-size: 0.73rem;
        color: var(--admin-gray);
        margin-top: 6px;
        flex-wrap: wrap
    }

    .camp-meta span {
        display: flex;
        align-items: center;
        gap: 4px
    }

    .camp-actions {
        display: flex;
        gap: 6px
    }

    .camp-actions a,
    .camp-actions button {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 8px;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
        display: flex;
        align-items: center;
        font-size: 0.85rem
    }

    .camp-actions a:hover {
        background: #eef2ff;
        border-color: #c7d2fe;
        color: #6366f1
    }

    .camp-actions .del {
        color: #ef4444;
        border-color: #fecaca
    }

    .camp-actions .del:hover {
        background: #fef2f2;
        border-color: #fca5a5
    }

    .discount-badge {
        font-weight: 700;
        font-size: 0.85rem;
        color: #059669
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #f3f4f6
    }
</style>

<!-- Header -->
<div class="camp-header">
    <div>
        <h2><i class="fas fa-bullhorn" style="color:#6366f1"></i> Kampanya Yönetimi</h2>
        <p>İndirim kodları, hediye çekleri ve özel kampanyaları buradan yönetin.</p>
    </div>
</div>

<?php showFlash('admin_campaign'); ?>

<!-- İstatistikler -->
<div class="camp-stats">
    <div class="camp-stat">
        <div class="si" style="background:#eef2ff;color:#6366f1"><i class="fas fa-bullhorn"></i></div>
        <div>
            <h4>
                <?= count($campaigns) ?>
            </h4><span>Toplam Kampanya</span>
        </div>
    </div>
    <div class="camp-stat">
        <div class="si" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-circle"></i></div>
        <div>
            <h4>
                <?= $totalActive ?>
            </h4><span>Aktif Kampanya</span>
        </div>
    </div>
    <div class="camp-stat">
        <div class="si" style="background:#fef3c7;color:#d97706"><i class="fas fa-chart-bar"></i></div>
        <div>
            <h4>
                <?= $totalUsed ?>
            </h4><span>Toplam Kullanım</span>
        </div>
    </div>
    <div class="camp-stat">
        <div class="si" style="background:#fee2e2;color:#ef4444"><i class="fas fa-coins"></i></div>
        <div>
            <h4>
                <?= formatPrice($totalDiscount) ?>
            </h4><span>Toplam İndirim</span>
        </div>
    </div>
</div>

<!-- Form -->
<div class="camp-card">
    <h3>
        <i class="fas fa-<?= $editCampaign ? 'edit' : 'plus-circle' ?>" style="color:#6366f1"></i>
        <?= $editCampaign ? 'Kampanyayı Düzenle — #' . $editCampaign['id'] : 'Yeni Kampanya Oluştur' ?>
    </h3>
    <form method="POST" class="camp-form" id="campForm">
        <input type="hidden" name="action" value="<?= $editCampaign ? 'edit' : 'add' ?>">
        <?php if ($editCampaign): ?><input type="hidden" name="id" value="<?= $editCampaign['id'] ?>">
        <?php endif; ?>

        <!-- Kampanya Türü Seçimi -->
        <div class="type-tabs" id="typeTabs">
            <?php foreach ($typeNames as $key => $label): ?>
                <label class="type-tab <?= ($editCampaign['type'] ?? 'discount_code') === $key ? 'active' : '' ?>"
                    data-type="<?= $key ?>">
                    <input type="radio" name="type" value="<?= $key ?>" <?= ($editCampaign['type'] ?? 'discount_code') === $key ? 'checked' : '' ?> style="display:none">
                    <i class="fas fa-<?= $typeIcons[$key] ?>" style="color:<?= $typeColors[$key] ?>"></i>
                    <span>
                        <?= $label ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="fr c2">
            <div class="fg">
                <label><i class="fas fa-tag"></i> Kampanya Adı *</label>
                <input type="text" name="name" class="form-control" value="<?= e($editCampaign['name'] ?? '') ?>"
                    required placeholder="Yaz İndirimi %20">
            </div>
            <div class="fg" id="codeField">
                <label><i class="fas fa-ticket-alt"></i> İndirim Kodu</label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="code" class="form-control" value="<?= e($editCampaign['code'] ?? '') ?>"
                        placeholder="YAZ2024" style="flex:1;text-transform:uppercase;letter-spacing:1px" id="codeInput">
                    <button type="button" onclick="generateCode()" class="admin-btn admin-btn-outline"
                        style="white-space:nowrap;padding:8px 14px" title="Otomatik Kod">
                        <i class="fas fa-random"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="fr c3">
            <div class="fg" id="percentField">
                <label><i class="fas fa-percent"></i> İndirim Yüzdesi (%)</label>
                <input type="number" name="discount_percent" class="form-control"
                    value="<?= $editCampaign['discount_percent'] ?? 0 ?>" min="0" max="100" step="0.01">
            </div>
            <div class="fg" id="amountField">
                <label><i class="fas fa-lira-sign"></i> İndirim Tutarı (₺)</label>
                <input type="number" name="discount_amount" class="form-control"
                    value="<?= $editCampaign['discount_amount'] ?? 0 ?>" min="0" step="0.01">
            </div>
            <div class="fg">
                <label><i class="fas fa-shield-alt"></i> Maks. İndirim (₺)</label>
                <input type="number" name="max_discount" class="form-control"
                    value="<?= $editCampaign['max_discount'] ?? 0 ?>" min="0" step="0.01">
                <small>0 = sınırsız</small>
            </div>
        </div>

        <div class="fr c3">
            <div class="fg">
                <label><i class="fas fa-shopping-cart"></i> Min. Sepet Tutarı (₺)</label>
                <input type="number" name="min_order_amount" class="form-control"
                    value="<?= $editCampaign['min_order_amount'] ?? 0 ?>" min="0" step="0.01">
            </div>
            <div class="fg">
                <label><i class="fas fa-redo"></i> Kullanım Limiti</label>
                <input type="number" name="usage_limit" class="form-control"
                    value="<?= $editCampaign['usage_limit'] ?? 0 ?>" min="0">
                <small>0 = sınırsız</small>
            </div>
            <div class="fg" id="userField">
                <label><i class="fas fa-user"></i> Müşteri (Özel İndirim)</label>
                <select name="user_id" class="form-control">
                    <option value="">— Tüm müşteriler —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($editCampaign['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['first_name'] . ' ' . $u['last_name']) ?> (
                            <?= e($u['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="fr c3">
            <div class="fg">
                <label><i class="fas fa-calendar-alt"></i> Başlangıç Tarihi</label>
                <input type="datetime-local" name="start_date" class="form-control"
                    value="<?= $editCampaign['start_date'] ? date('Y-m-d\TH:i', strtotime($editCampaign['start_date'])) : '' ?>">
            </div>
            <div class="fg">
                <label><i class="fas fa-calendar-times"></i> Bitiş Tarihi</label>
                <input type="datetime-local" name="end_date" class="form-control"
                    value="<?= $editCampaign['end_date'] ? date('Y-m-d\TH:i', strtotime($editCampaign['end_date'])) : '' ?>">
            </div>
            <div class="fg" style="justify-content:flex-end">
                <label style="cursor:pointer;display:flex;align-items:center;gap:8px;font-size:0.85rem;font-weight:500">
                    <input type="checkbox" name="status" <?= ($editCampaign['status'] ?? 1) ? 'checked' : '' ?>
                    style="width:18px;height:18px;accent-color:#6366f1">
                    Aktif
                </label>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:10px 24px">
                <i class="fas fa-<?= $editCampaign ? 'save' : 'plus' ?>"></i>
                <?= $editCampaign ? 'Güncelle' : 'Kampanya Oluştur' ?>
            </button>
            <?php if ($editCampaign): ?>
                <a href="<?= BASE_URL ?>/admin/campaigns.php" class="admin-btn admin-btn-outline"><i
                        class="fas fa-times"></i> İptal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Kampanya Listesi -->
<div class="camp-card">
    <h3>
        <i class="fas fa-list" style="color:#6366f1"></i> Kampanyalar
        <span
            style="margin-left:auto;background:#eef2ff;color:#6366f1;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:600">
            <?= count($campaigns) ?>
        </span>
    </h3>

    <?php if (empty($campaigns)): ?>
        <div style="text-align:center;padding:40px;color:var(--admin-gray)">
            <i class="fas fa-bullhorn" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:12px"></i>
            <p style="margin:0">Henüz kampanya oluşturulmamış.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;gap:10px">
            <?php foreach ($campaigns as $c):
                $isExpired = $c['end_date'] && $c['end_date'] < date('Y-m-d H:i:s');
                $isActive = $c['status'] && !$isExpired;
                ?>
                <div class="camp-item <?= !$isActive ? 'inactive' : '' ?>">
                    <div class="camp-type-icon" style="background:<?= $typeColors[$c['type']] ?>">
                        <i class="fas fa-<?= $typeIcons[$c['type']] ?>"></i>
                    </div>
                    <div class="camp-info">
                        <div class="camp-info-top">
                            <strong>
                                <?= e($c['name']) ?>
                            </strong>
                            <?php if ($c['code']): ?><span class="camp-code">
                                    <?= e($c['code']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($isActive): ?>
                                <span class="admin-badge admin-badge-green" style="font-size:.65rem">Aktif</span>
                            <?php elseif ($isExpired): ?>
                                <span class="admin-badge admin-badge-red" style="font-size:.65rem">Süresi Doldu</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-red" style="font-size:.65rem">Pasif</span>
                            <?php endif; ?>
                            <span class="discount-badge">
                                <?php if ($c['discount_percent'] > 0): ?>%
                                    <?= $c['discount_percent'] ?> indirim
                                <?php elseif ($c['discount_amount'] > 0): ?>
                                    <?= formatPrice($c['discount_amount']) ?> indirim
                                <?php endif; ?>
                            </span>
                        </div>
                        <div style="font-size:0.78rem;color:#6b7280;margin-top:2px">
                            <?= $typeNames[$c['type']] ?>
                            <?php if ($c['user_name']): ?> · <i class="fas fa-user"></i>
                                <?= e($c['user_name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="camp-meta">
                            <span><i class="fas fa-chart-bar"></i>
                                <?= $c['used_count'] ?>
                                <?= $c['usage_limit'] > 0 ? '/' . $c['usage_limit'] : '' ?> kullanım
                            </span>
                            <?php if ($c['min_order_amount'] > 0): ?><span><i class="fas fa-shopping-cart"></i> Min:
                                    <?= formatPrice($c['min_order_amount']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($c['start_date']): ?><span><i class="fas fa-calendar"></i>
                                    <?= date('d.m.Y', strtotime($c['start_date'])) ?> —
                                    <?= $c['end_date'] ? date('d.m.Y', strtotime($c['end_date'])) : '∞' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="camp-actions">
                        <a href="<?= BASE_URL ?>/admin/campaigns.php?edit=<?= $c['id'] ?>" title="Düzenle"><i
                                class="fas fa-pen"></i></a>
                        <form method="POST" onsubmit="return confirm('Bu kampanya silinecek. Emin misiniz?')" style="margin:0">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="del" title="Sil"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Tür tab seçimi
    document.querySelectorAll('.type-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.type-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            this.querySelector('input').checked = true;
            updateFormFields();
        });
    });

    function updateFormFields() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const codeField = document.getElementById('codeField');
        const percentField = document.getElementById('percentField');
        const amountField = document.getElementById('amountField');
        const userField = document.getElementById('userField');

        codeField.style.display = (type === 'customer_specific') ? 'none' : '';
        userField.style.display = (type === 'customer_specific') ? '' : 'none';

        if (type === 'percentage' || type === 'customer_specific') {
            percentField.style.display = '';
            amountField.style.display = 'none';
        } else if (type === 'gift_voucher') {
            percentField.style.display = 'none';
            amountField.style.display = '';
        } else {
            percentField.style.display = '';
            amountField.style.display = '';
        }
    }
    updateFormFields();

    function generateCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
        document.getElementById('codeInput').value = code;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>