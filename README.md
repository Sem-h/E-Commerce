<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" />
  <img src="https://img.shields.io/badge/Version-2.0.4-blue?style=for-the-badge" />
</p>

<h1 align="center">🛒 V-Commerce</h1>
<p align="center">
  <b>Modern, Hızlı ve Güvenli E-Ticaret Platformu</b><br/>
  <sub>PHP & MySQL ile geliştirilmiş, tam özellikli açık kaynak e-ticaret çözümü</sub>
</p>

<p align="center">
  <a href="#-özellikler">Özellikler</a> •
  <a href="#-kurulum">Kurulum</a> •
  <a href="#-ekran-görüntüleri">Ekran Görüntüleri</a> •
  <a href="#-teknolojiler">Teknolojiler</a> •
  <a href="#-changelog">Changelog</a>
</p>

---

## ✨ Özellikler

### 🏪 Mağaza (Frontend)
| Özellik | Açıklama |
|---------|----------|
| 🏠 **Modern Anasayfa** | Hero slider, kategori kartları, öne çıkan ürünler |
| 📦 **Ürün Detay** | Yapışkan galeri, zoom efekti, sekmeli açıklama, benzer ürünler |
| 🔍 **Arama & Filtreleme** | Kategori, marka ve fiyat aralığına göre filtreleme |
| 🛒 **Sepet Sistemi** | Miktar güncelleme, KDV hesaplama, kargo ücreti |
| 💳 **Ödeme** | Kapıda ödeme, havale/EFT, PayTR entegrasyonu (hazır) |
| 🎯 **Kampanya Sistemi** | % indirim, hediye çeki, indirim kodu, müşteriye özel |
| 🏠 **Adrese Teslim** | Şehir bazı gün içi teslimat seçeneği |
| 📍 **Akıllı Adres** | İl/İlçe cascading dropdown, 81 il desteği |
| 🔔 **Fiyat Uyarısı** | Fiyat düşünce haber ver, takip paneli |
| 👤 **Müşteri Paneli** | Siparişler, adresler, profil yönetimi |
| 📱 **Responsive Tasarım** | Tüm cihazlarda mükemmel görünüm |

### 🔧 Admin Paneli
| Özellik | Açıklama |
|---------|----------|
| 📊 **Dashboard** | Satış istatistikleri, günlük özet |
| 📋 **Ürün Yönetimi** | CRUD, toplu düzenleme |
| 📂 **Kategori Yönetimi** | Hiyerarşik alt kategori desteği |
| 📥 **XML Import** | URL/dosya import, çoklu format desteği |
| 💱 **Döviz Kuru** | TCMB entegrasyonu, otomatik USD/EUR → TRY çevirisi |
| 📈 **%20 Kâr Marjı** | XML fiyatlarına otomatik eklenen kâr oranı |
| 👥 **Müşteri Yönetimi** | Sipariş, harcama, arama, ciro istatistikleri |
| 🛡️ **Yönetici Yönetimi** | Admin kullanıcılar, yeni yönetici ekleme |
| 📦 **Sipariş Yönetimi** | Durum takibi, detay görüntüleme |
| 🎯 **Kampanya Yönetimi** | 4 tür kampanya, kupon kodu, kullanım takibi |
| 🚚 **Adrese Teslim Ayarları** | Şehir/ilçe bazlı, ek ücret, aktif/pasif |
| 🖼️ **Slider Yönetimi** | Hero slider, promosyon kartları |

### 💰 Fiyatlandırma Sistemi
```
XML Fiyatı (USD) × TCMB Kuru × 1.20 (Kâr Marjı) = Satış Fiyatı (TL)
Sepet: Ara Toplam + %20 KDV + Kargo = Genel Toplam
```

---

## 🚀 Kurulum

### Gereksinimler
- PHP 8.0+
- MySQL 8.0+ / MariaDB 10.5+
- Apache (mod_rewrite)
- XAMPP / WAMP / LAMP

### Adımlar

```bash
# 1. Projeyi klonlayın
git clone https://github.com/Sem-h/E-Commerce.git
cd E-Commerce

# 2. Veritabanı ayarlarını yapın
# config/db.php dosyasını düzenleyin

# 3. Kurulum sihirbazını çalıştırın
# Tarayıcıda: http://localhost/E-Commerce/setup.php
```

### Varsayılan Giriş Bilgileri
| Panel | URL | E-posta | Şifre |
|-------|-----|---------|-------|
| 🔧 Admin | `/admin/login.php` | `admin@vcommerce.com` | `admin123` |
| 👤 Müşteri | `/client/login.php` | Kayıt olun | — |

---

## 🏗️ Proje Yapısı

```
E-Ticaret/
├── admin/                  # Admin paneli
│   ├── includes/           # Admin header, footer, sidebar
│   ├── index.php           # Dashboard
│   ├── products.php        # Ürün yönetimi
│   ├── categories.php      # Kategori yönetimi
│   ├── orders.php          # Sipariş yönetimi
│   ├── customers.php       # Müşteri yönetimi
│   ├── users.php           # Yönetici yönetimi
│   ├── campaigns.php       # Kampanya yönetimi
│   ├── delivery-settings.php # Adrese teslim ayarları
│   ├── sliders.php         # Slider yönetimi
│   └── xml-import.php      # XML import (TCMB kuru)
├── ajax/                   # AJAX endpointleri
│   ├── cart.php             # Sepet işlemleri
│   └── wishlist.php         # Favori işlemleri
├── assets/
│   ├── css/                # Stylesheet'ler
│   │   ├── style.css       # Ana tema
│   │   ├── components.css  # Bileşen stilleri
│   │   ├── layout.css      # Layout stilleri
│   │   └── admin.css       # Admin panel stili
│   ├── js/                 # JavaScript dosyaları
│   └── uploads/            # Yüklenen dosyalar
├── client/                 # Müşteri paneli
│   ├── includes/           # Panel sidebar
│   ├── orders.php          # Siparişlerim
│   ├── addresses.php       # Adreslerim
│   └── profile.php         # Profil ayarları
├── config/
│   ├── config.php          # Ana konfigürasyon
│   └── db.php              # Veritabanı bağlantısı
├── includes/
│   ├── header.php          # Site header
│   ├── footer.php          # Site footer
│   ├── functions.php       # Yardımcı fonksiyonlar
│   └── product-card.php    # Ürün kartı bileşeni
├── index.php               # Anasayfa
├── products.php            # Ürün listesi
├── product-detail.php      # Ürün detay sayfası
├── cart.php                # Sepet
├── checkout.php            # Sipariş tamamlama
├── search.php              # Arama
└── setup.php               # Kurulum sihirbazı
```

---

## 🛠️ Teknolojiler

<table>
<tr>
<td align="center"><b>Backend</b></td>
<td align="center"><b>Frontend</b></td>
<td align="center"><b>Veritabanı</b></td>
<td align="center"><b>Entegrasyon</b></td>
</tr>
<tr>
<td>PHP 8.x</td>
<td>HTML5 / CSS3</td>
<td>MySQL 8.0</td>
<td>TCMB XML API</td>
</tr>
<tr>
<td>PDO</td>
<td>Vanilla JS</td>
<td>PDO Prepared</td>
<td>PayTR (hazır)</td>
</tr>
<tr>
<td>Session Auth</td>
<td>Font Awesome 6</td>
<td>Foreign Keys</td>
<td>XML Import</td>
</tr>
</table>

---

## 📋 Changelog

### v2.0.0 — 18 Şubat 2026
> 🎯 **Büyük Güncelleme — Fiyatlandırma & Tasarım**

#### 💱 Döviz & Fiyatlandırma
- ✅ TCMB'den canlı USD/EUR kuru çekme (5dk cache)
- ✅ XML import'ta otomatik kur çevirisi (USD → TRY)
- ✅ %20 kâr marjı otomatik ekleme
- ✅ Sepette %20 KDV hesaplama
- ✅ Fiyat para birimi seçimi (TRY / USD / EUR)

#### 🎨 Ürün Detay Sayfası Yeniden Tasarım
- ✅ Yapışkan galeri + hover zoom efekti
- ✅ Gradient fiyat kutusu + tasarruf gösterimi
- ✅ Marka rozeti (tıklanabilir)
- ✅ Modern miktar seçici + favori butonu
- ✅ Güvence kartları (Ücretsiz Kargo, Güvenli Ödeme, Kolay İade)
- ✅ Sekmeli açıklama (Ürün Açıklaması / Teknik Özellikler)
- ✅ HTML tablo formatı (zebra-stripe)

#### 🔤 Encoding Düzeltmeleri
- ✅ HTML entity decode (ürün adları & açıklamalar)
- ✅ 232 ürün adı + 1429 kısa açıklama düzeltildi
- ✅ Import script'te otomatik decode

---

### v2.0.4 — 19 Şubat 2026
> 🛠️ **Admin Panel İyileştirmeleri**

#### 📊 Admin Sidebar Reorganizasyonu
- ✅ E-ticaret odaklı menü sıralaması (Dashboard → Siparişler → Ürünler → Kategoriler → Müşteriler)
- ✅ 3 mantıksal grup: E-Ticaret, Pazarlama, Ayarlar

#### 👥 Müşteri & Yönetici Ayrımı
- ✅ `customers.php`: Müşteri listesi (sipariş sayısı, toplam harcama, ciro, arama)
- ✅ `users.php`: Sadece admin kullanıcılar + yeni yönetici ekleme modalı
- ✅ İstatistik kartları (toplam müşteri, aktif, sipariş veren, toplam ciro)

---

### v2.0.3 — 18 Şubat 2026
> 🔔 **Fiyat Uyarısı & İyileştirmeler**

#### 🔔 Fiyat Düşünce Haber Ver
- ✅ Ürün detayda "🔔 Fiyat Düşünce Haber Ver" butonu
- ✅ Ürün kartında 🔔 ikon (hover overlay)
- ✅ AJAX toggle (ekle/kaldır)
- ✅ Client panel: Fiyat Uyarılarım sayfası
- ✅ Kayıt fiyatı vs güncel fiyat karşılaştırması
- ✅ Fiyat düştüğünde yeşil badge + "Satın Al" butonu

#### 📍 Adres İyileştirmeleri
- ✅ Mahalle/Cadde alanı eklendi
- ✅ İl/İlçe cascading dropdown (81 il)
- ✅ Adrese teslim seçildiğinde kargo satırı "Adrese Teslim" olarak değişiyor

---

### v2.0.2 — 18 Şubat 2026
> 🎯 **Kampanya Modülü, Akıllı Adres & Adrese Teslim**

#### 🎯 Kampanya Sistemi
- ✅ 4 kampanya türü: % indirim, hediye çeki, indirim kodu, müşteriye özel
- ✅ Admin CRUD sayfası (istatistik kartları + tab'lı form)
- ✅ Otomatik kupon kodu üretici
- ✅ Sepette indirim kodu girişi + canlı hesaplama
- ✅ Kullanım limiti, min. sepet tutarı, tarih aralığı
- ✅ Sipariş kaydında kampanya takibi

#### 📍 Türkiye Adres Seçici
- ✅ 81 il + tüm ilçeler JSON veri dosyası
- ✅ Cascading İl → İlçe dropdown (AJAX)
- ✅ Mahalle/Cadde serbest metin alanı
- ✅ Adres ekleme + düzenleme formları
- ✅ Checkout entegrasyonu

#### 🚚 Adrese Teslim (Şehir İçi Teslimat)
- ✅ Admin ayar sayfası (aktif/pasif, şehir, ücret, ilçe filtresi)
- ✅ Müşteri önizleme paneli
- ✅ Checkout'ta dinamik göster/gizle (İl seçimine göre)
- ✅ Kargo satırı "Adrese Teslim" olarak değişiyor
- ✅ Sipariş kaydında teslimat bilgisi

#### 🖼️ Slider Yönetimi
- ✅ Admin slider CRUD sayfası (premium tasarım)
- ✅ Homepage dinamik slider + promosyon kartları

---

### v1.0.0 — 17 Şubat 2026
> 🚀 **İlk Sürüm**

- ✅ E-ticaret altyapısı (ürün, sepet, sipariş)
- ✅ Admin paneli (dashboard, ürün/kategori/sipariş yönetimi)
- ✅ Müşteri paneli (siparişler, adresler, profil)
- ✅ XML ürün import (dosya & URL)
- ✅ Hiyerarşik kategori sistemi (alt kategoriler)
- ✅ Mega menü & sidebar navigasyonu
- ✅ Responsive tasarım
- ✅ Arama & filtreleme (kategori, marka, fiyat)
- ✅ Kurulum sihirbazı (setup.php)

---

## 📄 Lisans

Bu proje **MIT Lisansı** ile lisanslanmıştır.

---

<p align="center">
  <sub>⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın!</sub><br/>
  <sub>Built with ❤️ by <b>Semih Akbaş</b></sub>
</p>
