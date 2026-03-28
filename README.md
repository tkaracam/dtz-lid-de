# DTZ Lernplattform

Deutsch-Test für Zuwanderer (DTZ) hazırlık platformu. A1-B1 seviyeleri için kapsamlı öğrenme araçları, modelltest simülasyonu ve AI destekli yazma pratiği.

## 🚀 Özellikler

### Modelltest Simülasyonu
- **Tam DTZ Deneyimi**: 120 dakikalık gerçek sınav simülasyonu
- **Zamanlı Modüller**:
  - 🎧 Hören (Dinleme) - 30 dk
  - 📖 Lesen (Okuma) - 45 dk  
  - ✍️ Schreiben (Yazma) - 30 dk
  - 🗣️ Sprechen (Konuşma) - 15 dk
- **Otomatik Seviye Tespiti**: A2/B1 seviye analizi
- **Detaylı Raporlama**: Güçlü/zayıf alan analizi ve öneriler

### Uyarlanmış Öğrenme
- SM-2 algoritması ile uzaylı tekrar
- Zayıf alan tespiti ve odaklı pratik
- Günlük seri takibi
- Kişiselleştirilmiş soru seçimi

### AI Yazma Asistanı
- GPT-4 ile yazma analizi
- A2-B1 seviyesine uygun düzeltmeler
- Admin onaylı geri bildirim sistemi

## 📁 Proje Yapısı

```
dtz-lid-de/
├── api/                    # API Endpoint'leri
│   ├── auth/              # Kimlik doğrulama
│   ├── modelltest/        # Modelltest API
│   ├── user/              # Kullanıcı API
│   └── writing/           # Yazma API
├── frontend/              # Kullanıcı arayüzü
│   ├── css/              # Stil dosyaları
│   ├── js/               # JavaScript
│   ├── login.html
│   ├── register.html
│   ├── dashboard.html
│   └── modelltest.html   # Test simülasyonu
├── src/                   # PHP Sınıfları
│   ├── Auth/             # JWT, AuthController
│   ├── Database/         # Database sınıfı
│   ├── Models/           # Veri modelleri
│   └── Services/         # OpenAI, Azure TTS
├── database/             # Veritabanı
│   └── migrations/       # Şema dosyaları
└── tools/                # Yardımcı araçlar
```

## 🛠️ Kurulum

### Gereksinimler
- PHP 8.2+
- SQLite (veya PostgreSQL)
- Composer (opsiyonel)

### Hızlı Başlangıç

```bash
# 1. Repoyu klonlayın
cd dtz-lid-de

# 2. Veritabanını oluşturun
php tools/seed_questions.php

# 3. Sunucuyu başlatın
php -S localhost:8080

# 4. Tarayıcıda açın
open http://localhost:8080/frontend/
```

### Test Kullanıcısı
- **Email**: test@example.com
- **Şifre**: test123

### Admin Girişi
- **Email**: admin@dtz.de
- **Şifre**: admin123

## 🔌 API Endpoint'leri

### Auth
```
POST /api/auth/login.php
POST /api/auth/register.php
```

### Modelltest
```
POST /api/modelltest/start.php      # Test başlat
GET  /api/modelltest/status.php?id=1 # Durum kontrolü
POST /api/modelltest/answer.php      # Cevap gönder
GET  /api/modelltest/result.php?id=1 # Sonuçları gör
```

### Kullanıcı
```
GET /api/user/stats.php             # İstatistikler
```

## ⚙️ Yapılandırma

`.env` dosyası oluşturun:

```env
# Veritabanı (SQLite)
DB_DRIVER=sqlite
DB_PATH=/path/to/dtz_learning.db

# PostgreSQL için:
# DB_DRIVER=pgsql
# DB_HOST=localhost
# DB_PORT=5432
# DB_NAME=dtz_learning
# DB_USER=postgres
# DB_PASS=password

# JWT Secret
JWT_SECRET=your-secret-key-here

# OpenAI (Opsiyonel)
OPENAI_API_KEY=sk-your-key

# Azure TTS (Opsiyonel)
AZURE_TTS_KEY=your-key
AZURE_TTS_REGION=westeurope
```

## 🧪 Test

```bash
# API test
./tools/test_api.sh

# Veritabanı bağlantı testi
php -r "require 'src/Database/Database.php'; DTZ\Database\Database::getInstance();"
```

## 📊 Veritabanı Şeması

### Ana Tablolar
- **users**: Kullanıcı bilgileri
- **question_pools**: Soru bankası
- **modelltest_attempts**: Test denemeleri
- **user_progress**: Öğrenme ilerlemesi
- **writing_submissions**: Yazma gönderimleri

## 🎨 Tasarım

Glass morphism UI:
- Blur efektleri
- Yarı saydam arka planlar
- Gradient aksan renkleri
- Responsive layout

## 🔒 Güvenlik

- JWT tabanlı kimlik doğrulama
- Argon2id şifre hash'leme
- Prepared statements (SQL injection koruması)
- CORS yapılandırması

## 📈 Gelecek Özellikler

- [ ] Stripe ödeme entegrasyonu
- [ ] Konuşma tanıma (Speech-to-Text)
- [ ] Mobil uygulama
- [ ] Sosyal özellikler (grup çalışması)
- [ ] Detaylı admin dashboard

## 📝 Lisans

MIT License

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request açın

---

**Not**: Bu proje DTZ sınavına hazırlık amaçlıdır. Resmi Goethe-Institut veya Bundesamt tarafından onaylı değildir.
