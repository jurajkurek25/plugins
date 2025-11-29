<?php
/**
 * Inštalačná trieda - aktivácia a deaktivácia pluginu
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Install {

    /**
     * Aktivácia pluginu
     */
    public static function activate() {
        // DÔLEŽITÉ: Vymazanie starých tabuliek pred vytvorením nových
        // Toto opravuje problém s nesprávnou štruktúrou z predchádzajúcich verzií
        self::drop_old_tables();

        self::create_tables();
        self::create_dummy_product();
        self::set_default_options();
        self::setup_cron();
        self::create_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Vymazanie starých tabuliek (oprava pre nesprávnu štruktúru)
     */
    private static function drop_old_tables() {
        global $wpdb;

        // Vymaž staré tabuľky ak existujú
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_books");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_lendings");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_ratings");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}kk_notifications");
    }

    /**
     * Deaktivácia pluginu
     */
    public static function deactivate() {
        self::remove_cron();
        // Ponechanie dát pre prípad opätovnej aktivácie
    }

    /**
     * Vytvorenie databázových tabuliek
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabuľka kníh - dbDelta vyžaduje špecifický formát
        $sql_books = "CREATE TABLE {$wpdb->prefix}kk_books (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            author varchar(255) NOT NULL,
            isbn varchar(20) DEFAULT NULL,
            genre varchar(100) NOT NULL,
            description text DEFAULT NULL,
            book_condition tinyint(2) NOT NULL DEFAULT 5,
            image_url varchar(500) DEFAULT NULL,
            lending_price decimal(10,2) NOT NULL DEFAULT 0.00,
            status varchar(20) NOT NULL DEFAULT 'available',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY genre (genre)
        ) $charset_collate;";

        // Tabuľka požičaní
        $sql_lendings = "CREATE TABLE {$wpdb->prefix}kk_lendings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            book_id bigint(20) unsigned NOT NULL,
            borrower_id bigint(20) unsigned NOT NULL,
            lender_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned DEFAULT NULL,
            lending_price decimal(10,2) NOT NULL DEFAULT 0.00,
            owner_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            community_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            start_date datetime NOT NULL,
            due_date datetime NOT NULL,
            return_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            shipping_name varchar(255) DEFAULT NULL,
            shipping_address varchar(255) DEFAULT NULL,
            shipping_city varchar(100) DEFAULT NULL,
            shipping_postcode varchar(20) DEFAULT NULL,
            shipping_country varchar(50) DEFAULT NULL,
            shipping_phone varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY book_id (book_id),
            KEY borrower_id (borrower_id),
            KEY lender_id (lender_id),
            KEY status (status)
        ) $charset_collate;";

        // Tabuľka hodnotení
        $sql_ratings = "CREATE TABLE {$wpdb->prefix}kk_ratings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            book_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            lending_id bigint(20) unsigned NOT NULL,
            rating tinyint(1) NOT NULL,
            review text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY book_id (book_id),
            KEY user_id (user_id),
            UNIQUE KEY unique_rating (lending_id)
        ) $charset_collate;";

        // Tabuľka notifikácií
        $sql_notifications = "CREATE TABLE {$wpdb->prefix}kk_notifications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(500) DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_books);
        dbDelta($sql_lendings);
        dbDelta($sql_ratings);
        dbDelta($sql_notifications);
    }

    /**
     * Vytvorenie FYZICKÉHO dummy produktu pre WooCommerce
     * KRITICKÉ: Produkt musí byť fyzický, nie virtuálny!
     */
    private static function create_dummy_product() {
        // Kontrola či už produkt existuje
        $existing_product_id = get_option('kk_dummy_product_id');
        if ($existing_product_id && get_post($existing_product_id)) {
            return;
        }

        // Vytvorenie FYZICKÉHO produktu
        $product = new WC_Product_Simple();
        $product->set_name(__('Požičanie knihy z knižnice', 'komunitna-kniznica'));
        $product->set_status('private'); // Neviditeľný v katalógu
        $product->set_catalog_visibility('hidden');
        $product->set_price(0);
        $product->set_regular_price(0);

        // KRITICKÉ: Nastavenie ako FYZICKÝ produkt
        $product->set_virtual(false); // NIE virtuálny!
        $product->set_downloadable(false);

        // Nastavenie hmotnosti a rozmerov
        $product->set_weight('0.5'); // 0.5 kg
        $product->set_length('20'); // 20 cm
        $product->set_width('15'); // 15 cm
        $product->set_height('3'); // 3 cm

        // Uloženie produktu
        $product_id = $product->save();

        // Uloženie ID produktu
        update_option('kk_dummy_product_id', $product_id);
    }

    /**
     * Nastavenie default options
     */
    private static function set_default_options() {
        add_option('kk_commission_rate', 30); // 30% provízia
        add_option('kk_default_lending_days', 30); // 30 dní požičanie
        add_option('kk_version', KK_VERSION);
    }

    /**
     * Nastavenie cron jobs
     */
    private static function setup_cron() {
        // Denná kontrola upomienok
        if (!wp_next_scheduled('kk_daily_reminders')) {
            wp_schedule_event(time(), 'daily', 'kk_daily_reminders');
        }
    }

    /**
     * Odstránenie cron jobs
     */
    private static function remove_cron() {
        $timestamp = wp_next_scheduled('kk_daily_reminders');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kk_daily_reminders');
        }
    }

    /**
     * Vytvorenie capabilities
     */
    private static function create_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_kk_library');
            $admin_role->add_cap('edit_kk_books');
            $admin_role->add_cap('delete_kk_books');
        }
    }
}
