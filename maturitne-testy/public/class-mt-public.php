<?php
/**
 * Public trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Public {

    public function __construct() {
        add_action('wp_ajax_mt_submit_test', array($this, 'submit_test'));
        add_action('wp_ajax_nopriv_mt_submit_test', array($this, 'submit_test'));
        add_action('wp_ajax_mt_record_cheating', array($this, 'record_cheating'));
        add_action('wp_ajax_nopriv_mt_record_cheating', array($this, 'record_cheating'));
    }

    public function submit_test() {
        check_ajax_referer('mt_ajax_nonce', 'nonce');

        $test_id = intval($_POST['test_id']);
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        $time_taken = isset($_POST['time_taken']) ? intval($_POST['time_taken']) : 0;
        $cheating_count = isset($_POST['cheating_count']) ? intval($_POST['cheating_count']) : 0;

        // Získanie informácií o používateľovi
        $user_id = get_current_user_id();
        $user_name = '';
        $user_email = '';

        if ($user_id > 0) {
            $user = wp_get_current_user();
            $user_name = $user->display_name;
            $user_email = $user->user_email;
        } else {
            $user_name = sanitize_text_field($_POST['user_name'] ?? '');
            $user_email = sanitize_email($_POST['user_email'] ?? '');
        }

        // Získanie otázok
        $questions = MT_Question::get_by_test($test_id);
        $total_points = 0;
        $earned_points = 0;

        // Vytvorenie záznamu výsledku
        $result_data = array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'cheating_detected' => $cheating_count > 0 ? 1 : 0,
            'cheating_count' => $cheating_count,
            'time_taken' => $time_taken,
            'started_at' => isset($_POST['started_at']) ? sanitize_text_field($_POST['started_at']) : current_time('mysql')
        );

        $result_id = MT_Result::create($result_data);

        if (!$result_id) {
            wp_send_json_error(array('message' => __('Chyba pri ukladaní výsledku.', 'maturitne-testy')));
        }

        // Vyhodnotenie odpovedí
        $results = array();

        foreach ($questions as $question) {
            $total_points += $question->points;
            $user_answer = isset($answers[$question->id]) ? $answers[$question->id] : '';
            $is_correct = 0;
            $points = 0;

            if ($question->question_type === 'multiple_choice') {
                $user_answer_id = intval($user_answer);
                $correct_option = null;

                foreach ($question->options as $option) {
                    if ($option->is_correct) {
                        $correct_option = $option;
                        break;
                    }
                }

                if ($correct_option && $user_answer_id == $correct_option->id) {
                    $is_correct = 1;
                    $points = $question->points;
                    $earned_points += $points;
                }

                $user_answer_text = '';
                foreach ($question->options as $option) {
                    if ($option->id == $user_answer_id) {
                        $user_answer_text = $option->option_text;
                        break;
                    }
                }

                MT_Result::save_answer($result_id, $question->id, $user_answer_text, $is_correct, $points);

                $results[] = array(
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'user_answer' => $user_answer_text,
                    'correct_answer' => $correct_option ? $correct_option->option_text : '',
                    'is_correct' => $is_correct,
                    'points' => $points,
                    'max_points' => $question->points
                );

            } else {
                $correct_answer = trim(strtolower($question->correct_answer));
                $user_answer_normalized = trim(strtolower($user_answer));

                if ($user_answer_normalized === $correct_answer) {
                    $is_correct = 1;
                    $points = $question->points;
                    $earned_points += $points;
                }

                MT_Result::save_answer($result_id, $question->id, $user_answer, $is_correct, $points);

                $results[] = array(
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'user_answer' => $user_answer,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $is_correct,
                    'points' => $points,
                    'max_points' => $question->points
                );
            }
        }

        // Aktualizácia výsledku
        $percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;

        MT_Result::update($result_id, array(
            'score' => $earned_points,
            'max_score' => $total_points,
            'percentage' => $percentage
        ));

        // Získanie testu pre passing score
        $test = MT_Test::get($test_id);
        $passed = $percentage >= $test->passing_score;

        wp_send_json_success(array(
            'result_id' => $result_id,
            'score' => $earned_points,
            'max_score' => $total_points,
            'percentage' => round($percentage, 2),
            'passed' => $passed,
            'passing_score' => $test->passing_score,
            'cheating_detected' => $cheating_count > 0,
            'results' => $results
        ));
    }

    public function record_cheating() {
        // Tento endpoint môže byť volaný aj bez prihláseného používateľa
        // Len zaznamená pokus o podvádzanie do session
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['mt_cheating_count'])) {
            $_SESSION['mt_cheating_count'] = 0;
        }

        $_SESSION['mt_cheating_count']++;

        wp_send_json_success(array(
            'count' => $_SESSION['mt_cheating_count']
        ));
    }
}
