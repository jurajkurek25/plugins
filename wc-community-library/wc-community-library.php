<?php
/**
 * Plugin Name: WooCommerce Komunitná Knižnica
 * Plugin URI: https://potrebnymuz.sk
 * Description: Požičovňa kníh pre členov komunity - knihy ako WooCommerce produkty
 * Version: 1.0.0
 * Author: Bratstvo Potrebných Mužov
 * Text Domain: wc-community-library
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

define('WCCL_VERSION', '1.0.0');
define('WCCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCCL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Hlavná trieda pluginu
 */
class WC_Community_Library {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Kontrola WooCommerce
        add_action('admin_notices', array($this, 'check_woocommerce'));

        // Inicializácia
        add_action('plugins_loaded', array($this, 'init'), 20);
    }

    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p><strong>WC Komunitná Knižnica:</strong> Vyžaduje WooCommerce plugin.</p></div>';
        }
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Načítaj moduly
        require_once WCCL_PLUGIN_DIR . 'includes/class-wccl-product-type.php';
        require_once WCCL_PLUGIN_DIR . 'includes/class-wccl-admin.php';
        require_once WCCL_PLUGIN_DIR . 'includes/class-wccl-frontend.php';
        require_once WCCL_PLUGIN_DIR . 'includes/class-wccl-orders.php';

        // Inicializuj
        WCCL_Product_Type::init();
        WCCL_Admin::init();
        WCCL_Frontend::init();
        WCCL_Orders::init();
    }
}

// Spusti plugin
WC_Community_Library::get_instance();
