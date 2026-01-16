<?php
/**
 * Inštalačná trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Install {

    public static function activate() {
        // Vytvorenie databázových tabuliek
        MT_Database::create_tables();

        // Nastavenie verzie pluginu
        update_option('mt_version', MT_VERSION);
        update_option('mt_install_date', current_time('mysql'));

        // Nastavenie základných opcií
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    private static function set_default_options() {
        $defaults = array(
            'mt_default_time_limit' => 60,
            'mt_default_passing_score' => 50,
            'mt_enable_cheating_detection' => 1,
            'mt_allow_retakes' => 1,
            'mt_show_correct_answers' => 1
        );

        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}
