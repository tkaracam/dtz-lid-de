# Production Deployment Guide

## 🚀 Quick Start

### 1. Render.com (En Kolay - Önerilen)

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy)

1. [Render.com](https://render.com)'da hesap oluştur
2. GitHub reposunu bağla
3. Blueprint kullanarak deploy et
4. Environment variables'ları ayarla
5. Bitiş! 🎉

**Otomatik:**
- SSL sertifikası
- Domain: `https://dtz-lernplattform.onrender.com`
- Otomatik deploy her push'ta

---

## 🐳 Docker ile Deploy

### Yerel Test

```bash
# 1. Environment dosyasını kopyala
cp .env.example .env
# .env dosyasını düzenle

# 2. Container'ları başlat
docker-compose up -d

# 3. Logları kontrol et
docker-compose logs -f app

# 4. Tarayıcıda aç
open http://localhost:8080
```

### Production Docker

```bash
# Image build et
docker build -t dtz-lernplattform:latest .

# Çalıştır
docker run -d \
  -p 8080:8080 \
  -e JWT_SECRET=$(openssl rand -base64 32) \
  -e OPENAI_API_KEY=sk-xxx \
  -v $(pwd)/database:/var/www/html/database \
  --name dtz-app \
  dtz-lernplattform:latest
```

---

## ☁️ Cloud Platformları

### DigitalOcean App Platform

1. [DigitalOcean](https://digitalocean.com) hesabı oluştur
2. Apps > Create App
3. GitHub reposunu seç
4. **Environment Variables** ekle:
   ```
   JWT_SECRET = <üret>
   OPENAI_API_KEY = sk-xxx
   STRIPE_SECRET_KEY = sk_live_xxx
   ```
5. Deploy!

**Maliyet:** $5/ay'dan başlar

### Railway.app

```bash
# Railway CLI kur
npm install -g @railway/cli

# Login
railway login

# Projeyi bağla
railway link

# Deploy
railway up

# Domain al
railway domain
```

### Fly.io

```bash
# Fly CLI kur
brew install flyctl

# Login
fly auth login

# Launch
fly launch

# Secrets ayarla
fly secrets set JWT_SECRET=$(openssl rand -base64 32)
fly secrets set OPENAI_API_KEY=sk-xxx

# Deploy
fly deploy
```

---

## 🔧 Manuel VPS Deploy (Ubuntu)

### Sunucu Hazırlığı

```bash
# Ubuntu 22.04 LTS

# 1. Güncelleme
sudo apt update && sudo apt upgrade -y

# 2. Docker kur
sudo apt install docker.io docker-compose -y
sudo usermod -aG docker $USER

# 3. Git kur
sudo apt install git -y

# 4. Repo klonla
git clone https://github.com/tkaracam/dtz-lid-de.git
cd dtz-lid-de

# 5. Environment ayarla
cp .env.example .env
nano .env  # Değerleri düzenle

# 6. Başlat
docker-compose up -d
```

### Nginx Reverse Proxy (Önerilen)

```nginx
# /etc/nginx/sites-available/dtz

server {
    listen 80;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/dtz /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### SSL Sertifikası (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d your-domain.com
```

---

## 🔐 Environment Variables

| Variable | Açıklama | Zorunlu |
|----------|----------|---------|
| `JWT_SECRET` | Güvenlik anahtarı (32+ karakter) | ✅ |
| `DB_DRIVER` | sqlite veya pgsql | ✅ |
| `DB_PATH` | SQLite dosya yolu | SQLite için |
| `OPENAI_API_KEY` | GPT-4 API anahtarı | ❌ |
| `AZURE_TTS_KEY` | Azure TTS anahtarı | ❌ |
| `STRIPE_SECRET_KEY` | Stripe gizli anahtar | ❌ |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook | ❌ |

### JWT Secret Üretme

```bash
openssl rand -base64 32
```

---

## 📊 Monitoring & Logs

### Docker Logs

```bash
# Tüm loglar
docker-compose logs -f

# Sadece app
docker-compose logs -f app

# Hata logları
docker-compose logs -f app | grep ERROR
```

### Health Check

```bash
# API sağlık kontrolü
curl http://localhost:8080/api/health.php

# Beklenen yanıt:
# {"status":"ok","timestamp":"2026-03-28T...","database":"connected"}
```

### Güncelleme

```bash
# 1. Pull latest
git pull origin main

# 2. Rebuild
docker-compose down
docker-compose up -d --build

# 3. Verify
docker-compose ps
```

---

## 🔄 CI/CD Pipeline

GitHub Actions otomatik olarak:
1. Her push'ta test çalıştırır
2. Docker image build eder
3. GitHub Container Registry'e push eder
4. Render.com'a deploy eder

### Secrets Ayarları (GitHub)

GitHub Repo > Settings > Secrets and variables > Actions

```
RENDER_API_KEY        # Render API anahtarı
RENDER_SERVICE_ID     # Render servis ID
GITHUB_TOKEN          # Otomatik oluşturulur
SLACK_WEBHOOK_URL     # Opsiyonel bildirimler
```

---

## 🛠️ Sorun Giderme

### "Du bist offline" Hatası

```bash
# Service Worker temizle
docker-compose exec app rm -rf /var/www/html/frontend/sw.js
docker-compose restart app

# Veya tarayıcıda:
# DevTools > Application > Service Workers > Unregister
```

### 500 Internal Server Error

```bash
# Logları kontrol et
docker-compose logs app | tail -50

# PHP syntax check
docker-compose exec app php -l /var/www/html/api/auth/login.php
```

### Veritabanı Hatası

```bash
# SQLite permissions
sudo chown -R 33:33 ./database
sudo chmod -R 775 ./database

# Container içinden test
docker-compose exec app php -r "var_dump(PDO::getAvailableDrivers());"
```

---

## 💰 Maliyet Karşılaştırması

| Platform | Fiyat | Özellikler |
|----------|-------|------------|
| **Render** | Free / $7+ | Kolay, otomatik SSL |
| **Railway** | $5+ | Kolay, iyi dashboard |
| **Fly.io** | Free / $2+ | Hızlı, edge deploy |
| **DigitalOcean** | $5+ | Full kontrol |
| **Hetzner** | €3+ | En ucuz VPS |
| **AWS Lightsail** | $5+ | AWS ekosistemi |

---

## 📞 Destek

Sorun yaşarsanız:
1. GitHub Issues: https://github.com/tkaracam/dtz-lid-de/issues
2. Logları kontrol edin
3. Health endpoint'i test edin

---

**Son Güncelleme:** 28.03.2026
**Versiyon:** 1.0.0
