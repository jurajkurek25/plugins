<?php
/**
 * Autentifikačná trieda - kontrola oprávnení
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Auth {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Inicializácia
    }

    /**
     * Kontrola či je používateľ prihlásený
     */
    public static function is_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Získanie aktuálneho používateľa
     */
    public static function get_current_user_id() {
        return get_current_user_id();
    }

    /**
     * Kontrola či používateľ môže spravovať knižnicu (admin)
     */
    public static function can_manage_library() {
        return current_user_can('manage_kk_library');
    }

    /**
     * Kontrola či používateľ vlastní knihu
     */
    public static function owns_book($book_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = self::get_current_user_id();
        }

        $book = KK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return false;
        }

        return $book->user_id == $user_id;
    }

    /**
     * Kontrola či používateľ môže upraviť knihu
     */
    public static function can_edit_book($book_id, $user_id = null) {
        if (self::can_manage_library()) {
            return true;
        }

        return self::owns_book($book_id, $user_id);
    }

    /**
     * Kontrola či používateľ si môže požičať knihu
     */
    public static function can_borrow_book($book_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = self::get_current_user_id();
        }

        if (!self::is_logged_in()) {
            return false;
        }

        $book = KK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return false;
        }

        // Nemôže si požičať vlastnú knihu
        if ($book->user_id == $user_id) {
            return false;
        }

        // Kniha musí byť dostupná
        if ($book->status !== 'available') {
            return false;
        }

        return true;
    }

    /**
     * Vyžadovanie prihlásenia
     */
    public static function require_login() {
        if (!self::is_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }

    /**
     * Vyžadovanie admin oprávnení
     */
    public static function require_admin() {
        if (!self::can_manage_library()) {
            wp_die(__('Nemáte oprávnenie na prístup k tejto stránke.', 'komunitna-kniznica'));
        }
    }
}
