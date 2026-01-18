<?php
/**
 * Public trieda - frontend funkcionalita
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Public {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_kk_borrow_free_book', array($this, 'ajax_borrow_free_book'));
    }

    /**
     * Načítanie frontend skriptov a štýlov
     */
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style(
            'kk-public-css',
            KK_PLUGIN_URL . 'assets/css/public.css',
            array(),
            KK_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'kk-public-js',
            KK_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            KK_VERSION,
            true
        );

        // Lokalizácia
        wp_localize_script('kk-public-js', 'kkPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'cartUrl' => wc_get_cart_url(),
            'nonces' => array(
                'add_book' => wp_create_nonce('kk_add_book_nonce'),
                'edit_book' => wp_create_nonce('kk_edit_book_nonce'),
                'delete_book' => wp_create_nonce('kk_delete_book_nonce'),
                'add_to_cart' => wp_create_nonce('kk_add_to_cart_nonce'),
                'borrow_free' => wp_create_nonce('kk_borrow_free_nonce'),
                'return_book' => wp_create_nonce('kk_return_book_nonce'),
                'add_rating' => wp_create_nonce('kk_add_rating_nonce'),
                'notifications' => wp_create_nonce('kk_notifications_nonce'),
                'upload_image' => wp_create_nonce('kk_upload_image_nonce')
            )
        ));
    }

    /**
     * AJAX: Požičanie bezplatnej knihy
     */
    public function ajax_borrow_free_book() {
        check_ajax_referer('kk_borrow_free_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $user_id = get_current_user_id();

        $result = KK_Lending::get_instance()->create_lending($book_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne požičaná!', 'komunitna-kniznica')));
    }

    /**
     * Získanie template súboru
     */
    public static function get_template($template_name, $args = array()) {
        extract($args);
        $template_path = KK_PLUGIN_DIR . 'templates/' . $template_name . '.php';

        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Získanie obsahu template
     */
    public static function get_template_html($template_name, $args = array()) {
        ob_start();
        self::get_template($template_name, $args);
        return ob_get_clean();
    }
}
