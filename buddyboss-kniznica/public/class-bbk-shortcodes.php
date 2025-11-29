<?php
/**
 * Shortcodes trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('bbk_catalog', array($this, 'catalog_shortcode'));
        add_shortcode('bbk_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('bbk_book_detail', array($this, 'book_detail_shortcode'));
        add_shortcode('bbk_add_book', array($this, 'add_book_shortcode'));
        add_shortcode('bbk_my_books', array($this, 'my_books_shortcode'));
    }

    /**
     * [bbk_catalog] - Katalóg kníh
     */
    public function catalog_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 20,
        ), $atts);

        ob_start();
        include BBK_PLUGIN_DIR . 'templates/catalog.php';
        return ob_get_clean();
    }

    /**
     * [bbk_dashboard] - Používateľský dashboard
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený.', 'buddyboss-kniznica') . '</p>';
        }

        ob_start();
        include BBK_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    /**
     * [bbk_book_detail] - Detail knihy
     */
    public function book_detail_shortcode($atts) {
        $book_id = isset($_GET['book_id']) ? absint($_GET['book_id']) : 0;

        if (!$book_id) {
            return '<p>' . __('Kniha nebola nájdená.', 'buddyboss-kniznica') . '</p>';
        }

        ob_start();
        include BBK_PLUGIN_DIR . 'templates/book-detail.php';
        return ob_get_clean();
    }

    /**
     * [bbk_add_book] - Formulár pridania knihy
     */
    public function add_book_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený.', 'buddyboss-kniznica') . '</p>';
        }

        // Kontrola aktívneho členstva
        if (!BBK_BuddyBoss::get_instance()->is_active_member()) {
            return '<p>' . __('Prístup len pre aktívnych členov komunity potrebnymuz.sk.', 'buddyboss-kniznica') . '</p>';
        }

        ob_start();
        include BBK_PLUGIN_DIR . 'templates/add-book.php';
        return ob_get_clean();
    }

    /**
     * [bbk_my_books] - Moje knihy
     */
    public function my_books_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený.', 'buddyboss-kniznica') . '</p>';
        }

        ob_start();
        $user_id = get_current_user_id();
        $my_books = BBK_Book::get_instance()->get_user_books($user_id);
        include BBK_PLUGIN_DIR . 'templates/my-books.php';
        return ob_get_clean();
    }
}
