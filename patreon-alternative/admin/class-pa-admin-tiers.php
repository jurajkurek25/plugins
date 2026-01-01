<?php
/**
 * Admin Tiers Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Admin_Tiers {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_pa_save_tier', array($this, 'save_tier'));
        add_action('wp_ajax_pa_delete_tier', array($this, 'delete_tier'));
        add_action('wp_ajax_pa_get_tier', array($this, 'get_tier'));
    }

    /**
     * Uloženie tier (AJAX)
     */
    public function save_tier() {
        check_ajax_referer('pa_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'patreon-alt')));
        }

        $tier_id = isset($_POST['tier_id']) ? intval($_POST['tier_id']) : 0;
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'price' => floatval($_POST['price']),
            'currency' => sanitize_text_field($_POST['currency']),
            'benefits' => isset($_POST['benefits']) ? array_map('sanitize_text_field', $_POST['benefits']) : array(),
            'max_members' => !empty($_POST['max_members']) ? intval($_POST['max_members']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => intval($_POST['sort_order'])
        );

        $tiers = PA_Tiers::get_instance();

        if ($tier_id) {
            // Update
            $result = $tiers->update_tier($tier_id, $data);
        } else {
            // Create
            $result = $tiers->create_tier($data);
        }

        if ($result) {
            wp_send_json_success(array('message' => __('Tier saved successfully', 'patreon-alt')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save tier', 'patreon-alt')));
        }
    }

    /**
     * Vymazanie tier (AJAX)
     */
    public function delete_tier() {
        check_ajax_referer('pa_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'patreon-alt')));
        }

        $tier_id = isset($_POST['tier_id']) ? intval($_POST['tier_id']) : 0;

        if (!$tier_id) {
            wp_send_json_error(array('message' => __('Invalid tier ID', 'patreon-alt')));
        }

        $result = PA_Tiers::get_instance()->delete_tier($tier_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        if ($result) {
            wp_send_json_success(array('message' => __('Tier deleted successfully', 'patreon-alt')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete tier', 'patreon-alt')));
        }
    }

    /**
     * Získanie tier (AJAX)
     */
    public function get_tier() {
        check_ajax_referer('pa_admin', 'nonce');

        $tier_id = isset($_POST['tier_id']) ? intval($_POST['tier_id']) : 0;

        if (!$tier_id) {
            wp_send_json_error(array('message' => __('Invalid tier ID', 'patreon-alt')));
        }

        $tier = PA_Tiers::get_instance()->get_tier($tier_id);

        if ($tier) {
            wp_send_json_success(array('tier' => $tier));
        } else {
            wp_send_json_error(array('message' => __('Tier not found', 'patreon-alt')));
        }
    }
}
