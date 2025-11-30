<?php
/**
 * Plugin Name: BBK Simple - Komunitná Knižnica
 * Description: Jednoduchá verzia bez komplikácií
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BBK_SIMPLE_VERSION', '1.0.0');

// AKTIVÁCIA - len základné veci
function bbk_simple_activate() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // 1 tabuľka - knihy
    $table_books = $wpdb->prefix . 'bbk_books';
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_books} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        author varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate}");

    update_option('bbk_simple_activated', date('Y-m-d H:i:s'));
}
register_activation_hook(__FILE__, 'bbk_simple_activate');

// DEAKTIVÁCIA
function bbk_simple_deactivate() {
    delete_option('bbk_simple_activated');
}
register_deactivation_hook(__FILE__, 'bbk_simple_deactivate');

// Admin notice
add_action('admin_notices', function() {
    $activated = get_option('bbk_simple_activated');
    if ($activated) {
        echo '<div class="notice notice-success"><p><strong>BBK Simple:</strong> Plugin aktivovaný ' . esc_html($activated) . '</p></div>';
    }
});
