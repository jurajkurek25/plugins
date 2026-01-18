<?php
/**
 * Plugin Name: Komunitná Knižnica
 * Plugin URI: https://example.com/komunitna-kniznica
 * Description: WordPress plugin pre komunitné požičiavanie kníh medzi členmi s WooCommerce integráciou
 * Version: 1.0.0
 * Author: Komunita
 * Author URI: https://example.com
 * Text Domain: komunitna-kniznica
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Zabránenie priameho prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definovanie konštánt
define('KK_VERSION', '1.0.0');
define('KK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KK_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Hlavná trieda pluginu Komunitná Knižnica
 */
class Komunitna_Kniznica {

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
        require_once KK_PLUGIN_DIR . 'includes/class-kk-install.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-database.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-book.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-lending.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-rating.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-notifications.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-woocommerce.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-auth.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-dashboard.php';
        require_once KK_PLUGIN_DIR . 'includes/class-kk-flashcards.php';

        // Admin triedy
        if (is_admin()) {
            require_once KK_PLUGIN_DIR . 'admin/class-kk-admin.php';
            require_once KK_PLUGIN_DIR . 'admin/class-kk-admin-books.php';
            require_once KK_PLUGIN_DIR . 'admin/class-kk-admin-lendings.php';
            require_once KK_PLUGIN_DIR . 'admin/class-kk-admin-settings.php';
            require_once KK_PLUGIN_DIR . 'admin/class-kk-admin-flashcards.php';
        }

        // Public triedy
        require_once KK_PLUGIN_DIR . 'public/class-kk-public.php';
        require_once KK_PLUGIN_DIR . 'public/class-kk-shortcodes.php';
    }

    /**
     * Inicializácia hooks
     */
    private function init_hooks() {
        // Aktivácia a deaktivácia
        register_activation_hook(__FILE__, array('KK_Install', 'activate'));
        register_deactivation_hook(__FILE__, array('KK_Install', 'deactivate'));

        // Inicializácia pluginu
        add_action('plugins_loaded', array($this, 'init'));

        // Načítanie textov
        add_action('init', array($this, 'load_textdomain'));

        // Kontrola WooCommerce závislosti
        add_action('admin_init', array($this, 'check_woocommerce'));
    }

    /**
     * Inicializácia pluginu
     */
    public function init() {
        // Inicializácia tried
        KK_Database::get_instance();
        KK_Book::get_instance();
        KK_Lending::get_instance();
        KK_Rating::get_instance();
        KK_Notifications::get_instance();
        KK_WooCommerce::get_instance();
        KK_Auth::get_instance();
        KK_Dashboard::get_instance();
        KK_Flashcards::get_instance();
        KK_Public::get_instance();
        KK_Shortcodes::get_instance();

        if (is_admin()) {
            KK_Admin::get_instance();
            KK_Admin_Books::get_instance();
            KK_Admin_Lendings::get_instance();
            KK_Admin_Settings::get_instance();
            KK_Admin_Flashcards::get_instance();
        }
    }

    /**
     * Načítanie prekladov
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'komunitna-kniznica',
            false,
            dirname(KK_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Kontrola či je WooCommerce aktívny
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(KK_PLUGIN_BASENAME);
        }
    }

    /**
     * Oznámenie o chýbajúcom WooCommerce
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Komunitná Knižnica vyžaduje nainštalovaný a aktívny WooCommerce plugin.', 'komunitna-kniznica'); ?></p>
        </div>
        <?php
    }
}

/**
 * Spustenie pluginu
 */
function kk_run() {
    return Komunitna_Kniznica::get_instance();
}

// Spustenie pluginu
kk_run();
