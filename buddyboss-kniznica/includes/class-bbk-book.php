<?php
/**
 * Trieda pre správu kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Book {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_bbk_add_book', array($this, 'ajax_add_book'));
        add_action('wp_ajax_bbk_edit_book', array($this, 'ajax_edit_book'));
        add_action('wp_ajax_bbk_delete_book', array($this, 'ajax_delete_book'));
        add_action('wp_ajax_bbk_upload_image', array($this, 'ajax_upload_image'));
    }

    /**
     * Pridanie knihy
     */
    public function add_book($data) {
        // Kontrola prihlásenia
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'buddyboss-kniznica'));
        }

        // Kontrola aktívneho členstva (len ak je BuddyBoss dostupný)
        if (class_exists('BBK_BuddyBoss') && !BBK_BuddyBoss::get_instance()->is_active_member()) {
            return new WP_Error('not_active_member', __('Prístup len pre aktívnych členov komunity.', 'buddyboss-kniznica'));
        }

        // Validácia povinných polí
        $required_fields = array('title', 'author', 'genre', 'book_condition');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_' . $field, sprintf(__('%s je povinný.', 'buddyboss-kniznica'), ucfirst($field)));
            }
        }

        // Validácia stavu knihy (1-10)
        if ($data['book_condition'] < 1 || $data['book_condition'] > 10) {
            return new WP_Error('invalid_condition', __('Stav knihy musí byť medzi 1 a 10.', 'buddyboss-kniznica'));
        }

        // Validácia ceny požičania (0 € alebo 2-10 €)
        $lending_price = isset($data['lending_price']) ? floatval($data['lending_price']) : 0;
        if ($lending_price > 0 && ($lending_price < 2 || $lending_price > 10)) {
            return new WP_Error('invalid_price', __('Cena požičania musí byť 0 € (zadarmo) alebo medzi 2-10 €.', 'buddyboss-kniznica'));
        }

        $user_id = get_current_user_id();

        $book_data = array(
            'user_id' => $user_id,
            'title' => sanitize_text_field($data['title']),
            'author' => sanitize_text_field($data['author']),
            'isbn' => !empty($data['isbn']) ? sanitize_text_field($data['isbn']) : null,
            'genre' => sanitize_text_field($data['genre']),
            'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'book_condition' => absint($data['book_condition']),
            'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'lending_price' => $lending_price,
            'status' => 'available'
        );

        $book_id = BBK_Database::insert('books', $book_data);

        if ($book_id) {
            // Odoslanie notifikácie komunite (voliteľné)
            do_action('bbk_book_added', $book_id, $user_id);
            return $book_id;
        }

        return new WP_Error('insert_failed', __('Nepodarilo sa pridať knihu.', 'buddyboss-kniznica'));
    }

    /**
     * Aktualizácia knihy
     */
    public function update_book($book_id, $data) {
        if (!$this->can_edit_book($book_id)) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie upraviť túto knihu.', 'buddyboss-kniznica'));
        }

        // Validácia ceny
        $lending_price = isset($data['lending_price']) ? floatval($data['lending_price']) : 0;
        if ($lending_price > 0 && ($lending_price < 2 || $lending_price > 10)) {
            return new WP_Error('invalid_price', __('Cena požičania musí byť 0 € (zadarmo) alebo medzi 2-10 €.', 'buddyboss-kniznica'));
        }

        $book_data = array(
            'title' => sanitize_text_field($data['title']),
            'author' => sanitize_text_field($data['author']),
            'isbn' => !empty($data['isbn']) ? sanitize_text_field($data['isbn']) : null,
            'genre' => sanitize_text_field($data['genre']),
            'description' => sanitize_textarea_field($data['description']),
            'book_condition' => absint($data['book_condition']),
            'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'lending_price' => $lending_price
        );

        $result = BBK_Database::update('books', $book_data, array('id' => $book_id));

        if ($result !== false) {
            return true;
        }

        return new WP_Error('update_failed', __('Nepodarilo sa aktualizovať knihu.', 'buddyboss-kniznica'));
    }

    /**
     * Zmazanie knihy
     */
    public function delete_book($book_id) {
        if (!$this->can_edit_book($book_id)) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie zmazať túto knihu.', 'buddyboss-kniznica'));
        }

        // Kontrola aktívnych požičaní
        $active_lendings = BBK_Database::get_count('lendings', array(
            'book_id' => $book_id,
            'status' => 'active'
        ));

        if ($active_lendings > 0) {
            return new WP_Error('has_active_lendings', __('Knihu nie je možné zmazať, pretože má aktívne požičania.', 'buddyboss-kniznica'));
        }

        $result = BBK_Database::delete('books', array('id' => $book_id));

        if ($result) {
            return true;
        }

        return new WP_Error('delete_failed', __('Nepodarilo sa zmazať knihu.', 'buddyboss-kniznica'));
    }

    /**
     * Získanie knihy
     */
    public function get_book($book_id) {
        return BBK_Database::get_row('books', array('id' => $book_id));
    }

    /**
     * Získanie kníh používateľa
     */
    public function get_user_books($user_id, $limit = null) {
        return BBK_Database::get_results('books', array('user_id' => $user_id), OBJECT, 'created_at DESC', $limit);
    }

    /**
     * Získanie dostupných kníh s filtrami
     */
    public function get_available_books($args = array()) {
        global $wpdb;
        $table = BBK_Database::get_table_name('books');

        $where = "status = 'available'";

        // Filter podľa žánru
        if (!empty($args['genre'])) {
            $genre = esc_sql($args['genre']);
            $where .= " AND genre = '{$genre}'";
        }

        // Filter podľa autora
        if (!empty($args['author'])) {
            $author = esc_sql($args['author']);
            $where .= " AND author LIKE '%{$author}%'";
        }

        // Vyhľadávanie
        if (!empty($args['search'])) {
            $search = esc_sql($args['search']);
            $where .= " AND (title LIKE '%{$search}%' OR author LIKE '%{$search}%' OR genre LIKE '%{$search}%')";
        }

        // Exclude vlastné knihy
        if (!empty($args['exclude_user'])) {
            $user_id = absint($args['exclude_user']);
            $where .= " AND user_id != {$user_id}";
        }

        // Filter podľa ceny (zadarmo / platené)
        if (isset($args['price_type'])) {
            if ($args['price_type'] === 'free') {
                $where .= " AND lending_price = 0";
            } elseif ($args['price_type'] === 'paid') {
                $where .= " AND lending_price > 0";
            }
        }

        $order = "created_at DESC";
        $limit = !empty($args['limit']) ? "LIMIT " . absint($args['limit']) : "";

        $query = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$order} {$limit}";

        return $wpdb->get_results($query);
    }

    /**
     * Získanie všetkých žánrov
     */
    public function get_genres() {
        global $wpdb;
        $table = BBK_Database::get_table_name('books');

        $query = "SELECT DISTINCT genre FROM {$table} ORDER BY genre ASC";
        $results = $wpdb->get_results($query);

        $genres = array();
        foreach ($results as $row) {
            $genres[] = $row->genre;
        }

        return $genres;
    }

    /**
     * Získanie všetkých autorov
     */
    public function get_authors() {
        global $wpdb;
        $table = BBK_Database::get_table_name('books');

        $query = "SELECT DISTINCT author FROM {$table} ORDER BY author ASC";
        $results = $wpdb->get_results($query);

        $authors = array();
        foreach ($results as $row) {
            $authors[] = $row->author;
        }

        return $authors;
    }

    /**
     * Aktualizácia statusu knihy
     */
    public function update_status($book_id, $status) {
        $valid_statuses = array('available', 'reserved', 'borrowed');

        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        return BBK_Database::update('books', array('status' => $status), array('id' => $book_id));
    }

    /**
     * Kontrola oprávnenia upraviť knihu
     */
    private function can_edit_book($book_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $book = $this->get_book($book_id);

        if (!$book) {
            return false;
        }

        // Admin môže upraviť všetky knihy
        if (current_user_can('manage_bbk_library')) {
            return true;
        }

        // Vlastník môže upraviť svoju knihu
        return $book->user_id == $user_id;
    }

    /**
     * AJAX: Pridanie knihy
     */
    public function ajax_add_book() {
        check_ajax_referer('bbk_nonce', 'nonce');

        $result = $this->add_book($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Kniha bola úspešne pridaná!', 'buddyboss-kniznica'),
            'book_id' => $result
        ));
    }

    /**
     * AJAX: Úprava knihy
     */
    public function ajax_edit_book() {
        check_ajax_referer('bbk_nonce', 'nonce');

        $book_id = absint($_POST['book_id']);
        $result = $this->update_book($book_id, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne aktualizovaná!', 'buddyboss-kniznica')));
    }

    /**
     * AJAX: Zmazanie knihy
     */
    public function ajax_delete_book() {
        check_ajax_referer('bbk_nonce', 'nonce');

        $book_id = absint($_POST['book_id']);
        $result = $this->delete_book($book_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne zmazaná!', 'buddyboss-kniznica')));
    }

    /**
     * AJAX: Upload obrázka
     */
    public function ajax_upload_image() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('Nebol nahraný žiadny súbor.', 'buddyboss-kniznica')));
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('image', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        $image_url = wp_get_attachment_url($attachment_id);

        wp_send_json_success(array(
            'message' => __('Obrázok bol úspešne nahraný.', 'buddyboss-kniznica'),
            'image_url' => $image_url,
            'attachment_id' => $attachment_id
        ));
    }
}
