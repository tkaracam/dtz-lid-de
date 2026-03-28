# IONOS DNS Yapılandırması - dtz-lid.de

## 🎯 IONOS Kontrol Paneli Adımları

### 1. Giriş Yap
https://login.ionos.de/ adresine git

### 2. Domain Yönetimine Git
- **"Meine Produkte"** (Ürünlerim)
- **"Domain & SSL"** tıkla
- **dtz-lid.de** domainini bul → **"Verwalten"** (Yönet)

### 3. DNS Ayarlarına Git
- **"DNS"** sekmesi
- **"DNS-Einstellungen bearbeiten"** (DNS ayarlarını düzenle)

---

## ⚙️ DNS Kayıtları Ekle

### A Kaydı (Root Domain)

```
Hostname:     @ (veya boş bırak)
Typ:          A
Wert:         [Render IP adresi]
TTL:          3600
```

**Render IP'sini öğrenmek için:**
1. Render Dashboard → Servis → Settings
2. Custom Domain ekle: `dtz-lid.de`
3. Render IP'yi gösterecek (örn: `216.24.57.1`)

---

### CNAME Kaydı (WWW)

```
Hostname:     www
Typ:          CNAME
Wert:         [Render servis URL]
TTL:          3600
```

**Örnek:** `dtz-lernplattform-abc123.onrender.com`

---

## 🔄 Önemli: Mevcut Kayıtları Düzenle

Eğer mevcut kayıtlar varsa:

1. **A Kaydı:**
   - Tip: A
   - Hostname: @
   - Points to: Render IP
   - **Kaydet**

2. **CNAME Kaydı:**
   - Tip: CNAME  
   - Hostname: www
   - Points to: Render servis URL
   - **Kaydet**

---

## ✅ Kontrol Et

**Tarayıcıda test et:**
```
https://www.dtz-lid.de
```

**Komut satırında test et:**
```bash
nslookup dtz-lid.de
nslookup www.dtz-lid.de
```

---

## ⏱️ Yayılım Süresi

IONOS genellikle **5-15 dakika** içinde günceller.
En fazla 24 saat bekleyebilir.

---

## 🆘 Sorun Olursa

### "Dieser Wert ist nicht zulässig" hatası
→ CNAME değerinin sonunda nokta olmamalı

### "Es existiert bereits ein Eintrag" hatası  
→ Mevcut A kaydını sil, yenisini ekle

### IONOS Redirection aktifse
→ "Weiterleitung" (Yönlendirme) bölümünden kaldır

---

**Render servis URL'niz nedir?** 
(Örn: `dtz-lernplattform-abc123.onrender.com`)

O adresi verirseniz tam DNS değerlerini yazayım! 🚀
