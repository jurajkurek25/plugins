<?php
/**
 * Databázová trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Database {

    public static function get_table_tests() {
        global $wpdb;
        return $wpdb->prefix . 'mt_tests';
    }

    public static function get_table_questions() {
        global $wpdb;
        return $wpdb->prefix . 'mt_questions';
    }

    public static function get_table_options() {
        global $wpdb;
        return $wpdb->prefix . 'mt_options';
    }

    public static function get_table_results() {
        global $wpdb;
        return $wpdb->prefix . 'mt_results';
    }

    public static function get_table_answers() {
        global $wpdb;
        return $wpdb->prefix . 'mt_answers';
    }

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabuľka testov
        $table_tests = self::get_table_tests();
        $sql_tests = "CREATE TABLE {$table_tests} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            time_limit int(11) DEFAULT 0,
            passing_score int(11) DEFAULT 50,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'draft',
            PRIMARY KEY (id)
        ) {$charset_collate};";

        // Tabuľka otázok
        $table_questions = self::get_table_questions();
        $sql_questions = "CREATE TABLE {$table_questions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL,
            question_text text NOT NULL,
            question_type varchar(20) NOT NULL,
            image_url varchar(500),
            points int(11) DEFAULT 1,
            correct_answer text,
            order_number int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) {$charset_collate};";

        // Tabuľka možností (pre multiple choice)
        $table_options = self::get_table_options();
        $sql_options = "CREATE TABLE {$table_options} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            question_id bigint(20) NOT NULL,
            option_text text NOT NULL,
            is_correct tinyint(1) DEFAULT 0,
            order_number int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) {$charset_collate};";

        // Tabuľka výsledkov
        $table_results = self::get_table_results();
        $sql_results = "CREATE TABLE {$table_results} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            user_name varchar(255),
            user_email varchar(255),
            score int(11) DEFAULT 0,
            max_score int(11) DEFAULT 0,
            percentage decimal(5,2) DEFAULT 0,
            time_taken int(11) DEFAULT 0,
            cheating_detected tinyint(1) DEFAULT 0,
            cheating_count int(11) DEFAULT 0,
            started_at datetime,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(50),
            user_agent text,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        // Tabuľka odpovedí
        $table_answers = self::get_table_answers();
        $sql_answers = "CREATE TABLE {$table_answers} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            result_id bigint(20) NOT NULL,
            question_id bigint(20) NOT NULL,
            user_answer text,
            is_correct tinyint(1) DEFAULT 0,
            points_earned int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY result_id (result_id),
            KEY question_id (question_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tests);
        dbDelta($sql_questions);
        dbDelta($sql_options);
        dbDelta($sql_results);
        dbDelta($sql_answers);
    }

    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            self::get_table_answers(),
            self::get_table_results(),
            self::get_table_options(),
            self::get_table_questions(),
            self::get_table_tests()
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}
