<?php
/**
 * Plugin Name: BuddyBoss Komunitná Knižnica
 * Plugin URI: https://potrebnymuz.sk
 * Description: Komunitná knižnica pre BuddyBoss s WooCommerce integráciou - zdieľanie kníh medzi členmi komunity Bratstva Potrebných Mužov
 * Version: 1.0.5
 * Author: Bratstvo Potrebných Mužov
 * Author URI: https://potrebnymuz.sk
 * Text Domain: buddyboss-kniznica
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * BuddyBoss requires at least: 2.0
 */

// Zabránenie priameho prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definovanie konštánt
define('BBK_VERSION', '1.0.5');
define('BBK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BBK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BBK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Hlavná trieda pluginu BuddyBoss Komunitná Knižnica
 */
class BuddyBoss_Kniznica {

    /**
     * Singleton inštancia
     */
    private static $instance = null;

    /**
     * Získanie singleton inštancie
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konštruktor
     */
    private function __construct() {
        // Načítať závislosti až keď sú všetky pluginy načítané
        add_action('plugins_loaded', array($this, 'load_dependencies'), 5);

        // Kontrola závislostí
        add_action('admin_init', array($this, 'check_dependencies'));

        // Načítanie textov
        add_action('init', array($this, 'load_textdomain'));

        // Vytvorenie WooCommerce produktu až po načítaní všetkých pluginov
        add_action('plugins_loaded', array($this, 'ensure_dummy_product'), 20);
    }

    /**
     * Zabezpečenie, že dummy produkt existuje (len ak je WooCommerce dostupné)
     */
    public function ensure_dummy_product() {
        if (!class_exists('WooCommerce')) {
            return;
        }

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
     * Načítanie závislostí - VOLÁ SA AŽ KEĎ SÚ VŠETKY PLUGINY NAČÍTANÉ
     */
    public function load_dependencies() {
        // Základné triedy - vždy potrebné
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-install.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-database.php';

        // BuddyBoss trieda - len ak je BuddyBoss dostupný
        if (function_exists('buddypress') || class_exists('BuddyBoss_Platform')) {
            require_once BBK_PLUGIN_DIR . 'includes/class-bbk-buddyboss.php';
        }

        // Hlavné triedy
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-book.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-lending.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-rating.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-notifications.php';

        // WooCommerce trieda - len ak je WooCommerce dostupný
        if (class_exists('WooCommerce')) {
            require_once BBK_PLUGIN_DIR . 'includes/class-bbk-woocommerce.php';
        }

        // Admin triedy
        if (is_admin()) {
            require_once BBK_PLUGIN_DIR . 'admin/class-bbk-admin.php';
        }

        // Public triedy
        require_once BBK_PLUGIN_DIR . 'public/class-bbk-public.php';
        require_once BBK_PLUGIN_DIR . 'public/class-bbk-shortcodes.php';

        // Inicializácia tried
        $this->init();
    }

    /**
     * Kontrola závislostí (BuddyBoss a WooCommerce)
     */
    public function check_dependencies() {
        // Kontrola BuddyBoss - len varovanie, nedeaktivujeme
        if (!function_exists('buddypress') && !class_exists('BuddyBoss_Platform')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo '<strong>BuddyBoss Komunitná Knižnica:</strong> Pre plnú funkcionalitu prosím nainštalujte a aktivujte BuddyBoss Platform plugin.';
                echo '</p></div>';
            });
        }

        // Kontrola WooCommerce - len varovanie, nedeaktivujeme
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo '<strong>BuddyBoss Komunitná Knižnica:</strong> Pre plnú funkcionalitu prosím nainštalujte a aktivujte WooCommerce plugin.';
                echo '</p></div>';
            });
        }
    }

    /**
     * Inicializácia pluginu
     */
    public function init() {
        // Inicializácia tried
        BBK_Database::get_instance();

        // BuddyBoss integrácia (len ak bola načítaná trieda)
        if (class_exists('BBK_BuddyBoss')) {
            BBK_BuddyBoss::get_instance();
        }

        BBK_Book::get_instance();
        BBK_Lending::get_instance();
        BBK_Rating::get_instance();

        // WooCommerce integrácia (len ak bola načítaná trieda)
        if (class_exists('BBK_WooCommerce')) {
            BBK_WooCommerce::get_instance();
        }

        BBK_Notifications::get_instance();
        BBK_Public::get_instance();
        BBK_Shortcodes::get_instance();

        if (is_admin()) {
            BBK_Admin::get_instance();
        }
    }

    /**
     * Načítanie prekladov
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'buddyboss-kniznica',
            false,
            dirname(BBK_PLUGIN_BASENAME) . '/languages'
        );
    }
}

/**
 * Aktivácia pluginu
 */
function bbk_activate_plugin() {
    require_once BBK_PLUGIN_DIR . 'includes/class-bbk-install.php';
    BBK_Install::activate();
}
register_activation_hook(__FILE__, 'bbk_activate_plugin');

/**
 * Deaktivácia pluginu
 */
function bbk_deactivate_plugin() {
    require_once BBK_PLUGIN_DIR . 'includes/class-bbk-install.php';
    BBK_Install::deactivate();
}
register_deactivation_hook(__FILE__, 'bbk_deactivate_plugin');

/**
 * Spustenie pluginu
 */
function bbk_run() {
    return BuddyBoss_Kniznica::get_instance();
}

// Spustenie pluginu
bbk_run();
