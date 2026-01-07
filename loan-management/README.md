# 💰 Systém správy pôžičiek

Webová aplikácia na správu osobných pôžičiek medzi používateľmi.

## 🌟 Funkcie

- **Registrácia a prihlásenie** používateľov
- **🎫 QR kódové prihlásenie** - rýchle prihlásenie pomocou QR kódu bez hesla
- **Prihlasovacia kartička** - vytlačiteľná kartička s QR kódom na zdieľanie
- **Žiadosti o pôžičku** - dlžník môže požiadať veriteľa o pôžičku
- **Schvaľovanie žiadostí** - veriteľ schvaľuje alebo zamieta žiadosti
- **Správa splátok** - dlžník pridáva splátky, veriteľ ich potvrdzuje
- **Obojstranné potvrdenie** - všetky akcie vyžadujú potvrdenie oboch strán
- **Notifikácie** - používatelia sú informovaní o zmenách
- **Prehľadný dashboard** - štatistiky a prehľad všetkých pôžičiek
- **Responzívny dizajn** - funguje na mobiloch aj počítačoch

## 📋 Požiadavky

- PHP 7.4 alebo vyššie
- MySQL 5.7 alebo vyššie (alebo MariaDB)
- Webový server (Apache, Nginx)
- PDO rozšírenie pre PHP

## 🚀 Inštalácia

### 1. Nahratie súborov

Nahrajte všetky súbory do adresára na vašom webovom serveri.

### 2. Nastavenie databázy

Spustite inštalačný skript v prehliadači:

```
http://vasa-domena.sk/loan-management/install.php
```

Vyplňte databázové údaje:
- **Host**: zvyčajne `localhost`
- **Názov databázy**: `loan_management` (alebo vlastný názov)
- **Používateľ**: databázový používateľ s oprávneniami
- **Heslo**: heslo databázového používateľa

Kliknite na "Spustiť inštaláciu" a počkajte na dokončenie.

### 3. Registrácia prvého používateľa

Po úspešnej inštalácii prejdite na:

```
http://vasa-domena.sk/loan-management/register.php
```

Vytvorte si prvý účet.

### 4. Hotovo!

Teraz sa môžete prihlásiť a začať používať systém.

## 📖 Použitie

### Ako požiadať o pôžičku?

1. Prihláste sa do systému
2. Kliknite na tlačidlo "Nová žiadosť o pôžičku"
3. Vyberte veriteľa (komu žiadate pôžičku)
4. Zadajte sumu a voliteľný popis
5. Odošlite žiadosť

Veriteľ dostane notifikáciu a môže žiadosť schváliť alebo zamietnuť.

### Ako schváliť žiadosť?

1. Prejdite do sekcie "Čakajúce žiadosti"
2. Uvidíte všetky žiadosti, kde ste veriteľom
3. Kliknite na "Schváliť" alebo "Zamietnuť"

### Ako pridať splátku?

1. Prejdite do sekcie "Požičal som si"
2. Kliknite na pôžičku, ktorú chcete splácať
3. V detailoch zadajte sumu splátky
4. Kliknite na "Pridať splátku"

Veriteľ dostane notifikáciu a musí splátku potvrdiť.

### Ako potvrdiť splátku?

1. Prejdite na detail pôžičky (sekcia "Požičal som")
2. V histórii splátok uvidíte čakajúce splátky
3. Kliknite na "Potvrdiť" alebo "Zamietnuť"

Po potvrdení sa automaticky znižuje zostávajúca suma.

### 🎫 Ako používať QR kódové prihlásenie?

Systém umožňuje vytvoriť prihlasovaciu kartičku s QR kódom, ktorú môžete zdieľať s ľuďmi, ktorým požičiavate.

#### Vytvorenie kartičky:

1. Prihláste sa do systému
2. V bočnom menu kliknite na "🎫 Moja kartička"
3. Systém automaticky vygeneruje QR kód
4. Kliknite na "🖼️ Zobraziť kartičku" pre plnú verziu na tlač
5. Vytlačte kartičku alebo ju uložte ako PDF

#### Použitie kartičky:

1. Osoba naskenuje QR kód na kartičke
2. Automaticky sa otvorí prihlasovací odkaz
3. Prihlási sa bez zadávania hesla
4. Vidí všetky svoje pôžičky a môže ich spravovať

#### Bezpečnosť:

- ⚠️ QR kód umožňuje prihlásenie bez hesla - zdieľajte len s dôveryhodnými osobami
- 🔄 Môžete kedykoľvek vygenerovať nový QR kód (starý prestane fungovať)
- 📋 Môžete skopírovať prihlasovací odkaz pre odoslanie emailom/SMS

#### Pre existujúce inštalácie:

Ak už máte nainštalovaný systém a chcete pridať podporu pre QR kód:

```
http://vasa-domena.sk/loan-management/migrate.php
```

Spustite migračný skript, ktorý pridá potrebné stĺpce do databázy.

## 🗂️ Štruktúra projektu

```
loan-management/
├── css/
│   └── style.css              # Štýly aplikácie
├── js/
│   ├── auth.js                # JavaScript pre autentifikáciu
│   └── app.js                 # JavaScript pre dashboard
├── php/
│   ├── api.php                # API endpoint handler
│   ├── auth.php               # Autentifikačný systém
│   ├── config.php             # Konfigurácia
│   ├── config.local.php       # Lokálna konfigurácia (auto-generovaná)
│   ├── database.php           # Databázové pripojenie
│   ├── loans.php              # Správa pôžičiek
│   └── payments.php           # Správa splátok
├── sql/
│   ├── schema.sql             # Databázová schéma
│   └── add_login_token.sql    # Migrácia pre QR kód
├── index.php                  # Dashboard (hlavná stránka)
├── login.php                  # Prihlásenie
├── login-token.php            # Prihlásenie cez QR kód
├── register.php               # Registrácia
├── card.php                   # Prihlasovacia kartička na tlač
├── install.php                # Inštalačný skript
├── migrate.php                # Migračný skript
└── README.md                  # Tento súbor
```

## 🔒 Bezpečnosť

- Heslá sú hashované pomocou `password_hash()` (bcrypt)
- SQL injection ochrana pomocou prepared statements
- XSS ochrana pomocou `htmlspecialchars()`
- Session zabezpečenie (httponly, samesite)
- CSRF ochrana v budúcich verziách

## 🛠️ Konfigurácia

Konfiguračné možnosti nájdete v súbore `php/config.php`:

- `SESSION_LIFETIME` - doba platnosti session (predvolené: 7 dní)
- `PASSWORD_MIN_LENGTH` - minimálna dĺžka hesla (predvolené: 8)
- `TIMEZONE` - časová zóna (predvolené: Europe/Bratislava)

## 📊 Databázová štruktúra

### Tabuľky:

1. **users** - používatelia
2. **loans** - pôžičky
3. **payments** - splátky
4. **notifications** - notifikácie

Všetky vzťahy sú zabezpečené cez foreign keys s CASCADE operáciami.

## 🐛 Riešenie problémov

### Chyba pripojenia k databáze

- Skontrolujte údaje v `php/config.local.php`
- Overte, že MySQL služba beží
- Skontrolujte oprávnenia databázového používateľa

### Session problémy

- Uistite sa, že PHP má práva zápisu do session adresára
- Skontrolujte `session.save_path` v `php.ini`

### Nezobrazujú sa štýly

- Overte, že CSS súbor je dostupný cez prehliadač
- Skontrolujte cesty v HTML súboroch

## 📝 Licencia

Tento projekt je voľne dostupný na osobné a komerčné použitie.

## 🤝 Podpora

Pri problémoch alebo otázkach nás kontaktujte.

## 🔄 Budúce vylepšenia

- CSRF ochrana
- Emailové notifikácie
- Export do PDF/Excel
- Graf histórie splátok
- Pripomienky splatnosti
- Podpora viacerých mien
- Úrokové sadzby
- Mobilná aplikácia

---

Vytvorené s ❤️ pre jednoduchú správu osobných pôžičiek
