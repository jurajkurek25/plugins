# Komunitná Knižnica

WordPress plugin pre komunitné požičiavanie kníh medzi členmi s WooCommerce integráciou.

## Popis

Komunitná Knižnica umožňuje členom komunity:
- Pridávať vlastné knihy do systému
- Požičiavať knihy od ostatných členov
- Spravovať bezplatné aj platené požičania
- Sledovať požičania v osobnom dashboarde
- Hodnotiť a recenzovať knihy

## Požiadavky

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+

## Inštalácia

1. **Nahrajte plugin**
   - Rozbaľte ZIP súbor
   - Nahrajte priečinok `komunitna-kniznica` do `/wp-content/plugins/`
   - ALEBO: Nahrajte ZIP súbor cez WordPress Admin > Pluginy > Pridať nový > Nahrať plugin

2. **Aktivujte plugin**
   - Prejdite do WordPress Admin > Pluginy
   - Aktivujte "Komunitná Knižnica"

3. **Automatická konfigurácia**
   Pri aktivácii plugin automaticky:
   - Vytvorí databázové tabuľky
   - Vytvorí FYZICKÝ dummy WooCommerce produkt
   - Nastaví predvolené hodnoty
   - Nastaví cron joby pre upomienky

## Nastavenie

### 1. Vytvorenie stránok

Vytvorte nasledujúce WordPress stránky a pridajte im shortcodes:

#### Katalóg kníh
- **Názov:** Katalóg kníh
- **Slug:** katalog-knih
- **Shortcode:** `[kk_catalog]`

#### Môj Dashboard
- **Názov:** Môj Dashboard
- **Slug:** moj-dashboard
- **Shortcode:** `[kk_dashboard]`

#### Detail knihy v knižnici
- **Názov:** Detail knihy v knižnici
- **Slug:** detail-knihy-v-kniznici
- **Shortcode:** `[kk_book_detail]`

**KRITICKÉ:** Táto stránka MUSÍ mať slug `detail-knihy-v-kniznici`!

#### Pridať knihu
- **Názov:** Pridať knihu
- **Slug:** pridat-knihu
- **Shortcode:** `[kk_add_book]`

### 2. Konfigurácia nastavení

Prejdite do **WordPress Admin > Knižnica > Nastavenia**

- **Provízna sadzba komunity:** Nastavte percento (default: 30%)
- **Predvolená dĺžka požičania:** Nastavte počet dní (default: 30)

### 3. WooCommerce konfigurácia

Plugin automaticky vytvorí FYZICKÝ dummy produkt:
- **Typ:** Fyzický produkt (NIE virtuálny!)
- **Status:** Private (neviditeľný v katalógu)
- **Hmotnosť:** 0.5 kg
- **Rozmery:** 20×15×3 cm

Tento produkt je potrebný pre získanie doručovacích údajov pri platených požičaniach.

## Používanie

### Pre členov

#### Pridanie knihy
1. Prejdite na stránku "Pridať knihu"
2. Vyplňte formulár:
   - Názov knihy
   - Autor
   - ISBN (voliteľné)
   - Žáner
   - Popis
   - Stav knihy (1-10)
   - Obrázok knihy
   - Cena požičania (0 = zadarmo)
3. Kliknite "Pridať knihu"

#### Požičanie knihy

**Bezplatné požičanie:**
1. Nájdite knihu v katalógu
2. Kliknite "Detail knihy"
3. Kliknite "Požičať knihu"
4. Kniha je okamžite požičaná

**Platené požičanie:**
1. Nájdite knihu v katalógu
2. Kliknite "Detail knihy"
3. Kliknite "Pridať do košíka"
4. Dokončite WooCommerce checkout proces
5. **DÔLEŽITÉ:** Vyplňte doručovacie údaje (kam má majiteľ poslať knihu)
6. Dokončite platbu
7. Majiteľ dostane email s vašimi doručovacími údajmi

#### Dashboard
Váš dashboard zobrazuje:
- **Moje knihy:** Knihy, ktoré ste pridali
- **Požičané iným:** Vaše knihy, ktoré má niekto požičané (vrátane doručovacích údajov)
- **Požičané odo mňa:** Knihy, ktoré ste si požičali

### Pre adminov

Prejdite do **WordPress Admin > Knižnica**

- **Dashboard:** Prehľad štatistík
- **Knihy:** Zoznam všetkých kníh
- **Požičania:** Zoznam všetkých požičaní
- **Nastavenia:** Konfigurácia pluginu

## Shortcodes

```
[kk_catalog] - Zobrazí katalóg kníh s filtrami
[kk_dashboard] - Zobrazí používateľský dashboard
[kk_book_detail] - Zobrazí detail knihy (vyžaduje ?book_id= parameter)
[kk_add_book] - Zobrazí formulár na pridanie knihy
```

## Hlavné funkcie

### 1. Správa kníh
- Pridávanie, úprava, mazanie kníh
- Nahrávanie obrázkov
- Nastavenie ceny (0 = zadarmo)
- Tri statusy: Dostupná, Rezervovaná, Požičaná

### 2. Systém požičiavania
- **Bezplatné:** Okamžité vytvorenie požičania
- **Platené:** Cez WooCommerce košík a checkout
- Automatické rozdelenie provízií (70/30)
- Email notifikácie

### 3. WooCommerce integrácia
- FYZICKÝ dummy produkt
- Získanie doručovacích údajov
- Zobrazenie doručovacích údajov majiteľovi
- Automatické spracovanie po platbe

### 4. Dashboard
- Štatistiky používateľa
- Tri kategórie kníh
- Doručovacie údaje v zlatom boxe
- Tlačidlá na akcie

### 5. Hodnotenia
- Hodnotenie 1-5 hviezdičiek
- Textová recenzia
- Priemerné hodnotenie pri knihe

### 6. Notifikácie
- In-app notifikácie
- Email notifikácie
- Upomienky pred termínom vrátenia
- Upozornenia na omeškanie

### 7. Automatické provízie
- 70% ide majiteľovi knihy
- 30% ide komunite
- Zobrazenie v dashboarde

## Farebná schéma

Plugin používa tmavú tému so zlatými akcentmi:

```css
--gold: #d4af37;
--gold-ink: #6b5700;
--ink: #eee;
--bg-1: #0f1419;
--bg-2: #1a2332;
--panel: rgba(26, 35, 50, 0.9);
```

## Databázové tabuľky

Plugin vytvorí 4 tabuľky:
- `wp_kk_books` - Knihy
- `wp_kk_lendings` - Požičania
- `wp_kk_ratings` - Hodnotenia
- `wp_kk_notifications` - Notifikácie

## KRITICKÉ body

✅ **Dummy produkt je FYZICKÝ** (nie virtuálny) - vyžaduje doručovacie údaje
✅ **Detail knihy musí mať slug:** `detail-knihy-v-kniznici`
✅ **Linky používajú absolútne URL:** `/detail-knihy-v-kniznici/?book_id=X`
✅ **Doručovacie údaje sa zobrazujú majiteľovi** v zlatom boxe
✅ **Výhradne používa špecifikovanú farebnu schému**

## Riešenie problémov

### Plugin sa neaktivuje
- Skontrolujte či je WooCommerce nainštalovaný a aktívny
- Skontrolujte PHP verziu (minimálne 7.4)

### Dummy produkt nebol vytvorený
- Deaktivujte a znova aktivujte plugin
- Skontrolujte WordPress Admin > Knižnica > Nastavenia

### Doručovacie údaje sa nezobrazujú
- Skontrolujte či bol dokončený WooCommerce checkout
- Skontrolujte či boli vyplnené doručovacie údaje pri objednávke

### Linky na detail knihy nefungujú
- Skontrolujte či stránka má slug `detail-knihy-v-kniznici`
- Prejdite do Nastavenia > Permalinky a uložte (flush rewrite rules)

## Podpora

Pre otázky a problémy kontaktujte správcu systému.

## Changelog

### 1.0.0
- Prvé vydanie
- Základné funkcie požičiavania
- WooCommerce integrácia
- Dashboard a správa kníh
- Hodnotenia a notifikácie

## Autor

Vytvorené pre Komunitnú Knižnicu

## Licencia

Proprietárny softvér
