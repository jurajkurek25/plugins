<?php
/**
 * Admin rozhranie
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Pridanie admin menu
     */
    public function add_admin_menu() {
        // Hlavné menu
        add_menu_page(
            __('Patreon Alternative', 'patreon-alt'),
            __('Patreon Alt', 'patreon-alt'),
            'manage_options',
            'patreon-alt',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'patreon-alt',
            __('Dashboard', 'patreon-alt'),
            __('Dashboard', 'patreon-alt'),
            'manage_options',
            'patreon-alt',
            array($this, 'dashboard_page')
        );

        // Membership Tiers submenu
        add_submenu_page(
            'patreon-alt',
            __('Membership Tiers', 'patreon-alt'),
            __('Membership Tiers', 'patreon-alt'),
            'manage_options',
            'pa-tiers',
            array($this, 'tiers_page')
        );

        // Members submenu
        add_submenu_page(
            'patreon-alt',
            __('Members', 'patreon-alt'),
            __('Members', 'patreon-alt'),
            'manage_options',
            'pa-members',
            array($this, 'members_page')
        );

        // Payments submenu
        add_submenu_page(
            'patreon-alt',
            __('Payments', 'patreon-alt'),
            __('Payments', 'patreon-alt'),
            'manage_options',
            'pa-payments',
            array($this, 'payments_page')
        );

        // Settings submenu
        add_submenu_page(
            'patreon-alt',
            __('Settings', 'patreon-alt'),
            __('Settings', 'patreon-alt'),
            'manage_options',
            'pa-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Načítanie admin skriptov
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'patreon-alt') === false && strpos($hook, 'pa-') === false) {
            return;
        }

        wp_enqueue_style('pa-admin', PA_PLUGIN_URL . 'assets/css/admin.css', array(), PA_VERSION);
        wp_enqueue_script('pa-admin', PA_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PA_VERSION, true);

        wp_localize_script('pa-admin', 'paAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pa_admin')
        ));
    }

    /**
     * Dashboard stránka
     */
    public function dashboard_page() {
        $stats = PA_Membership::get_instance()->get_statistics();
        include PA_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Tiers stránka
     */
    public function tiers_page() {
        $tiers = PA_Tiers::get_instance()->get_all_tiers(false);
        include PA_PLUGIN_DIR . 'admin/views/tiers.php';
    }

    /**
     * Members stránka
     */
    public function members_page() {
        $members = PA_Membership::get_instance()->get_all_patrons();
        include PA_PLUGIN_DIR . 'admin/views/members.php';
    }

    /**
     * Payments stránka
     */
    public function payments_page() {
        global $wpdb;
        $table = PA_Database::get_table_name('payments');

        $payments = $wpdb->get_results(
            "SELECT p.*, u.display_name, u.user_email
            FROM $table p
            INNER JOIN {$wpdb->users} u ON p.user_id = u.ID
            ORDER BY p.payment_date DESC
            LIMIT 100"
        );

        include PA_PLUGIN_DIR . 'admin/views/payments.php';
    }

    /**
     * Settings stránka
     */
    public function settings_page() {
        include PA_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Registrácia nastavení
     */
    public function register_settings() {
        // Stripe settings
        register_setting('pa_settings', 'pa_stripe_test_mode');
        register_setting('pa_settings', 'pa_stripe_test_public_key');
        register_setting('pa_settings', 'pa_stripe_test_secret_key');
        register_setting('pa_settings', 'pa_stripe_live_public_key');
        register_setting('pa_settings', 'pa_stripe_live_secret_key');

        // General settings
        register_setting('pa_settings', 'pa_currency');
        register_setting('pa_settings', 'pa_currency_symbol');
        register_setting('pa_settings', 'pa_profile_title');
        register_setting('pa_settings', 'pa_profile_tagline');
        register_setting('pa_settings', 'pa_enable_video_player');
        register_setting('pa_settings', 'pa_video_protection');
    }
}
