<?php
/**
 * Trieda pre správu hodnotení
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Rating {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_kk_add_rating', array($this, 'ajax_add_rating'));
    }

    /**
     * Pridanie hodnotenia
     */
    public function add_rating($lending_id, $rating, $review = '') {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'komunitna-kniznica'));
        }

        $user_id = get_current_user_id();

        // Získanie požičania
        $lending = KK_Database::get_row('lendings', array('id' => $lending_id));

        if (!$lending) {
            return new WP_Error('lending_not_found', __('Požičanie nebolo nájdené.', 'komunitna-kniznica'));
        }

        // Kontrola či používateľ si požičal túto knihu
        if ($lending->borrower_id != $user_id) {
            return new WP_Error('permission_denied', __('Môžete hodnotiť len knihy, ktoré ste si požičali.', 'komunitna-kniznica'));
        }

        // Kontrola či bola kniha vrátená
        if ($lending->status !== 'returned') {
            return new WP_Error('not_returned', __('Môžete hodnotiť len vrátené knihy.', 'komunitna-kniznica'));
        }

        // Kontrola či už neexistuje hodnotenie
        $existing = KK_Database::get_row('ratings', array('lending_id' => $lending_id));
        if ($existing) {
            return new WP_Error('already_rated', __('Túto knihu ste už ohodnotili.', 'komunitna-kniznica'));
        }

        // Validácia hodnotenia
        $rating = absint($rating);
        if ($rating < 1 || $rating > 5) {
            return new WP_Error('invalid_rating', __('Hodnotenie musí byť medzi 1 a 5.', 'komunitna-kniznica'));
        }

        // Vytvorenie hodnotenia
        $rating_data = array(
            'book_id' => $lending->book_id,
            'user_id' => $user_id,
            'lending_id' => $lending_id,
            'rating' => $rating,
            'review' => sanitize_textarea_field($review)
        );

        $rating_id = KK_Database::insert('ratings', $rating_data);

        if ($rating_id) {
            // Notifikácia majiteľovi knihy
            $book = KK_Database::get_row('books', array('id' => $lending->book_id));
            KK_Notifications::get_instance()->create_notification(
                $book->user_id,
                'new_rating',
                __('Nové hodnotenie knihy', 'komunitna-kniznica'),
                sprintf(__('Vaša kniha "%s" dostala hodnotenie %d/5.', 'komunitna-kniznica'),
                    $book->title,
                    $rating
                )
            );

            return $rating_id;
        }

        return new WP_Error('insert_failed', __('Nepodarilo sa pridať hodnotenie.', 'komunitna-kniznica'));
    }

    /**
     * Získanie hodnotení knihy
     */
    public function get_book_ratings($book_id) {
        return KK_Database::get_results('ratings', array('book_id' => $book_id), OBJECT, 'created_at DESC');
    }

    /**
     * Získanie priemerného hodnotenia knihy
     */
    public function get_average_rating($book_id) {
        global $wpdb;
        $table = KK_Database::get_table_name('ratings');

        $query = $wpdb->prepare(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM {$table} WHERE book_id = %d",
            $book_id
        );

        $result = $wpdb->get_row($query);

        return array(
            'average' => $result->avg_rating ? round($result->avg_rating, 1) : 0,
            'count' => $result->count
        );
    }

    /**
     * Kontrola či používateľ môže hodnotiť knihu
     */
    public function can_rate_book($book_id, $user_id) {
        global $wpdb;
        $lendings_table = KK_Database::get_table_name('lendings');
        $ratings_table = KK_Database::get_table_name('ratings');

        // Získanie vrátených požičaní používateľa pre túto knihu
        $query = $wpdb->prepare(
            "SELECT l.id FROM {$lendings_table} l
            LEFT JOIN {$ratings_table} r ON l.id = r.lending_id
            WHERE l.book_id = %d AND l.borrower_id = %d AND l.status = 'returned' AND r.id IS NULL
            LIMIT 1",
            $book_id,
            $user_id
        );

        $lending = $wpdb->get_row($query);

        return $lending ? $lending->id : false;
    }

    /**
     * Získanie hodnotení s detailami používateľa
     */
    public function get_book_ratings_with_users($book_id) {
        global $wpdb;
        $table = KK_Database::get_table_name('ratings');
        $users_table = $wpdb->prefix . 'users';

        $query = $wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_email
            FROM {$table} r
            LEFT JOIN {$users_table} u ON r.user_id = u.ID
            WHERE r.book_id = %d
            ORDER BY r.created_at DESC",
            $book_id
        );

        return $wpdb->get_results($query);
    }

    /**
     * AJAX: Pridanie hodnotenia
     */
    public function ajax_add_rating() {
        check_ajax_referer('kk_add_rating_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $lending_id = absint($_POST['lending_id']);
        $rating = absint($_POST['rating']);
        $review = sanitize_textarea_field($_POST['review']);

        $result = $this->add_rating($lending_id, $rating, $review);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Ďakujeme za hodnotenie!', 'komunitna-kniznica')));
    }
}
