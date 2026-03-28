# Cloudflare Kurulumu - dtz-lid.de

## 🚀 Adım 1: Cloudflare Hesabı

1. https://dash.cloudflare.com/sign-up adresine git
2. E-posta ve şifre ile kaydol
3. E-posta doğrulamasını yap

## 🌐 Adım 2: Site Ekle

1. "Add a Site" (Site Ekle) butonuna tıkla
2. Domain yaz: `dtz-lid.de`
3. "Add Site" → Plan seç: **Free**
4. Continue

## 📋 Adım 3: DNS Kayıtları

Cloudflare mevcut kayıtları tarayacak. Şunları görmen lazım:

**Ekle (manuel):**
```
Type:    CNAME
Name:    www
Target:  dtz-plattform-de.onrender.com
Proxy:   ON (turuncu bulut)
```

```
Type:    CNAME
Name:    @  (kök domain)
Target:  dtz-plattform-de.onrender.com  
Proxy:   ON (turuncu bulut)
```

> **Not:** IONOS'taki A kayıtlarını silebilirsin.

## 🔄 Adım 4: Nameserver Değiştir (IONOS'ta)

Cloudflare sana şu nameserver'ları verecek:
```
lara.ns.cloudflare.com
greg.ns.cloudflare.com
```

**IONOS'ta değiştir:**
1. IONOS Panel → Domain → Nameserver
2. "Eigene Nameserver verwenden" (Özel nameserver)
3. Yaz:
   ```
   lara.ns.cloudflare.com
   greg.ns.cloudflare.com
   ```
4. Speichern

## ⏱️ Adım 5: Bekle

Cloudflare kontrol ediyor... (genelde 5-15 dakika)

Status: **"Active"** olunca hazır!

## ✅ Adım 6: SSL/TLS Ayarları

Cloudflare Dashboard → SSL/TLS:

**Overview:**
- Encryption mode: **Full (strict)**

**Edge Certificates:**
- Always Use HTTPS: **ON** ✅
- Automatic HTTPS Rewrites: **ON** ✅

## 🎯 Adım 7: Page Rules (www → non-www)

Cloudflare Dashboard → Rules → Page Rules:

**Ekle:**
```
URL: www.dtz-lid.de/*
Setting: Forwarding URL
Destination: https://dtz-lid.de/$1
Status: 301 Permanent Redirect
```

## 🎉 Bitti!

Şimdi test et:
```
https://dtz-lid.de       ✅
https://www.dtz-lid.de   → dtz-lid.de yönlendirir
```

## 🆘 Sorun Olursa

**"Site not found" hatası:**
→ Render'da custom domain ekle: `dtz-lid.de`

**"SSL Error":**
→ Cloudflare SSL/TLS → Full (strict)

**DNS yayilmiyor:**
→ 24 saat bekle veya IONOS'ta nameserver'ları kontrol et

---

**Hazır!** 🚀
