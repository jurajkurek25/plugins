<?php
/**
 * Admin trieda pre správu požičaní
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Admin_Lendings {

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
     * Zobrazenie stránky so zoznamom požičaní
     */
    public static function display_page() {
        include KK_PLUGIN_DIR . 'admin/views/lendings.php';
    }

    /**
     * Získanie všetkých požičaní
     */
    public static function get_all_lendings() {
        global $wpdb;
        $table = KK_Database::get_table_name('lendings');
        $books_table = KK_Database::get_table_name('books');
        $users_table = $wpdb->prefix . 'users';

        $query = "SELECT l.*,
                         b.title as book_title, b.author as book_author,
                         u1.display_name as borrower_name,
                         u2.display_name as lender_name
                  FROM {$table} l
                  LEFT JOIN {$books_table} b ON l.book_id = b.id
                  LEFT JOIN {$users_table} u1 ON l.borrower_id = u1.ID
                  LEFT JOIN {$users_table} u2 ON l.lender_id = u2.ID
                  ORDER BY l.created_at DESC";

        return $wpdb->get_results($query);
    }

    /**
     * Získanie štatistík
     */
    public static function get_stats() {
        global $wpdb;
        $lendings_table = KK_Database::get_table_name('lendings');

        // Celkový počet požičaní
        $total_lendings = $wpdb->get_var("SELECT COUNT(*) FROM {$lendings_table}");

        // Aktívne požičania
        $active_lendings = $wpdb->get_var("SELECT COUNT(*) FROM {$lendings_table} WHERE status = 'active'");

        // Celkové príjmy komunity
        $total_community_income = $wpdb->get_var("SELECT SUM(community_commission) FROM {$lendings_table}");

        // Celkové príjmy majiteľov
        $total_owner_income = $wpdb->get_var("SELECT SUM(owner_commission) FROM {$lendings_table}");

        return array(
            'total_lendings' => $total_lendings,
            'active_lendings' => $active_lendings,
            'total_community_income' => $total_community_income ? floatval($total_community_income) : 0,
            'total_owner_income' => $total_owner_income ? floatval($total_owner_income) : 0
        );
    }
}
