<?php
$pageTitle = 'XML Import';
$adminPage = 'xml';
require_once __DIR__ . '/includes/header.php';

// XML Import İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $importType = $_POST['import_type'] ?? '';
    $xml = null;
    $sourceName = '';

    // Dosyadan Import
    if ($importType === 'file' && !empty($_FILES['xml_file']['tmp_name'])) {
        $xml = @simplexml_load_file($_FILES['xml_file']['tmp_name']);
        $sourceName = $_FILES['xml_file']['name'];
        if (!$xml) {
            flash('admin_xml', 'Geçersiz XML dosyası.', 'error');
            redirect('/admin/xml-import.php');
        }
    }
    // URL'den Import
    elseif ($importType === 'url' && !empty(trim($_POST['xml_url'] ?? ''))) {
        $url = trim($_POST['xml_url']);
        $sourceName = $url;

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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || !$xmlContent) {
            flash('admin_xml', 'XML URL\'den alınamadı. ' . ($curlError ?: 'Boş yanıt'), 'error');
            redirect('/admin/xml-import.php');
        }

        $xml = @simplexml_load_string($xmlContent);
        if (!$xml) {
            flash('admin_xml', 'URL\'deki içerik geçerli bir XML değil.', 'error');
            redirect('/admin/xml-import.php');
        }

        // Rate-limit / Hata kontrolü
        if ($xml->getName() === 'hata' || isset($xml->mesaj)) {
            $msg = (string) ($xml->mesaj ?? 'Bilinmeyen hata');
            flash('admin_xml', 'XML Kaynağı Hata Döndü: ' . $msg, 'error');
            redirect('/admin/xml-import.php');
        }

    } else {
        flash('admin_xml', 'Lütfen bir dosya veya URL belirtin.', 'error');
        redirect('/admin/xml-import.php');
    }

    // XML Parse & Import
    // Kur çevirisi
    $currency = $_POST['currency'] ?? 'TRY';
    $exchangeRate = floatval($_POST['exchange_rate'] ?? 1);
    if ($exchangeRate <= 0)
        $exchangeRate = 1;
    if ($currency === 'TRY')
        $exchangeRate = 1; // TRY ise çevirme

    if ($xml) {
        $imported = 0;
        $updated = 0;
        $errors = 0;

        // XML yapısını algıla - geniş format desteği
        $rootName = $xml->getName();
        if (isset($xml->urun))
            $items = $xml->urun;
        elseif (isset($xml->product))
            $items = $xml->product;
        elseif (isset($xml->item))
            $items = $xml->item;
        elseif (isset($xml->products->product))
            $items = $xml->products->product;
        else
            $items = $xml->children();

        $counter = 0;
        foreach ($items as $item) {
            // Her 100 üründe bağlantıyı canlı tut
            if (++$counter % 100 === 0) {
                Database::reconnect();
            }
            try {
                // İsim - tüm olası etiketler
                $name = trim((string) ($item->urunadi ?? $item->urun_adi ?? $item->name ?? $item->title ?? $item->productName ?? ''));

                // Fiyat
                $price = floatval(str_replace(',', '.', (string) ($item->fiyat ?? $item->fiyat_tl ?? $item->price ?? $item->salePrice ?? $item->listPrice ?? 0)));
                $price = round($price * $exchangeRate * 1.20, 2); // Kur çevirisi + %20 kâr marjı

                if (empty($name) || $price <= 0) {
                    $errors++;
                    continue;
                }

                // SKU / Stok Kodu
                $sku = trim((string) ($item->stokkodu ?? $item->stok_kodu ?? $item->sku ?? $item->barcode ?? $item->productCode ?? $item->gtin ?? ''));

                // Stok
                $stock = intval($item->stok ?? $item->stock ?? $item->stokadedi ?? $item->quantity ?? 0);

                // Marka
                $brand = trim((string) ($item->marka ?? $item->brand ?? $item->manufacturer ?? ''));

                // Açıklama
                $desc = trim((string) ($item->detay ?? $item->description ?? $item->aciklama ?? $item->details ?? ''));

                // İndirimli Fiyat
                $discountPrice = floatval(str_replace(',', '.', (string) ($item->indirimli_fiyat ?? $item->discount_price ?? $item->discountedPrice ?? $item->satisFiyati ?? 0))) ?: null;
                if ($discountPrice)
                    $discountPrice = round($discountPrice * $exchangeRate * 1.20, 2); // Kur + %20 kâr
                if ($discountPrice && $discountPrice >= $price)
                    $discountPrice = null;

                // Resim - çoklu resim desteği
                $image = '';
                if (isset($item->resimler->resim)) {
                    $image = trim((string) $item->resimler->resim[0]);
                } elseif (isset($item->resim)) {
                    $image = trim((string) $item->resim);
                } elseif (isset($item->image)) {
                    $image = trim((string) $item->image);
                } elseif (isset($item->imageUrl)) {
                    $image = trim((string) $item->imageUrl);
                } elseif (isset($item->img)) {
                    $image = trim((string) $item->img);
                }

                // Ek Resimler (JSON)
                $extraImages = [];
                if (isset($item->resimler->resim)) {
                    foreach ($item->resimler->resim as $r) {
                        $extraImages[] = trim((string) $r);
                    }
                }

                // Kategori — hiyerarşik yapı desteği
                $categoryName = trim((string) ($item->kategori ?? $item->category ?? $item->categoryName ?? $item->KategoriAdi ?? $item->kategori_adi ?? ''));

                $categoryId = null;
                $xmlBrand = null; // 3. seviye marka bilgisi
                if ($categoryName) {
                    // HTML entity'leri decode et (&gt; -> >)
                    $categoryName = html_entity_decode($categoryName, ENT_QUOTES, 'UTF-8');

                    // "ANA KATEGORİ > Alt Kategori > Marka" formatını parse et
                    $parts = [$categoryName];
                    $separators = [' > ', ' >> ', ' / ', ' | '];
                    foreach ($separators as $sep) {
                        if (strpos($categoryName, $sep) !== false) {
                            $parts = array_map('trim', explode(trim($sep), $categoryName));
                            break;
                        }
                    }

                    // 3. seviye varsa marka olarak ayır
                    if (count($parts) >= 3) {
                        $xmlBrand = trim($parts[2]);
                        $parts = array_slice($parts, 0, 2);
                    }

                    // Sadece ilk 2 seviyeyi hiyerarşik kategori olarak oluştur/bul
                    $parentId = null;
                    $parentSlug = '';
                    foreach ($parts as $partName) {
                        $partName = trim($partName);
                        if (empty($partName))
                            continue;
                        $baseSlug = slugify($partName);
                        $slug = $parentSlug ? $parentSlug . '-' . $baseSlug : $baseSlug;

                        if ($parentId === null) {
                            $cat = Database::fetch("SELECT id, slug FROM categories WHERE slug = ? AND parent_id IS NULL", [$slug]);
                        } else {
                            $cat = Database::fetch("SELECT id, slug FROM categories WHERE slug = ? AND parent_id = ?", [$slug, $parentId]);
                        }

                        if ($cat) {
                            $parentId = $cat['id'];
                            $parentSlug = $cat['slug'];
                        } else {
                            Database::query(
                                "INSERT INTO categories (name, slug, icon, parent_id, status) VALUES (?,?,?,?,1)",
                                [$partName, $slug, 'fas fa-tag', $parentId]
                            );
                            $parentId = Database::lastInsertId();
                            $parentSlug = $slug;
                        }
                    }
                    $categoryId = $parentId;
                }

                // Marka: XML marka alanı boşsa, kategori 3. seviyesinden al
                if (empty($brand) && $xmlBrand) {
                    $brand = $xmlBrand;
                }

                // Özellikler (JSON)
                $specifications = [];
                if (isset($item->ozellikler->ozellik)) {
                    foreach ($item->ozellikler->ozellik as $oz) {
                        $specifications[] = [
                            'name' => trim((string) ($oz->adi ?? '')),
                            'value' => trim((string) ($oz->degeri ?? ''))
                        ];
                    }
                }

                // Veritabanına kaydet
                $existing = $sku ? Database::fetch("SELECT id FROM products WHERE sku = ?", [$sku]) : null;

                if ($existing) {
                    $sql = "UPDATE products SET name=?, price=?, stock=?";
                    $params = [$name, $price, $stock];
                    if ($discountPrice) {
                        $sql .= ", discount_price=?";
                        $params[] = $discountPrice;
                    }
                    if ($brand) {
                        $sql .= ", brand=?";
                        $params[] = $brand;
                    }
                    if ($image) {
                        $sql .= ", image=?";
                        $params[] = $image;
                    }
                    if ($categoryId) {
                        $sql .= ", category_id=?";
                        $params[] = $categoryId;
                    }
                    if ($desc) {
                        $sql .= ", description=?";
                        $params[] = $desc;
                    }
                    if (!empty($extraImages)) {
                        $sql .= ", images=?";
                        $params[] = json_encode($extraImages);
                    }
                    if (!empty($specifications)) {
                        $sql .= ", specifications=?";
                        $params[] = json_encode($specifications);
                    }
                    $sql .= " WHERE id=?";
                    $params[] = $existing['id'];
                    Database::query($sql, $params);
                    $updated++;
                } else {
                    Database::query(
                        "INSERT INTO products (name, slug, price, discount_price, stock, sku, brand, description, short_description, image, images, specifications, category_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)",
                        [
                            $name,
                            slugify($name),
                            $price,
                            $discountPrice,
                            $stock,
                            $sku,
                            $brand,
                            $desc,
                            truncate(strip_tags($desc), 200),
                            $image ?: null,
                            !empty($extraImages) ? json_encode($extraImages) : null,
                            !empty($specifications) ? json_encode($specifications) : null,
                            $categoryId
                        ]
                    );
                    $imported++;
                }
            } catch (Exception $e) {
                $errors++;
            }
        }

        // Log
        Database::query(
            "INSERT INTO xml_imports (filename, total_items, imported_items, failed_items, status) VALUES (?,?,?,?,?)",
            [truncate($sourceName, 250), $imported + $updated + $errors, $imported + $updated, $errors, 'completed']
        );

        $msg = "";
        if ($imported > 0)
            $msg .= "$imported yeni ürün eklendi. ";
        if ($updated > 0)
            $msg .= "$updated ürün güncellendi. ";
        if ($errors > 0)
            $msg .= "$errors hata. ";
        if ($imported === 0 && $updated === 0 && $errors === 0)
            $msg = "XML'de ürün bulunamadı.";

        flash('admin_xml', trim($msg), ($errors > 0 && ($imported + $updated) > 0) ? 'warning' : (($imported + $updated) > 0 ? 'success' : 'error'));
        redirect('/admin/xml-import.php');
    }
}

$imports = Database::fetchAll("SELECT * FROM xml_imports ORDER BY created_at DESC LIMIT 20");
$tcmbRates = getTCMBRates();
$usdRate = $tcmbRates['USD'] ?: 43.67;
$eurRate = $tcmbRates['EUR'] ?: 51.69;
?>

<div class="admin-header">
    <h1><i class="fas fa-file-code" style="color:var(--admin-primary)"></i> XML Import</h1>
</div>
<?php showFlash('admin_xml'); ?>

<!-- Tab Seçimi -->
<div style="display:flex;gap:8px;margin-bottom:20px">
    <button onclick="switchTab('url')" id="tabUrl" class="admin-btn admin-btn-primary"
        style="flex:1;justify-content:center"><i class="fas fa-link"></i> URL'den Import</button>
    <button onclick="switchTab('file')" id="tabFile" class="admin-btn admin-btn-outline"
        style="flex:1;justify-content:center"><i class="fas fa-upload"></i> Dosya Yükle</button>
</div>

<!-- URL Import -->
<div id="panelUrl" class="admin-card">
    <h3><i class="fas fa-link"></i> XML URL'den Import</h3>
    <form method="POST" class="admin-form" id="urlForm">
        <input type="hidden" name="import_type" value="url">
        <div class="form-group">
            <label>XML Feed URL'si</label>
            <input type="url" name="xml_url" class="form-control" placeholder="https://ornek.com/urunler.xml" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <div class="form-group" style="margin-bottom:0">
                <label>Fiyat Para Birimi</label>
                <select name="currency" class="form-control" onchange="toggleRate(this,'rateUrl')">
                    <option value="TRY">₺ TRY (Türk Lirası)</option>
                    <option value="USD" selected>$ USD (Amerikan Doları)</option>
                    <option value="EUR">€ EUR (Euro)</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0" id="rateUrl">
                <label>Döviz Kuru (1 USD = ? TL)</label>
                <input type="number" name="exchange_rate" class="form-control" value="<?= $usdRate ?>" step="0.01"
                    min="0.01" id="rateUrlInput" data-usd="<?= $usdRate ?>" data-eur="<?= $eurRate ?>">
            </div>
        </div>
        <?php if ($tcmbRates['date']): ?>
            <div
                style="padding:10px 14px;background:#ecfdf5;border-radius:8px;margin-bottom:12px;border:1px solid #a7f3d0;display:flex;align-items:center;gap:8px">
                <i class="fas fa-university" style="color:#059669"></i>
                <span style="font-size:0.775rem;color:#065f46"><strong>TCMB Güncel Kur (<?= $tcmbRates['date'] ?>):</strong>
                    1 USD = <?= number_format($usdRate, 4, ',', '.') ?> ₺ &nbsp;|&nbsp; 1 EUR =
                    <?= number_format($eurRate, 4, ',', '.') ?> ₺</span>
            </div>
        <?php endif; ?>
        <div style="padding:12px 16px;background:#f0f7ff;border-radius:8px;margin-bottom:16px;border:1px solid #bfdbfe">
            <p style="font-size:0.8125rem;color:#1e40af;margin:0"><i class="fas fa-info-circle"></i> <strong>Desteklenen
                    XML Formatları:</strong></p>
            <div
                style="display:grid;grid-template-columns:1fr 1fr;gap:4px 24px;margin-top:8px;font-size:0.75rem;color:#1e40af">
                <span>• Ürün: <code>urun, product, item</code></span>
                <span>• İsim: <code>urunadi, name, title</code></span>
                <span>• Fiyat: <code>fiyat, price, salePrice</code></span>
                <span>• Stok: <code>stok, stock, quantity</code></span>
                <span>• SKU: <code>stokkodu, sku, barcode, gtin</code></span>
                <span>• Marka: <code>marka, brand, manufacturer</code></span>
                <span>• Resim: <code>resimler>resim, image, img</code></span>
                <span>• Kategori: <code>kategori, category</code></span>
                <span>• Detay: <code>detay, description, aciklama</code></span>
                <span>• Özellik: <code>ozellikler>ozellik</code></span>
            </div>
        </div>
        <button type="submit" class="admin-btn admin-btn-primary" id="importBtn"><i
                class="fas fa-cloud-download-alt"></i> URL'den Import Et</button>
    </form>
</div>

<!-- Dosya Import -->
<div id="panelFile" class="admin-card" style="display:none">
    <h3><i class="fas fa-upload"></i> XML Dosyası Yükle</h3>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="import_type" value="file">
        <div class="form-group">
            <label>XML Dosyası Seçin</label>
            <input type="file" name="xml_file" class="form-control" accept=".xml" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <div class="form-group" style="margin-bottom:0">
                <label>Fiyat Para Birimi</label>
                <select name="currency" class="form-control" onchange="toggleRate(this,'rateFile')">
                    <option value="TRY">₺ TRY (Türk Lirası)</option>
                    <option value="USD" selected>$ USD (Amerikan Doları)</option>
                    <option value="EUR">€ EUR (Euro)</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0" id="rateFile">
                <label>Döviz Kuru (1 USD = ? TL)</label>
                <input type="number" name="exchange_rate" class="form-control" value="<?= $usdRate ?>" step="0.01"
                    min="0.01" id="rateFileInput" data-usd="<?= $usdRate ?>" data-eur="<?= $eurRate ?>">
            </div>
        </div>
        <?php if ($tcmbRates['date']): ?>
            <div
                style="padding:10px 14px;background:#ecfdf5;border-radius:8px;margin-bottom:12px;border:1px solid #a7f3d0;display:flex;align-items:center;gap:8px">
                <i class="fas fa-university" style="color:#059669"></i>
                <span style="font-size:0.775rem;color:#065f46"><strong>TCMB Kur (<?= $tcmbRates['date'] ?>):</strong> 1 USD
                    = <?= number_format($usdRate, 4, ',', '.') ?> ₺ &nbsp;|&nbsp; 1 EUR =
                    <?= number_format($eurRate, 4, ',', '.') ?> ₺</span>
            </div>
        <?php endif; ?>
        <p style="font-size:0.8rem;color:var(--admin-gray);margin-bottom:16px">
            <i class="fas fa-info-circle"></i> Bilgisayarınızdan XML dosyası seçerek import edebilirsiniz.
        </p>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-upload"></i> Dosyadan Import
            Et</button>
    </form>
</div>

<!-- Import Geçmişi -->
<div class="admin-card">
    <h3><i class="fas fa-history"></i> Import Geçmişi</h3>
    <?php if (empty($imports)): ?>
        <p style="color:var(--admin-gray);text-align:center;padding:16px">Henüz import yapılmamış.</p>
    <?php else: ?>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kaynak</th>
                        <th>Toplam</th>
                        <th>Başarılı</th>
                        <th>Hatalı</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $imp): ?>
                        <tr>
                            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                title="<?= e($imp['filename']) ?>">
                                <i class="fas fa-<?= strpos($imp['filename'], 'http') === 0 ? 'link' : 'file-code' ?>"
                                    style="color:var(--admin-primary)"></i>
                                <?= e(truncate($imp['filename'], 60)) ?>
                            </td>
                            <td><?= $imp['total_items'] ?></td>
                            <td><span class="admin-badge admin-badge-green"><?= $imp['imported_items'] ?></span></td>
                            <td><?= $imp['failed_items'] > 0 ? '<span class="admin-badge admin-badge-red">' . $imp['failed_items'] . '</span>' : '0' ?>
                            </td>
                            <td><span class="admin-badge admin-badge-green"><?= e($imp['status']) ?></span></td>
                            <td><?= date('d.m.Y H:i', strtotime($imp['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function switchTab(tab) {
        const isUrl = tab === 'url';
        document.getElementById('panelUrl').style.display = isUrl ? 'block' : 'none';
        document.getElementById('panelFile').style.display = isUrl ? 'none' : 'block';
        document.getElementById('tabUrl').className = 'admin-btn ' + (isUrl ? 'admin-btn-primary' : 'admin-btn-outline');
        document.getElementById('tabFile').className = 'admin-btn ' + (isUrl ? 'admin-btn-outline' : 'admin-btn-primary');
        [document.getElementById('tabUrl'), document.getElementById('tabFile')].forEach(b => { b.style.flex = '1'; b.style.justifyContent = 'center'; });
    }

    // Import sırasında loading göster
    document.getElementById('urlForm').addEventListener('submit', function () {
        const btn = document.getElementById('importBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Import ediliyor, lütfen bekleyin...';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    });

    function toggleRate(sel, rateId) {
        const rateDiv = document.getElementById(rateId);
        const input = rateDiv.querySelector('input');
        if (sel.value === 'TRY') {
            rateDiv.style.display = 'none';
        } else {
            rateDiv.style.display = 'block';
            rateDiv.querySelector('label').textContent = 'Döviz Kuru (1 ' + sel.value + ' = ? TL)';
            // TCMB kurunu otomatik doldur
            if (input && input.dataset) {
                input.value = sel.value === 'EUR' ? input.dataset.eur : input.dataset.usd;
            }
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>