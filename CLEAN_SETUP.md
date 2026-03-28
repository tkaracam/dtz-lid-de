# SIFIRDAN TEMİZ KURULUM

## 🗑️ ADIM 1: Her Şeyi Temizle

### 1.1 Render'da Servisi Sil
```
Render Dashboard → dtz-plattform-de → Settings
→ Delete Service → Onayla
```

### 1.2 Cloudflare Kayıtları Sil
```
Cloudflare Dashboard → dtz-lid.de → DNS → Records
→ Tüm kayıtları seç → Delete
```

### 1.3 IONOS'ta Nameserver Eski Haline Getir
```
IONOS → Domain → Nameserver
→ ns1.ionos.de
→ ns2.ionos.de
```

**5 dakika bekle**

---

## 🚀 ADIM 2: Yeni Render Servisi Oluştur

### 2.1 Blueprint ile Deploy
```
https://github.com/tkaracam/dtz-lid-de
```
GitHub repo URL'sini kopyala.

### 2.2 Render'da
```
Render Dashboard → New + → Blueprint
→ GitHub repo seç: tkaracam/dtz-lid-de
→ Apply
```

### 2.3 Servis Ayarları
```
Name: dtz-app
Region: Frankfurt (EU)
Plan: Free
```

### 2.4 Environment Variables
```
JWT_SECRET = [üret: openssl rand -base64 32]
APP_ENV = production
```

### 2.5 Disk Ekle
```
Mount Path: /var/www/html/database
Size: 1 GB
```

**Create Service** → Deploy başlayacak (5-10 dk)

---

## ☁️ ADIM 3: Yeni Render URL'ni Al

Deploy tamamlanınca Render sana şöyle bir URL verecek:
```
https://dtz-app-xxx.onrender.com
```

Bu URL'yi **kopyala** (sonraki adımda lazım)

---

## 🌐 ADIM 4: Cloudflare DNS

### 4.1 Site Ekle (varsa atla)
```
Cloudflare → Add Site → dtz-lid.de
Plan: Free
```

### 4.2 DNS Kayıtları

**CNAME - Root Domain:**
```
Type:     CNAME
Name:     @
Target:   [YENI-RENDER-URL].onrender.com
Proxy:    DNS only (GRI bulut) ⚠️
TTL:      Auto
```

**CNAME - WWW:**
```
Type:     CNAME
Name:     www
Target:   [YENI-RENDER-URL].onrender.com
Proxy:    DNS only (GRI bulut) ⚠️
TTL:      Auto
```

**Save**

### 4.3 SSL/TLS
```
SSL/TLS → Overview
Encryption mode: Flexible
```

---

## 🔗 ADIM 5: Render'da Custom Domain

Render Dashboard → Servis → Settings → Custom Domains

```
[+] Add Custom Domain
dtz-lid.de

[+] Add Custom Domain
www.dtz-lid.de
```

**Verify** tıkla (doğrulanacak)

---

## ⏱️ ADIM 6: Bekle ve Test

**10-15 dakika bekle** (DNS yayılımı)

Test et:
```
https://dtz-lid.de
https://www.dtz-lid.de
```

---

## ✅ KONTROL LİSTESİ

- [ ] Yeni Render servisi çalışıyor
- [ ] Cloudflare'da 2 CNAME kaydı var (GRI proxy)
- [ ] IONOS'ta nameserver: ns1.ionos.de, ns2.ionos.de
- [ ] Render'da 2 custom domain verify edildi
- [ ] SSL/TLS: Flexible
- [ ] Site açılıyor

---

**Hangi adımdasın?** 🎯
