<?php
/**
 * Plugin Name: Maturitné Testy
 * Plugin URI: https://example.com/maturitne-testy
 * Description: Plugin na vytváranie a vypĺňanie maturitných testov online s anti-cheat ochranou
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: maturitne-testy
 * Domain Path: /languages
 */

// Zabránenie priamemu prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definovanie konštánt
define('MT_VERSION', '1.0.0');
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Načítanie tried
require_once MT_PLUGIN_DIR . 'includes/class-mt-database.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-install.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-test.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-question.php';
require_once MT_PLUGIN_DIR . 'includes/class-mt-result.php';
require_once MT_PLUGIN_DIR . 'admin/class-mt-admin.php';
require_once MT_PLUGIN_DIR . 'admin/class-mt-admin-tests.php';
require_once MT_PLUGIN_DIR . 'admin/class-mt-admin-questions.php';
require_once MT_PLUGIN_DIR . 'public/class-mt-public.php';
require_once MT_PLUGIN_DIR . 'public/class-mt-shortcodes.php';

// Hook pre aktiváciu pluginu
register_activation_hook(__FILE__, array('MT_Install', 'activate'));

// Hook pre deaktiváciu pluginu
register_deactivation_hook(__FILE__, array('MT_Install', 'deactivate'));

// Inicializácia pluginu
function mt_init() {
    // Načítanie textovej domény pre preklady
    load_plugin_textdomain('maturitne-testy', false, dirname(MT_PLUGIN_BASENAME) . '/languages');

    // Inicializácia admin rozhrania
    if (is_admin()) {
        new MT_Admin();
        new MT_Admin_Tests();
        new MT_Admin_Questions();
    }

    // Inicializácia public rozhrania
    new MT_Public();
    new MT_Shortcodes();
}
add_action('plugins_loaded', 'mt_init');

// Načítanie štýlov a skriptov
function mt_enqueue_scripts() {
    if (!is_admin()) {
        // CSS
        wp_enqueue_style('mt-public-css', MT_PLUGIN_URL . 'assets/css/public.css', array(), MT_VERSION);

        // JavaScript
        wp_enqueue_script('mt-public-js', MT_PLUGIN_URL . 'assets/js/public.js', array('jquery'), MT_VERSION, true);

        // Lokalizácia pre AJAX
        wp_localize_script('mt-public-js', 'mt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'mt_enqueue_scripts');

// Admin skripty
function mt_admin_enqueue_scripts() {
    wp_enqueue_media();
    wp_enqueue_style('mt-admin-css', MT_PLUGIN_URL . 'assets/css/admin.css', array(), MT_VERSION);
    wp_enqueue_script('mt-admin-js', MT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), MT_VERSION, true);

    wp_localize_script('mt-admin-js', 'mt_admin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mt_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'mt_admin_enqueue_scripts');
