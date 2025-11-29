<?php
/**
 * Plugin Name: BuddyBoss Komunitná Knižnica
 * Plugin URI: https://potrebnymuz.sk
 * Description: Komunitná knižnica pre BuddyBoss s WooCommerce integráciou - zdieľanie kníh medzi členmi komunity Bratstva Potrebných Mužov
 * Version: 1.0.0
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
define('BBK_VERSION', '1.0.0');
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Načítanie závislostí
     */
    private function load_dependencies() {
        // Core triedy
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-install.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-database.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-buddyboss.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-book.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-lending.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-rating.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-woocommerce.php';
        require_once BBK_PLUGIN_DIR . 'includes/class-bbk-notifications.php';

        // Admin triedy
        if (is_admin()) {
            require_once BBK_PLUGIN_DIR . 'admin/class-bbk-admin.php';
        }

        // Public triedy
        require_once BBK_PLUGIN_DIR . 'public/class-bbk-public.php';
        require_once BBK_PLUGIN_DIR . 'public/class-bbk-shortcodes.php';
    }

    /**
     * Inicializácia hooks
     */
    private function init_hooks() {
        // Aktivácia a deaktivácia
        register_activation_hook(__FILE__, array('BBK_Install', 'activate'));
        register_deactivation_hook(__FILE__, array('BBK_Install', 'deactivate'));

        // Kontrola závislostí
        add_action('admin_init', array($this, 'check_dependencies'));

        // Inicializácia pluginu
        add_action('plugins_loaded', array($this, 'init'));

        // Načítanie textov
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Kontrola závislostí (BuddyBoss a WooCommerce)
     */
    public function check_dependencies() {
        $errors = array();

        // Kontrola BuddyBoss
        if (!function_exists('buddypress') && !class_exists('BuddyBoss_Platform')) {
            $errors[] = __('BuddyBoss Komunitná Knižnica vyžaduje nainštalovaný a aktívny BuddyBoss Platform plugin.', 'buddyboss-kniznica');
        }

        // Kontrola WooCommerce
        if (!class_exists('WooCommerce')) {
            $errors[] = __('BuddyBoss Komunitná Knižnica vyžaduje nainštalovaný a aktívny WooCommerce plugin.', 'buddyboss-kniznica');
        }

        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                foreach ($errors as $error) {
                    echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
                }
            });
            deactivate_plugins(BBK_PLUGIN_BASENAME);
        }
    }

    /**
     * Inicializácia pluginu
     */
    public function init() {
        // Inicializácia tried
        BBK_Database::get_instance();
        BBK_BuddyBoss::get_instance();
        BBK_Book::get_instance();
        BBK_Lending::get_instance();
        BBK_Rating::get_instance();
        BBK_WooCommerce::get_instance();
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
 * Spustenie pluginu
 */
function bbk_run() {
    return BuddyBoss_Kniznica::get_instance();
}

// Spustenie pluginu
bbk_run();
