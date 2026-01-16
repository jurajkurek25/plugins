<?php
/**
 * Trieda pre prácu s testami
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Test {

    public static function create($data) {
        global $wpdb;
        $table = MT_Database::get_table_tests();

        $defaults = array(
            'title' => '',
            'description' => '',
            'time_limit' => get_option('mt_default_time_limit', 60),
            'passing_score' => get_option('mt_default_passing_score', 50),
            'created_by' => get_current_user_id(),
            'status' => 'draft'
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            $table,
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => wp_kses_post($data['description']),
                'time_limit' => intval($data['time_limit']),
                'passing_score' => intval($data['passing_score']),
                'created_by' => intval($data['created_by']),
                'status' => sanitize_text_field($data['status'])
            ),
            array('%s', '%s', '%d', '%d', '%d', '%s')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    public static function update($test_id, $data) {
        global $wpdb;
        $table = MT_Database::get_table_tests();

        $update_data = array();
        $format = array();

        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
            $format[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = wp_kses_post($data['description']);
            $format[] = '%s';
        }

        if (isset($data['time_limit'])) {
            $update_data['time_limit'] = intval($data['time_limit']);
            $format[] = '%d';
        }

        if (isset($data['passing_score'])) {
            $update_data['passing_score'] = intval($data['passing_score']);
            $format[] = '%d';
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update(
            $table,
            $update_data,
            array('id' => intval($test_id)),
            $format,
            array('%d')
        );
    }

    public static function get($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_tests();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            intval($test_id)
        ));
    }

    public static function get_all($status = null) {
        global $wpdb;
        $table = MT_Database::get_table_tests();

        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC",
                $status
            ));
        }

        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
    }

    public static function delete($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_tests();

        // Najprv vymazať všetky súvisiace otázky
        MT_Question::delete_by_test($test_id);

        // Vymazať výsledky
        $results_table = MT_Database::get_table_results();
        $wpdb->delete($results_table, array('test_id' => intval($test_id)), array('%d'));

        // Vymazať test
        return $wpdb->delete($table, array('id' => intval($test_id)), array('%d'));
    }

    public static function get_questions($test_id) {
        return MT_Question::get_by_test($test_id);
    }

    public static function get_total_points($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points) FROM {$table} WHERE test_id = %d",
            intval($test_id)
        ));

        return $total ? intval($total) : 0;
    }
}
