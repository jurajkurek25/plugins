<?php
/**
 * Admin trieda pre správu kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Admin_Books {

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
     * Zobrazenie stránky so zoznamom kníh
     */
    public static function display_page() {
        include KK_PLUGIN_DIR . 'admin/views/books.php';
    }

    /**
     * Získanie všetkých kníh
     */
    public static function get_all_books() {
        global $wpdb;
        $table = KK_Database::get_table_name('books');
        $users_table = $wpdb->prefix . 'users';

        $query = "SELECT b.*, u.display_name as owner_name, u.user_email as owner_email
                  FROM {$table} b
                  LEFT JOIN {$users_table} u ON b.user_id = u.ID
                  ORDER BY b.created_at DESC";

        return $wpdb->get_results($query);
    }
}
