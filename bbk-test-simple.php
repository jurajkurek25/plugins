<?php
/**
 * Plugin Name: BBK Test Simple
 * Description: Minimálny test plugin na zistenie problému
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Test 1: Plugin sa načítava
add_action('admin_notices', function() {
    echo '<div class="notice notice-success"><p>BBK TEST: Plugin sa úspešne načítal</p></div>';
});

// Test 2: Activation hook
function bbk_test_activate() {
    update_option('bbk_test_activated', current_time('mysql'));
    error_log('BBK TEST: Activation hook executed');
}
register_activation_hook(__FILE__, 'bbk_test_activate');

// Test 3: Vytvorenie databázovej tabuľky
function bbk_test_create_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE {$wpdb->prefix}bbk_test (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        test_data varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
    error_log('BBK TEST: Table created');
}
add_action('plugins_loaded', 'bbk_test_create_table');
