<?php
$pageTitle = 'Site Ayarları';
$adminPage = 'settings';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? ''),
        'site_phone' => trim($_POST['site_phone'] ?? ''),
        'site_address' => trim($_POST['site_address'] ?? ''),
        'free_shipping_limit' => floatval($_POST['free_shipping_limit'] ?? 2000),
        'shipping_cost' => floatval($_POST['shipping_cost'] ?? 49.90),
        'instagram' => trim($_POST['instagram'] ?? ''),
        'facebook' => trim($_POST['facebook'] ?? ''),
        'twitter' => trim($_POST['twitter'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'paytr_merchant_id' => trim($_POST['paytr_merchant_id'] ?? ''),
        'paytr_merchant_key' => trim($_POST['paytr_merchant_key'] ?? ''),
        'paytr_merchant_salt' => trim($_POST['paytr_merchant_salt'] ?? ''),
        'paytr_test_mode' => isset($_POST['paytr_test_mode']) ? '1' : '0',
    ];
    foreach ($settings as $key => $value) {
        setSetting($key, $value);
    }
    flash('admin_settings', 'Ayarlar kaydedildi.', 'success');
    redirect('/admin/settings.php');
}
?>
<div class="admin-header">
    <h1><i class="fas fa-cog" style="color:var(--admin-primary)"></i> Site Ayarları</h1>
</div>
<?php showFlash('admin_settings'); ?>

<form method="POST" class="admin-form">
    <div class="admin-card">
        <h3><i class="fas fa-globe"></i> Genel Ayarlar</h3>
        <div class="form-row">
            <div class="form-group"><label>Site Adı</label><input type="text" name="site_name" class="form-control"
                    value="<?= e(getSetting('site_name', 'V-Commerce')) ?>"></div>
            <div class="form-group"><label>Site E-posta</label><input type="email" name="site_email"
                    class="form-control" value="<?= e(getSetting('site_email')) ?>"></div>
        </div>
        <div class="form-group"><label>Site Açıklaması</label><textarea name="site_description" class="form-control"
                rows="2"><?= e(getSetting('site_description')) ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Telefon</label><input type="text" name="site_phone" class="form-control"
                    value="<?= e(getSetting('site_phone')) ?>"></div>
            <div class="form-group"><label>Adres</label><input type="text" name="site_address" class="form-control"
                    value="<?= e(getSetting('site_address')) ?>"></div>
        </div>
    </div>

    <div class="admin-card">
        <h3><i class="fas fa-truck"></i> Kargo Ayarları</h3>
        <div class="form-row">
            <div class="form-group"><label>Ücretsiz Kargo Limiti (₺)</label><input type="number"
                    name="free_shipping_limit" class="form-control" step="0.01"
                    value="<?= e(getSetting('free_shipping_limit', 2000)) ?>"></div>
            <div class="form-group"><label>Kargo Ücreti (₺)</label><input type="number" name="shipping_cost"
                    class="form-control" step="0.01" value="<?= e(getSetting('shipping_cost', 49.90)) ?>"></div>
        </div>
    </div>

    <div class="admin-card">
        <h3><i class="fas fa-share-alt"></i> Sosyal Medya</h3>
        <div class="form-row">
            <div class="form-group"><label>Instagram URL</label><input type="url" name="instagram" class="form-control"
                    value="<?= e(getSetting('instagram')) ?>"></div>
            <div class="form-group"><label>Facebook URL</label><input type="url" name="facebook" class="form-control"
                    value="<?= e(getSetting('facebook')) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Twitter URL</label><input type="url" name="twitter" class="form-control"
                    value="<?= e(getSetting('twitter')) ?>"></div>
            <div class="form-group"><label>WhatsApp Numara</label><input type="text" name="whatsapp"
                    class="form-control" value="<?= e(getSetting('whatsapp')) ?>" placeholder="905xxxxxxxxx"></div>
        </div>
    </div>

    <div class="admin-card">
        <h3><i class="fas fa-credit-card"></i> PayTR Ayarları</h3>
        <div class="form-row">
            <div class="form-group"><label>Merchant ID</label><input type="text" name="paytr_merchant_id"
                    class="form-control" value="<?= e(getSetting('paytr_merchant_id')) ?>"></div>
            <div class="form-group"><label>Merchant Key</label><input type="text" name="paytr_merchant_key"
                    class="form-control" value="<?= e(getSetting('paytr_merchant_key')) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Merchant Salt</label><input type="text" name="paytr_merchant_salt"
                    class="form-control" value="<?= e(getSetting('paytr_merchant_salt')) ?>"></div>
            <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:16px">
                <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="paytr_test_mode"
                        <?= getSetting('paytr_test_mode', '1') === '1' ? 'checked' : '' ?>> Test Modu</label>
            </div>
        </div>
    </div>

    <button type="submit" class="admin-btn admin-btn-primary" style="padding:12px 32px"><i class="fas fa-save"></i> Tüm
        Ayarları Kaydet</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>