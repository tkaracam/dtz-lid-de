# dtz-lid.de Domain Yapılandırması

## 🎯 Adım 1: DNS Ayarları

Domain sağlayıcınızın (Namecheap, GoDaddy, Cloudflare vb.) paneline gidin.

### DNS Kayıtları:

**CNAME Kaydı (Önerilen):**
```
Type:    CNAME
Name:    www
Value:   dtz-lernplattform-xxx.onrender.com
TTL:     3600
```

**A Kaydı (Root domain için):**
```
Type:    A
Name:    @ (veya boş)
Value:   216.24.57.1   (Render IP - servis oluşturulunca verilecek)
TTL:     3600
```

**Alternatif: Cloudflare ile (Önerilen)**
- Nameserver'ları Cloudflare'a yönlendir
- CNAME flattening ile root domain çalışır
- Ücretsiz SSL + CDN

---

## 🚀 Adım 2: Render'da Custom Domain Ekle

1. Render Dashboard → Servis → Settings
2. "Custom Domains" bölümüne git
3. "Add Custom Domain"
4. `dtz-lid.de` ve `www.dtz-lid.de` ekle

Render otomatik SSL sertifikası oluşturacak (Let's Encrypt)

---

## ⚙️ Adım 3: Environment Variables Güncelle

Render Dashboard → Servis → Environment:

```
APP_URL = https://dtz-lid.de
```

---

## 🔄 Adım 4: www → non-www Yönlendirme

**Seçenek A: Cloudflare'da (Kolay)**
- "Always Use HTTPS" aç
- "Automatic HTTPS Rewrites" aç

**Seçenek B: PHP'de**

`.htaccess` ekle:
```apache
RewriteEngine On
RewriteCond %{HTTP_HOST} ^www\.dtz-lid\.de [NC]
RewriteRule ^(.*)$ https://dtz-lid.de/$1 [L,R=301]
```

---

## ✅ Kontrol Listesi

- [ ] DNS kayıtları oluşturuldu
- [ ] Render'da custom domain eklendi
- [ ] SSL sertifikası aktif (yeşil kilit)
- [ ] `https://dtz-lid.de` çalışıyor
- [ ] `https://www.dtz-lid.de` → `dtz-lid.de` yönlendiriyor

---

## 🆘 Sorun Olursa

### "DNS_PROBE_FINISHED_NXDOMAIN"
→ DNS yayılımı bekleniyor (24-48 saat)

### "SSL Certificate Error"
→ Render'da "Verify" butonuna tıkla

### "502 Bad Gateway"
→ Servis çalışmıyor, logları kontrol et

---

**Hazır olduğunda:** `https://dtz-lid.de` çalışacak! 🎉
