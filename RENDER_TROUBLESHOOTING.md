# Render Deploy Sorun Giderme

## ❌ "Deploy Failed" Hatası

### Çözüm 1: Manuel Deploy Dene

1. Render Dashboard → Servis → Manual Deploy
2. "Deploy latest commit"
3. Logları kontrol et

### Çözüm 2: Blueprint Yerine Manuel Servis Oluştur

Eğer blueprint çalışmazsa:

```yaml
# render.yaml - BU DOSYAYI KULLAN
services:
  - type: web
    name: dtz-app
    runtime: docker
    repo: https://github.com/tkaracam/dtz-lid-de
    dockerfilePath: ./Dockerfile
    envVars:
      - key: JWT_SECRET
        generateValue: true
    disk:
      name: db
      mountPath: /var/www/html/database
      sizeGB: 1
```

### Çözüm 3: Docker Build Test Et (Yerel)

```bash
# Repoya git
cd dtz-lid-de

# Docker build test et
docker build -t dtz-test .

# Çalıştır
docker run -p 8080:8080 -e JWT_SECRET=test dtz-test

# Hata varsa burada gözükür
```

### Çözüm 4: Basit PHP Server (Docker olmadan)

Render'da **New Web Service** → **Runtime: Native**

**Build Command:**
```bash
apt-get update && apt-get install -y php8.1-cli php8.1-sqlite3 php8.1-mbstring unzip curl && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && composer install --no-dev
```

**Start Command:**
```bash
mkdir -p database && chmod 775 database && php -S 0.0.0.0:8080
```

---

## 🔍 Logları Kontrol Et

Render Dashboard → Servis → Logs

### Yaygın Hatalar

**1. "No such file or directory"**
```
Çözüm: Dosya yollarını kontrol et
```

**2. "Permission denied"**
```
Çözüm: chmod komutlarını Dockerfile'a ekle
```

**3. "Port already in use"**
```
Çözüm: Port 8080 kullan (Render zorunlu)
```

**4. "Cannot find composer"**
```
Çözüm: Composer'ı Dockerfile'da kur
```

---

## ✅ Çalışan Basit Dockerfile

```dockerfile
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Enable mod_rewrite
RUN a2enmod rewrite

# Set port
RUN sed -i 's/80/8080/' /etc/apache2/ports.conf /etc/apache2/sites-available/*

# Copy files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
```

---

## 🆘 Hala Çalışmıyor?

### GitHub Issues Aç

1. https://github.com/tkaracam/dtz-lid-de/issues/new
2. Render loglarını yapıştır
3. Hata mesajını yaz

### Alternatif Platformlar Dene

- **Railway.app** - Daha kolay
- **Fly.io** - Ücretsiz tier var
- **DigitalOcean App Platform** - $5/ay

---

## 🎯 Hızlı Test

```bash
# Local test
git clone https://github.com/tkaracam/dtz-lid-de.git
cd dtz-lid-de
docker build -t dtz .
docker run -p 8080:8080 dtz

# Çalışıyorsa Render'da da çalışır
curl http://localhost:8080/api/health.php
```

---

**Son Güncelleme:** 28.03.2026
