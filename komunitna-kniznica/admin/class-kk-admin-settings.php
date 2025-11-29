<?php
/**
 * Admin trieda pre nastavenia
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Admin_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_kk_save_settings', array($this, 'save_settings'));
    }

    /**
     * Registrácia nastavení
     */
    public function register_settings() {
        register_setting('kk_settings', 'kk_commission_rate');
        register_setting('kk_settings', 'kk_default_lending_days');
    }

    /**
     * Zobrazenie stránky s nastaveniami
     */
    public static function display_page() {
        include KK_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Uloženie nastavení
     */
    public function save_settings() {
        check_admin_referer('kk_save_settings_nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_die(__('Nemáte oprávnenie na zmenu nastavení.', 'komunitna-kniznica'));
        }

        $commission_rate = absint($_POST['kk_commission_rate']);
        $lending_days = absint($_POST['kk_default_lending_days']);

        // Validácia
        if ($commission_rate < 0 || $commission_rate > 100) {
            $commission_rate = 30;
        }

        if ($lending_days < 1) {
            $lending_days = 30;
        }

        update_option('kk_commission_rate', $commission_rate);
        update_option('kk_default_lending_days', $lending_days);

        wp_redirect(add_query_arg(
            array(
                'page' => 'kk-settings',
                'message' => 'saved'
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}
