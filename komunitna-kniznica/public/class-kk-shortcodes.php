<?php
/**
 * Shortcodes trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('kk_catalog', array($this, 'catalog_shortcode'));
        add_shortcode('kk_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('kk_book_detail', array($this, 'book_detail_shortcode'));
        add_shortcode('kk_add_book', array($this, 'add_book_shortcode'));

        // Maruritka flashcards shortcodes
        add_shortcode('maruritka_flashcards', array($this, 'flashcards_list_shortcode'));
        add_shortcode('maruritka_flashcards_deck', array($this, 'flashcards_deck_shortcode'));
        add_shortcode('maruritka_flashcards_stats', array($this, 'flashcards_stats_shortcode'));
    }

    /**
     * Shortcode: Katalóg kníh
     */
    public function catalog_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 20
        ), $atts);

        ob_start();
        KK_Public::get_template('catalog', array('limit' => $atts['limit']));
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard používateľa
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený na zobrazenie dashboardu.', 'komunitna-kniznica') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Prihlásiť sa', 'komunitna-kniznica') . '</a></p>';
        }

        ob_start();
        KK_Public::get_template('dashboard');
        return ob_get_clean();
    }

    /**
     * Shortcode: Detail knihy
     */
    public function book_detail_shortcode($atts) {
        $book_id = isset($_GET['book_id']) ? absint($_GET['book_id']) : 0;

        if (!$book_id) {
            return '<p>' . __('Kniha nebola nájdená.', 'komunitna-kniznica') . '</p>';
        }

        ob_start();
        KK_Public::get_template('book-detail', array('book_id' => $book_id));
        return ob_get_clean();
    }

    /**
     * Shortcode: Pridanie knihy
     */
    public function add_book_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený na pridanie knihy.', 'komunitna-kniznica') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Prihlásiť sa', 'komunitna-kniznica') . '</a></p>';
        }

        ob_start();
        KK_Public::get_template('add-book');
        return ob_get_clean();
    }

    /**
     * Shortcode: Zoznam flashcard decks
     * Použitie: [maruritka_flashcards category="slovenčina" subject="gramatika"]
     */
    public function flashcards_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'subject' => '',
            'difficulty' => ''
        ), $atts);

        ob_start();
        KK_Public::get_template('flashcards-list', $atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Flashcard deck - učenie
     * Použitie: [maruritka_flashcards_deck deck_id="1"]
     * alebo automaticky z URL parametra ?deck_id=1
     */
    public function flashcards_deck_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený na učenie sa.', 'komunitna-kniznica') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Prihlásiť sa', 'komunitna-kniznica') . '</a></p>';
        }

        $atts = shortcode_atts(array(
            'deck_id' => isset($_GET['deck_id']) ? absint($_GET['deck_id']) : 0
        ), $atts);

        if (!$atts['deck_id']) {
            return '<p>' . __('Deck nebol nájdený.', 'komunitna-kniznica') . '</p>';
        }

        ob_start();
        KK_Public::get_template('flashcards-deck', $atts);
        return ob_get_clean();
    }

    /**
     * Shortcode: Flashcard štatistiky
     * Použitie: [maruritka_flashcards_stats deck_id="1"]
     */
    public function flashcards_stats_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Musíte byť prihlásený.', 'komunitna-kniznica') . '</p>';
        }

        $atts = shortcode_atts(array(
            'deck_id' => isset($_GET['deck_id']) ? absint($_GET['deck_id']) : 0
        ), $atts);

        if (!$atts['deck_id']) {
            return '<p>' . __('Deck nebol nájdený.', 'komunitna-kniznica') . '</p>';
        }

        ob_start();
        KK_Public::get_template('flashcards-stats', $atts);
        return ob_get_clean();
    }
}
