# Portfolio - Juraj Augustín Kurek

Jednoduchá moderná webová stránka s čiernym pozadím, split-screen dizajnom a plynulými animáciami.

## 📁 Štruktúra súborov

```
portfolio/
├── index.html      # Hlavný HTML súbor
├── style.css       # CSS štýly
├── script.js       # JavaScript funkcionalita
└── README.md       # Tento súbor
```

## 🎨 Dizajn

- **Čierne pozadie** s elegantným minimalistickým dizajnom
- **Split-screen layout**: Ľavá strana s fotografiou, pravá s obsahom
- **Responzívny dizajn** pre všetky zariadenia
- **Plynulé animácie** a prechody
- **Moderná typografia** a čitateľný obsah

## 🖼️ Pridanie fotografie

Pre pridanie vašej fotografie upravte súbor `style.css`:

1. Nájdite triedu `.photo-placeholder` (riadok ~78)
2. Odkomentujte a upravte:

```css
.photo-placeholder {
    background-image: url('vasa-fotografia.jpg');
    background-size: cover;
    background-position: center;
    filter: grayscale(100%);
    /* Ak chcete len polovicu tváre */
    clip-path: inset(0 50% 0 0);
}
```

3. Umiestnite fotografiu do priečinka `portfolio/`
4. Zmeňte `'vasa-fotografia.jpg'` na názov vášho súboru

### Alternatívne riešenia pre fotografiu:

**Celá fotografia:**
```css
background-size: cover;
background-position: center;
```

**Len ľavá polovica:**
```css
clip-path: inset(0 50% 0 0);
```

**Len pravá polovica:**
```css
clip-path: inset(0 0 0 50%);
```

## ✨ Funkcionality

- **Smooth scrolling** - plynulé posúvanie medzi sekciami
- **Aktívna navigácia** - zvýraznenie aktuálnej sekcie
- **Parallax efekt** - jemný pohyb fotografie pri scrollovaní
- **Hover animácie** - interaktívne efekty pri prejdení myšou
- **Responzívny dizajn** - prispôsobenie pre mobily a tablety
- **Easter egg** - skrytá funkcia (Konami kód 😉)

## 🚀 Spustenie

### Lokálne (jednoduchý spôsob):

1. Otvorte súbor `index.html` v prehliadači
2. Dvojklik na `index.html` alebo
3. Pravý klik → Otvoriť v prehliadači

### Lokálny server (odporúčané):

**Python 3:**
```bash
cd portfolio
python -m http.server 8000
```
Otvorte: http://localhost:8000

**Node.js (npx):**
```bash
cd portfolio
npx http-server -p 8000
```
Otvorte: http://localhost:8000

**PHP:**
```bash
cd portfolio
php -S localhost:8000
```
Otvorte: http://localhost:8000

## 📱 Responzivita

Stránka je plne responzívna:

- **Desktop (1200px+)**: Split screen 35/65
- **Tablet (968px - 1200px)**: Split screen 40/60
- **Mobile (< 968px)**: Fotografia nahor, obsah dole
- **Small mobile (< 480px)**: Optimalizované pre malé displeje

## 🎨 Prispôsobenie

### Farby

V súbore `style.css` môžete upraviť farby v `:root`:

```css
:root {
    --black: #000000;
    --white: #ffffff;
    --gray: #cccccc;
    --dark-gray: #333333;
    --accent: #666666;
}
```

### Fonty

Aktuálne použitý font: `Segoe UI`

Pre zmenu fontu:
```css
body {
    font-family: 'Váš font', sans-serif;
}
```

### Layout proporcie

Pre zmenu pomeru ľavej/pravej časti:
```css
.left-section {
    width: 40%; /* Zmeňte podľa potreby */
}

.right-section {
    margin-left: 40%; /* Rovnaké číslo */
}
```

## 📧 Kontakt

**Email:** juraj@jurajkurek.com
**Web:** jurajkurek.com

## 📄 Licencia

© 2025 Juraj Augustín Kurek. Všetky práva vyhradené.

---

**Vytvorené s ❤️ pomocou HTML, CSS a vanilla JavaScript**
