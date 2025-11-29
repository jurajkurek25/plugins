<?php
/**
 * Trieda pre správu notifikácií
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Notifications {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_bbk_mark_notification_read', array($this, 'ajax_mark_as_read'));
        add_action('wp_ajax_bbk_get_notifications', array($this, 'ajax_get_notifications'));
    }

    /**
     * Vytvorenie notifikácie
     */
    public function create_notification($user_id, $type, $title, $message, $link = '') {
        $notification_data = array(
            'user_id' => $user_id,
            'type' => sanitize_text_field($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_textarea_field($message),
            'link' => !empty($link) ? esc_url_raw($link) : null,
            'is_read' => 0
        );

        return BBK_Database::insert('notifications', $notification_data);
    }

    /**
     * Označenie notifikácie ako prečítanej
     */
    public function mark_as_read($notification_id) {
        return BBK_Database::update('notifications', array('is_read' => 1), array('id' => $notification_id));
    }

    /**
     * Označenie všetkých notifikácií ako prečítaných
     */
    public function mark_all_as_read($user_id) {
        return BBK_Database::update('notifications', array('is_read' => 1), array('user_id' => $user_id));
    }

    /**
     * Získanie notifikácií používateľa
     */
    public function get_user_notifications($user_id, $unread_only = false, $limit = null) {
        $where = array('user_id' => $user_id);

        if ($unread_only) {
            $where['is_read'] = 0;
        }

        return BBK_Database::get_results('notifications', $where, OBJECT, 'created_at DESC', $limit);
    }

    /**
     * Získanie počtu neprečítaných notifikácií
     */
    public function get_unread_count($user_id) {
        return BBK_Database::get_count('notifications', array(
            'user_id' => $user_id,
            'is_read' => 0
        ));
    }

    /**
     * AJAX: Označenie ako prečítané
     */
    public function ajax_mark_as_read() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        $notification_id = absint($_POST['notification_id']);
        $this->mark_as_read($notification_id);

        wp_send_json_success();
    }

    /**
     * AJAX: Získanie notifikácií
     */
    public function ajax_get_notifications() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        $user_id = get_current_user_id();
        $notifications = $this->get_user_notifications($user_id, false, 10);

        wp_send_json_success(array('notifications' => $notifications));
    }
}
