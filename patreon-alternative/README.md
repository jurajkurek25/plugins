# Patreon Alternative - WordPress Membership Plugin

🎉 **Kompletný membership systém pre WordPress - alternatíva k Patreonu**

## Popis

Patreon Alternative je kompletný WordPress plugin, ktorý umožňuje tvorcom obsahu vytvoriť vlastnú platformu pre členstvo a monetizáciu, podobne ako Patreon. Plugin obsahuje všetko potrebné pre spustenie úspešnej creator platformy.

## Hlavné funkcie

### ✨ Membership Tiers
- Vytvorenie neobmedzeného počtu členských úrovní
- Vlastné ceny a výhody pre každú úroveň
- Limitovanie počtu členov pre exkluzivitu
- Automatická správa členských výhod

### 💳 Platobný systém
- **Stripe integrácia** pre bezpečné platby
- Mesačné opakujúce sa platby (subscriptions)
- Test a live režim
- Webhook podpora pre automatické aktualizácie
- História platieb a reporting

### 🎥 HTML5 Video Player
- Vlastný video player s ochranou obsahu
- Zakázanie sťahovania videí
- Watermark s užívateľským menom
- Responzívny design
- Klávesové skratky
- Podpora pre MP4, WebM, OGG formáty

### 📝 Patron Posts
- Vlastný post type pre exkluzívny obsah
- Pridelenie obsahu konkrétnym tier úrovniam
- Automatická ochrana obsahu
- Video integrácia v postoch
- Verejné vs. exkluzívne posty

### 🎨 Vlastná téma
- Moderná téma inšpirovaná Patreonom
- Profil stránka tvorcu
- Responzívny design
- Customizovateľné farby a logo
- Optimalizovaná pre konverziu

### 📊 Admin Dashboard
- Prehľadný dashboard so štatistikami
- Správa členov a tier-ov
- História platieb
- Nastavenia pluginu
- Quick actions

## Inštalácia

### Automatická inštalácia

1. Skopírujte celý priečinok `patreon-alternative` do `/wp-content/plugins/`
2. Prejdite na **Plugins** → **Installed Plugins** v WordPress admin
3. Nájdite "Patreon Alternative" a kliknite na **Activate**
4. Po aktivácii uvidíte uvítaciu správu s ďalšími krokmi

### Manuálna inštalácia

1. Upload `patreon-alternative.zip` cez WordPress admin: **Plugins** → **Add New** → **Upload Plugin**
2. Aktivujte plugin
3. Dokončite nastavenie podľa pokynov nižšie

## Prvotné nastavenie

### 1. Konfigurácia Stripe

1. Prejdite na **Patreon Alt** → **Settings** → **Stripe Payment**
2. Zaregistrujte sa na [Stripe.com](https://stripe.com)
3. Získajte API kľúče z Stripe Dashboard
4. Vložte **Publishable Key** a **Secret Key**
5. Pre testovanie použite Test režim

### 2. Vytvorenie Membership Tiers

1. Prejdite na **Patreon Alt** → **Membership Tiers**
2. Kliknite na **Add New Tier**
3. Vyplňte:
   - Názov tier (napr. "Bronze Patron")
   - Popis
   - Cena (mesačne)
   - Výhody (benefits)
   - Max počet členov (voliteľné)
4. Uložte tier

### 3. Aktivácia témy

1. Prejdite na **Appearance** → **Themes**
2. Aktivujte **Patreon Creator Theme**
3. Nastavte logo a farby podľa vašej značky

### 4. Vytvorenie obsahu

1. Prejdite na **Patreon Alt** → **Patron Posts** → **Add New**
2. Vytvorte váš prvý exkluzívny post
3. Priraďte tier úroveň
4. Pridajte video (voliteľné)
5. Publikujte

## Použitie

### Shortcodes

Plugin obsahuje užitočné shortcodes:

```php
// Zobrazenie profilu tvorcu
[pa_creator_profile]

// Zobrazenie membership tiers
[pa_membership_tiers]

// Feed patron postov
[pa_posts_feed posts_per_page="10"]

// Moje členstvo (pre prihlásených)
[pa_my_membership]

// Video player
[pa_video url="http://example.com/video.mp4" poster="poster.jpg"]
```

### Stránky

Plugin automaticky vytvorí tieto stránky:
- **Creator Profile** - Váš hlavný profil
- **Become a Patron** - Zoznam membership tiers
- **Patron Posts** - Feed exkluzívneho obsahu
- **My Membership** - Dashboard pre členov

## Štruktúra súborov

```
patreon-alternative/
├── patreon-alternative.php    # Hlavný súbor pluginu
├── includes/                   # Core funkčnosť
│   ├── class-pa-database.php
│   ├── class-pa-install.php
│   ├── class-pa-membership.php
│   ├── class-pa-tiers.php
│   ├── class-pa-posts.php
│   ├── class-pa-payments.php
│   └── class-pa-video-player.php
├── admin/                      # Admin rozhranie
│   ├── class-pa-admin.php
│   ├── class-pa-admin-tiers.php
│   ├── class-pa-admin-posts.php
│   └── views/                  # Admin templates
├── public/                     # Frontend
│   ├── class-pa-public.php
│   └── class-pa-shortcodes.php
├── theme/                      # WordPress téma
│   ├── style.css
│   ├── functions.php
│   ├── header.php
│   ├── footer.php
│   ├── index.php
│   └── single.php
├── assets/                     # CSS & JS
│   ├── css/
│   └── js/
└── templates/                  # Template súbory
```

## Databázové tabuľky

Plugin vytvorí nasledujúce tabuľky:

- `wp_pa_tiers` - Membership úrovne
- `wp_pa_memberships` - Členstvá používateľov
- `wp_pa_patron_posts` - Exkluzívne posty
- `wp_pa_payments` - História platieb
- `wp_pa_benefits` - Výhody pre tiers

## Funkcie API

### PHP funkcie

```php
// Získanie membership používateľa
$membership = PA_Membership::get_instance()->get_user_membership($user_id);

// Kontrola prístupu k tier
$has_access = PA_Membership::get_instance()->user_has_tier_access($user_id, $tier_id);

// Získanie všetkých tiers
$tiers = PA_Tiers::get_instance()->get_all_tiers();

// Kontrola prístupu k postu
$can_access = PA_Posts::get_instance()->user_can_access_post($user_id, $post_id);
```

## Technické požiadavky

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+
- Stripe účet (pre platby)

## Bezpečnosť

Plugin obsahuje:
- Nonce validáciu pre všetky formuláre
- SQL injection ochranu
- XSS ochranu
- CSRF ochranu
- Video watermarking
- Download protection pre videá

## Podpora a dokumentácia

- **GitHub**: [github.com/yourrepo/patreon-alternative](https://github.com)
- **Dokumentácia**: Pozrite `/docs` folder
- **Issues**: Reportujte problémy na GitHub

## Changelog

### Version 1.0.0
- Prvé vydanie
- Membership tier systém
- Stripe platby
- HTML5 video player
- Admin dashboard
- Vlastná téma
- Shortcodes
- Automatická inštalácia

## Licencia

GPL-2.0+ - GNU General Public License v2 or later

## Autor

Vytvorené s ❤️ pre WordPress komunitu

## Screenshots

1. **Dashboard** - Prehľad štatistík a quick actions
2. **Membership Tiers** - Správa členských úrovní
3. **Creator Profile** - Frontendový profil tvorcu
4. **Video Player** - Vlastný HTML5 player s ochranou
5. **Admin Settings** - Nastavenia Stripe a pluginu

## FAQ

**Q: Je potrebné Paid Membership Pro?**
A: Nie, tento plugin je kompletná samostatná alternatíva.

**Q: Podporuje plugin PayPal?**
A: Momentálne len Stripe, PayPal bude v budúcej verzii.

**Q: Môžem upraviť tému?**
A: Áno, téma je plne customizovateľná cez WordPress Customizer.

**Q: Je obsah chránený pred sťahovaním?**
A: Áno, videá majú ochranu proti sťahovaniu a watermark.

## Ďalší vývoj

Plánované funkcie pre budúce verzie:
- PayPal integrácia
- Discord integrácia
- Email marketing
- Analytics a reporting
- Mobile aplikácia
- Live streaming podpora

---

**Ďakujeme za použitie Patreon Alternative!** 🚀
