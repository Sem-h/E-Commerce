---
description: GitHub'a güncelleme gönder - versiyonu artırarak commit & push yap
---

# GitHub Güncelleme Workflow

Bu workflow "github güncelle" komutu ile tetiklenir.

## Proje Bilgileri
- **GitHub URL**: https://github.com/Sem-h/E-Commerce
- **E-posta**: semih@mynet.com
- **Branch**: main

## Adımlar

1. `VERSION` dosyasını oku ve mevcut versiyonu al (örn: 2.0.0)
2. Versiyonun son numarasını (patch) 1 artır (2.0.0 → 2.0.1)
3. `VERSION` dosyasını güncelle
4. `README.md` içindeki versiyon badge'ini güncelle (img.shields.io badge)
5. Tüm değişiklikleri stage'le:
// turbo
```
git add -A
```
6. Değişiklikleri commit et (commit mesajı Türkçe, yapılan değişiklikleri özetle):
```
git commit -m "v{YENİ_VERSİYON}: {değişiklik özeti}"
```
7. GitHub'a push et:
```
git push origin main
```

## Önemli Notlar
- Versiyon her zaman SemVer formatında: MAJOR.MINOR.PATCH
- "github güncelle" denildiğinde PATCH numarası 1 artar (2.0.0 → 2.0.1)
- Büyük değişikliklerde MINOR artabilir (kullanıcı belirtirse)
- README.md'deki changelog bölümüne yeni versiyon notu eklenmeli
