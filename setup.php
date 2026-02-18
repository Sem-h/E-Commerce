<?php
/**
 * V-Commerce - Veritabanı Kurulum Scripti
 * Bu script veritabanını ve tabloları oluşturur, demo verileri ekler.
 */

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'vcommerce';

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // MySQL bağlantısı (veritabanı olmadan)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Veritabanı oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
    $pdo->exec("USE `$dbname`");

    echo "<h2>✅ Veritabanı '$dbname' oluşturuldu.</h2>";

    // ==================== TABLOLAR ====================

    // Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `email` VARCHAR(100) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(50) DEFAULT '',
        `last_name` VARCHAR(50) DEFAULT '',
        `phone` VARCHAR(20) DEFAULT '',
        `role` ENUM('admin','customer') DEFAULT 'customer',
        `avatar` VARCHAR(255) DEFAULT NULL,
        `status` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(120) UNIQUE NOT NULL,
        `description` TEXT DEFAULT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `icon` VARCHAR(50) DEFAULT 'fas fa-box',
        `parent_id` INT DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `status` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT DEFAULT NULL,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(280) UNIQUE NOT NULL,
        `description` TEXT DEFAULT NULL,
        `short_description` VARCHAR(500) DEFAULT NULL,
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `discount_price` DECIMAL(10,2) DEFAULT NULL,
        `stock` INT DEFAULT 0,
        `sku` VARCHAR(50) DEFAULT NULL,
        `brand` VARCHAR(100) DEFAULT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `images` JSON DEFAULT NULL,
        `specifications` JSON DEFAULT NULL,
        `featured` TINYINT(1) DEFAULT 0,
        `status` TINYINT(1) DEFAULT 1,
        `view_count` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Orders
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `order_number` VARCHAR(20) UNIQUE NOT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `shipping_cost` DECIMAL(10,2) DEFAULT 0,
        `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `status` ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        `payment_method` VARCHAR(50) DEFAULT 'kapida_odeme',
        `payment_status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
        `shipping_first_name` VARCHAR(50) DEFAULT '',
        `shipping_last_name` VARCHAR(50) DEFAULT '',
        `shipping_phone` VARCHAR(20) DEFAULT '',
        `shipping_address` TEXT DEFAULT NULL,
        `shipping_city` VARCHAR(50) DEFAULT '',
        `shipping_district` VARCHAR(50) DEFAULT '',
        `shipping_zip` VARCHAR(10) DEFAULT '',
        `notes` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Order Items
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT DEFAULT NULL,
        `product_name` VARCHAR(255) NOT NULL,
        `product_image` VARCHAR(255) DEFAULT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Addresses
    $pdo->exec("CREATE TABLE IF NOT EXISTS `addresses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(50) DEFAULT 'Ev',
        `first_name` VARCHAR(50) NOT NULL,
        `last_name` VARCHAR(50) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `address_line` TEXT NOT NULL,
        `city` VARCHAR(50) NOT NULL,
        `district` VARCHAR(50) DEFAULT '',
        `zip_code` VARCHAR(10) DEFAULT '',
        `is_default` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Wishlist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `wishlist` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `user_product` (`user_id`, `product_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Cart
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cart` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `session_id` VARCHAR(100) DEFAULT NULL,
        `product_id` INT NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // Settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) UNIQUE NOT NULL,
        `setting_value` TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    // XML Imports Log
    $pdo->exec("CREATE TABLE IF NOT EXISTS `xml_imports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `filename` VARCHAR(255) NOT NULL DEFAULT '',
        `total_items` INT DEFAULT 0,
        `imported_items` INT DEFAULT 0,
        `failed_items` INT DEFAULT 0,
        `status` ENUM('pending','running','completed','failed') DEFAULT 'pending',
        `log` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci");

    echo "<h3>✅ Tüm tablolar oluşturuldu.</h3>";

    // ==================== DEMO VERİLER ====================

    // Admin kullanıcı
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (username, email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
    $stmt->execute(['admin', 'admin@vcommerce.com', $adminPass, 'Admin', 'V-Commerce']);

    // Demo müşteri
    $customerPass = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (username, email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'customer', 1)");
    $stmt->execute(['demo', 'demo@vcommerce.com', $customerPass, 'Demo', 'Kullanıcı', '0532 000 00 00']);

    echo "<p>✅ Admin ve demo kullanıcı oluşturuldu.</p>";

    // Kategoriler
    $categories = [
        ['Bilgisayarlar', 'bilgisayarlar', 'Laptop ve masaüstü bilgisayarlar', 'fas fa-laptop', 1],
        ['Telefonlar', 'telefonlar', 'Akıllı telefonlar ve aksesuarlar', 'fas fa-mobile-alt', 2],
        ['Kulaklıklar', 'kulakliklar', 'Bluetooth ve kablolu kulaklıklar', 'fas fa-headphones', 3],
        ['Akıllı Saatler', 'akilli-saatler', 'Akıllı saat ve fitness bileklikleri', 'fas fa-clock', 4],
        ['Projektörler', 'projektorler', 'Taşınabilir ve ev projektörleri', 'fas fa-video', 5],
        ['Powerbank', 'powerbank', 'Taşınabilir şarj cihazları', 'fas fa-battery-full', 6],
        ['Tabletler', 'tabletler', 'Tablet bilgisayarlar', 'fas fa-tablet-alt', 7],
        ['Aksesuarlar', 'aksesuarlar', 'Telefon, tablet ve bilgisayar aksesuarları', 'fas fa-plug', 8],
        ['Monitörler', 'monitorler', 'Bilgisayar monitörleri', 'fas fa-desktop', 9],
        ['Oyun & Gaming', 'oyun-gaming', 'Gaming ekipmanları', 'fas fa-gamepad', 10],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (name, slug, description, icon, sort_order, status) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    echo "<p>✅ Kategoriler oluşturuldu.</p>";

    // Demo Ürünler
    $products = [
        // Bilgisayarlar
        [1, 'Apple MacBook Air M2 13.6"', 'apple-macbook-air-m2', 'Apple M2 çip, 8GB RAM, 256GB SSD, 13.6 inç Liquid Retina ekran. Gün boyu pil ömrü ve süper sessiz fanless tasarım.', 'Apple M2 Çip | 8GB RAM | 256GB SSD', 42999.00, 39999.00, 15, 'MBA-M2-256', 'Apple'],
        [1, 'Lenovo ThinkPad X13 Gen 2', 'lenovo-thinkpad-x13-gen2', 'Intel Core i7-1165G7, 16GB RAM, 512GB SSD, 13.3 inç IPS dokunmatik ekran. İş dünyası için tasarlanmış dayanıklı ultrabook.', 'Intel i7 | 16GB RAM | 512GB SSD | Dokunmatik', 28999.00, 24999.00, 8, 'TP-X13-G2', 'Lenovo'],
        [1, 'HP EliteDesk 800 G5 Mini PC', 'hp-elitedesk-800-g5-mini', 'Intel Core i5-9500T, 8GB RAM, 256GB SSD. Kompakt masaüstü bilgisayar, sessiz çalışma.', 'Intel i5 | 8GB RAM | 256GB SSD | Mini PC', 12999.00, 9999.00, 20, 'ED-800-G5', 'HP'],
        [1, 'Dell Latitude 3520 Laptop', 'dell-latitude-3520', 'Intel Core i5-1135G7, 16GB RAM, 256GB SSD, 15.6 inç Full HD ekran. Kurumsal sınıf laptop.', 'Intel i5 | 16GB RAM | 256GB SSD | 15.6"', 18999.00, 15999.00, 12, 'DL-3520', 'Dell'],

        // Telefonlar
        [2, 'iPhone 15 Pro Max 256GB', 'iphone-15-pro-max-256gb', 'A17 Pro çip, 48MP kamera sistemi, Titanium tasarım, USB-C. En güçlü iPhone deneyimi.', 'A17 Pro | 48MP | Titanium | USB-C', 74999.00, 69999.00, 10, 'IP15PM-256', 'Apple'],
        [2, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Snapdragon 8 Gen 3, 12GB RAM, 256GB, 200MP kamera, S Pen dahil, Titanium çerçeve.', '200MP Kamera | S Pen | 6.8" AMOLED', 64999.00, 59999.00, 8, 'SGS24U-256', 'Samsung'],
        [2, 'Xiaomi 14 Pro', 'xiaomi-14-pro', 'Snapdragon 8 Gen 3, 12GB RAM, 256GB depolama, Leica kamera, 120W hızlı şarj.', 'Leica Kamera | 120W Şarj | AMOLED', 29999.00, 26999.00, 15, 'XI14P-256', 'Xiaomi'],

        // Kulaklıklar
        [3, 'Apple AirPods Pro 2. Nesil', 'apple-airpods-pro-2', 'Aktif gürültü engelleme, uyarlanabilir ses geçirgenliği, kişiselleştirilmiş uzamsal ses, USB-C şarj kutusu.', 'ANC | Uzamsal Ses | USB-C', 8999.00, 7499.00, 30, 'APP2-USBC', 'Apple'],
        [3, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Sektör lideri gürültü engelleme, 30 saat pil ömrü, çok noktalı bağlantı, premium ses kalitesi.', 'ANC | 30 Saat Pil | Bluetooth 5.3', 12999.00, 10999.00, 18, 'WH1000XM5', 'Sony'],
        [3, 'JBL Tune 770NC', 'jbl-tune-770nc', 'Aktif gürültü engelleme, 44 saat pil ömrü, JBL Pure Bass ses, hafif tasarım.', 'ANC | 44 Saat Pil | JBL Pure Bass', 3499.00, 2799.00, 40, 'JBLT770NC', 'JBL'],

        // Akıllı Saatler
        [4, 'Apple Watch Ultra 2', 'apple-watch-ultra-2', '49mm Titanium kasa, çift frekanslı GPS, 36 saat pil ömrü, 100m su geçirmezlik, aksiyon butonu.', '49mm | Titanium | GPS | 100m WR', 29999.00, 27999.00, 12, 'AWU2-49', 'Apple'],
        [4, 'Samsung Galaxy Watch 6 Classic', 'samsung-galaxy-watch-6-classic', '47mm, dönen çerçeve, Sapphire Crystal, BioActive sensör, Wear OS.', '47mm | Dönen Çerçeve | BioActive', 11999.00, 9499.00, 20, 'SGW6C-47', 'Samsung'],
        [4, 'Xiaomi Watch S3', 'xiaomi-watch-s3', 'AMOLED ekran, GPS, 15 gün pil ömrü, 150+ spor modu, değiştirilebilir çerçeve.', 'AMOLED | GPS | 15 Gün Pil', 4999.00, 3999.00, 35, 'XWS3', 'Xiaomi'],

        // Projektörler
        [5, 'Epson EH-TW6250 4K Projektör', 'epson-eh-tw6250-4k', '4K PRO-UHD, 2800 lümen, Android TV dahili, otomatik odaklama, ev sineması projektörü.', '4K | 2800 Lümen | Android TV', 32999.00, 28999.00, 6, 'ETW6250', 'Epson'],
        [5, 'Xiaomi Smart Projector 2', 'xiaomi-smart-projector-2', '1080p, 500 ANSI lümen, Android TV, otomatik odaklama ve oto-keystone.', '1080p | 500 Lümen | Android TV', 12999.00, 10999.00, 10, 'XSP2', 'Xiaomi'],

        // Powerbank
        [6, 'Anker 737 PowerCore 24K', 'anker-737-powercore-24k', '24.000 mAh, 140W çıkış, USB-C PD 3.1, akıllı dijital ekran, laptop şarj desteği.', '24000mAh | 140W | USB-C PD', 4999.00, 4299.00, 25, 'A737-24K', 'Anker'],
        [6, 'Baseus Blade 100W 20000mAh', 'baseus-blade-100w', '20.000 mAh, 100W çıkış, ultra ince tasarım, dijital ekran, hızlı şarj.', '20000mAh | 100W | Ultra İnce', 3499.00, 2999.00, 30, 'BB100W', 'Baseus'],
        [6, 'Konfulon A35 10000mAh Mini', 'konfulon-a35-10000mah', '10.000 mAh, 15W kablosuz şarj, kompakt tasarım, çift çıkış.', '10000mAh | 15W | Mini Boy', 999.00, 799.00, 50, 'KA35-10K', 'Konfulon'],

        // Tabletler
        [7, 'iPad Air M2 11" 128GB', 'ipad-air-m2-11', 'M2 çip, 11 inç Liquid Retina ekran, Apple Pencil Pro desteği, WiFi 6E.', 'M2 Çip | 11" | 128GB | WiFi 6E', 24999.00, 22999.00, 10, 'IPA-M2-128', 'Apple'],
        [7, 'Samsung Galaxy Tab S9 FE', 'samsung-galaxy-tab-s9-fe', '10.9 inç, Exynos 1380, 6GB RAM, 128GB, S Pen dahil, IP68.', '10.9" | S Pen | IP68 | 128GB', 14999.00, 12499.00, 15, 'SGTS9FE', 'Samsung'],

        // Aksesuarlar
        [8, 'Logitech MX Master 3S Mouse', 'logitech-mx-master-3s', 'Sessiz tıklama, 8K DPI, MagSpeed scroll, USB-C, çoklu cihaz desteği.', 'Sessiz | 8K DPI | MagSpeed', 3999.00, 3499.00, 20, 'MXM3S', 'Logitech'],
        [8, 'Samsung T7 Shield 1TB SSD', 'samsung-t7-shield-1tb', 'USB 3.2 Gen 2, 1050 MB/s okuma, IP65 dayanıklılık, kompakt taşınabilir SSD.', '1TB | 1050MB/s | IP65', 3299.00, 2799.00, 25, 'ST7S-1TB', 'Samsung'],
        [8, 'Ugreen 100W GaN Şarj Cihazı', 'ugreen-100w-gan', '100W GaN, 4 port (3x USB-C, 1x USB-A), kompakt tasarım, laptop şarj.', '100W | GaN | 4 Port', 1999.00, 1599.00, 40, 'UG100W', 'Ugreen'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `products` (category_id, name, slug, description, short_description, price, discount_price, stock, sku, brand, featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    echo "<p>✅ " . count($products) . " demo ürün eklendi.</p>";

    // Site Ayarları
    $settings = [
        ['site_name', 'V-Commerce'],
        ['site_description', 'Elektronik Ürünlerde Güvenilir Alışveriş'],
        ['site_email', 'info@vcommerce.com'],
        ['site_phone', '+90 555 000 00 00'],
        ['site_address', 'İstanbul, Türkiye'],
        ['currency', 'TRY'],
        ['currency_symbol', '₺'],
        ['shipping_cost', '49.90'],
        ['free_shipping_limit', '2000'],
        ['paytr_merchant_id', ''],
        ['paytr_merchant_key', ''],
        ['paytr_merchant_salt', ''],
        ['paytr_test_mode', '1'],
        ['instagram', 'https://instagram.com/vcommerce'],
        ['facebook', 'https://facebook.com/vcommerce'],
        ['twitter', 'https://twitter.com/vcommerce'],
        ['whatsapp', '+905550000000'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }

    echo "<p>✅ Site ayarları oluşturuldu.</p>";

    echo "<hr><h2 style='color:green'>🎉 Kurulum tamamlandı!</h2>";
    echo "<p><a href='index.php'>🏠 Ana Sayfaya Git</a> | <a href='admin/login.php'>🔑 Admin Paneli</a></p>";
    echo "<p><strong>Admin:</strong> admin / admin123</p>";
    echo "<p><strong>Demo Müşteri:</strong> demo / 123456</p>";

} catch (PDOException $e) {
    die("<h2 style='color:red'>❌ Hata: " . $e->getMessage() . "</h2>");
}
?>