<?php
/**
 * Membership Tiers systém
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Tiers {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks
    }

    /**
     * Získanie všetkých tier-ov
     */
    public function get_all_tiers($active_only = true) {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        $where = $active_only ? "WHERE is_active = 1" : "";

        return $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY sort_order ASC, price ASC"
        );
    }

    /**
     * Získanie tier podľa ID
     */
    public function get_tier($tier_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $tier_id
        ));
    }

    /**
     * Vytvorenie nového tier
     */
    public function create_tier($data) {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        $defaults = array(
            'name' => '',
            'description' => '',
            'price' => 0.00,
            'currency' => 'EUR',
            'benefits' => '',
            'max_members' => null,
            'is_active' => 1,
            'sort_order' => 0
        );

        $data = wp_parse_args($data, $defaults);

        // Ak sú benefits array, konvertovať na JSON
        if (is_array($data['benefits'])) {
            $data['benefits'] = json_encode($data['benefits']);
        }

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Aktualizácia tier
     */
    public function update_tier($tier_id, $data) {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        // Ak sú benefits array, konvertovať na JSON
        if (isset($data['benefits']) && is_array($data['benefits'])) {
            $data['benefits'] = json_encode($data['benefits']);
        }

        return $wpdb->update($table, $data, array('id' => $tier_id));
    }

    /**
     * Vymazanie tier
     */
    public function delete_tier($tier_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        // Skontrolovať či tier nemá aktívnych členov
        $memberships_table = PA_Database::get_table_name('memberships');
        $has_members = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $memberships_table WHERE tier_id = %d AND status = 'active'",
            $tier_id
        ));

        if ($has_members > 0) {
            return new WP_Error('has_members', __('Cannot delete tier with active members', 'patreon-alt'));
        }

        return $wpdb->delete($table, array('id' => $tier_id));
    }

    /**
     * Získanie benefits pre tier
     */
    public function get_tier_benefits($tier_id) {
        $tier = $this->get_tier($tier_id);

        if (!$tier) {
            return array();
        }

        $benefits = json_decode($tier->benefits, true);

        return is_array($benefits) ? $benefits : array();
    }

    /**
     * Formátovanie ceny
     */
    public function format_price($price, $currency = 'EUR') {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CZK' => 'Kč'
        );

        $symbol = isset($symbols[$currency]) ? $symbols[$currency] : $currency;

        return $symbol . number_format($price, 2);
    }

    /**
     * Skontrolovať či je tier plný
     */
    public function is_tier_full($tier_id) {
        $tier = $this->get_tier($tier_id);

        if (!$tier || !$tier->max_members) {
            return false;
        }

        return $tier->current_members >= $tier->max_members;
    }
}
