<?php
/**
 * Trieda pre správu notifikácií
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Notifications {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_kk_mark_notification_read', array($this, 'ajax_mark_read'));
        add_action('wp_ajax_kk_mark_all_read', array($this, 'ajax_mark_all_read'));
        add_action('wp_ajax_kk_get_notifications', array($this, 'ajax_get_notifications'));
    }

    /**
     * Vytvorenie notifikácie
     */
    public function create_notification($user_id, $type, $title, $message, $link = null) {
        $notification_data = array(
            'user_id' => $user_id,
            'type' => sanitize_text_field($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_textarea_field($message),
            'link' => $link ? esc_url_raw($link) : null,
            'is_read' => 0
        );

        return KK_Database::insert('notifications', $notification_data);
    }

    /**
     * Označenie notifikácie ako prečítanej
     */
    public function mark_as_read($notification_id) {
        return KK_Database::update(
            'notifications',
            array('is_read' => 1),
            array('id' => $notification_id)
        );
    }

    /**
     * Označenie všetkých notifikácií používateľa ako prečítané
     */
    public function mark_all_as_read($user_id) {
        return KK_Database::update(
            'notifications',
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0)
        );
    }

    /**
     * Získanie notifikácií používateľa
     */
    public function get_user_notifications($user_id, $limit = 20, $unread_only = false) {
        $where = array('user_id' => $user_id);

        if ($unread_only) {
            $where['is_read'] = 0;
        }

        return KK_Database::get_results('notifications', $where, OBJECT, 'created_at DESC', $limit);
    }

    /**
     * Získanie počtu neprečítaných notifikácií
     */
    public function get_unread_count($user_id) {
        return KK_Database::get_count('notifications', array(
            'user_id' => $user_id,
            'is_read' => 0
        ));
    }

    /**
     * Zmazanie starých notifikácií (staršie ako 30 dní)
     */
    public function delete_old_notifications() {
        global $wpdb;
        $table = KK_Database::get_table_name('notifications');
        $date = date('Y-m-d H:i:s', strtotime('-30 days'));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s AND is_read = 1",
            $date
        ));
    }

    /**
     * AJAX: Označenie notifikácie ako prečítanej
     */
    public function ajax_mark_read() {
        check_ajax_referer('kk_notifications_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $notification_id = absint($_POST['notification_id']);
        $result = $this->mark_as_read($notification_id);

        if ($result !== false) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * AJAX: Označenie všetkých notifikácií ako prečítané
     */
    public function ajax_mark_all_read() {
        check_ajax_referer('kk_notifications_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $user_id = get_current_user_id();
        $result = $this->mark_all_as_read($user_id);

        if ($result !== false) {
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * AJAX: Získanie notifikácií
     */
    public function ajax_get_notifications() {
        check_ajax_referer('kk_notifications_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $user_id = get_current_user_id();
        $notifications = $this->get_user_notifications($user_id);
        $unread_count = $this->get_unread_count($user_id);

        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ));
    }
}
