<?php
/**
 * Public Frontend trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Public {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('template_include', array($this, 'template_include'));
    }

    /**
     * Načítanie frontend skriptov
     */
    public function enqueue_scripts() {
        wp_enqueue_style('pa-public', PA_PLUGIN_URL . 'assets/css/public.css', array(), PA_VERSION);
        wp_enqueue_script('pa-public', PA_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PA_VERSION, true);

        wp_localize_script('pa-public', 'paPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_nonce' => wp_create_nonce('pa_checkout'),
            'cancel_nonce' => wp_create_nonce('pa_cancel_subscription'),
            'stripe_public_key' => PA_Payments::get_instance()->get_public_key(),
            'is_logged_in' => is_user_logged_in()
        ));
    }

    /**
     * Custom templates
     */
    public function template_include($template) {
        // Ak je aktivovaná Patreon téma, použiť jej templates
        if (get_template() === 'patreon-theme') {
            return $template;
        }

        // Single patron post
        if (is_singular('pa_post')) {
            $custom_template = PA_PLUGIN_DIR . 'templates/single-pa-post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        // Archive patron posts
        if (is_post_type_archive('pa_post')) {
            $custom_template = PA_PLUGIN_DIR . 'templates/archive-pa-post.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }
}
