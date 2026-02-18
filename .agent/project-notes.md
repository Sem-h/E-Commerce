# V-Commerce E-Ticaret Projesi — Geliştirici Notları

## Proje Bilgileri
- **Proje Adı:** V-Commerce
- **Klasör:** `b:\Projeler\E-Ticaret`
- **GitHub:** https://github.com/Sem-h/E-Commerce
- **E-posta:** semih@mynet.com
- **Versiyon:** VERSION dosyasından okunur (şu an 2.0.0)
- **Branch:** main
- **Veritabanı:** MySQL — `vcommerce` (root / rexe2026)
- **Localhost:** http://127.0.0.1/E-Ticaret/
- **PHP Yolu:** C:\xampp\php\php.exe (PATH'te yok, tam yol kullanılmalı)

## Teknik Mimari
- **Backend:** PHP 8.x (framework yok, saf PHP)
- **Veritabanı:** MySQL + PDO (`config/db.php` — Database sınıfı, statik metodlar)
- **Frontend:** Vanilla HTML/CSS/JS, Font Awesome 6
- **CSS:** 3 dosya: `style.css` (temel), `components.css` (bileşenler), `layout.css` (responsive)
- **Admin CSS:** `admin.css` (ayrı)

## Önemli Dosyalar
| Dosya | Açıklama |
|-------|----------|
| `config/config.php` | Ana config, session, BASE_URL, DB ve functions dahil |
| `config/db.php` | Database sınıfı (PDO wrapper, reconnect destekli) |
| `includes/functions.php` | Tüm yardımcı fonksiyonlar (TCMB kuru dahil) |
| `includes/header.php` | Site header + mega menü |
| `includes/product-card.php` | Ürün kartı bileşeni |
| `admin/xml-import.php` | XML ürün import (URL + dosya, TCMB kuru, %20 kâr) |
| `setup.php` | Kurulum sihirbazı (DB tabloları oluşturur) |

## Fiyatlandırma Sistemi
1. **XML Import:** `USD fiyat × TCMB kuru × 1.20 (kâr marjı)` = ürün fiyatı
2. **Sepet/Checkout:** `Ara Toplam + %20 KDV + Kargo` = genel toplam
3. **TCMB Kuru:** `getTCMBRates()` fonksiyonu — `includes/functions.php`'de, 5dk cache
4. **Kargo:** `shipping_cost` (49.90 TL) ve `free_shipping_limit` (2000 TL) — settings tablosundan

## Veritabanı Tabloları
- `products` — Ürünler (id, name, slug, price, discount_price, stock, image, category_id, brand, description, short_description, view_count)
- `categories` — Kategoriler (hiyerarşik, parent_id)
- `orders` / `order_items` — Siparişler
- `users` — Kullanıcılar (admin / customer rolleri)
- `addresses` — Adresler
- `cart` — Sepet (NOT: tablo adı `cart`, `cart_items` DEĞİL!)
- `wishlist` — Favoriler
- `settings` — Site ayarları (key/value)
- `xml_imports` — Import geçmişi
- `sliders` — Ana sayfa slider yönetimi (hero slider + promosyon kartları)
- `campaigns` — Kampanya/indirim yönetimi (4 tür: %, hediye çeki, kod, müşteriye özel)
- `campaign_usage` — Kampanya kullanım takibi

## Kampanya Sistemi
- 4 tür: `percentage` (% indirim), `gift_voucher` (hediye çeki), `discount_code` (indirim kodu), `customer_specific` (müşteriye özel)
- Sepette kod girişi → doğrulama → indirim uygulama
- Müşteriye özel kampanyalar login sonrası otomatik uygulanır
- Kullanım limiti, tarih aralığı, min sipariş tutarı, max indirim kontrolü

## CLI Uyarıları
- `php` PATH'te yok, `C:\xampp\php\php.exe` kullanılmalı
- `config.php` CLI'da `$_SERVER['SCRIPT_NAME']` sorunları yaratabilir
- Veritabanı işlemleri için doğrudan PDO bağlantısı daha güvenli:
  ```php
  $pdo = new PDO('mysql:host=localhost;dbname=vcommerce;charset=utf8mb4', 'root', 'rexe2026');
  ```
- Foreign key constraint'ler nedeniyle silme işlemlerinde `SET FOREIGN_KEY_CHECKS=0` gerekebilir

## GitHub Güncelleme Kuralı
- Kullanıcı "github güncelle" dediğinde: `/github-guncelle` workflow'unu kullan
- VERSION dosyasındaki patch numarasını 1 artır
- README badge'ini güncelle
- `git add -A && git commit -m "v{VER}: özet" && git push origin main`

## Admin Giriş
- URL: http://127.0.0.1/E-Ticaret/admin/login.php
- Kullanıcı Adı: administrator
- Şifre: SS44723646bb!!..
