<?php
/**
 * Plugin Name: BBK SQL Test
 * Description: Test SQL tabuliek
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function bbk_sql_test_activate() {
    global $wpdb;

    $errors = array();

    // Test 1: Základný charset
    $charset_collate = $wpdb->get_charset_collate();
    update_option('bbk_sql_test_charset', $charset_collate);

    // Test 2: Vytvor ultra jednoduchú tabuľku
    $table_name = $wpdb->prefix . 'bbk_test_simple';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        test_text varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) {$charset_collate}";

    $result = $wpdb->query($sql);

    if ($result === false) {
        $errors[] = 'SQL ERROR: ' . $wpdb->last_error;
    } else {
        $errors[] = 'SQL OK: Tabuľka vytvorená';
    }

    // Skontroluj či tabuľka existuje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    $errors[] = 'Tabuľka existuje: ' . ($table_exists ? 'ÁNO' : 'NIE');

    update_option('bbk_sql_test_errors', $errors);
}
register_activation_hook(__FILE__, 'bbk_sql_test_activate');

// Zobraz výsledky
add_action('admin_notices', function() {
    $charset = get_option('bbk_sql_test_charset', 'neznámy');
    $errors = get_option('bbk_sql_test_errors', array());

    echo '<div class="notice notice-info">';
    echo '<h3>BBK SQL Test Výsledky:</h3>';
    echo '<p><strong>Charset:</strong> ' . esc_html($charset) . '</p>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
});
