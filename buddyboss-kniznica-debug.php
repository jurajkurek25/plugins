<?php
/**
 * Plugin Name: BBK Debug - Diagnostika
 * Description: Debug verzia na zistenie presnej chyby
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BBK_DEBUG_LOG', WP_CONTENT_DIR . '/bbk-debug.log');

function bbk_debug_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(BBK_DEBUG_LOG, "[$timestamp] $message\n", FILE_APPEND);
}

bbk_debug_log('=== PLUGIN ZAČÍNA NAČÍTAVANIE ===');

// Test aktivácie
function bbk_debug_activate() {
    bbk_debug_log('ACTIVATION HOOK: Start');

    global $wpdb;
    bbk_debug_log('ACTIVATION: Global wpdb loaded');

    $charset_collate = $wpdb->get_charset_collate();
    bbk_debug_log('ACTIVATION: Charset: ' . $charset_collate);

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    bbk_debug_log('ACTIVATION: upgrade.php included');

    // Simplifikovaná tabuľka
    $sql = "CREATE TABLE {$wpdb->prefix}bbk_debug_test (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        test_data varchar(255) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    bbk_debug_log('ACTIVATION: SQL prepared');

    try {
        dbDelta($sql);
        bbk_debug_log('ACTIVATION: dbDelta executed successfully');
    } catch (Exception $e) {
        bbk_debug_log('ACTIVATION ERROR: ' . $e->getMessage());
    }

    // Test či tabuľka existuje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}bbk_debug_test'");
    bbk_debug_log('ACTIVATION: Table exists check: ' . ($table_exists ? 'YES' : 'NO'));

    bbk_debug_log('ACTIVATION HOOK: End');
}
register_activation_hook(__FILE__, 'bbk_debug_activate');

bbk_debug_log('=== PLUGIN NAČÍTANÝ ===');

// Admin notice s logom
add_action('admin_notices', function() {
    if (file_exists(BBK_DEBUG_LOG)) {
        $log_content = file_get_contents(BBK_DEBUG_LOG);
        echo '<div class="notice notice-info">';
        echo '<h3>BBK Debug Log:</h3>';
        echo '<pre style="background:#f0f0f0;padding:10px;overflow:auto;max-height:300px;">' . esc_html($log_content) . '</pre>';
        echo '<p><a href="' . content_url('/bbk-debug.log') . '" target="_blank">Otvoriť log súbor</a></p>';
        echo '</div>';
    }
});
