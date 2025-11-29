<?php
/**
 * Public trieda - frontend funkcionalita
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Public {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Načítanie CSS a JS
     */
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style('bbk-public-css', BBK_PLUGIN_URL . 'assets/css/public.css', array(), BBK_VERSION);

        // JS
        wp_enqueue_script('bbk-public-js', BBK_PLUGIN_URL . 'assets/js/public.js', array('jquery'), BBK_VERSION, true);

        // Localize script
        wp_localize_script('bbk-public-js', 'bbkAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bbk_nonce'),
            'strings' => array(
                'confirm_delete' => __('Naozaj chcete zmazať túto knihu?', 'buddyboss-kniznica'),
                'confirm_return' => __('Naozaj chcete vrátiť túto knihu?', 'buddyboss-kniznica'),
                'loading' => __('Načítavam...', 'buddyboss-kniznica'),
            )
        ));
    }
}
