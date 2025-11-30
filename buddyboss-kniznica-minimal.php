<?php
/**
 * Plugin Name: BuddyBoss Komunitná Knižnica (Minimal)
 * Plugin URI: https://potrebnymuz.sk
 * Description: Minimalistická verzia na diagnostiku
 * Version: 1.0.7
 * Author: Bratstvo Potrebných Mužov
 * Text Domain: buddyboss-kniznica
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BBK_VERSION', '1.0.7');
define('BBK_PLUGIN_DIR', plugin_dir_path(__FILE__));

// AKTIVÁCIA
function bbk_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Vytvor 4 tabuľky priamo
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bbk_books (
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
        PRIMARY KEY (id)
    ) {$charset_collate}");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bbk_lendings (
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
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate}");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bbk_ratings (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        book_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        lending_id bigint(20) NOT NULL,
        rating tinyint(1) NOT NULL,
        review text,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate}");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bbk_notifications (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        type varchar(50) NOT NULL,
        title varchar(255) NOT NULL,
        message text NOT NULL,
        link varchar(500) DEFAULT NULL,
        is_read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate}");

    update_option('bbk_version', BBK_VERSION);
    update_option('bbk_activated', date('Y-m-d H:i:s'));
}
register_activation_hook(__FILE__, 'bbk_activate');

// DEAKTIVÁCIA
function bbk_deactivate() {
    delete_option('bbk_activated');
}
register_deactivation_hook(__FILE__, 'bbk_deactivate');

// Admin notice
add_action('admin_notices', function() {
    $activated = get_option('bbk_activated');
    if ($activated) {
        echo '<div class="notice notice-success"><p><strong>BBK Minimal:</strong> Plugin aktivovaný ' . esc_html($activated) . '</p></div>';
    }
});
