<?php
/**
 * Databázová trieda pre správu tabuliek
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Database {

    /**
     * Získanie názvu tabuľky s prefixom
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'pa_' . $table;
    }

    /**
     * Vytvorenie všetkých databázových tabuliek
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabuľka pre membership tiers (úrovne členstva)
        $table_tiers = self::get_table_name('tiers');
        $sql_tiers = "CREATE TABLE $table_tiers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL DEFAULT '0.00',
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            benefits text,
            max_members int(11) DEFAULT NULL,
            current_members int(11) DEFAULT '0',
            is_active tinyint(1) DEFAULT '1',
            sort_order int(11) DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql_tiers);

        // Tabuľka pre členstvá (subscriptions)
        $table_memberships = self::get_table_name('memberships');
        $sql_memberships = "CREATE TABLE $table_memberships (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tier_id bigint(20) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime DEFAULT NULL,
            next_billing_date datetime DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY tier_id (tier_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_memberships);

        // Tabuľka pre patron posty
        $table_posts = self::get_table_name('patron_posts');
        $sql_posts = "CREATE TABLE $table_posts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            tier_id bigint(20) NOT NULL,
            access_level varchar(50) DEFAULT 'all_patrons',
            has_video tinyint(1) DEFAULT '0',
            video_url varchar(500) DEFAULT NULL,
            is_public tinyint(1) DEFAULT '0',
            likes_count int(11) DEFAULT '0',
            comments_count int(11) DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY tier_id (tier_id)
        ) $charset_collate;";

        dbDelta($sql_posts);

        // Tabuľka pre platby
        $table_payments = self::get_table_name('payments');
        $sql_payments = "CREATE TABLE $table_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            membership_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'EUR',
            status varchar(50) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) DEFAULT NULL,
            transaction_id varchar(255) DEFAULT NULL,
            stripe_payment_id varchar(255) DEFAULT NULL,
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY membership_id (membership_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_payments);

        // Tabuľka pre benefit rewards (odmeny pre členov)
        $table_benefits = self::get_table_name('benefits');
        $sql_benefits = "CREATE TABLE $table_benefits (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tier_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            type varchar(50) DEFAULT 'perk',
            icon varchar(100) DEFAULT NULL,
            sort_order int(11) DEFAULT '0',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY tier_id (tier_id)
        ) $charset_collate;";

        dbDelta($sql_benefits);

        // Uloženie verzie databázy
        update_option('pa_db_version', PA_VERSION);
    }

    /**
     * Vymazanie tabuliek pri deaktivácii (voliteľné)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array('tiers', 'memberships', 'patron_posts', 'payments', 'benefits');

        foreach ($tables as $table) {
            $table_name = self::get_table_name($table);
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        delete_option('pa_db_version');
    }
}
