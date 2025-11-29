<?php
/**
 * BuddyBoss integrácia - kontrola členstva a prístupových práv
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_BuddyBoss {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // BuddyBoss hooks
        add_action('bp_setup_nav', array($this, 'setup_nav'), 100);
        add_filter('bp_settings_admin_nav', array($this, 'setup_settings_nav'), 100);
    }

    /**
     * Kontrola či je používateľ aktívny člen komunity
     */
    public function is_active_member($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Kontrola či má používateľ aktívne členstvo
        // Môžeš tu pridať vlastné podmienky pre členstvo v komunite

        // Základná kontrola - musí byť registrovaný používateľ
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Kontrola BuddyBoss členstva
        if (function_exists('bp_is_active') && bp_is_active('xprofile')) {
            // Kontrola či má používateľ vyplnený profil
            $profile_data = xprofile_get_field_data(1, $user_id); // ID 1 je zvyčajne Name field
            if (empty($profile_data)) {
                return false;
            }
        }

        // Môžeš pridať vlastné podmienky, napr.:
        // - Kontrola aktívneho predplatného
        // - Kontrola membership skupiny
        // - Kontrola meta fields

        return true;
    }

    /**
     * Získanie BuddyBoss profilu používateľa
     */
    public function get_member_profile_url($user_id) {
        if (function_exists('bp_core_get_user_domain')) {
            return bp_core_get_user_domain($user_id);
        }
        return get_author_posts_url($user_id);
    }

    /**
     * Získanie avatara člena
     */
    public function get_member_avatar($user_id, $args = array()) {
        if (function_exists('bp_core_fetch_avatar')) {
            $defaults = array(
                'item_id' => $user_id,
                'type' => 'full',
                'width' => 150,
                'height' => 150,
            );
            $args = wp_parse_args($args, $defaults);
            return bp_core_fetch_avatar($args);
        }
        return get_avatar($user_id, 150);
    }

    /**
     * Pridanie navigácie do BuddyBoss profilu
     */
    public function setup_nav() {
        if (!bp_is_active('activity')) {
            return;
        }

        $user_id = bp_displayed_user_id();

        bp_core_new_nav_item(array(
            'name' => __('Moja Knižnica', 'buddyboss-kniznica'),
            'slug' => 'kniznica',
            'position' => 80,
            'screen_function' => array($this, 'screen_kniznica'),
            'default_subnav_slug' => 'dashboard',
            'show_for_displayed_user' => true,
        ));

        bp_core_new_subnav_item(array(
            'name' => __('Dashboard', 'buddyboss-kniznica'),
            'slug' => 'dashboard',
            'parent_url' => bp_core_get_user_domain($user_id) . 'kniznica/',
            'parent_slug' => 'kniznica',
            'screen_function' => array($this, 'screen_dashboard'),
            'position' => 10,
        ));

        bp_core_new_subnav_item(array(
            'name' => __('Moje knihy', 'buddyboss-kniznica'),
            'slug' => 'moje-knihy',
            'parent_url' => bp_core_get_user_domain($user_id) . 'kniznica/',
            'parent_slug' => 'kniznica',
            'screen_function' => array($this, 'screen_my_books'),
            'position' => 20,
        ));

        bp_core_new_subnav_item(array(
            'name' => __('Pridať knihu', 'buddyboss-kniznica'),
            'slug' => 'pridat-knihu',
            'parent_url' => bp_core_get_user_domain($user_id) . 'kniznica/',
            'parent_slug' => 'kniznica',
            'screen_function' => array($this, 'screen_add_book'),
            'position' => 30,
        ));
    }

    /**
     * Screen funkcie pre BuddyBoss navigáciu
     */
    public function screen_kniznica() {
        add_action('bp_template_content', array($this, 'screen_dashboard_content'));
        bp_core_load_template('buddypress/members/single/plugins');
    }

    public function screen_dashboard() {
        add_action('bp_template_content', array($this, 'screen_dashboard_content'));
        bp_core_load_template('buddypress/members/single/plugins');
    }

    public function screen_my_books() {
        add_action('bp_template_content', array($this, 'screen_my_books_content'));
        bp_core_load_template('buddypress/members/single/plugins');
    }

    public function screen_add_book() {
        add_action('bp_template_content', array($this, 'screen_add_book_content'));
        bp_core_load_template('buddypress/members/single/plugins');
    }

    /**
     * Content funkcie
     */
    public function screen_dashboard_content() {
        echo do_shortcode('[bbk_dashboard]');
    }

    public function screen_my_books_content() {
        echo do_shortcode('[bbk_my_books]');
    }

    public function screen_add_book_content() {
        echo do_shortcode('[bbk_add_book]');
    }

    /**
     * Odoslanie BuddyBoss notifikácie
     */
    public function send_notification($user_id, $title, $message, $link = '') {
        if (function_exists('bp_notifications_add_notification')) {
            bp_notifications_add_notification(array(
                'user_id' => $user_id,
                'item_id' => 0,
                'secondary_item_id' => 0,
                'component_name' => 'buddyboss_kniznica',
                'component_action' => 'bbk_notification',
                'date_notified' => bp_core_current_time(),
                'is_new' => 1,
            ));
        }
    }

    /**
     * Získanie členov komunity
     */
    public function get_community_members($args = array()) {
        if (function_exists('bp_core_get_users')) {
            $defaults = array(
                'type' => 'active',
                'per_page' => 20,
            );
            $args = wp_parse_args($args, $defaults);
            return bp_core_get_users($args);
        }

        // Fallback na WordPress users
        return get_users(array(
            'number' => 20,
            'orderby' => 'registered',
            'order' => 'DESC',
        ));
    }
}
