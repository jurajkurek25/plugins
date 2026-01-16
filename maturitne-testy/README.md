# Maturitné Testy - WordPress Plugin

Komplexný WordPress plugin na vytváranie a vypĺňanie maturitných testov online s pokročilou anti-cheat ochranou.

## Funkcie

### Pre Administrátorov

- ✅ **Vytváranie testov** - Jednoduchý interface pre vytvorenie nových testov
- ✅ **Správa otázok** - Pridávanie a úprava otázok s podporou dvoch typov:
  - Výber z možností (multiple choice)
  - Textová odpoveď
- ✅ **Pridávanie obrázkov** - K otázkam je možné priložiť obrázky
- ✅ **Nastavenia testu**:
  - Časový limit
  - Percentuálny prah na úspech
  - Body za otázky
- ✅ **Shortcode integrácia** - Vloženie testu na ľubovoľnú stránku pomocou `[maturitny_test id="X"]`
- ✅ **Prehľad výsledkov** - Kompletný prehľad všetkých výsledkov testov

### Pre Študentov

- ✅ **Vypĺňanie testov online** - Intuitívne rozhranie pre absolvovanie testov
- ✅ **Časový limit** - Odpočítavanie času (ak je nastavené)
- ✅ **Okamžité výsledky** - Po odoslaní testu sa zobrazia výsledky
- ✅ **Detailné vyhodnotenie** - Zobrazenie správnych a nesprávnych odpovedí
- ✅ **Možnosť opakovania** - Test sa dá vykonať viackrát

### Anti-Cheat Ochrana

Plugin obsahuje pokročilý systém na detekciu podvádzania:

- 🛡️ **Detekcia prepínania okien/kariet** - Sledovanie focus/blur eventov
- 🛡️ **Detekcia Visibility API** - Monitorovanie viditeľnosti stránky
- 🛡️ **Blokovanie DevTools** - Zablokované klávesové skratky (F12, Ctrl+Shift+I, atď.)
- 🛡️ **Blokovanie pravého tlačidla myši** - Zakázanie kontextového menu
- 🛡️ **Vizuálne upozornenia** - Pri detekcii podvádzania sa zobrazí výrazné varovanie
- 🛡️ **Označenie výsledku** - Výsledky s detekovaným pokusem o podvádzanie sú označené ako nerelevantné
- 🛡️ **Počítadlo podvodov** - Sledovanie počtu pokusov o podvádzanie

**Dôležité:** Vzhľadom na obmedzenia prehliadača nie je možné úplne zakázať otvorenie nových okien/kariet. Plugin však každý takýto pokus zaznamenáva a študenta o tom informuje výrazným upozornením.

## Inštalácia

1. Nahrajte priečinok `maturitne-testy` do `/wp-content/plugins/`
2. Aktivujte plugin v administrácii WordPress cez 'Plugins'
3. V menu sa objaví nová položka "Maturitné Testy"

## Použitie

### Vytvorenie testu

1. Prejdite do **Maturitné Testy > Testy**
2. Kliknite na **Pridať nový**
3. Vyplňte:
   - Názov testu
   - Popis (voliteľné)
   - Časový limit v minútach (0 = bez limitu)
   - Percentuálny prah na úspech (napr. 50%)
   - Stav (Koncept/Publikované)
4. Uložte test

### Pridanie otázok

1. Po vytvorení testu kliknite na **Otázky**
2. Kliknite na **Pridať otázku**
3. Vyplňte:
   - Text otázky
   - Typ otázky (Výber z možností / Textová odpoveď)
   - Voliteľne nahrajte obrázok
   - Počet bodov
   - Pre multiple choice: Pridajte možnosti a označte správnu
   - Pre textovú odpoveď: Zadajte správnu odpoveď
4. Uložte otázku
5. Opakujte pre všetky otázky

### Vloženie testu na stránku

1. Vytvorte alebo upravte stránku/príspevok
2. Použijte shortcode: `[maturitny_test id="1"]`
   - Číslo `id` nahraďte ID vášho testu (nájdete v zozname testov)
3. Publikujte stránku

### Zobrazenie výsledkov

1. Prejdite do **Maturitné Testy > Výsledky**
2. Zobrazí sa zoznam všetkých odoslaných testov s:
   - Menom študenta
   - Dosiahnutým skóre
   - Percentom úspešnosti
   - Časom vypĺňania
   - Informáciou o podvádzaní

## Databázová štruktúra

Plugin vytvára nasledujúce tabuľky:

- `wp_mt_tests` - Uloženie testov
- `wp_mt_questions` - Otázky k testom
- `wp_mt_options` - Možnosti pre multiple choice otázky
- `wp_mt_results` - Výsledky študentov
- `wp_mt_answers` - Odpovede študentov na jednotlivé otázky

## Technické detaily

- **Minimum WordPress verzia:** 5.0
- **Minimum PHP verzia:** 7.2
- **Databáza:** MySQL 5.6+
- **Frontend:** jQuery, CSS3, HTML5
- **Backend:** PHP OOP, WordPress API

## Bezpečnosť

- ✅ Nonce validácia pre všetky formuláre
- ✅ Sanitizácia všetkých vstupov
- ✅ Escapovanie všetkých výstupov
- ✅ Kontrola oprávnení používateľa
- ✅ Prepared statements pre databázové dotazy
- ✅ CSRF ochrana

## Podpora pre neprihlásených používateľov

Plugin podporuje vykonanie testov aj pre neprihlásených používateľov. V takom prípade:
- Študent musí zadať meno a email pred začatím testu
- Výsledky sa uložia s týmito údajmi
- IP adresa a user agent sa zaznamená pre identifikáciu

## Changelog

### Verzia 1.0.0
- Prvé vydanie
- Základná funkcionalita pre vytváranie a vypĺňanie testov
- Anti-cheat ochrana
- Podpora obrázkov v otázkach
- Časový limit
- Detailné výsledky
- Shortcode integrácia

## Licencia

GPL-2.0+

## Autor

Vytvorené pre potreby online maturitných skúšok.

## Poznámky

- Plugin je určený pre testovacie účely, preto umožňuje opakovanie testov
- Anti-cheat mechanizmy fungujú najlepšie v moderných prehliadačoch
- Pre produkčné nasadenie odporúčame dodatočné bezpečnostné opatrenia

## Podpora

Pre hlásenie chýb alebo návrhy na vylepšenie použite GitHub Issues.
