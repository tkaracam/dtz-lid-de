# App Store & Google Play Yayınlama Rehberi

## Gereksinimler

### iOS (App Store)
- macOS/Xcode (13.0+)
- Apple Developer Program ($99/yıl)
- App Store Connect hesabı

### Android (Google Play)
- Android Studio
- Google Play Console ($25 bir kerelik)
- Keystore dosyası

---

## Kurulum Adımları

### 1. Node.js ve Capacitor Kurulumu

```bash
# macOS'ta Homebrew ile Node.js kurulumu
brew install node

# Capacitor CLI kurulumu
npm install -g @capacitor/cli

# Proje bağımlılıklarını kur
npm install
```

### 2. iOS Projesi Oluşturma

```bash
# iOS platformunu ekle
npx cap add ios

# Web assets'i kopyala
npx cap copy ios

# Xcode ile aç
npx cap open ios
```

**Xcode'da Yapılacaklar:**
1. `de.dtz.lernen` bundle ID'sini kontrol et
2. Signing & Capabilities > Team seç
3. App Icons'u ekle (AppIcon.appiconset)
4. Launch Screen'i özelleştir
5. Build ve Archive yap

### 3. Android Projesi Oluşturma

```bash
# Android platformunu ekle
npx cap add android

# Web assets'i kopyala
npx cap copy android

# Android Studio ile aç
npx cap open android
```

**Android Studio'da Yapılacaklar:**
1. Build > Generate Signed Bundle/APK
2. Keystore oluştur (ilk seferde)
3. Release AAB (Android App Bundle) oluştur

---

## App Icons

### iOS Icons (AppIcon.appiconset)
- 20x20pt (@2x, @3x) - Notification
- 29x29pt (@2x, @3x) - Settings
- 40x40pt (@2x, @3x) - Spotlight
- 60x60pt (@2x, @3x) - iPhone App
- 76x76pt (@2x) - iPad App
- 83.5x83.5pt (@2x) - iPad Pro
- 1024x1024pt - App Store

### Android Icons (mipmap-*)
- mdpi: 48x48px
- hdpi: 72x72px
- xhdpi: 96x96px
- xxhdpi: 144x144px
- xxxhdpi: 192x192px
- Google Play: 512x512px

---

## App Store Bilgileri

### App Store (iOS)

**App Information:**
- Name: DTZ Lernplattform
- Subtitle: Deutsch-Test für Zuwanderer
- Bundle ID: de.dtz.lernen
- SKU: DTZ-LEARNING-001
- Category: Education

**Pricing:**
- Free with In-App Purchases

**App Review Information:**
- Contact: [email]
- Demo Account: test@example.com / password123
- Notes: App für DTZ Prüfungsvorbereitung

### Google Play (Android)

**Store Listing:**
- Title: DTZ Lernplattform
- Short description: Interaktive DTZ Prüfungsvorbereitung
- Full description: [aşağıda]

**Content Rating:**
- PEGI 3 / Everyone

---

## Store Açıklamaları

### Deutsch (Almanca)

**Kısa Açıklama:**
```
Bereite dich optimal auf den Deutsch-Test für Zuwanderer (DTZ) vor. Mit KI-gestütztem Feedback!
```

**Tam Açıklama:**
```
DTZ Lernplattform - Deine Vorbereitung auf den Deutsch-Test für Zuwanderer

🎯 Perfekte Vorbereitung auf den DTZ
- Hören, Lesen, Schreiben und Sprechen üben
- Originale DTZ-Prüfungsaufgaben
- Interaktive Übungen mit sofortigem Feedback

🤖 KI-gestütztes Lernen
- Automatische Korrektur deiner Schreibaufgaben
- Detailliertes Feedback zu Grammatik und Wortschatz
- Persönliche Lernempfehlungen

📊 Fortschrittsverfolgung
- Detaillierte Statistiken zu deinem Lernfortschritt
- Schwachstellenanalyse
- Tägliche Lernziele

🎓 Modelltests
- Komplette DTZ-Prüfungssimulation
- Zeitgesteuerte Tests
- Level-Bestimmung (A2/B1)

📱 Offline-Modus
- Lernen ohne Internetverbindung
- Audio-Dateien herunterladen
- Überall und jederzeit lernen

Ideal für:
• Zuwanderer, die den DTZ ablegen müssen
• Deutschlerner auf dem Niveau A2/B1
• Integrationskurs-Teilnehmer

Starte jetzt deine kostenlose Testphase!

---

Hinweise:
- Kostenloser Testzeitraum: 7 Tage
- Danach Premium-Abonnement erforderlich
- Preise: 9,99€/Monat (Basis) oder 19,99€/Monat (Premium)
```

### English

**Short Description:**
```
Prepare for the German DTZ exam with AI-powered writing feedback and comprehensive practice tests.
```

---

## Ekran Görüntüleri Gereksinimleri

### iOS
- 6.7" (iPhone 14 Pro Max): 1290x2796px
- 6.5" (iPhone 11 Pro Max): 1242x2688px
- 5.5" (iPhone 8 Plus): 1242x2208px
- iPad Pro (12.9"): 2048x2732px
- iPad Pro (11"): 1668x2388px

### Android
- Phone: 16:9 or 9:16, min 320px
- 7-inch tablet: 16:9 or 9:16
- 10-inch tablet: 16:9 or 9:16

---

## Yayınlama Checklist

### Önce Yapılacaklar
- [ ] Tüm testler tamamlandı
- [ ] App Icons oluşturuldu
- [ ] Splash screen hazırlandı
- [ ] Privacy Policy sayfası eklendi
- [ ] Terms of Service hazırlandı
- [ ] In-App Purchase yapılandırması tamamlandı (Apple/Google)

### iOS Özel
- [ ] App Store Connect'te app oluşturuldu
- [ ] App Icons yüklendi
- [ ] Screenshots yüklendi
- [ ] App Review bilgileri dolduruldu
- [ ] Export Compliance bilgileri (ITSAppUsesNonExemptEncryption: false)
- [ ] Archive oluşturuldu ve yüklendi

### Android Özel
- [ ] Google Play Console'da app oluşturuldu
- [ ] Feature graphic (1024x500px) hazırlandı
- [ ] Signed AAB oluşturuldu
- [ ] Content rating dolduruldu
- [ ] Data safety formu dolduruldu

---

## In-App Purchase Yapılandırması

### App Store (StoreKit)
1. App Store Connect > Features > In-App Purchases
2. Ürünleri ekle:
   - `dtz_basic_monthly` - 9,99€
   - `dtz_premium_monthly` - 19,99€
   - `dtz_premium_yearly` - 199,99€

### Google Play (Billing Library)
1. Google Play Console > Monetize > Products
2. Subscriptions ekle:
   - `dtz_basic_monthly` - 9,99€
   - `dtz_premium_monthly` - 19,99€
   - `dtz_premium_yearly` - 199,99€

---

## Sorun Giderme

### iOS
**"Signing certificate" hatası:**
- Xcode > Preferences > Accounts > Apple ID ekle
- Project > Signing & Capabilities > Team seç

**"Bundle identifier unavailable":**
- Başka bir bundle ID dene (de.dtz.lernen.app)

### Android
**"Keystore file not found":**
```bash
cd android/app
keytool -genkey -v -keystore my-release-key.keystore -alias alias_name -keyalg RSA -keysize 2048 -validity 10000
```

---

## Hızlı Komutlar

```bash
# iOS Build
npx cap copy ios && npx cap open ios

# Android Build  
npx cap copy android && npx cap open android

# Sync web assets
npx cap sync

# Live reload (development)
npx cap run ios --livereload --external
npx cap run android --livereload --external
```

---

## İletişim & Destek

App Store Review Team için:
- Support URL: [destek sitesi]
- Marketing URL: [marketing sitesi]
- Privacy Policy URL: [gizlilik politikası]

---

**Son Güncelleme:** 2026-03-28
**Versiyon:** 1.0.0
