<?php
/**
 * Trieda pre prácu s výsledkami
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Result {

    public static function create($data) {
        global $wpdb;
        $table = MT_Database::get_table_results();

        $defaults = array(
            'test_id' => 0,
            'user_id' => get_current_user_id(),
            'user_name' => '',
            'user_email' => '',
            'score' => 0,
            'max_score' => 0,
            'percentage' => 0,
            'time_taken' => 0,
            'cheating_detected' => 0,
            'cheating_count' => 0,
            'started_at' => current_time('mysql'),
            'ip_address' => self::get_user_ip(),
            'user_agent' => self::get_user_agent()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            $table,
            array(
                'test_id' => intval($data['test_id']),
                'user_id' => intval($data['user_id']),
                'user_name' => sanitize_text_field($data['user_name']),
                'user_email' => sanitize_email($data['user_email']),
                'score' => intval($data['score']),
                'max_score' => intval($data['max_score']),
                'percentage' => floatval($data['percentage']),
                'time_taken' => intval($data['time_taken']),
                'cheating_detected' => intval($data['cheating_detected']),
                'cheating_count' => intval($data['cheating_count']),
                'started_at' => $data['started_at'],
                'ip_address' => sanitize_text_field($data['ip_address']),
                'user_agent' => sanitize_text_field($data['user_agent'])
            ),
            array('%d', '%d', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%d', '%s', '%s', '%s')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    public static function update($result_id, $data) {
        global $wpdb;
        $table = MT_Database::get_table_results();

        $update_data = array();
        $format = array();

        if (isset($data['score'])) {
            $update_data['score'] = intval($data['score']);
            $format[] = '%d';
        }

        if (isset($data['max_score'])) {
            $update_data['max_score'] = intval($data['max_score']);
            $format[] = '%d';
        }

        if (isset($data['percentage'])) {
            $update_data['percentage'] = floatval($data['percentage']);
            $format[] = '%f';
        }

        if (isset($data['time_taken'])) {
            $update_data['time_taken'] = intval($data['time_taken']);
            $format[] = '%d';
        }

        if (isset($data['cheating_detected'])) {
            $update_data['cheating_detected'] = intval($data['cheating_detected']);
            $format[] = '%d';
        }

        if (isset($data['cheating_count'])) {
            $update_data['cheating_count'] = intval($data['cheating_count']);
            $format[] = '%d';
        }

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update(
            $table,
            $update_data,
            array('id' => intval($result_id)),
            $format,
            array('%d')
        );
    }

    public static function get($result_id) {
        global $wpdb;
        $table = MT_Database::get_table_results();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            intval($result_id)
        ));
    }

    public static function get_by_test($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_results();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE test_id = %d ORDER BY completed_at DESC",
            intval($test_id)
        ));
    }

    public static function get_by_user($user_id, $test_id = null) {
        global $wpdb;
        $table = MT_Database::get_table_results();

        if ($test_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND test_id = %d ORDER BY completed_at DESC",
                intval($user_id),
                intval($test_id)
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY completed_at DESC",
            intval($user_id)
        ));
    }

    public static function save_answer($result_id, $question_id, $user_answer, $is_correct, $points_earned) {
        global $wpdb;
        $table = MT_Database::get_table_answers();

        return $wpdb->insert(
            $table,
            array(
                'result_id' => intval($result_id),
                'question_id' => intval($question_id),
                'user_answer' => sanitize_textarea_field($user_answer),
                'is_correct' => intval($is_correct),
                'points_earned' => intval($points_earned)
            ),
            array('%d', '%d', '%s', '%d', '%d')
        );
    }

    public static function get_answers($result_id) {
        global $wpdb;
        $table = MT_Database::get_table_answers();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE result_id = %d",
            intval($result_id)
        ));
    }

    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }

    private static function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
