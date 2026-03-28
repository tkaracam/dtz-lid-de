# IONOS'ta Nameserver Değiştirme - Adım Adım

## 🎯 Adım 1: IONOS Paneline Giriş

1. https://login.ionos.de/ adresine git
2. Giriş yap

## 📋 Adım 2: Domain Bul

**Seçenek A:**
- Ana sayfada **"Meine Produkte"** tıkla
- **Domain & SSL** bul
- **dtz-lid.de** yanındaki **"Verwalten"** (Yönet) tıkla

**Seçenek B:**
- Doğrudan: https://my.ionos.com/domain-center

## ⚙️ Adım 3: Nameserver Menüsü

Sol menüde şunu bul:
```
├─ Domain-Übersicht
├─ DNS
├─ Weiterleitung
└─ Nameserver              ← BUNA TIKLA
```

Veya:
- **"Nameserver ändern"** (Nameserver değiştir)
- **"Eigene Nameserver"** (Özel nameserver)

## 📝 Adım 4: Nameserver'ları Gir

Karşına çıkan forma yaz:

```
Nameserver 1:  lara.ns.cloudflare.com
Nameserver 2:  greg.ns.cloudflare.com
```

> **Önemli:** Eski nameserver'ları sil, yeni olanları ekle

## 💾 Adım 5: Kaydet

- **"Speichern"** veya **"Übernehmen"** tıkla
- Onay mesajı bekleyebilir
- **"Ja, ändern"** (Evet, değiştir)

## ✅ Adım 6: Doğrula

IONOS şunu göstermeli:
```
Aktive Nameserver:
1. lara.ns.cloudflare.com
2. greg.ns.cloudflare.com

Status: Aktualisierung läuft... (Güncelleniyor)
```

## ⏱️ Adım 7: Bekle

**5 dakika - 24 saat** arasında yayılır.

---

## 🆘 Sorun Olursa

### "Nameserver" seçeneği görünmüyor mu?

Muhtemelen şu paketlerden biri aktif:
- ❌ MyWebsite
- ❌ Homepage-Baukasten
- ❌ Hosting

**Çözüm:**
```
IONOS → Meine Produkte → Hosting/MyWebsite → Kündigen
```

Paketi iptal et, sonra Nameserver seçeneği görünecek.

### "Nur Leserecht" (Sadece okuma) hatası

→ Domain sahibi sensin ama yönetim yetkin yok
→ IONOS destek ile görüş

### Eski Nameserver silinmiyor

→ Önce yeni nameserver'ları ekle
→ Sonra eskileri sil
→ En az 2 nameserver kalmalı

---

## 📞 IONOS Destek (Yardım gerekirse)

Telefon: 030 300 146 010
Mail: support@ionos.de

---

**Hangi adımda takıldın?** 🎯
