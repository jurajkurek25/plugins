<?php
/**
 * Trieda pre prácu s otázkami
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Question {

    public static function create($data) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $defaults = array(
            'test_id' => 0,
            'question_text' => '',
            'question_type' => 'multiple_choice',
            'image_url' => '',
            'points' => 1,
            'correct_answer' => '',
            'order_number' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(
            $table,
            array(
                'test_id' => intval($data['test_id']),
                'question_text' => wp_kses_post($data['question_text']),
                'question_type' => sanitize_text_field($data['question_type']),
                'image_url' => esc_url_raw($data['image_url']),
                'points' => intval($data['points']),
                'correct_answer' => sanitize_textarea_field($data['correct_answer']),
                'order_number' => intval($data['order_number'])
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%d')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    public static function update($question_id, $data) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $update_data = array();
        $format = array();

        if (isset($data['question_text'])) {
            $update_data['question_text'] = wp_kses_post($data['question_text']);
            $format[] = '%s';
        }

        if (isset($data['question_type'])) {
            $update_data['question_type'] = sanitize_text_field($data['question_type']);
            $format[] = '%s';
        }

        if (isset($data['image_url'])) {
            $update_data['image_url'] = esc_url_raw($data['image_url']);
            $format[] = '%s';
        }

        if (isset($data['points'])) {
            $update_data['points'] = intval($data['points']);
            $format[] = '%d';
        }

        if (isset($data['correct_answer'])) {
            $update_data['correct_answer'] = sanitize_textarea_field($data['correct_answer']);
            $format[] = '%s';
        }

        if (isset($data['order_number'])) {
            $update_data['order_number'] = intval($data['order_number']);
            $format[] = '%d';
        }

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update(
            $table,
            $update_data,
            array('id' => intval($question_id)),
            $format,
            array('%d')
        );
    }

    public static function get($question_id) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $question = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            intval($question_id)
        ));

        if ($question && $question->question_type === 'multiple_choice') {
            $question->options = self::get_options($question_id);
        }

        return $question;
    }

    public static function get_by_test($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE test_id = %d ORDER BY order_number ASC, id ASC",
            intval($test_id)
        ));

        foreach ($questions as $question) {
            if ($question->question_type === 'multiple_choice') {
                $question->options = self::get_options($question->id);
            }
        }

        return $questions;
    }

    public static function delete($question_id) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        // Vymazať možnosti
        self::delete_options($question_id);

        // Vymazať odpovede
        $answers_table = MT_Database::get_table_answers();
        $wpdb->delete($answers_table, array('question_id' => intval($question_id)), array('%d'));

        // Vymazať otázku
        return $wpdb->delete($table, array('id' => intval($question_id)), array('%d'));
    }

    public static function delete_by_test($test_id) {
        global $wpdb;
        $table = MT_Database::get_table_questions();

        $questions = self::get_by_test($test_id);
        foreach ($questions as $question) {
            self::delete($question->id);
        }
    }

    // Metódy pre možnosti (multiple choice)
    public static function add_option($question_id, $option_text, $is_correct = 0, $order_number = 0) {
        global $wpdb;
        $table = MT_Database::get_table_options();

        return $wpdb->insert(
            $table,
            array(
                'question_id' => intval($question_id),
                'option_text' => sanitize_text_field($option_text),
                'is_correct' => intval($is_correct),
                'order_number' => intval($order_number)
            ),
            array('%d', '%s', '%d', '%d')
        );
    }

    public static function update_option($option_id, $option_text, $is_correct) {
        global $wpdb;
        $table = MT_Database::get_table_options();

        return $wpdb->update(
            $table,
            array(
                'option_text' => sanitize_text_field($option_text),
                'is_correct' => intval($is_correct)
            ),
            array('id' => intval($option_id)),
            array('%s', '%d'),
            array('%d')
        );
    }

    public static function get_options($question_id) {
        global $wpdb;
        $table = MT_Database::get_table_options();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE question_id = %d ORDER BY order_number ASC, id ASC",
            intval($question_id)
        ));
    }

    public static function delete_options($question_id) {
        global $wpdb;
        $table = MT_Database::get_table_options();

        return $wpdb->delete($table, array('question_id' => intval($question_id)), array('%d'));
    }

    public static function delete_option($option_id) {
        global $wpdb;
        $table = MT_Database::get_table_options();

        return $wpdb->delete($table, array('id' => intval($option_id)), array('%d'));
    }
}
