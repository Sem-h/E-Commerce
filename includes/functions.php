<?php
/**
 * V-Commerce - Yardımcı Fonksiyonlar
 */

// ==================== GENEL ====================

function slugify($text)
{
    $turkish = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'İ', 'Ö', 'Ş', 'Ü'];
    $latin = ['c', 'g', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'];
    $text = str_replace($turkish, $latin, $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function formatPrice($price)
{
    return number_format($price, 2, ',', '.') . ' ₺';
}

function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit;
}

function redirectUrl($url)
{
    header("Location: " . $url);
    exit;
}

function flash($key, $message = null, $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
    } elseif (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function showFlash($key)
{
    $flash = flash($key);
    if ($flash) {
        $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        echo '<div class="alert alert-' . $type . ' alert-dismissible">';
        echo '<i class="fas fa-' . ($type === 'success' ? 'check-circle' : 'exclamation-triangle') . '"></i> ';
        echo htmlspecialchars($flash['message']);
        echo '<button class="alert-close" onclick="this.parentElement.remove()">&times;</button>';
        echo '</div>';
    }
}

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function truncate($text, $length = 100)
{
    if (mb_strlen($text) <= $length)
        return $text;
    return mb_substr($text, 0, $length) . '...';
}

function generateOrderNumber()
{
    return 'VC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function timeAgo($datetime)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0)
        return $diff->y . ' yıl önce';
    if ($diff->m > 0)
        return $diff->m . ' ay önce';
    if ($diff->d > 0)
        return $diff->d . ' gün önce';
    if ($diff->h > 0)
        return $diff->h . ' saat önce';
    if ($diff->i > 0)
        return $diff->i . ' dakika önce';
    return 'Az önce';
}

// ==================== AUTH ====================

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        flash('login', 'Lütfen giriş yapın.', 'error');
        redirect('/client/login.php');
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        redirect('/admin/login.php');
    }
}

function currentUser()
{
    if (!isLoggedIn())
        return null;
    return Database::fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// ==================== ÜRÜNLER ====================

function getProducts($limit = null, $where = '', $params = [], $orderBy = 'created_at DESC')
{
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
    if ($where)
        $sql .= " WHERE $where";
    $sql .= " ORDER BY $orderBy";
    if ($limit)
        $sql .= " LIMIT $limit";
    return Database::fetchAll($sql, $params);
}

function getFeaturedProducts($limit = 8)
{
    return getProducts($limit, 'p.featured = 1 AND p.status = 1');
}

function getNewProducts($limit = 8)
{
    return getProducts($limit, 'p.status = 1', [], 'p.created_at DESC');
}

function getProduct($id)
{
    return Database::fetch("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?", [$id]);
}

function getProductBySlug($slug)
{
    return Database::fetch("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ?", [$slug]);
}

// ==================== KATEGORİLER ====================

function getCategories($activeOnly = true)
{
    $sql = "SELECT * FROM categories WHERE parent_id IS NULL";
    if ($activeOnly)
        $sql .= " AND status = 1";
    $sql .= " ORDER BY sort_order ASC";
    return Database::fetchAll($sql);
}

function getCategory($id)
{
    return Database::fetch("SELECT * FROM categories WHERE id = ?", [$id]);
}

function getCategoryBySlug($slug)
{
    return Database::fetch("SELECT * FROM categories WHERE slug = ?", [$slug]);
}

function getCategoryProductCount($categoryId)
{
    // Ana kategori + tüm alt kategorilerin ürünlerini say
    $childIds = [$categoryId];
    $children = Database::fetchAll("SELECT id FROM categories WHERE parent_id = ?", [$categoryId]);
    foreach ($children as $child) {
        $childIds[] = $child['id'];
    }
    $placeholders = implode(',', array_fill(0, count($childIds), '?'));
    $result = Database::fetch("SELECT COUNT(*) as count FROM products WHERE category_id IN ($placeholders) AND status = 1", $childIds);
    return $result['count'];
}

function getSubCategories($parentId, $activeOnly = true)
{
    $sql = "SELECT * FROM categories WHERE parent_id = ?";
    if ($activeOnly)
        $sql .= " AND status = 1";
    $sql .= " ORDER BY sort_order ASC, name ASC";
    return Database::fetchAll($sql, [$parentId]);
}

function getAllCategoriesFlat($parentId = null, $level = 0, $activeOnly = false)
{
    $sql = $parentId === null
        ? "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c WHERE c.parent_id IS NULL"
        : "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c WHERE c.parent_id = ?";
    if ($activeOnly)
        $sql .= " AND c.status = 1";
    $sql .= " ORDER BY c.sort_order ASC, c.name ASC";

    $params = $parentId === null ? [] : [$parentId];
    $cats = Database::fetchAll($sql, $params);

    $result = [];
    foreach ($cats as $cat) {
        $cat['level'] = $level;
        $result[] = $cat;
        $children = getAllCategoriesFlat($cat['id'], $level + 1, $activeOnly);
        $result = array_merge($result, $children);
    }
    return $result;
}

// ==================== SEPET ====================

function getCartItems()
{
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        return Database::fetchAll(
            "SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock, p.slug 
             FROM cart c JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?",
            [$userId]
        );
    }
    return Database::fetchAll(
        "SELECT c.*, p.name, p.price, p.discount_price, p.image, p.stock, p.slug 
         FROM cart c JOIN products p ON c.product_id = p.id 
         WHERE c.session_id = ?",
        [$sessionId]
    );
}

function getCartCount()
{
    $items = getCartItems();
    $count = 0;
    foreach ($items as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function getCartTotal()
{
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $price = $item['discount_price'] ?? $item['price'];
        $total += $price * $item['quantity'];
    }
    return $total;
}

function addToCart($productId, $quantity = 1)
{
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;

    $whereCol = $userId ? 'user_id' : 'session_id';
    $whereVal = $userId ?? $sessionId;

    // Ürün zaten sepette mi?
    $existing = Database::fetch(
        "SELECT * FROM cart WHERE $whereCol = ? AND product_id = ?",
        [$whereVal, $productId]
    );

    if ($existing) {
        Database::query(
            "UPDATE cart SET quantity = quantity + ? WHERE id = ?",
            [$quantity, $existing['id']]
        );
    } else {
        $cols = $userId ? 'user_id' : 'session_id';
        Database::query(
            "INSERT INTO cart ($cols, product_id, quantity) VALUES (?, ?, ?)",
            [$whereVal, $productId, $quantity]
        );
    }
}

function removeFromCart($cartId)
{
    Database::query("DELETE FROM cart WHERE id = ?", [$cartId]);
}

function updateCartQuantity($cartId, $quantity)
{
    if ($quantity <= 0) {
        removeFromCart($cartId);
    } else {
        Database::query("UPDATE cart SET quantity = ? WHERE id = ?", [$quantity, $cartId]);
    }
}

function clearCart()
{
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        Database::query("DELETE FROM cart WHERE user_id = ?", [$userId]);
    } else {
        Database::query("DELETE FROM cart WHERE session_id = ?", [$sessionId]);
    }
}

function mergeCartOnLogin($userId)
{
    $sessionId = session_id();
    // Session sepetindeki ürünleri kullanıcı sepetine aktar
    $sessionItems = Database::fetchAll("SELECT * FROM cart WHERE session_id = ?", [$sessionId]);
    foreach ($sessionItems as $item) {
        $existing = Database::fetch("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
        if ($existing) {
            Database::query("UPDATE cart SET quantity = quantity + ? WHERE id = ?", [$item['quantity'], $existing['id']]);
        } else {
            Database::query("UPDATE cart SET user_id = ?, session_id = NULL WHERE id = ?", [$userId, $item['id']]);
        }
    }
    // Kalan session itemlerini temizle
    Database::query("DELETE FROM cart WHERE session_id = ? AND user_id IS NULL", [$sessionId]);
}

// ==================== AYARLAR ====================

function getSetting($key, $default = '')
{
    $result = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

function setSetting($key, $value)
{
    $existing = Database::fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($existing) {
        Database::query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    } else {
        Database::query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
    }
}

// ==================== GÖRSELLER ====================

function uploadImage($file, $directory = 'products')
{
    $targetDir = UPLOADS_PATH . $directory . DIRECTORY_SEPARATOR;
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowed)) {
        return ['success' => false, 'error' => 'Geçersiz dosya formatı.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        return ['success' => false, 'error' => 'Dosya boyutu çok büyük (max 5MB).'];
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $directory . '/' . $filename];
    }

    return ['success' => false, 'error' => 'Dosya yüklenemedi.'];
}

function getImageUrl($path)
{
    if (empty($path)) {
        return BASE_URL . '/assets/images/no-image.png';
    }
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return UPLOADS_URL . '/' . $path;
}

// ==================== SAYFALAMA ====================

function paginate($totalItems, $perPage = 12, $currentPage = 1)
{
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function renderPagination($pagination, $baseUrl = '?')
{
    if ($pagination['total_pages'] <= 1)
        return;

    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];

    echo '<div class="pagination">';

    if ($current > 1) {
        echo '<a href="' . $baseUrl . 'page=' . ($current - 1) . '" class="page-link"><i class="fas fa-chevron-left"></i></a>';
    }

    for ($i = max(1, $current - 2); $i <= min($total, $current + 2); $i++) {
        $active = $i === $current ? ' active' : '';
        echo '<a href="' . $baseUrl . 'page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($current < $total) {
        echo '<a href="' . $baseUrl . 'page=' . ($current + 1) . '" class="page-link"><i class="fas fa-chevron-right"></i></a>';
    }

    echo '</div>';
}

// ==================== İSTATİSTİKLER ====================

function getStats()
{
    return [
        'total_products' => Database::fetch("SELECT COUNT(*) as c FROM products")['c'],
        'total_orders' => Database::fetch("SELECT COUNT(*) as c FROM orders")['c'],
        'total_users' => Database::fetch("SELECT COUNT(*) as c FROM users WHERE role='customer'")['c'],
        'total_revenue' => Database::fetch("SELECT COALESCE(SUM(total),0) as c FROM orders WHERE status != 'cancelled'")['c'],
        'pending_orders' => Database::fetch("SELECT COUNT(*) as c FROM orders WHERE status='pending'")['c'],
    ];
}

// ==================== DÖVİZ KURU ====================

/**
 * TCMB'den güncel döviz kurlarını çeker
 * @return array ['USD' => float, 'EUR' => float, 'date' => string]
 */
function getTCMBRates()
{
    // 5 dakika cache
    $cacheFile = sys_get_temp_dir() . '/tcmb_rates.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached)
            return $cached;
    }

    $rates = ['USD' => 0, 'EUR' => 0, 'date' => ''];

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $xmlStr = @file_get_contents('https://www.tcmb.gov.tr/kurlar/today.xml', false, $ctx);
        if (!$xmlStr)
            return $rates;

        $xml = @simplexml_load_string($xmlStr);
        if (!$xml)
            return $rates;

        $rates['date'] = (string) $xml['Tarih'];

        foreach ($xml->Currency as $cur) {
            $code = (string) $cur['CurrencyCode'];
            if ($code === 'USD') {
                $rates['USD'] = floatval((string) $cur->ForexSelling);
            } elseif ($code === 'EUR') {
                $rates['EUR'] = floatval((string) $cur->ForexSelling);
            }
        }

        // Cache
        file_put_contents($cacheFile, json_encode($rates));
    } catch (Exception $e) {
        // Sessiz hata
    }

    return $rates;
}

// ==================== KAMPANYA FONKSİYONLARI ====================

/**
 * Kupon kodu doğrula ve indirim hesapla
 * @return array ['success' => bool, 'message' => string, 'discount' => float, 'campaign' => array|null]
 */
function applyCoupon($code, $userId, $subtotal)
{
    $code = strtoupper(trim($code));
    if (empty($code)) {
        return ['success' => false, 'message' => 'Lütfen bir indirim kodu girin.', 'discount' => 0, 'campaign' => null];
    }

    $campaign = Database::fetch(
        "SELECT * FROM campaigns WHERE UPPER(code) = ? AND status = 1",
        [$code]
    );

    if (!$campaign) {
        return ['success' => false, 'message' => 'Geçersiz indirim kodu.', 'discount' => 0, 'campaign' => null];
    }

    // Tarih kontrolü
    $now = date('Y-m-d H:i:s');
    if ($campaign['start_date'] && $now < $campaign['start_date']) {
        return ['success' => false, 'message' => 'Bu kampanya henüz başlamadı.', 'discount' => 0, 'campaign' => null];
    }
    if ($campaign['end_date'] && $now > $campaign['end_date']) {
        return ['success' => false, 'message' => 'Bu kampanyanın süresi dolmuş.', 'discount' => 0, 'campaign' => null];
    }

    // Kullanım limiti
    if ($campaign['usage_limit'] > 0 && $campaign['used_count'] >= $campaign['usage_limit']) {
        return ['success' => false, 'message' => 'Bu kampanyanın kullanım limiti dolmuş.', 'discount' => 0, 'campaign' => null];
    }

    // Müşteriye özel kontrol
    if ($campaign['user_id'] && $campaign['user_id'] != $userId) {
        return ['success' => false, 'message' => 'Bu kod sizin için geçerli değil.', 'discount' => 0, 'campaign' => null];
    }

    // Minimum sipariş kontrolü
    if ($campaign['min_order_amount'] > 0 && $subtotal < $campaign['min_order_amount']) {
        return [
            'success' => false,
            'message' => 'Minimum sipariş tutarı: ' . formatPrice($campaign['min_order_amount']),
            'discount' => 0,
            'campaign' => null
        ];
    }

    // Kullanıcı daha önce kullandı mı (tek kullanımlık kontrolü)
    if ($userId && $campaign['type'] === 'gift_voucher') {
        $used = Database::fetch(
            "SELECT id FROM campaign_usage WHERE campaign_id = ? AND user_id = ?",
            [$campaign['id'], $userId]
        );
        if ($used) {
            return ['success' => false, 'message' => 'Bu hediye çekini zaten kullandınız.', 'discount' => 0, 'campaign' => null];
        }
    }

    // İndirim hesapla
    $discount = 0;
    if ($campaign['discount_percent'] > 0) {
        $discount = round($subtotal * $campaign['discount_percent'] / 100, 2);
    }
    if ($campaign['discount_amount'] > 0) {
        $discount = $campaign['discount_amount'];
    }

    // Max indirim sınırı
    if ($campaign['max_discount'] > 0 && $discount > $campaign['max_discount']) {
        $discount = $campaign['max_discount'];
    }

    // İndirim toplam fiyattan fazla olamaz
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    $typeNames = [
        'percentage' => '% İndirim',
        'gift_voucher' => 'Hediye Çeki',
        'discount_code' => 'İndirim Kodu',
        'customer_specific' => 'Özel İndirim',
    ];

    return [
        'success' => true,
        'message' => $typeNames[$campaign['type']] . ' uygulandı! ' . formatPrice($discount) . ' indirim.',
        'discount' => $discount,
        'campaign' => $campaign
    ];
}

/**
 * Müşteriye özel aktif kampanyaları getir
 */
function getActiveCampaignsForUser($userId)
{
    if (!$userId)
        return [];
    $now = date('Y-m-d H:i:s');
    return Database::fetchAll(
        "SELECT * FROM campaigns 
         WHERE status = 1 AND type = 'customer_specific' AND user_id = ?
         AND (start_date IS NULL OR start_date <= ?)
         AND (end_date IS NULL OR end_date >= ?)
         AND (usage_limit = 0 OR used_count < usage_limit)
         ORDER BY created_at DESC",
        [$userId, $now, $now]
    );
}

/**
 * Kampanya kullanımını kaydet
 */
function recordCampaignUsage($campaignId, $userId, $orderId, $discountAmount)
{
    Database::query(
        "INSERT INTO campaign_usage (campaign_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)",
        [$campaignId, $userId, $orderId, $discountAmount]
    );
    Database::query("UPDATE campaigns SET used_count = used_count + 1 WHERE id = ?", [$campaignId]);
}

/**
 * Rastgele kupon kodu oluştur
 */
function generateCouponCode($length = 8)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
