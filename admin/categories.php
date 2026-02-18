<?php
$pageTitle = 'Kategori Yönetimi';
$adminPage = 'categories';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $parentId = intval($_POST['parent_id']) ?: null;
        Database::query(
            "INSERT INTO categories (name, slug, icon, parent_id, status) VALUES (?,?,?,?,1)",
            [$name, slugify($name), trim($_POST['icon'] ?? 'fas fa-folder'), $parentId]
        );
        flash('admin_cat', 'Kategori eklendi.', 'success');
        redirect('/admin/categories.php');
    }
    if ($action === 'edit') {
        $name = trim($_POST['name']);
        $parentId = intval($_POST['parent_id']) ?: null;
        Database::query(
            "UPDATE categories SET name=?, slug=?, icon=?, parent_id=? WHERE id=?",
            [$name, slugify($name), trim($_POST['icon']), $parentId, intval($_POST['category_id'])]
        );
        flash('admin_cat', 'Kategori güncellendi.', 'success');
        redirect('/admin/categories.php');
    }
    if ($action === 'delete') {
        Database::query("DELETE FROM categories WHERE id = ?", [intval($_POST['category_id'])]);
        flash('admin_cat', 'Kategori silindi.', 'success');
        redirect('/admin/categories.php');
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids)) {
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            Database::query("DELETE FROM categories WHERE id IN ($placeholders)", $ids);
            flash('admin_cat', count($ids) . ' kategori silindi.', 'success');
        }
        redirect('/admin/categories.php');
    }

    // XML'den Kategori Import
    if ($action === 'xml_import_categories') {
        $url = trim($_POST['xml_url'] ?? '');
        if (empty($url)) {
            flash('admin_cat', 'Lütfen bir XML URL girin.', 'error');
            redirect('/admin/categories.php');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => ['Accept: application/xml, text/xml, */*'],
        ]);
        $xmlContent = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$xmlContent) {
            flash('admin_cat', 'XML URL\'den alınamadı. ' . ($curlError ?: 'Boş yanıt'), 'error');
            redirect('/admin/categories.php');
        }

        $xml = @simplexml_load_string($xmlContent);
        if (!$xml) {
            flash('admin_cat', 'Geçerli bir XML dosyası değil.', 'error');
            redirect('/admin/categories.php');
        }

        if ($xml->getName() === 'hata' || isset($xml->mesaj)) {
            flash('admin_cat', 'XML Hatası: ' . (string) ($xml->mesaj ?? 'Bilinmeyen hata'), 'error');
            redirect('/admin/categories.php');
        }

        // XML'den kategori isimlerini çıkar
        $categoryPaths = [];
        $items = $xml->urun ?? $xml->product ?? $xml->item ?? $xml->children();
        foreach ($items as $item) {
            $catStr = trim((string) ($item->kategori ?? $item->category ?? $item->categoryName ?? $item->KategoriAdi ?? $item->kategori_adi ?? ''));
            if (!empty($catStr) && !in_array($catStr, $categoryPaths)) {
                $categoryPaths[] = $catStr;
            }
        }

        if (empty($categoryPaths)) {
            flash('admin_cat', 'XML içinde kategori bilgisi bulunamadı.', 'error');
            redirect('/admin/categories.php');
        }

        // Kategori ağacını oluştur
        $added = 0;
        $skipped = 0;

        foreach ($categoryPaths as $path) {
            // HTML entity'leri decode et (&gt; -> >)
            $path = html_entity_decode($path, ENT_QUOTES, 'UTF-8');

            // "Ana Kategori > Alt Kategori > Marka" formatını parse et
            $separators = [' > ', ' >> ', ' / ', ' | '];
            $parts = [$path];
            foreach ($separators as $sep) {
                if (strpos($path, $sep) !== false) {
                    $parts = array_map('trim', explode(trim($sep), $path));
                    break;
                }
            }

            // Sadece ilk 2 seviyeyi kategori olarak al (3. seviye marka)
            $parts = array_slice($parts, 0, 2);

            $parentId = null;
            foreach ($parts as $partName) {
                $partName = trim($partName);
                if (empty($partName))
                    continue;

                $slug = slugify($partName);

                // Bu seviyede bu isimde kategori var mı?
                if ($parentId === null) {
                    $existing = Database::fetch("SELECT id FROM categories WHERE slug = ? AND parent_id IS NULL", [$slug]);
                } else {
                    $existing = Database::fetch("SELECT id FROM categories WHERE slug = ? AND parent_id = ?", [$slug, $parentId]);
                }

                if ($existing) {
                    $parentId = $existing['id'];
                    $skipped++;
                } else {
                    Database::query(
                        "INSERT INTO categories (name, slug, icon, parent_id, status) VALUES (?,?,?,?,1)",
                        [$partName, $slug, 'fas fa-tag', $parentId]
                    );
                    $parentId = Database::lastInsertId();
                    $added++;
                }
            }
        }

        $msg = '';
        if ($added > 0)
            $msg .= "$added yeni kategori eklendi. ";
        if ($skipped > 0)
            $msg .= "$skipped kategori zaten mevcuttu. ";
        if ($added === 0 && $skipped === 0)
            $msg = 'XML\'de kategori bulunamadı.';

        flash('admin_cat', trim($msg), $added > 0 ? 'success' : 'warning');
        redirect('/admin/categories.php');
    }
}

$allCategories = getAllCategoriesFlat();
$flatListForSelect = $allCategories; // Select dropdown için
?>
<div class="admin-header">
    <h1><i class="fas fa-th-list" style="color:var(--admin-primary)"></i> Kategoriler</h1>
    <div style="display:flex;gap:8px">
        <button onclick="document.getElementById('xmlImportModal').classList.add('active')"
            class="admin-btn admin-btn-outline">
            <i class="fas fa-cloud-download-alt"></i> XML'den İmport
        </button>
        <button onclick="document.getElementById('addCatModal').classList.add('active')"
            class="admin-btn admin-btn-primary">
            <i class="fas fa-plus"></i> Yeni Kategori
        </button>
    </div>
</div>
<?php showFlash('admin_cat'); ?>

<!-- Toplu İşlem Barı -->
<form id="bulkForm" method="POST">
    <input type="hidden" name="action" value="bulk_delete">
    <div id="bulkBar"
        style="display:none;padding:10px 16px;background:var(--admin-danger, #e74c3c);color:#fff;border-radius:8px;margin-bottom:12px;align-items:center;gap:12px;justify-content:space-between">
        <span><strong id="selectedCount">0</strong> kategori seçildi</span>
        <button type="submit" class="admin-btn admin-btn-sm"
            style="background:#fff;color:var(--admin-danger, #e74c3c);font-weight:600"
            onclick="return confirm('Seçili kategorileri silmek istediğinize emin misiniz?')">
            <i class="fas fa-trash"></i> Seçilenleri Sil
        </button>
    </div>

    <div class="admin-card" style="padding:0">
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="selectAll" title="Tümünü Seç"></th>
                        <th>İkon</th>
                        <th>Kategori</th>
                        <th>Üst Kategori</th>
                        <th>Slug</th>
                        <th>Ürün Sayısı</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allCategories as $c): ?>
                        <tr>
                            <td><input type="checkbox" class="cat-check" name="ids[]" value="<?= $c['id'] ?>"></td>
                            <td><i class="<?= e($c['icon']) ?>" style="font-size:18px;color:var(--admin-primary)"></i></td>
                            <td>
                                <strong style="padding-left:<?= $c['level'] * 24 ?>px">
                                    <?php if ($c['level'] > 0): ?>
                                        <span
                                            style="color:var(--admin-gray);margin-right:4px"><?= str_repeat('—', $c['level']) ?></span>
                                    <?php endif; ?>
                                    <?= e($c['name']) ?>
                                </strong>
                            </td>
                            <td>
                                <?php
                                if ($c['parent_id']) {
                                    $parent = Database::fetch("SELECT name FROM categories WHERE id = ?", [$c['parent_id']]);
                                    echo e($parent['name'] ?? '-');
                                } else {
                                    echo '<span class="admin-badge admin-badge-green">Ana Kategori</span>';
                                }
                                ?>
                            </td>
                            <td><code style="font-size:0.75rem"><?= e($c['slug']) ?></code></td>
                            <td><?= $c['product_count'] ?></td>
                            <td>
                                <form method="POST" style="display:inline"
                                    onsubmit="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden"
                                        name="category_id" value="<?= $c['id'] ?>">
                                    <button class="admin-btn admin-btn-danger admin-btn-sm"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form><!-- /bulkForm -->

<script>
    const selectAll = document.getElementById('selectAll');
    const bulkBar = document.getElementById('bulkBar');
    const selectedCount = document.getElementById('selectedCount');
    const checkboxes = () => document.querySelectorAll('.cat-check');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.cat-check:checked').length;
        selectedCount.textContent = checked;
        bulkBar.style.display = checked > 0 ? 'flex' : 'none';
        selectAll.checked = checked > 0 && checked === checkboxes().length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes().forEach(cb => cb.checked = this.checked);
        updateBulkBar();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('cat-check')) updateBulkBar();
    });
</script>

<!-- XML'den Kategori Import Modal -->
<div id="xmlImportModal" class="admin-modal-bg" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3><i class="fas fa-cloud-download-alt" style="color:var(--admin-primary)"></i> XML'den Kategori İmport
            </h3>
            <button class="admin-modal-close"
                onclick="document.getElementById('xmlImportModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" class="admin-form" id="xmlImportForm">
            <div class="admin-modal-body">
                <input type="hidden" name="action" value="xml_import_categories">
                <div class="form-group">
                    <label>XML Feed URL'si</label>
                    <input type="url" name="xml_url" class="form-control" placeholder="https://ornek.com/urunler.xml"
                        required>
                </div>
                <div style="padding:12px 16px;background:#f0f7ff;border-radius:8px;border:1px solid #bfdbfe">
                    <p style="font-size:0.8125rem;color:#1e40af;margin:0"><i class="fas fa-info-circle"></i>
                        <strong>Nasıl çalışır?</strong>
                    </p>
                    <ul style="font-size:0.75rem;color:#1e40af;margin:8px 0 0 0;padding-left:16px">
                        <li>XML'deki ürünlerden kategori bilgisi okunur</li>
                        <li><code>Ana Kategori > Alt Kategori</code> formatı desteklenir</li>
                        <li>Mevcut kategoriler atlanır, sadece yeniler eklenir</li>
                        <li>Hiyerarşik yapı otomatik oluşturulur</li>
                    </ul>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button type="button" class="admin-btn admin-btn-outline"
                    onclick="document.getElementById('xmlImportModal').classList.remove('active')">İptal</button>
                <button type="submit" class="admin-btn admin-btn-primary" id="xmlImportBtn">
                    <i class="fas fa-cloud-download-alt"></i> Kategorileri İmport Et
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('xmlImportForm').addEventListener('submit', function () {
        const btn = document.getElementById('xmlImportBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> İmport ediliyor...';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    });
</script>

<!-- Yeni Kategori Modal -->
<div id="addCatModal" class="admin-modal-bg" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3><i class="fas fa-plus" style="color:var(--admin-primary)"></i> Yeni Kategori</h3><button
                class="admin-modal-close"
                onclick="document.getElementById('addCatModal').classList.remove('active')">&times;</button>
        </div>
        <form method="POST" class="admin-form">
            <div class="admin-modal-body">
                <input type="hidden" name="action" value="add">
                <div class="form-group"><label>Kategori Adı *</label><input type="text" name="name" class="form-control"
                        required></div>
                <div class="form-group"><label>İkon (Font Awesome Sınıfı)</label><input type="text" name="icon"
                        class="form-control" value="fas fa-tag" placeholder="fas fa-tag"></div>
                <div class="form-group"><label>Üst Kategori</label><select name="parent_id" class="form-control">
                        <option value="0">Yok (Ana Kategori)</option>
                        <?php foreach ($flatListForSelect as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= str_repeat('— ', $c['level']) ?>     <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div class="admin-modal-footer"><button type="button" class="admin-btn admin-btn-outline"
                    onclick="document.getElementById('addCatModal').classList.remove('active')">İptal</button>
                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>