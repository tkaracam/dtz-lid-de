# Render.com Deploy Talimatları

## 🚀 5 Dakikada Deploy

### Adım 1: Render Hesabı Oluştur

1. [render.com](https://render.com) adresine git
2. "Get Started for Free" butonuna tıkla
3. GitHub ile bağlan (önerilen)

### Adım 2: Blueprint Deploy

**Seçenek A: Blueprint Button (En Kolay)**

1. GitHub reposuna git: https://github.com/tkaracam/dtz-lid-de
2. README'deki "Deploy to Render" butonuna tıkla
3. Render'da servis oluştur

**Seçenek B: Manuel**

1. Render Dashboard → "New +" → "Blueprint"
2. GitHub reposunu seç: `tkaracam/dtz-lid-de`
3. "Apply" butonuna tıkla

### Adım 3: Environment Variables Ayarla

Render Dashboard → Servis → Environment

```
JWT_SECRET          = [Aşağıdaki komutla üret]
OPENAI_API_KEY      = sk-xxx (OpenAI'dan al)
AZURE_TTS_KEY       = xxx (Azure'dan al)
STRIPE_SECRET_KEY   = sk_live_xxx (Stripe'dan al)
```

**JWT Secret Üret:**
```bash
openssl rand -base64 32
```

### Adım 4: Deploy!

"Manual Deploy" → "Deploy latest commit"

**Bekle:** İlk deploy ~5-10 dakika sürer

### Adım 5: Domain Ayarla

1. Render Dashboard → Servis → Settings
2. Custom Domain ekle (opsiyonel)
3. Veya varsayılan domain'i kullan:
   `https://dtz-lernplattform-xxx.onrender.com`

---

## ✅ Deploy Sonrası Kontrol

### Sağlık Kontrolü

```bash
curl https://dtz-lernplattform-xxx.onrender.com/api/health.php
```

**Beklenen yanıt:**
```json
{"status":"ok","timestamp":"2026-03-28T...","database":"connected"}
```

### Siteyi Test Et

1. Tarayıcıda aç: `https://dtz-lernplattform-xxx.onrender.com`
2. Kayıt olmayı dene
3. Giriş yapmayı dene

---

## 🔧 Sorun Giderme

### Deploy Başarısız Olursa

1. Render Dashboard → Servis → Logs
2. Hata mesajını kontrol et
3. Common issues:
   - `JWT_SECRET` eksik → Environment variable ekle
   - Build hatası → Dockerfile kontrol et

### "Du bist offline" Hatası

```bash
# Render Dashboard → Servis → Shell
rm -rf /var/www/html/frontend/sw.js
curl -X POST https://api.render.com/v1/services/SERVICE_ID/deploys \
  -H "Authorization: Bearer API_KEY" \
  -d '{"clearCache": true}'
```

### Veritabanı Hatası

1. Render Dashboard → Disks
2. Disk bağlı mı kontrol et
3. Permissions:
   ```bash
   chmod 775 /var/www/html/database
   chown -R www-data:www-data /var/www/html/database
   ```

---

## 💰 Maliyet

| Plan | Fiyat | Özellikler |
|------|-------|------------|
| **Free** | $0 | 512 MB RAM, uyku modu (15 dk kullanımdan sonra) |
| **Starter** | $7/ay | 512 MB RAM, 7/24 çalışır |
| **Standard** | $25/ay | 2 GB RAM, custom domain |

**Başlangıç için Free plan yeterli!**

---

## 🔄 Otomatik Deploy

Her `git push origin main` → Otomatik deploy!

### Branch deploy:
```bash
git checkout -b feature/yeni-ozellik
git push origin feature/yeni-ozellik
# Render'da preview deploy otomatik oluşur
```

---

## 📞 Destek

Render destek: https://render.com/docs
Sorun yaşarsan: GitHub Issues aç

---

**Hazır mısın?** 👉 [render.com](https://render.com)
