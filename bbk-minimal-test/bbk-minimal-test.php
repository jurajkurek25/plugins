<?php
/**
 * Plugin Name: BBK Minimal Test
 * Description: Ultra minimalistický test
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Aktivácia - úplne prázdna
function bbk_minimal_activate() {
    // Absolútne nič
    update_option('bbk_minimal_test', 'Plugin aktivovaný ' . date('Y-m-d H:i:s'));
}
register_activation_hook(__FILE__, 'bbk_minimal_activate');

// Zobraz hlášku
add_action('admin_notices', function() {
    $test = get_option('bbk_minimal_test', 'neaktivované');
    echo '<div class="notice notice-success"><p><strong>BBK Minimal Test:</strong> ' . esc_html($test) . '</p></div>';
});
