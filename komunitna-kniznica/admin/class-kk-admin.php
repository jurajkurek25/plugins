<?php
/**
 * Admin hlavná trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Pridanie admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Komunitná Knižnica', 'komunitna-kniznica'),
            __('Knižnica', 'komunitna-kniznica'),
            'manage_kk_library',
            'komunitna-kniznica',
            array($this, 'display_dashboard'),
            'dashicons-book',
            30
        );

        add_submenu_page(
            'komunitna-kniznica',
            __('Dashboard', 'komunitna-kniznica'),
            __('Dashboard', 'komunitna-kniznica'),
            'manage_kk_library',
            'komunitna-kniznica',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'komunitna-kniznica',
            __('Knihy', 'komunitna-kniznica'),
            __('Knihy', 'komunitna-kniznica'),
            'manage_kk_library',
            'kk-books',
            array('KK_Admin_Books', 'display_page')
        );

        add_submenu_page(
            'komunitna-kniznica',
            __('Požičania', 'komunitna-kniznica'),
            __('Požičania', 'komunitna-kniznica'),
            'manage_kk_library',
            'kk-lendings',
            array('KK_Admin_Lendings', 'display_page')
        );

        add_submenu_page(
            'komunitna-kniznica',
            __('Nastavenia', 'komunitna-kniznica'),
            __('Nastavenia', 'komunitna-kniznica'),
            'manage_kk_library',
            'kk-settings',
            array('KK_Admin_Settings', 'display_page')
        );

        // Maruritka Flashcards
        add_submenu_page(
            'komunitna-kniznica',
            __('Flashcards - Balíčky', 'komunitna-kniznica'),
            __('Flashcards', 'komunitna-kniznica'),
            'manage_kk_library',
            'kk-flashcards',
            array('KK_Admin_Flashcards', 'display_page')
        );
    }

    /**
     * Zobrazenie dashboard stránky
     */
    public function display_dashboard() {
        include KK_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Načítanie admin skriptov a štýlov
     */
    public function enqueue_scripts($hook) {
        // Načítať len na stránkach pluginu
        if (strpos($hook, 'komunitna-kniznica') === false && strpos($hook, 'kk-') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'kk-admin-css',
            KK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            KK_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'kk-admin-js',
            KK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            KK_VERSION,
            true
        );

        // Lokalizácia
        wp_localize_script('kk-admin-js', 'kkAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kk_admin_nonce')
        ));
    }
}
