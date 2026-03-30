# DTZ Lernplattform - CI/CD ve Monitoring Kurulumu

## 🚀 Yapılan Geliştirmeler

### 1. CI/CD Pipeline (GitHub Actions)

**Dosya:** `.github/workflows/ci.yml`

Her push ve PR'da otomatik çalışan:
- ✅ PHP Syntax Check
- ✅ Security Scan (eval/exec detection)
- ✅ Code Style Check (PSR-12)
- ✅ Frontend ESLint
- ✅ Otomatik Staging/Production deployment

**Kullanım:**
```bash
# GitHub'da repository ayarlarından Secrets ekle:
# - DEPLOY_HOST
# - DEPLOY_USER  
# - DEPLOY_KEY
# - JWT_SECRET
```

### 2. Test Altyapısı

**PHPUnit Testleri:**
```bash
# Bağımlılıkları yükle
composer install

# Testleri çalıştır
composer test

# Code style kontrol
composer lint

# Code style düzelt
composer lint:fix

# Static analiz
composer analyse
```

**Mevcut Testler:**
- JWT generation/verification
- Email validation
- Password strength
- XSS/SQL Injection detection
- String sanitization

### 3. Monitoring Sistemi

**Frontend Monitoring:**
- `frontend/js/monitoring.js` - Error tracking, performance metrics
- Otomatik hata yakalama
- API response time tracking
- Page load metrics
- Session tracking

**Health Check:**
- `api/health.php` - Sistem durumu endpoint
- Database bağlantı kontrolü
- Disk space kontrolü
- JWT secret kontrolü

**Metrics Logging:**
- `api/metrics/log.php` - Client-side metrics toplama
- Otomatik log rotation
- Error alerting hazırlığı

### 4. Güvenlik İyileştirmeleri

- ✅ Content Security Policy (CSP)
- ✅ XSS Protection
- ✅ Rate Limiting
- ✅ Input Validation
- ✅ SQL Injection detection
- ✅ Security Headers

## 📁 Yeni Dosya Yapısı

```
dtz-lid-de/
├── .github/
│   └── workflows/
│       └── ci.yml              # GitHub Actions CI/CD
├── src/
│   ├── Security/
│   │   ├── SecurityHeaders.php  # Güvenlik header'ları
│   │   └── InputValidator.php   # Input validasyon
│   └── Auth/
│       └── JWT.php              # JWT işlemleri
├── tests/
│   └── AuthTest.php            # PHPUnit testleri
├── frontend/
│   └── js/
│       ├── security.js         # Client-side güvenlik
│       └── monitoring.js       # Monitoring
├── api/
│   ├── health.php              # Health check endpoint
│   └── metrics/
│       └── log.php             # Metrics logging
├── composer.json               # Composer bağımlılıkları
├── phpunit.xml                 # PHPUnit konfigürasyonu
└── .htaccess                   # Apache güvenlik ayarları
```

## 🔧 Kurulum Adımları

### 1. Composer Bağımlılıkları

```bash
# Production
cd /Users/Tolga/dtz-lid-de
composer install --no-dev --optimize-autoloader

# Development
composer install
```

### 2. GitHub Secrets Ayarları

GitHub repo → Settings → Secrets and variables → Actions:

| Secret | Açıklama |
|--------|----------|
| `DEPLOY_HOST` | SSH host (örn: 123.45.67.89) |
| `DEPLOY_USER` | SSH kullanıcı adı |
| `DEPLOY_KEY` | SSH private key |
| `JWT_SECRET` | Test ortamı için JWT secret |

### 3. Monitoring Aktivasyonu

```javascript
// Her sayfada otomatik aktif
// Manuel event tracking:
Monitoring.logEvent('quiz', 'answer_submitted', 'teil1', score);
Monitoring.logEvent('user', 'login');
```

### 4. Health Check

```bash
# Sistem durumunu kontrol et
curl https://your-domain.com/api/health.php
```

Örnek response:
```json
{
    "status": "healthy",
    "timestamp": "2024-03-30T12:00:00+00:00",
    "version": "1.0.0",
    "checks": {
        "database": "OK",
        "jwt_secret": "OK",
        "disk_space": "45.2% free",
        "memory_limit": "256M",
        "php_version": "8.2.15"
    }
}
```

## 📊 Monitoring Dashboard

Log dosyalarını inceleme:

```bash
# Günlük loglar
tail -f logs/metrics-2024-03-30.log

# Error loglarını filtrele
grep "error" logs/metrics-*.log | jq '.data'

# Slow request'leri bul
grep "slow_request" logs/metrics-*.log
```

## 🚀 Deployment

### Otomatik Deployment

| Branch | Hedef | Tetikleyici |
|--------|-------|-------------|
| `develop` | Staging | Her push |
| `main` | Production | Her push + Manuel onay |

### Manuel Deployment

```bash
# Staging
git checkout develop
git push origin develop

# Production  
git checkout main
git merge develop
git push origin main
```

## ⚠️ Önemli Notlar

1. **Log Rotation:** `/logs` dizini disk dolmasın diye otomatik rotate ediliyor
2. **Rate Limiting:** IP bazlı rate limiting aktif (login: 5/dk, register: 3/saat)
3. **Hassas Veri:** Monitoring otomatik olarak password/token gibi verileri maskeleyip gönderiyor
4. **Error Tracking:** Production'da `console.log` yerine `Monitoring.logError()` kullanın

## 🔮 Gelecek İyileştirmeler

- [ ] Slack webhook entegrasyonu (kritik hatalar için)
- [ ] Redis caching layer
- [ ] A/B testing altyapısı
- [ ] Feature flags sistemi
- [ ] Performance budget monitoring
