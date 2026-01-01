<?php
/**
 * Plugin Name: Patreon Alternative - Membership & Creator Platform
 * Plugin URI: https://yoursite.com
 * Description: Komplexný membership systém s video playerom a vlastnou témou - alternatíva k Patreonu. Umožňuje tvorcom obsahu monetizovať svoj obsah cez mesačné členstvo s rôznymi tier úrovňami.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: patreon-alt
 * Domain Path: /languages
 */

// Zabránenie priameho prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definovanie konštánt
define('PA_VERSION', '1.0.0');
define('PA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Hlavná trieda pluginu
 */
class Patreon_Alternative {

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
        $this->define_hooks();
    }

    /**
     * Načítanie všetkých závislostí
     */
    private function load_dependencies() {
        // Core includes
        require_once PA_PLUGIN_DIR . 'includes/class-pa-database.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-install.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-membership.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-tiers.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-posts.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-payments.php';
        require_once PA_PLUGIN_DIR . 'includes/class-pa-video-player.php';

        // Admin includes
        if (is_admin()) {
            require_once PA_PLUGIN_DIR . 'admin/class-pa-admin.php';
            require_once PA_PLUGIN_DIR . 'admin/class-pa-admin-tiers.php';
            require_once PA_PLUGIN_DIR . 'admin/class-pa-admin-posts.php';
        }

        // Public includes
        require_once PA_PLUGIN_DIR . 'public/class-pa-public.php';
        require_once PA_PLUGIN_DIR . 'public/class-pa-shortcodes.php';
    }

    /**
     * Definovanie WordPress hooks
     */
    private function define_hooks() {
        // Aktivácia a deaktivácia
        register_activation_hook(__FILE__, array('PA_Install', 'activate'));
        register_deactivation_hook(__FILE__, array('PA_Install', 'deactivate'));

        // Inicializácia komponentov
        add_action('plugins_loaded', array($this, 'init_components'));

        // Načítanie textových domén pre preklady
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Registrácia vlastných post typov
        add_action('init', array($this, 'register_post_types'));

        // Admin notices pre inštaláciu
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Inicializácia komponentov
     */
    public function init_components() {
        // Inicializácia membership systému
        PA_Membership::get_instance();
        PA_Tiers::get_instance();
        PA_Posts::get_instance();
        PA_Payments::get_instance();
        PA_Video_Player::get_instance();

        // Inicializácia admin rozhrania
        if (is_admin()) {
            PA_Admin::get_instance();
            PA_Admin_Tiers::get_instance();
            PA_Admin_Posts::get_instance();
        }

        // Inicializácia public rozhrania
        PA_Public::get_instance();
        PA_Shortcodes::get_instance();
    }

    /**
     * Načítanie textových domén
     */
    public function load_textdomain() {
        load_plugin_textdomain('patreon-alt', false, dirname(PA_PLUGIN_BASENAME) . '/languages/');
    }

    /**
     * Registrácia vlastných post typov
     */
    public function register_post_types() {
        // Custom post type pre patron posty
        register_post_type('pa_post', array(
            'labels' => array(
                'name' => __('Patron Posts', 'patreon-alt'),
                'singular_name' => __('Patron Post', 'patreon-alt'),
                'add_new' => __('Add New Post', 'patreon-alt'),
                'add_new_item' => __('Add New Patron Post', 'patreon-alt'),
                'edit_item' => __('Edit Patron Post', 'patreon-alt'),
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => 'patreon-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'comments'),
            'rewrite' => array('slug' => 'posts'),
        ));

        // Taxonomia pre kategórie
        register_taxonomy('pa_category', 'pa_post', array(
            'labels' => array(
                'name' => __('Categories', 'patreon-alt'),
                'singular_name' => __('Category', 'patreon-alt'),
            ),
            'hierarchical' => true,
            'show_in_menu' => true,
        ));
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        // Skontrolovať či bola zobrazená uvítacia správa
        if (get_transient('pa_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <h2><?php _e('🎉 Patreon Alternative úspešne nainštalovaný!', 'patreon-alt'); ?></h2>
                <p><?php _e('Plugin bol úspešne aktivovaný. Prosím dokončite nastavenie:', 'patreon-alt'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>✅ <strong>Paid Membership Pro</strong> - Môžete nainštalovať (voliteľné)</li>
                    <li>✅ <strong>HTML5 Video Player</strong> - Integrovaný</li>
                    <li>✅ <strong>Patreon-style téma</strong> - Dostupná v Appearance > Themes</li>
                    <li>✅ <strong>Membership tiers</strong> - Vytvorte v <a href="<?php echo admin_url('admin.php?page=pa-tiers'); ?>">Membership Tiers</a></li>
                    <li>✅ <strong>Platobné nastavenia</strong> - Nakonfigurujte v <a href="<?php echo admin_url('admin.php?page=pa-settings'); ?>">Settings</a></li>
                </ul>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=patreon-alt'); ?>" class="button button-primary">
                        <?php _e('Začať nastavenie', 'patreon-alt'); ?>
                    </a>
                    <a href="<?php echo admin_url('themes.php'); ?>" class="button">
                        <?php _e('Aktivovať tému', 'patreon-alt'); ?>
                    </a>
                </p>
            </div>
            <?php
            delete_transient('pa_activation_notice');
        }
    }
}

/**
 * Spustenie pluginu
 */
function patreon_alternative() {
    return Patreon_Alternative::get_instance();
}

// Inicializácia
patreon_alternative();
