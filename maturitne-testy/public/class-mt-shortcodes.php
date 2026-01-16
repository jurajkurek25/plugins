<?php
/**
 * Shortcodes trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Shortcodes {

    public function __construct() {
        add_shortcode('maturitny_test', array($this, 'test_shortcode'));
    }

    public function test_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        $test_id = intval($atts['id']);

        if (!$test_id) {
            return '<p>' . __('Neplatné ID testu.', 'maturitne-testy') . '</p>';
        }

        $test = MT_Test::get($test_id);

        if (!$test) {
            return '<p>' . __('Test nenájdený.', 'maturitne-testy') . '</p>';
        }

        if ($test->status !== 'published') {
            return '<p>' . __('Tento test ešte nie je publikovaný.', 'maturitne-testy') . '</p>';
        }

        $questions = MT_Test::get_questions($test_id);

        if (empty($questions)) {
            return '<p>' . __('Tento test nemá žiadne otázky.', 'maturitne-testy') . '</p>';
        }

        // Inicializácia session pre sledovanie podvádzania
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['mt_cheating_count'] = 0;

        ob_start();
        $this->render_test($test, $questions);
        return ob_get_clean();
    }

    private function render_test($test, $questions) {
        ?>
        <div class="mt-test-container" data-test-id="<?php echo esc_attr($test->id); ?>" data-time-limit="<?php echo esc_attr($test->time_limit); ?>">
            <div class="mt-test-header">
                <h2><?php echo esc_html($test->title); ?></h2>
                <?php if ($test->description): ?>
                    <div class="mt-test-description">
                        <?php echo wp_kses_post($test->description); ?>
                    </div>
                <?php endif; ?>

                <div class="mt-test-info">
                    <p>
                        <strong><?php _e('Počet otázok:', 'maturitne-testy'); ?></strong> <?php echo count($questions); ?><br>
                        <strong><?php _e('Celkový počet bodov:', 'maturitne-testy'); ?></strong> <?php echo MT_Test::get_total_points($test->id); ?><br>
                        <?php if ($test->time_limit > 0): ?>
                            <strong><?php _e('Časový limit:', 'maturitne-testy'); ?></strong> <?php echo $test->time_limit; ?> <?php _e('minút', 'maturitne-testy'); ?><br>
                        <?php endif; ?>
                        <strong><?php _e('Percentuálny prah na úspech:', 'maturitne-testy'); ?></strong> <?php echo $test->passing_score; ?>%
                    </p>
                </div>

                <div class="mt-anti-cheat-warning" style="display: none;">
                    <div class="mt-warning-box">
                        <strong>⚠️ <?php _e('UPOZORNENIE!', 'maturitne-testy'); ?></strong>
                        <p><?php _e('Boli ste nachytaní pri pokuse o podvádzanie (otvorenie nového okna/karty). Váš výsledok bude označený ako nerelevantný.', 'maturitne-testy'); ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-test-start" id="mt-test-start">
                <?php if (!is_user_logged_in()): ?>
                    <div class="mt-user-info">
                        <h3><?php _e('Pred začatím testu vyplňte svoje údaje', 'maturitne-testy'); ?></h3>
                        <p>
                            <label for="mt-user-name"><?php _e('Meno a priezvisko:', 'maturitne-testy'); ?> *</label><br>
                            <input type="text" id="mt-user-name" class="mt-input" required>
                        </p>
                        <p>
                            <label for="mt-user-email"><?php _e('Email:', 'maturitne-testy'); ?> *</label><br>
                            <input type="email" id="mt-user-email" class="mt-input" required>
                        </p>
                    </div>
                <?php endif; ?>

                <button class="mt-btn mt-btn-primary" id="mt-start-test-btn">
                    <?php _e('Začať test', 'maturitne-testy'); ?>
                </button>
            </div>

            <div class="mt-test-content" id="mt-test-content" style="display: none;">
                <?php if ($test->time_limit > 0): ?>
                    <div class="mt-timer">
                        <strong><?php _e('Zostávajúci čas:', 'maturitne-testy'); ?></strong>
                        <span id="mt-timer-display">--:--</span>
                    </div>
                <?php endif; ?>

                <form id="mt-test-form">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="mt-question" data-question-id="<?php echo esc_attr($question->id); ?>">
                            <div class="mt-question-number">
                                <?php echo esc_html(($index + 1) . '.'); ?>
                            </div>

                            <div class="mt-question-content">
                                <div class="mt-question-text">
                                    <?php echo wp_kses_post($question->question_text); ?>
                                    <span class="mt-question-points">
                                        (<?php echo esc_html($question->points); ?> <?php echo $question->points === 1 ? __('bod', 'maturitne-testy') : __('body', 'maturitne-testy'); ?>)
                                    </span>
                                </div>

                                <?php if ($question->image_url): ?>
                                    <div class="mt-question-image">
                                        <img src="<?php echo esc_url($question->image_url); ?>" alt="">
                                    </div>
                                <?php endif; ?>

                                <div class="mt-question-answer">
                                    <?php if ($question->question_type === 'multiple_choice'): ?>
                                        <?php foreach ($question->options as $option): ?>
                                            <label class="mt-option">
                                                <input type="radio" name="question_<?php echo esc_attr($question->id); ?>" value="<?php echo esc_attr($option->id); ?>">
                                                <span><?php echo esc_html($option->option_text); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <input type="text" name="question_<?php echo esc_attr($question->id); ?>" class="mt-text-answer" placeholder="<?php _e('Vaša odpoveď...', 'maturitne-testy'); ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-test-footer">
                        <button type="submit" class="mt-btn mt-btn-primary" id="mt-submit-test-btn">
                            <?php _e('Odoslať test', 'maturitne-testy'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-test-results" id="mt-test-results" style="display: none;">
                <h3><?php _e('Výsledky testu', 'maturitne-testy'); ?></h3>

                <div class="mt-result-summary">
                    <div class="mt-result-score">
                        <div class="mt-score-circle" id="mt-score-circle">
                            <span id="mt-score-percentage">0%</span>
                        </div>
                        <p id="mt-score-text"></p>
                    </div>

                    <div class="mt-result-details">
                        <p><strong><?php _e('Skóre:', 'maturitne-testy'); ?></strong> <span id="mt-result-score">0</span> / <span id="mt-result-max-score">0</span></p>
                        <p><strong><?php _e('Percento:', 'maturitne-testy'); ?></strong> <span id="mt-result-percentage">0</span>%</p>
                        <p><strong><?php _e('Čas:', 'maturitne-testy'); ?></strong> <span id="mt-result-time">00:00</span></p>
                        <p id="mt-result-passed" style="display: none;">
                            <strong style="color: green;">✓ <?php _e('Úspešne ste zvládli test!', 'maturitne-testy'); ?></strong>
                        </p>
                        <p id="mt-result-failed" style="display: none;">
                            <strong style="color: red;">✗ <?php _e('Nepodarilo sa vám dosiahnuť požadované percento.', 'maturitne-testy'); ?></strong>
                        </p>
                        <p id="mt-result-cheating" style="display: none;">
                            <strong style="color: red;">⚠️ <?php _e('UPOZORNENIE: Bol zaznamenaný pokus o podvádzanie. Tento výsledok je nerelevantný.', 'maturitne-testy'); ?></strong>
                        </p>
                    </div>
                </div>

                <div id="mt-detailed-results">
                    <h4><?php _e('Detailné výsledky', 'maturitne-testy'); ?></h4>
                    <div id="mt-results-list"></div>
                </div>

                <div class="mt-test-footer">
                    <button class="mt-btn mt-btn-primary" id="mt-retake-test-btn">
                        <?php _e('Opakovať test', 'maturitne-testy'); ?>
                    </button>
                </div>
            </div>

            <div class="mt-loading" id="mt-loading" style="display: none;">
                <div class="mt-spinner"></div>
                <p><?php _e('Vyhodnocujem test...', 'maturitne-testy'); ?></p>
            </div>
        </div>
        <?php
    }
}
