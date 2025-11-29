<?php
/**
 * Trieda pre správu kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Book {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_kk_add_book', array($this, 'ajax_add_book'));
        add_action('wp_ajax_kk_edit_book', array($this, 'ajax_edit_book'));
        add_action('wp_ajax_kk_delete_book', array($this, 'ajax_delete_book'));
        add_action('wp_ajax_kk_upload_book_image', array($this, 'ajax_upload_book_image'));
    }

    /**
     * Pridanie knihy
     */
    public function add_book($data) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'komunitna-kniznica'));
        }

        $user_id = get_current_user_id();

        $book_data = array(
            'user_id' => $user_id,
            'title' => sanitize_text_field($data['title']),
            'author' => sanitize_text_field($data['author']),
            'isbn' => !empty($data['isbn']) ? sanitize_text_field($data['isbn']) : null,
            'genre' => sanitize_text_field($data['genre']),
            'description' => sanitize_textarea_field($data['description']),
            'book_condition' => absint($data['book_condition']),
            'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'lending_price' => floatval($data['lending_price']),
            'status' => 'available'
        );

        $book_id = KK_Database::insert('books', $book_data);

        if ($book_id) {
            return $book_id;
        }

        return new WP_Error('insert_failed', __('Nepodarilo sa pridať knihu.', 'komunitna-kniznica'));
    }

    /**
     * Aktualizácia knihy
     */
    public function update_book($book_id, $data) {
        if (!$this->can_edit_book($book_id)) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie upraviť túto knihu.', 'komunitna-kniznica'));
        }

        $book_data = array(
            'title' => sanitize_text_field($data['title']),
            'author' => sanitize_text_field($data['author']),
            'isbn' => !empty($data['isbn']) ? sanitize_text_field($data['isbn']) : null,
            'genre' => sanitize_text_field($data['genre']),
            'description' => sanitize_textarea_field($data['description']),
            'book_condition' => absint($data['book_condition']),
            'image_url' => !empty($data['image_url']) ? esc_url_raw($data['image_url']) : null,
            'lending_price' => floatval($data['lending_price'])
        );

        $result = KK_Database::update('books', $book_data, array('id' => $book_id));

        if ($result !== false) {
            return true;
        }

        return new WP_Error('update_failed', __('Nepodarilo sa aktualizovať knihu.', 'komunitna-kniznica'));
    }

    /**
     * Zmazanie knihy
     */
    public function delete_book($book_id) {
        if (!$this->can_edit_book($book_id)) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie zmazať túto knihu.', 'komunitna-kniznica'));
        }

        // Kontrola aktívnych požičaní
        $active_lendings = KK_Database::get_count('lendings', array(
            'book_id' => $book_id,
            'status' => 'active'
        ));

        if ($active_lendings > 0) {
            return new WP_Error('has_active_lendings', __('Knihu nie je možné zmazať, pretože má aktívne požičania.', 'komunitna-kniznica'));
        }

        $result = KK_Database::delete('books', array('id' => $book_id));

        if ($result) {
            return true;
        }

        return new WP_Error('delete_failed', __('Nepodarilo sa zmazať knihu.', 'komunitna-kniznica'));
    }

    /**
     * Získanie knihy
     */
    public function get_book($book_id) {
        return KK_Database::get_row('books', array('id' => $book_id));
    }

    /**
     * Získanie kníh používateľa
     */
    public function get_user_books($user_id, $limit = null) {
        return KK_Database::get_results('books', array('user_id' => $user_id), OBJECT, 'created_at DESC', $limit);
    }

    /**
     * Získanie všetkých dostupných kníh
     */
    public function get_available_books($args = array()) {
        global $wpdb;
        $table = KK_Database::get_table_name('books');

        $where = "status = 'available'";

        // Filter podľa žánru
        if (!empty($args['genre'])) {
            $genre = esc_sql($args['genre']);
            $where .= " AND genre = '{$genre}'";
        }

        // Vyhľadávanie
        if (!empty($args['search'])) {
            $search = esc_sql($args['search']);
            $where .= " AND (title LIKE '%{$search}%' OR author LIKE '%{$search}%' OR genre LIKE '%{$search}%')";
        }

        // Exclude používateľove vlastné knihy
        if (!empty($args['exclude_user'])) {
            $user_id = absint($args['exclude_user']);
            $where .= " AND user_id != {$user_id}";
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
        $table = KK_Database::get_table_name('books');

        $query = "SELECT DISTINCT genre FROM {$table} ORDER BY genre ASC";
        $results = $wpdb->get_results($query);

        $genres = array();
        foreach ($results as $row) {
            $genres[] = $row->genre;
        }

        return $genres;
    }

    /**
     * Aktualizácia statusu knihy
     */
    public function update_status($book_id, $status) {
        $valid_statuses = array('available', 'reserved', 'borrowed');

        if (!in_array($status, $valid_statuses)) {
            return false;
        }

        return KK_Database::update('books', array('status' => $status), array('id' => $book_id));
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
        if (current_user_can('manage_kk_library')) {
            return true;
        }

        // Vlastník môže upraviť svoju knihu
        return $book->user_id == $user_id;
    }

    /**
     * AJAX: Pridanie knihy
     */
    public function ajax_add_book() {
        check_ajax_referer('kk_add_book_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $result = $this->add_book($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Kniha bola úspešne pridaná.', 'komunitna-kniznica'),
            'book_id' => $result
        ));
    }

    /**
     * AJAX: Úprava knihy
     */
    public function ajax_edit_book() {
        check_ajax_referer('kk_edit_book_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $result = $this->update_book($book_id, $_POST);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne aktualizovaná.', 'komunitna-kniznica')));
    }

    /**
     * AJAX: Zmazanie knihy
     */
    public function ajax_delete_book() {
        check_ajax_referer('kk_delete_book_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $result = $this->delete_book($book_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne zmazaná.', 'komunitna-kniznica')));
    }

    /**
     * AJAX: Upload obrázka knihy
     */
    public function ajax_upload_book_image() {
        check_ajax_referer('kk_upload_image_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        if (empty($_FILES['image'])) {
            wp_send_json_error(array('message' => __('Nebol nahraný žiadny súbor.', 'komunitna-kniznica')));
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
            'message' => __('Obrázok bol úspešne nahraný.', 'komunitna-kniznica'),
            'image_url' => $image_url,
            'attachment_id' => $attachment_id
        ));
    }
}
