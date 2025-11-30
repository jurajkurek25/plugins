<?php
/**
 * Inštalačná trieda - aktivácia a deaktivácia pluginu
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Install {

    /**
     * Aktivácia pluginu
     */
    public static function activate() {
        try {
            self::create_tables();
            self::set_default_options();
            self::setup_cron();
            self::create_capabilities();

            // POZNÁMKA: Dummy WooCommerce produkt sa vytvorí až pri plugins_loaded hooku
            // cez metódu ensure_dummy_product(), nie tu pri aktivácii

            // flush_rewrite_rules môže spôsobiť problémy pri aktivácii
            // Namiesto toho nastavíme flag a urobíme flush pri ďalšom načítaní
            update_option('bbk_flush_rewrite_rules', 1);

        } catch (Exception $e) {
            // Log error
            error_log('BBK Activation Error: ' . $e->getMessage());
            wp_die('BBK Plugin activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Deaktivácia pluginu
     */
    public static function deactivate() {
        self::remove_cron();
    }

    /**
     * Vytvorenie databázových tabuliek
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabuľka kníh
        $table_books = $wpdb->prefix . 'bbk_books';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_books}'") != $table_books) {
            $wpdb->query("CREATE TABLE {$table_books} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                title varchar(255) NOT NULL,
                author varchar(255) NOT NULL,
                isbn varchar(20) DEFAULT NULL,
                genre varchar(100) NOT NULL,
                description text,
                book_condition tinyint(2) NOT NULL DEFAULT 5,
                image_url varchar(500) DEFAULT NULL,
                lending_price decimal(10,2) NOT NULL DEFAULT 0.00,
                status varchar(20) NOT NULL DEFAULT 'available',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY status (status),
                KEY genre (genre)
            ) {$charset_collate}");
        }

        // Tabuľka požičaní
        $table_lendings = $wpdb->prefix . 'bbk_lendings';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_lendings}'") != $table_lendings) {
            $wpdb->query("CREATE TABLE {$table_lendings} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                book_id bigint(20) NOT NULL,
                borrower_id bigint(20) NOT NULL,
                lender_id bigint(20) NOT NULL,
                order_id bigint(20) DEFAULT NULL,
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
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY book_id (book_id),
                KEY borrower_id (borrower_id),
                KEY lender_id (lender_id),
                KEY status (status)
            ) {$charset_collate}");
        }

        // Tabuľka hodnotení
        $table_ratings = $wpdb->prefix . 'bbk_ratings';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_ratings}'") != $table_ratings) {
            $wpdb->query("CREATE TABLE {$table_ratings} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                book_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                lending_id bigint(20) NOT NULL,
                rating tinyint(1) NOT NULL,
                review text,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY book_id (book_id),
                KEY user_id (user_id),
                UNIQUE KEY unique_rating (lending_id)
            ) {$charset_collate}");
        }

        // Tabuľka notifikácií
        $table_notifications = $wpdb->prefix . 'bbk_notifications';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_notifications}'") != $table_notifications) {
            $wpdb->query("CREATE TABLE {$table_notifications} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                type varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                message text NOT NULL,
                link varchar(500) DEFAULT NULL,
                is_read tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY is_read (is_read)
            ) {$charset_collate}");
        }
    }

    /**
     * Vytvorenie FYZICKÉHO dummy produktu pre WooCommerce
     */
    private static function create_dummy_product() {
        $existing_product_id = get_option('bbk_dummy_product_id');
        if ($existing_product_id && get_post($existing_product_id)) {
            return;
        }

        // Vytvorenie FYZICKÉHO produktu (pre získanie doručovacích údajov)
        $product = new WC_Product_Simple();
        $product->set_name(__('Požičanie knihy - Komunitná Knižnica', 'buddyboss-kniznica'));
        $product->set_status('private');
        $product->set_catalog_visibility('hidden');
        $product->set_price(0);
        $product->set_regular_price(0);

        // FYZICKÝ produkt (nie virtuálny)
        $product->set_virtual(false);
        $product->set_downloadable(false);
        $product->set_weight('0.5');
        $product->set_length('20');
        $product->set_width('15');
        $product->set_height('3');

        $product_id = $product->save();
        update_option('bbk_dummy_product_id', $product_id);
    }

    /**
     * Nastavenie predvolených hodnôt
     */
    private static function set_default_options() {
        add_option('bbk_commission_rate', 30); // 30% provízia komunity
        add_option('bbk_default_lending_days', 30); // 30 dní požičanie
        add_option('bbk_version', BBK_VERSION);
    }

    /**
     * Nastavenie cron jobs
     */
    private static function setup_cron() {
        if (!wp_next_scheduled('bbk_daily_reminders')) {
            wp_schedule_event(time(), 'daily', 'bbk_daily_reminders');
        }
    }

    /**
     * Odstránenie cron jobs
     */
    private static function remove_cron() {
        $timestamp = wp_next_scheduled('bbk_daily_reminders');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'bbk_daily_reminders');
        }
    }

    /**
     * Vytvorenie capabilities
     */
    private static function create_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_bbk_library');
            $admin_role->add_cap('edit_bbk_books');
            $admin_role->add_cap('delete_bbk_books');
        }
    }
}
