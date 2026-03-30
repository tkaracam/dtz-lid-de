# Sorular Nerede ve Nasıl Çalışıyor?

## 📍 Şu Anki Durum

Sorular **3 farklı yerde** saklanıyor:

### 1️⃣ Veritabanı (Asıl Kaynak)
```
📁 database/dtz_learning.db  (SQLite)
   └─ Tablo: question_pools
   └─ Toplam: 63 soru
```

**Sorular burada:**
- Lesen: 25 soru (5 Teil × 5 soru)
- Hören: 20 soru (4 Teil × 5 soru)
- Schreiben: 6 soru (2 Teil × 3 soru)
- Sprechen: 12 soru (3 Teil × 4 soru)

### 2️⃣ SQL Yedek Dosyaları
```
📁 database/seeds/
   ├─ 003_dtz_format_questions.sql  (Ana sorular)
   └─ dtz_realistic_questions.sql   (G.A.S.T. format)
```

### 3️⃣ HTML/JS İçinde (API'siz)
```
📁 frontend/
   ├─ learn-inline.html      (8 soru - çalışıyor)
   ├─ test-sorular.html      (5 soru - çalışıyor)
   └─ all-questions.js       (tüm 63 soru)
```

---

## 🔌 Sorular Nasıl Filtreleniyor?

### Modül + Teil Filtresi:

```javascript
// Kullanıcı "Lesen Teil 1" seçtiğinde:
const questions = ALL_QUESTIONS.filter(q => 
    q.module === "lesen" && 
    q.teil === 1
);
// Sonuç: 5 soru
```

### Seviye Filtresi:

```javascript
// Sadece A2 seviyesi:
const a2Questions = questions.filter(q => q.level === "A2");
```

### Rastgele Seçim:

```javascript
// Karıştır ve ilk 5'i al:
const randomQuestions = questions
    .sort(() => Math.random() - 0.5)
    .slice(0, 5);
```

---

## ⚠️ Sorun: API Çalışmıyor

Render'da API'den sorular gelmiyor çünkü:
1. ❌ Database bağlantısı hatalı
2. ❌ CORS ayarları eksik
3. ❌ API endpoint bulunamıyor

### ✅ Çözüm: Direkt HTML

**Şu sayfalar API'siz çalışıyor:**
```
✅ https://www.dtz-lid.de/frontend/learn-inline.html
✅ https://www.dtz-lid.de/frontend/test-sorular.html
```

---

## 🎯 Nasıl Çalışır?

### Senaryo: Üye giriş yapıp soru çözüyor

1. **Kullanıcı login oluyor**
   ```
   POST /api/auth/login.php → JWT token alıyor
   ```

2. **Lernen sayfasına gidiyor**
   ```
   /frontend/learn.html
   ```

3. **Modül seçiyor** (örn: Lesen)
   ```javascript
   // Frontend filtreleme yapıyor:
   sorular = getQuestions("lesen", 1);
   // VEYA
   sorular = ALL_QUESTIONS.filter(q => q.module === "lesen");
   ```

4. **Sorular gösteriliyor**
   - API'den çekilirse: `fetch(/api/questions.php)`
   - Direkt JS'den: `ALL_QUESTIONS.filter(...)`

---

## 📊 Soru Havuzu Özeti

| Modül | Teil | Soru Sayısı | Tür |
|-------|------|-------------|-----|
| **Lesen** | 1 | 5 | Anzeigen |
| **Lesen** | 2 | 5 | Briefe |
| **Lesen** | 3 | 5 | Berufstexte |
| **Lesen** | 4 | 5 | Zeitungsartikel |
| **Lesen** | 5 | 5 | Mehrere Texte |
| **Hören** | 1 | 5 | Telefonansagen |
| **Hören** | 2 | 5 | Alltagsgespräche |
| **Hören** | 3 | 5 | Arbeitsgespräche |
| **Hören** | 4 | 5 | Informationen |
| **Schreiben** | 1 | 3 | Formular/Brief |
| **Schreiben** | 2 | 3 | Freier Text |
| **Sprechen** | 1 | 4 | Vorstellung |
| **Sprechen** | 2 | 4 | Thema |
| **Sprechen** | 3 | 4 | Diskussion |

**Toplam: 63 soru**

---

## 🔧 Tamir Etmemi İster misin?

Eğer istersen:
1. Tüm 63 soruyu tek bir `all-questions.js` dosyasına koyarım
2. `learn.html`'i API'siz çalışacak şekilde düzenlerim
3. Kesin çalışan versiyonu deploy ederim

**Ne dersin?** 🛠️
