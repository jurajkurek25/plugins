<?php
/**
 * Membership systém
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Membership {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        // Hooks pre membership management
        add_action('user_register', array($this, 'on_user_register'));
    }

    /**
     * Získanie členstva používateľa
     */
    public function get_user_membership($user_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        ));

        return $membership;
    }

    /**
     * Skontrolovať či používateľ má aktívne členstvo
     */
    public function has_active_membership($user_id) {
        $membership = $this->get_user_membership($user_id);
        return !empty($membership);
    }

    /**
     * Skontrolovať či používateľ má prístup k tier
     */
    public function user_has_tier_access($user_id, $tier_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND tier_id >= %d AND status = 'active'",
            $user_id,
            $tier_id
        ));

        return !empty($membership);
    }

    /**
     * Vytvorenie nového členstva
     */
    public function create_membership($user_id, $tier_id, $payment_method = 'stripe', $stripe_subscription_id = null) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        // Deaktivovať staré členstvá
        $wpdb->update(
            $table,
            array('status' => 'cancelled'),
            array('user_id' => $user_id, 'status' => 'active')
        );

        // Vytvoriť nové členstvo
        $next_billing = date('Y-m-d H:i:s', strtotime('+1 month'));

        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'tier_id' => $tier_id,
            'status' => 'active',
            'start_date' => current_time('mysql'),
            'next_billing_date' => $next_billing,
            'payment_method' => $payment_method,
            'stripe_subscription_id' => $stripe_subscription_id
        ));

        if ($result) {
            // Aktualizovať počet členov v tier
            $this->update_tier_member_count($tier_id);

            // Trigger action
            do_action('pa_membership_created', $wpdb->insert_id, $user_id, $tier_id);

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Zrušenie členstva
     */
    public function cancel_membership($membership_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        $result = $wpdb->update(
            $table,
            array(
                'status' => 'cancelled',
                'end_date' => current_time('mysql')
            ),
            array('id' => $membership_id)
        );

        if ($result) {
            $membership = $this->get_membership_by_id($membership_id);
            $this->update_tier_member_count($membership->tier_id);

            do_action('pa_membership_cancelled', $membership_id);
        }

        return $result;
    }

    /**
     * Získanie členstva podľa ID
     */
    public function get_membership_by_id($membership_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $membership_id
        ));
    }

    /**
     * Aktualizácia počtu členov v tier
     */
    private function update_tier_member_count($tier_id) {
        global $wpdb;
        $memberships_table = PA_Database::get_table_name('memberships');
        $tiers_table = PA_Database::get_table_name('tiers');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $memberships_table WHERE tier_id = %d AND status = 'active'",
            $tier_id
        ));

        $wpdb->update(
            $tiers_table,
            array('current_members' => $count),
            array('id' => $tier_id)
        );
    }

    /**
     * Pri registrácii používateľa
     */
    public function on_user_register($user_id) {
        // Môžeme pridať default free tier alebo iné akcie
        do_action('pa_user_registered', $user_id);
    }

    /**
     * Získanie všetkých členov
     */
    public function get_all_patrons() {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        return $wpdb->get_results(
            "SELECT m.*, u.display_name, u.user_email
            FROM $table m
            INNER JOIN {$wpdb->users} u ON m.user_id = u.ID
            WHERE m.status = 'active'
            ORDER BY m.created_at DESC"
        );
    }

    /**
     * Získanie štatistík
     */
    public function get_statistics() {
        global $wpdb;
        $memberships_table = PA_Database::get_table_name('memberships');
        $payments_table = PA_Database::get_table_name('payments');

        $stats = array();

        // Počet aktívnych patrónovnov
        $stats['active_patrons'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM $memberships_table WHERE status = 'active'"
        );

        // Mesačné príjmy
        $stats['monthly_revenue'] = $wpdb->get_var(
            "SELECT SUM(amount) FROM $payments_table
            WHERE status = 'completed'
            AND MONTH(payment_date) = MONTH(CURRENT_DATE())
            AND YEAR(payment_date) = YEAR(CURRENT_DATE())"
        );

        // Celkové príjmy
        $stats['total_revenue'] = $wpdb->get_var(
            "SELECT SUM(amount) FROM $payments_table WHERE status = 'completed'"
        );

        return $stats;
    }
}
