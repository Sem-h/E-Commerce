<?php
$adminPage = 'delivery_settings';
$pageTitle = 'Adrese Teslim Ayarları';
require_once __DIR__ . '/includes/header.php';

// Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'delivery_enabled' => isset($_POST['delivery_enabled']) ? '1' : '0',
        'delivery_city' => trim($_POST['delivery_city'] ?? 'Bursa'),
        'delivery_fee' => floatval($_POST['delivery_fee'] ?? 250),
        'delivery_title' => trim($_POST['delivery_title'] ?? 'Adresinize Teslim'),
        'delivery_description' => trim($_POST['delivery_description'] ?? 'Gün içinde adresinize teslimat'),
        'delivery_districts' => trim($_POST['delivery_districts'] ?? ''),
    ];
    foreach ($settings as $key => $value) {
        setSetting($key, $value);
    }
    flash('admin_delivery', 'Adrese teslim ayarları kaydedildi.', 'success');
    redirect('/admin/delivery-settings.php');
}

$enabled = getSetting('delivery_enabled', '0');
$city = getSetting('delivery_city', 'Bursa');
$fee = getSetting('delivery_fee', '250');
$title = getSetting('delivery_title', 'Adresinize Teslim');
$desc = getSetting('delivery_description', 'Gün içinde adresinize teslimat');
$districts = getSetting('delivery_districts', '');
?>

<style>
    .del-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px
    }

    .del-header h2 {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px
    }

    .del-header p {
        color: var(--admin-gray);
        margin: 4px 0 0;
        font-size: 0.85rem
    }

    .del-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px
    }

    .del-card h3 {
        margin: 0 0 20px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f3f4f6
    }

    .del-form .fr {
        display: grid;
        gap: 16px;
        margin-bottom: 16px
    }

    .del-form .fr.c2 {
        grid-template-columns: 1fr 1fr
    }

    .del-form .fr.c3 {
        grid-template-columns: 1fr 1fr 1fr
    }

    .del-form .fg {
        display: flex;
        flex-direction: column;
        gap: 6px
    }

    .del-form .fg label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 5px
    }

    .del-form .fg .form-control {
        padding: 9px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.85rem;
        transition: border-color .2s
    }

    .del-form .fg .form-control:focus {
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, .1)
    }

    .del-form .fg small {
        font-size: 0.7rem;
        color: var(--admin-gray)
    }

    .status-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 2px solid #e2e8f0
    }

    .status-toggle.active-status {
        border-color: #22c55e;
        background: #f0fdf4
    }

    .status-toggle input[type="checkbox"] {
        width: 22px;
        height: 22px;
        accent-color: #22c55e
    }

    .status-toggle .label-text strong {
        font-size: 0.95rem
    }

    .status-toggle .label-text span {
        font-size: 0.75rem;
        color: var(--admin-gray);
        display: block;
        margin-top: 2px
    }

    .preview-box {
        background: linear-gradient(135deg, #eff6ff, #f0fdf4);
        border: 2px dashed #93c5fd;
        border-radius: 12px;
        padding: 20px;
        margin-top: 16px
    }

    .preview-box h4 {
        margin: 0 0 8px;
        font-size: 0.85rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 1px
    }

    .preview-inner {
        background: #fff;
        border-radius: 10px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 14px
    }

    .preview-inner .icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem
    }

    .preview-inner .text h5 {
        margin: 0;
        font-size: 0.95rem
    }

    .preview-inner .text p {
        margin: 2px 0 0;
        font-size: 0.8rem;
        color: #6b7280
    }

    .preview-inner .price {
        margin-left: auto;
        font-size: 1.1rem;
        font-weight: 700;
        color: #059669
    }
</style>

<!-- Header -->
<div class="del-header">
    <div>
        <h2><i class="fas fa-shipping-fast" style="color:#3b82f6"></i> Adrese Teslim Ayarları</h2>
        <p>Belirli şehirdeki müşterilere ek ücretli gün içi adrese teslim seçeneği sunun.</p>
    </div>
</div>

<?php showFlash('admin_delivery'); ?>

<form method="POST" class="del-form" id="deliveryForm">

    <!-- Durum -->
    <div class="del-card">
        <h3><i class="fas fa-power-off" style="color:#22c55e"></i> Özellik Durumu</h3>
        <label class="status-toggle <?= $enabled === '1' ? 'active-status' : '' ?>" id="statusToggle">
            <input type="checkbox" name="delivery_enabled" <?= $enabled === '1' ? 'checked' : '' ?>
            onchange="document.getElementById('statusToggle').classList.toggle('active-status', this.checked)">
            <div class="label-text">
                <strong>Adrese Teslim Seçeneği</strong>
                <span>Aktif edildiğinde, belirtilen şehirdeki müşteriler checkout sırasında adrese teslim seçeneğini
                    görecek.</span>
            </div>
        </label>
    </div>

    <!-- Ayarlar -->
    <div class="del-card">
        <h3><i class="fas fa-sliders-h" style="color:#6366f1"></i> Teslimat Ayarları</h3>

        <div class="fr c2">
            <div class="fg">
                <label><i class="fas fa-city"></i> Geçerli Şehir</label>
                <select name="delivery_city" id="deliveryCity" class="form-control">
                    <option value="">Şehir seçiniz...</option>
                </select>
                <small>Bu şehirden sipariş veren müşteriler seçeneği görecek</small>
            </div>
            <div class="fg">
                <label><i class="fas fa-lira-sign"></i> Ek Teslimat Ücreti (₺)</label>
                <input type="number" name="delivery_fee" class="form-control" value="<?= e($fee) ?>" min="0"
                    step="0.01">
                <small>Standart kargo ücretine ek olarak alınacak tutar</small>
            </div>
        </div>

        <div class="fr c2">
            <div class="fg">
                <label><i class="fas fa-heading"></i> Başlık</label>
                <input type="text" name="delivery_title" class="form-control" value="<?= e($title) ?>"
                    placeholder="Adresinize Teslim">
                <small>Checkout'ta görünecek seçenek başlığı</small>
            </div>
            <div class="fg">
                <label><i class="fas fa-comment-alt"></i> Açıklama</label>
                <input type="text" name="delivery_description" class="form-control" value="<?= e($desc) ?>"
                    placeholder="Gün içinde adresinize teslimat">
                <small>Seçenek altında görünecek açıklama metni</small>
            </div>
        </div>

        <div class="fg">
            <label><i class="fas fa-map-pin"></i> Geçerli İlçeler (Opsiyonel)</label>
            <input type="text" name="delivery_districts" class="form-control" value="<?= e($districts) ?>"
                placeholder="Nilüfer, Osmangazi, Yıldırım">
            <small>Virgülle ayırarak belirli ilçeleri belirtin. Boş bırakılırsa tüm ilçeler geçerli olur.</small>
        </div>
    </div>

    <!-- Önizleme -->
    <div class="del-card">
        <h3><i class="fas fa-eye" style="color:#f59e0b"></i> Müşteri Önizleme</h3>
        <p style="font-size:0.8rem;color:var(--admin-gray);margin:0 0 12px">Checkout ekranında müşterinin göreceği
            seçeneğin örneği:</p>
        <div class="preview-box">
            <h4>Teslimat Seçenekleri</h4>
            <div class="preview-inner">
                <div class="icon"><i class="fas fa-home"></i></div>
                <div class="text">
                    <h5 id="prevTitle">
                        <?= e($title) ?>
                    </h5>
                    <p id="prevDesc">
                        <?= e($desc) ?>
                    </p>
                </div>
                <div class="price" id="prevPrice">+
                    <?= formatPrice(floatval($fee)) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Kaydet -->
    <button type="submit" class="admin-btn admin-btn-primary" style="padding:12px 32px">
        <i class="fas fa-save"></i> Ayarları Kaydet
    </button>
</form>

<script src="<?= BASE_URL ?>/js/address-selector.js"></script>
<script>
    const BASE_URL = '<?= BASE_URL ?>';
    const baseUrl = BASE_URL + '/api/address.php';

    // Şehir dropdown'ını doldur
    fetch(baseUrl + '?action=provinces')
        .then(r => r.json())
        .then(provinces => {
            const sel = document.getElementById('deliveryCity');
            sel.innerHTML = '<option value="">Şehir seçiniz...</option>';
            provinces.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === '<?= e($city) ?>') opt.selected = true;
                sel.appendChild(opt);
            });
        });

    // Canlı önizleme
    document.querySelector('input[name="delivery_title"]').addEventListener('input', e => {
        document.getElementById('prevTitle').textContent = e.target.value || 'Adresinize Teslim';
    });
    document.querySelector('input[name="delivery_description"]').addEventListener('input', e => {
        document.getElementById('prevDesc').textContent = e.target.value || 'Gün içinde adresinize teslimat';
    });
    document.querySelector('input[name="delivery_fee"]').addEventListener('input', e => {
        const v = parseFloat(e.target.value) || 0;
        document.getElementById('prevPrice').textContent = '+' + v.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>