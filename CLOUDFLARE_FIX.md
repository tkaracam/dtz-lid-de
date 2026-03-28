# 404 Hatası Çözümü

## 🚨 Sorun
Cloudflare → Render bağlantısı çalışmıyor

## 🔧 Çözüm Adımları

### 1. Cloudflare SSL/TLS Ayarını Değiştir

Cloudflare Dashboard → SSL/TLS → Overview

```
Encryption mode: Flexible  (Full yerine)
```

**Neden:** Render kendi SSL'sini yönetiyor

---

### 2. Proxy'yi Kapat (Test için)

Cloudflare DNS → Kayıtlar

```
CNAME @   dtz-plattform-de.onrender.com  🟠 Proxied
CNAME www dtz-plattform-de.onrender.com  🟠 Proxied
          ↓
          Değiştir: 🟡 DNS only (gri bulut)
```

**Save**

5 dakika bekle, sonra dene:
```
https://dtz-lid.de
```

---

### 3. Render'da Custom Domain Kontrol

Render Dashboard → Servis → Settings → Custom Domains

Eğer şu hata varsa:
```
❌ dtz-lid.de - Not verified
```

**Sil** ve **yeniden ekle**:
```
[+] Add Custom Domain
   Name: dtz-lid.de
   
[+] Add Custom Domain  
   Name: www.dtz-lid.de
```

Render doğrulama için CNAME kaydı isteyecek:
```
_acme-challenge.dtz-lid.de → [Render verecek]
```

Cloudflare'da ekle, Render'da verify tıkla.

---

### 4. DNS Kaydını Düzelt

Cloudflare'da şöyle olmalı:

```
Type: CNAME
Name: @
Target: dtz-plattform-de.onrender.com
Proxy: DNS only (gri)  ← Önemli!
TTL: Auto
```

```
Type: CNAME
Name: www
Target: dtz-plattform-de.onrender.com
Proxy: DNS only (gri)  ← Önemli!
TTL: Auto
```

---

## ✅ Test

```bash
# 5-10 dk bekle
https://dtz-lid.de
https://www.dtz-lid.de
```

---

## 🆘 Hala Çalışmazsa

Sorun: Render servis çalışmıyor olabilir

Render Dashboard → Servis → Logs kontrol et

Hata varsa bana göster!
