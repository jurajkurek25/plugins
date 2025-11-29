<?php
/**
 * Trieda pre hodnotenia a recenzie kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Rating {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_bbk_add_rating', array($this, 'ajax_add_rating'));
    }

    /**
     * Pridanie hodnotenia
     */
    public function add_rating($book_id, $user_id, $lending_id, $rating, $review = '') {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'buddyboss-kniznica'));
        }

        // Validácia hodnotenia (1-5)
        if ($rating < 1 || $rating > 5) {
            return new WP_Error('invalid_rating', __('Hodnotenie musí byť medzi 1 a 5.', 'buddyboss-kniznica'));
        }

        // Kontrola či používateľ už hodnotil túto knihu pri tomto požičaní
        $existing = BBK_Database::get_row('ratings', array('lending_id' => $lending_id));

        if ($existing) {
            return new WP_Error('already_rated', __('Túto knihu ste už ohodnotili.', 'buddyboss-kniznica'));
        }

        // Kontrola či používateľ požičal túto knihu
        $lending = BBK_Database::get_row('lendings', array(
            'id' => $lending_id,
            'borrower_id' => $user_id,
            'book_id' => $book_id
        ));

        if (!$lending) {
            return new WP_Error('not_borrowed', __('Môžete hodnotiť len knihy, ktoré ste si požičali.', 'buddyboss-kniznica'));
        }

        // Vytvorenie hodnotenia
        $rating_data = array(
            'book_id' => $book_id,
            'user_id' => $user_id,
            'lending_id' => $lending_id,
            'rating' => absint($rating),
            'review' => sanitize_textarea_field($review)
        );

        $rating_id = BBK_Database::insert('ratings', $rating_data);

        if ($rating_id) {
            // Notifikácia majiteľovi knihy
            $book = BBK_Book::get_instance()->get_book($book_id);
            BBK_Notifications::get_instance()->create_notification(
                $book->user_id,
                'new_rating',
                __('Nové hodnotenie', 'buddyboss-kniznica'),
                sprintf(__('%s ohodnotil vašu knihu "%s" %d hviezdičkami.', 'buddyboss-kniznica'),
                    get_user_by('id', $user_id)->display_name,
                    $book->title,
                    $rating
                )
            );

            return $rating_id;
        }

        return new WP_Error('insert_failed', __('Nepodarilo sa pridať hodnotenie.', 'buddyboss-kniznica'));
    }

    /**
     * Získanie priemerného hodnotenia knihy
     */
    public function get_average_rating($book_id) {
        global $wpdb;
        $table = BBK_Database::get_table_name('ratings');

        $query = $wpdb->prepare(
            "SELECT AVG(rating) as average, COUNT(*) as count FROM {$table} WHERE book_id = %d",
            $book_id
        );

        $result = $wpdb->get_row($query);

        return array(
            'average' => $result->average ? round($result->average, 1) : 0,
            'count' => $result->count ? $result->count : 0
        );
    }

    /**
     * Získanie hodnotení knihy
     */
    public function get_book_ratings($book_id, $limit = null) {
        return BBK_Database::get_results('ratings', array('book_id' => $book_id), OBJECT, 'created_at DESC', $limit);
    }

    /**
     * AJAX: Pridanie hodnotenia
     */
    public function ajax_add_rating() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $lending_id = absint($_POST['lending_id']);
        $rating = absint($_POST['rating']);
        $review = isset($_POST['review']) ? $_POST['review'] : '';
        $user_id = get_current_user_id();

        $result = $this->add_rating($book_id, $user_id, $lending_id, $rating, $review);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Hodnotenie bolo úspešne pridané!', 'buddyboss-kniznica')));
    }
}
