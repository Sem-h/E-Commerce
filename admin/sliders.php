<?php
$adminPage = 'sliders';
$pageTitle = 'Slider Yönetimi';
require_once __DIR__ . '/includes/header.php';

// Slider tablosunu oluştur (yoksa)
Database::query("CREATE TABLE IF NOT EXISTS `sliders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` VARCHAR(500) DEFAULT '',
    `badge` VARCHAR(100) DEFAULT '',
    `button_text` VARCHAR(100) DEFAULT 'Keşfet',
    `button_url` VARCHAR(500) DEFAULT '#',
    `gradient_start` VARCHAR(7) DEFAULT '#1a56db',
    `gradient_end` VARCHAR(7) DEFAULT '#1e40af',
    `image` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'badge' => trim($_POST['badge'] ?? ''),
            'button_text' => trim($_POST['button_text'] ?? 'Keşfet'),
            'button_url' => trim($_POST['button_url'] ?? '#'),
            'gradient_start' => trim($_POST['gradient_start'] ?? '#1a56db'),
            'gradient_end' => trim($_POST['gradient_end'] ?? '#1e40af'),
            'image' => trim($_POST['image'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'status' => isset($_POST['status']) ? 1 : 0,
        ];
        if (empty($data['title'])) {
            flash('admin_slider', 'Başlık zorunludur.', 'error');
        } else {
            if ($action === 'add') {
                Database::query("INSERT INTO sliders (title, description, badge, button_text, button_url, gradient_start, gradient_end, image, sort_order, status) VALUES (?,?,?,?,?,?,?,?,?,?)", array_values($data));
                flash('admin_slider', 'Slider başarıyla eklendi!', 'success');
            } else {
                $id = intval($_POST['id']);
                Database::query("UPDATE sliders SET title=?, description=?, badge=?, button_text=?, button_url=?, gradient_start=?, gradient_end=?, image=?, sort_order=?, status=? WHERE id=?", [...array_values($data), $id]);
                flash('admin_slider', 'Slider başarıyla güncellendi!', 'success');
            }
            redirect('/admin/sliders.php');
        }
    }
    if ($action === 'delete') {
        Database::query("DELETE FROM sliders WHERE id = ?", [intval($_POST['id'])]);
        flash('admin_slider', 'Slider silindi.', 'success');
        redirect('/admin/sliders.php');
    }
}

$sliders = Database::fetchAll("SELECT * FROM sliders ORDER BY sort_order ASC, id ASC");
$activeSliders = array_filter($sliders, fn($s) => $s['status']);
$totalActive = count($activeSliders);
$editSlider = null;
if (isset($_GET['edit'])) {
    $editSlider = Database::fetch("SELECT * FROM sliders WHERE id = ?", [intval($_GET['edit'])]);
}
?>

<style>
    .slider-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
    }

    .slider-page-header h2 {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .slider-page-header p {
        color: var(--admin-gray);
        margin: 4px 0 0;
        font-size: 0.85rem;
    }

    .slider-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }

    .slider-stat {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .slider-stat .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .slider-stat .stat-info h4 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .slider-stat .stat-info span {
        font-size: 0.75rem;
        color: var(--admin-gray);
    }

    .slider-form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .slider-form-card h3 {
        margin: 0 0 20px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f3f4f6;
    }

    .slider-list-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
    }

    .slider-list-card h3 {
        margin: 0 0 16px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f3f4f6;
    }

    .slider-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .slider-item:hover {
        border-color: #c7d2fe;
        box-shadow: 0 2px 12px rgba(99, 102, 241, 0.08);
    }

    .slider-item.inactive {
        opacity: 0.6;
        background: #fafafa;
    }

    .slider-preview-mini {
        width: 200px;
        min-width: 200px;
        height: 90px;
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 14px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .slider-preview-mini .mini-badge {
        font-size: 0.6rem;
        opacity: 0.8;
        margin-bottom: 3px;
    }

    .slider-preview-mini .mini-title {
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .slider-preview-mini .mini-btn {
        font-size: 0.55rem;
        margin-top: 6px;
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        width: fit-content;
    }

    .slider-info {
        flex: 1;
        min-width: 0;
    }

    .slider-info-top {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .slider-info-top strong {
        font-size: 0.95rem;
    }

    .slider-meta {
        display: flex;
        gap: 14px;
        font-size: 0.75rem;
        color: var(--admin-gray);
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .slider-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .location-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .location-badge.hero {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .location-badge.promo {
        background: #fef3c7;
        color: #92400e;
    }

    .slider-actions {
        display: flex;
        gap: 6px;
    }

    .slider-actions .btn-edit {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 8px;
        color: #6366f1;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: flex;
        align-items: center;
    }

    .slider-actions .btn-edit:hover {
        background: #eef2ff;
        border-color: #c7d2fe;
    }

    .slider-actions .btn-delete {
        padding: 8px 12px;
        border: 1px solid #fecaca;
        background: #fff;
        border-radius: 8px;
        color: #ef4444;
        cursor: pointer;
        transition: all 0.2s;
    }

    .slider-actions .btn-delete:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .color-pick-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .color-pick-group input[type="color"] {
        width: 44px;
        height: 38px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        padding: 2px;
    }

    .color-pick-group input[type="color"]:hover {
        border-color: #6366f1;
    }

    .color-pick-group .color-hex {
        font-size: 0.75rem;
        color: var(--admin-gray);
        font-family: monospace;
        background: #f9fafb;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .preview-box {
        border-radius: 14px;
        padding: 36px 30px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin: 16px 0;
    }

    .preview-box .prev-badge {
        display: inline-block;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        margin-bottom: 10px;
    }

    .preview-box h2 {
        margin: 0 0 6px;
        font-size: 1.4rem;
    }

    .preview-box p {
        opacity: 0.9;
        margin: 0 0 14px;
        font-size: 0.9rem;
    }

    .preview-box .prev-btn {
        display: inline-block;
        background: #fff;
        color: #1a56db;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .form-row {
        display: grid;
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-row.cols-2 {
        grid-template-columns: 1fr 1fr;
    }

    .form-row.cols-4 {
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
    }

    .form-group .form-control {
        padding: 9px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.85rem;
        transition: border-color 0.2s;
    }

    .form-group .form-control:focus {
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-group small {
        font-size: 0.7rem;
        color: var(--admin-gray);
    }

    .status-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .status-toggle input {
        width: 18px;
        height: 18px;
        accent-color: #6366f1;
    }

    .btn-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #f3f4f6;
    }
</style>

<!-- Header -->
<div class="slider-page-header">
    <div>
        <h2><i class="fas fa-images" style="color:#6366f1"></i> Slider Yönetimi</h2>
        <p>Ana sayfadaki hero slider ve promosyon kartlarını buradan yönetin.</p>
    </div>
    <a href="<?= BASE_URL ?>/" target="_blank" class="admin-btn admin-btn-outline" style="font-size:0.8rem">
        <i class="fas fa-external-link-alt"></i> Siteyi Gör
    </a>
</div>

<?php showFlash('admin_slider'); ?>

<!-- İstatistikler -->
<div class="slider-stats">
    <div class="slider-stat">
        <div class="stat-icon" style="background:#eef2ff;color:#6366f1"><i class="fas fa-images"></i></div>
        <div class="stat-info">
            <h4><?= count($sliders) ?></h4>
            <span>Toplam Slider</span>
        </div>
    </div>
    <div class="slider-stat">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h4><?= $totalActive ?></h4>
            <span>Aktif Slider</span>
        </div>
    </div>
    <div class="slider-stat">
        <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-info">
            <h4>2</h4>
            <span>Gösterim Alanı</span>
        </div>
    </div>
</div>

<!-- Konum Bilgi Kartı -->
<div class="admin-card"
    style="margin-bottom:24px;background:linear-gradient(135deg,#f0f4ff,#e8ecf8);border:1px solid #c7d2fe">
    <h3 style="margin:0 0 14px;font-size:0.95rem;color:#4338ca"><i class="fas fa-info-circle"></i> Slider Gösterim
        Alanları</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #e0e7ff">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span class="location-badge hero"><i class="fas fa-desktop"></i> HERO SLIDER</span>
                <span style="font-size:0.7rem;color:#6b7280">Tüm aktif sliderlar</span>
            </div>
            <p style="margin:0;font-size:0.78rem;color:#4b5563;line-height:1.5">
                Ana sayfanın en üstünde, tam genişlikte dönen slider. Otomatik geçiş (5sn) ve ok tuşları ile navigasyon.
                Tüm <strong>aktif</strong> sliderlar burada gösterilir.
            </p>
        </div>
        <div style="background:#fff;border-radius:12px;padding:16px;border:1px solid #fde68a">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <span class="location-badge promo"><i class="fas fa-th-large"></i> PROMOSYON KARTLARI</span>
                <span style="font-size:0.7rem;color:#6b7280">İlk 4 aktif slider</span>
            </div>
            <p style="margin:0;font-size:0.78rem;color:#4b5563;line-height:1.5">
                Hero slider'ın hemen altında, 2×2 grid halinde promosyon kartları. Hover efektli, tıklanabilir. Sadece
                ilk <strong>4 aktif</strong> slider gösterilir.
            </p>
        </div>
    </div>
</div>

<!-- Slider Ekle / Düzenle Formu -->
<div class="slider-form-card">
    <h3>
        <i class="fas fa-<?= $editSlider ? 'edit' : 'plus-circle' ?>" style="color:#6366f1"></i>
        <?= $editSlider ? 'Slider Düzenle — #' . $editSlider['id'] : 'Yeni Slider Ekle' ?>
    </h3>
    <form method="POST" id="sliderForm">
        <input type="hidden" name="action" value="<?= $editSlider ? 'edit' : 'add' ?>">
        <?php if ($editSlider): ?>
            <input type="hidden" name="id" value="<?= $editSlider['id'] ?>">
        <?php endif; ?>

        <div class="form-row cols-2">
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Başlık *</label>
                <input type="text" name="title" class="form-control" value="<?= e($editSlider['title'] ?? '') ?>"
                    required placeholder="Teknolojinin Gücünü Keşfedin">
            </div>
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Rozet (Badge)</label>
                <input type="text" name="badge" class="form-control" value="<?= e($editSlider['badge'] ?? '') ?>"
                    placeholder="🔥 Özel Kampanya">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Açıklama</label>
                <input type="text" name="description" class="form-control"
                    value="<?= e($editSlider['description'] ?? '') ?>"
                    placeholder="En yeni elektronik ürünleri en uygun fiyatlarla bulun.">
            </div>
        </div>

        <div class="form-row cols-2">
            <div class="form-group">
                <label><i class="fas fa-mouse-pointer"></i> Buton Yazısı</label>
                <input type="text" name="button_text" class="form-control"
                    value="<?= e($editSlider['button_text'] ?? 'Keşfet') ?>">
            </div>
            <div class="form-group">
                <label><i class="fas fa-link"></i> Buton Linki</label>
                <input type="text" name="button_url" class="form-control"
                    value="<?= e($editSlider['button_url'] ?? '#') ?>" placeholder="/products.php?category=...">
            </div>
        </div>

        <div class="form-row cols-4">
            <div class="form-group">
                <label><i class="fas fa-palette"></i> Gradient Başlangıç</label>
                <div class="color-pick-group">
                    <input type="color" name="gradient_start"
                        value="<?= e($editSlider['gradient_start'] ?? '#1a56db') ?>" id="colorStart">
                    <span class="color-hex"
                        id="colorStartHex"><?= e($editSlider['gradient_start'] ?? '#1a56db') ?></span>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-palette"></i> Gradient Bitiş</label>
                <div class="color-pick-group">
                    <input type="color" name="gradient_end" value="<?= e($editSlider['gradient_end'] ?? '#1e40af') ?>"
                        id="colorEnd">
                    <span class="color-hex" id="colorEndHex"><?= e($editSlider['gradient_end'] ?? '#1e40af') ?></span>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-sort-numeric-up"></i> Sıralama</label>
                <input type="number" name="sort_order" class="form-control"
                    value="<?= $editSlider['sort_order'] ?? 0 ?>" min="0">
            </div>
            <div class="form-group" style="justify-content:flex-end">
                <label class="status-toggle">
                    <input type="checkbox" name="status" <?= ($editSlider['status'] ?? 1) ? 'checked' : '' ?>>
                    Aktif
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-image"></i> Görsel URL (opsiyonel)</label>
                <input type="text" name="image" class="form-control" value="<?= e($editSlider['image'] ?? '') ?>"
                    placeholder="https://example.com/banner.jpg">
                <small>Boş bırakılırsa gradient arkaplan kullanılır. Görsel varsa overlay ile gösterilir.</small>
            </div>
        </div>

        <!-- Canlı Önizleme -->
        <label style="font-weight:600;font-size:0.8rem;display:flex;align-items:center;gap:6px;margin-bottom:6px">
            <i class="fas fa-eye" style="color:#6366f1"></i> Canlı Önizleme
        </label>
        <div class="preview-box" id="previewSlider"
            style="background:linear-gradient(135deg, <?= e($editSlider['gradient_start'] ?? '#1a56db') ?>, <?= e($editSlider['gradient_end'] ?? '#1e40af') ?>)">
            <span class="prev-badge" id="prevBadge"><?= e($editSlider['badge'] ?? '🔥 Özel Kampanya') ?></span>
            <h2 id="prevTitle"><?= e($editSlider['title'] ?? 'Slider Başlığı') ?></h2>
            <p id="prevDesc"><?= e($editSlider['description'] ?? 'Slider açıklaması buraya gelecek.') ?></p>
            <a class="prev-btn" id="prevBtn"><?= e($editSlider['button_text'] ?? 'Keşfet') ?></a>
        </div>

        <div class="btn-group">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:10px 24px">
                <i class="fas fa-<?= $editSlider ? 'save' : 'plus' ?>"></i>
                <?= $editSlider ? 'Güncelle' : 'Slider Ekle' ?>
            </button>
            <?php if ($editSlider): ?>
                <a href="<?= BASE_URL ?>/admin/sliders.php" class="admin-btn admin-btn-outline">
                    <i class="fas fa-times"></i> İptal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Mevcut Sliderlar Listesi -->
<div class="slider-list-card">
    <h3>
        <i class="fas fa-layer-group" style="color:#6366f1"></i>
        Mevcut Sliderlar
        <span
            style="margin-left:auto;background:#eef2ff;color:#6366f1;padding:2px 10px;border-radius:12px;font-size:0.75rem;font-weight:600"><?= count($sliders) ?></span>
    </h3>

    <?php if (empty($sliders)): ?>
        <div style="text-align:center;padding:40px 20px;color:var(--admin-gray)">
            <i class="fas fa-images" style="font-size:2.5rem;opacity:0.3;margin-bottom:12px;display:block"></i>
            <p style="margin:0">Henüz slider eklenmemiş. Yukarıdaki formu kullanarak ilk slider'ınızı ekleyin.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;gap:10px">
            <?php foreach ($sliders as $idx => $s):
                // Konum hesapla
                $isHero = $s['status'];
                $isPromo = $s['status'] && $idx < 4;
                ?>
                <div class="slider-item <?= !$s['status'] ? 'inactive' : '' ?>">
                    <!-- Sıra numarası -->
                    <div style="width:32px;text-align:center;font-size:1.1rem;font-weight:700;color:#d1d5db"><?= $idx + 1 ?>
                    </div>

                    <!-- Mini önizleme -->
                    <div class="slider-preview-mini"
                        style="background:linear-gradient(135deg, <?= e($s['gradient_start']) ?>, <?= e($s['gradient_end']) ?>)">
                        <?php if ($s['badge']): ?><span class="mini-badge"><?= e($s['badge']) ?></span><?php endif; ?>
                        <span class="mini-title"><?= e(truncate($s['title'], 28)) ?></span>
                        <span class="mini-btn"><?= e($s['button_text']) ?></span>
                    </div>

                    <!-- Bilgiler -->
                    <div class="slider-info">
                        <div class="slider-info-top">
                            <strong><?= e($s['title']) ?></strong>
                            <?php if ($s['status']): ?>
                                <span class="admin-badge admin-badge-green" style="font-size:0.65rem">Aktif</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-red" style="font-size:0.65rem">Pasif</span>
                            <?php endif; ?>
                        </div>
                        <p style="color:var(--admin-gray);font-size:0.8rem;margin:0 0 6px"><?= e($s['description'] ?: '—') ?>
                        </p>

                        <!-- Konum Etiketleri -->
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
                            <?php if ($isHero): ?>
                                <span class="location-badge hero"><i class="fas fa-desktop"></i> Hero Slider</span>
                            <?php endif; ?>
                            <?php if ($isPromo): ?>
                                <span class="location-badge promo"><i class="fas fa-th-large"></i> Promosyon Kartı</span>
                            <?php endif; ?>
                            <?php if (!$s['status']): ?>
                                <span class="location-badge" style="background:#f3f4f6;color:#6b7280"><i
                                        class="fas fa-eye-slash"></i> Gösterilmiyor</span>
                            <?php endif; ?>
                        </div>

                        <div class="slider-meta">
                            <span><i class="fas fa-link"></i> <?= e(truncate($s['button_url'], 35)) ?></span>
                            <span><i class="fas fa-sort"></i> Sıra: <?= $s['sort_order'] ?></span>
                            <span><i class="fas fa-palette"></i>
                                <span
                                    style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?= e($s['gradient_start']) ?>;vertical-align:middle"></span>
                                →
                                <span
                                    style="display:inline-block;width:12px;height:12px;border-radius:3px;background:<?= e($s['gradient_end']) ?>;vertical-align:middle"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Aksiyon butonları -->
                    <div class="slider-actions">
                        <a href="<?= BASE_URL ?>/admin/sliders.php?edit=<?= $s['id'] ?>" class="btn-edit" title="Düzenle">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" onsubmit="return confirm('Bu slider silinecek. Emin misiniz?')" style="margin:0">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-delete" title="Sil">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Canlı önizleme
    const form = document.getElementById('sliderForm');
    const preview = document.getElementById('previewSlider');
    const prevBadge = document.getElementById('prevBadge');
    const prevTitle = document.getElementById('prevTitle');
    const prevDesc = document.getElementById('prevDesc');
    const prevBtn = document.getElementById('prevBtn');
    const colorStartHex = document.getElementById('colorStartHex');
    const colorEndHex = document.getElementById('colorEndHex');

    form.querySelector('[name="title"]').addEventListener('input', e => prevTitle.textContent = e.target.value || 'Slider Başlığı');
    form.querySelector('[name="description"]').addEventListener('input', e => prevDesc.textContent = e.target.value || 'Açıklama');
    form.querySelector('[name="badge"]').addEventListener('input', e => prevBadge.textContent = e.target.value || '🔥 Rozet');
    form.querySelector('[name="button_text"]').addEventListener('input', e => prevBtn.textContent = e.target.value || 'Keşfet');

    form.querySelector('[name="gradient_start"]').addEventListener('input', function () {
        colorStartHex.textContent = this.value;
        updateGradient();
    });
    form.querySelector('[name="gradient_end"]').addEventListener('input', function () {
        colorEndHex.textContent = this.value;
        updateGradient();
    });

    function updateGradient() {
        const s = form.querySelector('[name="gradient_start"]').value;
        const e = form.querySelector('[name="gradient_end"]').value;
        preview.style.background = `linear-gradient(135deg, ${s}, ${e})`;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>