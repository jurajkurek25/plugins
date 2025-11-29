<?php
/**
 * Dashboard trieda - data pre používateľský dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Dashboard {

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
     * Získanie dashboardových dát pre používateľa
     */
    public function get_dashboard_data($user_id) {
        return array(
            'my_books' => $this->get_my_books($user_id),
            'books_lent_to_others' => $this->get_books_lent_to_others($user_id),
            'books_borrowed' => $this->get_books_borrowed($user_id),
            'stats' => $this->get_user_stats($user_id),
            'notifications' => $this->get_recent_notifications($user_id)
        );
    }

    /**
     * Moje knihy (ktoré som pridal)
     */
    private function get_my_books($user_id) {
        return KK_Book::get_instance()->get_user_books($user_id);
    }

    /**
     * Knihy požičané iným (moje knihy, ktoré má niekto požičané)
     */
    private function get_books_lent_to_others($user_id) {
        global $wpdb;
        $lendings_table = KK_Database::get_table_name('lendings');
        $books_table = KK_Database::get_table_name('books');
        $users_table = $wpdb->prefix . 'users';

        $query = $wpdb->prepare(
            "SELECT l.*, b.title as book_title, b.author as book_author,
                    u.display_name as borrower_name, u.user_email as borrower_email
            FROM {$lendings_table} l
            LEFT JOIN {$books_table} b ON l.book_id = b.id
            LEFT JOIN {$users_table} u ON l.borrower_id = u.ID
            WHERE l.lender_id = %d AND l.status = 'active'
            ORDER BY l.created_at DESC",
            $user_id
        );

        return $wpdb->get_results($query);
    }

    /**
     * Knihy požičané odo mňa (knihy, ktoré som si požičal)
     */
    private function get_books_borrowed($user_id) {
        global $wpdb;
        $lendings_table = KK_Database::get_table_name('lendings');
        $books_table = KK_Database::get_table_name('books');
        $users_table = $wpdb->prefix . 'users';

        $query = $wpdb->prepare(
            "SELECT l.*, b.title as book_title, b.author as book_author, b.image_url,
                    u.display_name as lender_name, u.user_email as lender_email
            FROM {$lendings_table} l
            LEFT JOIN {$books_table} b ON l.book_id = b.id
            LEFT JOIN {$users_table} u ON l.lender_id = u.ID
            WHERE l.borrower_id = %d
            ORDER BY l.created_at DESC",
            $user_id
        );

        return $wpdb->get_results($query);
    }

    /**
     * Štatistiky používateľa
     */
    private function get_user_stats($user_id) {
        // Počet mojích kníh
        $my_books_count = KK_Database::get_count('books', array('user_id' => $user_id));

        // Počet aktívnych požičaní (požičané iným)
        $lent_count = KK_Database::get_count('lendings', array(
            'lender_id' => $user_id,
            'status' => 'active'
        ));

        // Počet požičaných kníh (požičané odo mňa)
        $borrowed_count = KK_Database::get_count('lendings', array(
            'borrower_id' => $user_id,
            'status' => 'active'
        ));

        // Celkové príjmy
        global $wpdb;
        $lendings_table = KK_Database::get_table_name('lendings');
        $total_earnings = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(owner_commission) FROM {$lendings_table} WHERE lender_id = %d",
            $user_id
        ));

        return array(
            'my_books_count' => $my_books_count,
            'lent_count' => $lent_count,
            'borrowed_count' => $borrowed_count,
            'total_earnings' => $total_earnings ? floatval($total_earnings) : 0
        );
    }

    /**
     * Najnovšie notifikácie
     */
    private function get_recent_notifications($user_id) {
        return KK_Notifications::get_instance()->get_user_notifications($user_id, 5);
    }
}
