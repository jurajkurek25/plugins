# BuddyBoss Komunitná Knižnica

WordPress plugin pre komunitné požičiavanie kníh medzi členmi s **BuddyBoss** a **WooCommerce** integráciou.

## 📚 Popis

Komunitná knižnica pre **Bratstvo Potrebných Mužov** umožňuje členom komunity:
- ✅ Pridávať vlastné knihy do systému
- 🔍 Prehľadávať knihy od ostatných členov
- 🤝 Požičiavať knihy zadarmo alebo za poplatok (2-10 €)
- 💰 Automatické rozdelenie poplatkov (70% majiteľ / 30% komunita)
- 📅 Sledovať požičania v osobnom dashboarde
- ⭐ Hodnotiť a recenzovať knihy
- 🔔 Dostávať notifikácie a upomienky

## 🎯 Požiadavky

- **WordPress** 6.0+
- **BuddyBoss Platform** 2.0+
- **WooCommerce** 5.0+
- **PHP** 7.4+

## 🚀 Inštalácia

### 1. Nahratie pluginu

```bash
# Rozbaľte ZIP súbor
# Nahrajte priečinok buddyboss-kniznica do /wp-content/plugins/
```

ALEBO cez WordPress Admin:
1. Prejdite do **Pluginy → Pridať nový → Nahrať plugin**
2. Vyberte ZIP súbor
3. Kliknite **Nainštalovať**

### 2. Aktivácia

1. Prejdite do **Pluginy**
2. Nájdite **BuddyBoss Komunitná Knižnica**
3. Kliknite **Aktivovať**

### 3. Automatická konfigurácia

Pri aktivácii plugin automaticky:
- ✅ Vytvorí databázové tabuľky
- ✅ Vytvorí FYZICKÝ dummy WooCommerce produkt
- ✅ Nastaví predvolené hodnoty (30% provízia, 30 dní požičanie)
- ✅ Nastaví cron joby pre upomienky
- ✅ Vytvorí oprávnenia pre administrátorov

## ⚙️ Nastavenie

### 1. Vytvorenie stránok

Vytvorte nasledujúce WordPress stránky a pridajte im shortcodes:

#### 📖 Katalóg kníh
- **Názov:** Katalóg kníh
- **Slug:** katalog-knih
- **Shortcode:** `[bbk_catalog]`

#### 📅 Môj Dashboard
- **Názov:** Môj Dashboard
- **Slug:** dashboard
- **Shortcode:** `[bbk_dashboard]`

#### 📄 Detail knihy
- **Názov:** Detail knihy
- **Slug:** detail-knihy
- **Shortcode:** `[bbk_book_detail]`

#### ➕ Pridať knihu
- **Názov:** Pridať knihu
- **Slug:** pridat-knihu
- **Shortcode:** `[bbk_add_book]`

#### 📚 Moje knihy
- **Názov:** Moje knihy
- **Slug:** moje-knihy
- **Shortcode:** `[bbk_my_books]`

### 2. Konfigurácia nastavení

Prejdite do **WordPress Admin → Knižnica → Nastavenia**

- **Provízna sadzba komunity:** 30% (default)
- **Predvolená dĺžka požičania:** 30 dní (default)

### 3. BuddyBoss integrácia

Plugin automaticky:
- Pridá **Moja Knižnica** záložku do BuddyBoss profilu každého člena
- Vytvorí submenu: Dashboard, Moje knihy, Pridať knihu
- Integruje notifikácie do BuddyBoss systému

## 💡 Ako to funguje

### 🔐 Prístup

- **Len pre aktívnych členov** komunity potrebnymuz.sk
- Registrácia prebieha cez hlavný web bratstva
- Automatická kontrola členstva cez BuddyBoss

### 📖 Pridanie knihy

1. Prejdite na **Pridať knihu**
2. Vyplňte formulár:
   - Názov knihy *
   - Autor *
   - ISBN (voliteľné)
   - Žáner *
   - Popis
   - Stav knihy (1-10) *
   - Obrázok knihy
   - **Cena požičania:**
     - **0 € = Zadarmo** (bez poplatkov)
     - **2-10 € = Platené** (rozdelenie 70/30)
3. Kliknite **Pridať knihu**

### 🔍 Prehľadávanie katalógu

- Filter podľa **žánru**
- Filter podľa **autora**
- Filter podľa **ceny** (zadarmo/platené)
- **Fulltextové vyhľadávanie**
- Zobrazenie **hodnotení** a **stavu** knihy

### 🤝 Požičanie knihy

#### Bezplatné požičanie (0 €):
1. Nájdite knihu v katalógu
2. Kliknite **Detail knihy**
3. Kliknite **Požičať zadarmo**
4. Kniha je okamžite požičaná na 30 dní

#### Platené požičanie (2-10 €):
1. Nájdite knihu v katalógu
2. Kliknite **Detail knihy**
3. Kliknite **Pridať do košíka**
4. Dokončite WooCommerce checkout proces
5. **DÔLEŽITÉ:** Vyplňte **doručovacie údaje** (kam má majiteľ poslať knihu)
6. Dokončite platbu
7. Majiteľ dostane email s doručovacími údajmi

### 💰 Automatické rozdelenie poplatkov

Pri platenom požičaní sa poplatok automaticky rozdelí:

- **70%** → Majiteľ knihy (kompenzácia za opotrebenie)
- **30%** → Komunita (rozvoj a podpora bratstva)

**Príklad:**
- Cena požičania: **5.00 €**
- Majiteľ dostane: **3.50 €** (70%)
- Komunita dostane: **1.50 €** (30%)

### 📅 Dashboard

Váš dashboard zobrazuje:

1. **Štatistiky:**
   - 📚 Moje knihy
   - 🤝 Požičané iným
   - 📖 Požičané odo mňa
   - 💰 Celkový príjem (70%)

2. **Moje knihy:**
   - Zoznam vašich kníh
   - Status: Dostupná / Rezervovaná / Požičaná
   - Cena požičania

3. **Požičané iným:**
   - Knihy, ktoré má niekto požičané
   - **Doručovacie údaje** v zlatom boxe
   - Dátum vrátenia
   - Vaša provízia (70%)

4. **Požičané odo mňa:**
   - Knihy, ktoré ste si požičali
   - Dátum vrátenia
   - Tlačidlo **Vrátiť knihu**
   - Tlačidlo **Ohodnotiť**

### ⭐ Hodnotenia a recenzie

Po vrátení knihy môžete:
- Pridať hodnotenie (1-5 hviezdičiek)
- Napísať textovú recenziu
- Odporučiť knihu ostatným bratom

### 🔔 Notifikácie

Plugin posiela:
- **Email notifikácie:**
  - Nové požičanie knihy
  - Platené požičanie s doručovacími údajmi
  - Upomienka 3 dni pred vrátením
  - Upozornenie na omeškanie
- **In-app notifikácie** v BuddyBoss systéme

## 🎨 Dizajn

Plugin používa **tmavú tému** so **zlatými akcentmi**:

```css
--gold: #d4af37
--gold-dark: #c9a22e
--gold-ink: #6b5700
--ink: #eee
--bg-1: #0f1419
--bg-2: #1a2332
--panel: rgba(26, 35, 50, 0.9)
```

## 🗄️ Databázová štruktúra

Plugin vytvorí 4 tabuľky:

1. **wp_bbk_books** - Knihy
2. **wp_bbk_lendings** - Požičania (s doručovacími údajmi)
3. **wp_bbk_ratings** - Hodnotenia
4. **wp_bbk_notifications** - Notifikácie

## 📋 Shortcodes

```
[bbk_catalog] - Katalóg kníh s filtrami
[bbk_dashboard] - Používateľský dashboard
[bbk_book_detail] - Detail knihy (vyžaduje ?book_id= parameter)
[bbk_add_book] - Formulár na pridanie knihy
[bbk_my_books] - Moje knihy
```

## 🎯 Hlavné funkcie

### 1. BuddyBoss integrácia
- ✅ Kontrola aktívneho členstva
- ✅ Integrácia s BuddyBoss profilom
- ✅ BuddyBoss notifikácie
- ✅ Navigácia v profile člena

### 2. Správa kníh
- ✅ Pridávanie, úprava, mazanie kníh
- ✅ Nahrávanie obrázkov
- ✅ Nastavenie ceny (0 € alebo 2-10 €)
- ✅ Tri statusy: Dostupná, Rezervovaná, Požičaná

### 3. Systém požičiavania
- ✅ **Bezplatné:** Okamžité vytvorenie požičania
- ✅ **Platené:** Cez WooCommerce košík a checkout
- ✅ Automatické rozdelenie provízií (70/30)
- ✅ Email notifikácie s doručovacími údajmi

### 4. WooCommerce integrácia
- ✅ FYZICKÝ dummy produkt (vyžaduje doručovacie údaje)
- ✅ Získanie doručovacích údajov pri platených požičaniach
- ✅ Zobrazenie doručovacích údajov majiteľovi
- ✅ Automatické spracovanie po platbe

### 5. Dashboard
- ✅ Štatistiky používateľa
- ✅ Tri kategórie kníh
- ✅ Doručovacie údaje v zlatom boxe
- ✅ Tlačidlá na akcie

### 6. Hodnotenia
- ✅ Hodnotenie 1-5 hviezdičiek
- ✅ Textová recenzia
- ✅ Priemerné hodnotenie pri knihe

### 7. Notifikácie
- ✅ In-app notifikácie (BuddyBoss)
- ✅ Email notifikácie
- ✅ Upomienky pred termínom vrátenia
- ✅ Upozornenia na omeškanie

### 8. Automatické provízie
- ✅ 70% ide majiteľovi knihy
- ✅ 30% ide komunite
- ✅ Zobrazenie v dashboarde

## 🔧 Pre administrátorov

Prejdite do **WordPress Admin → Knižnica**

- **Dashboard:** Prehľad štatistík
- **Knihy:** Zoznam všetkých kníh
- **Požičania:** Zoznam všetkých požičaní (s províziami)
- **Nastavenia:** Konfigurácia pluginu

## 🆘 Riešenie problémov

### Plugin sa neaktivuje
- Skontrolujte či je **BuddyBoss Platform** nainštalovaný a aktívny
- Skontrolujte či je **WooCommerce** nainštalovaný a aktívny
- Skontrolujte **PHP verziu** (minimálne 7.4)

### Dummy produkt nebol vytvorený
- Deaktivujte a znova aktivujte plugin
- Skontrolujte **WordPress Admin → Knižnica → Nastavenia**

### Doručovacie údaje sa nezobrazujú
- Skontrolujte či bol dokončený WooCommerce checkout
- Skontrolujte či boli vyplnené doručovacie údaje pri objednávke

### BuddyBoss navigácia sa nezobrazuje
- Skontrolujte či je BuddyBoss Platform aktívny
- Vymažte cache
- Skontrolujte nastavenia BuddyBoss

## 📁 Štruktúra pluginu

```
buddyboss-kniznica/
├── admin/                          # Admin rozhranie
│   └── class-bbk-admin.php
├── assets/                         # CSS a JS
│   ├── css/
│   │   ├── public.css
│   │   └── admin.css
│   └── js/
│       ├── public.js
│       └── admin.js
├── includes/                       # Core triedy
│   ├── class-bbk-install.php
│   ├── class-bbk-database.php
│   ├── class-bbk-buddyboss.php   # BuddyBoss integrácia
│   ├── class-bbk-book.php
│   ├── class-bbk-lending.php
│   ├── class-bbk-rating.php
│   ├── class-bbk-woocommerce.php
│   └── class-bbk-notifications.php
├── public/                         # Frontend
│   ├── class-bbk-public.php
│   └── class-bbk-shortcodes.php
├── templates/                      # Šablóny
│   ├── catalog.php
│   ├── dashboard.php
│   ├── book-detail.php
│   ├── add-book.php
│   └── my-books.php
├── buddyboss-kniznica.php         # Hlavný súbor
└── README.md                       # Dokumentácia
```

## 🎁 Funkcie podľa landing page

### ✅ Implementované

1. **🔐 Prihlásenie členov** - Len aktívni členovia komunity
2. **📖 Pridaj knihu** - Formulár s nastavením ceny (zadarmo/platené)
3. **🔍 Prehliadaj katalóg** - Filtre podľa žánru, autora, dostupnosti
4. **🤝 Požičaj si knihu** - Jednoduchý proces
5. **💰 Automatické rozdelenie** - 70% majiteľ / 30% komunita
6. **📅 Sleduj požičania** - Intuitívny dashboard
7. **⭐ Hodnoť a odporúčaj** - Hodnotenia a recenzie

### 💸 Ceny

- **🎁 Zadarmo (0 €)** - Bez poplatkov, jednoduché odovzdanie
- **💰 S poplatkom (2-10 €)** - Rozdelenie 70/30, kompenzácia
- **🔄 Flexibilné** - Plná kontrola nad cenami

### 🔒 Bezpečnosť

- Všetky transakcie cez WooCommerce
- Doručovacie údaje len pre majiteľa
- Transparentné rozdelenie poplatkov
- Email notifikácie

## 📞 Podpora

Pre otázky a problémy kontaktujte:
- **Email:** kniznica@potrebnymuz.sk
- **Web:** potrebnymuz.sk

## 📄 Licencia

Proprietárny softvér pre Bratstvo Potrebných Mužov

## 👨‍💻 Autor

Vytvorené pre **Komunitnú Knižnicu - Bratstvo Potrebných Mužov**

---

© 2025 Komunitná Knižnica – Bratstvo Potrebných Mužov
Zdieľame múdrosť, rastieme spolu. 📚
