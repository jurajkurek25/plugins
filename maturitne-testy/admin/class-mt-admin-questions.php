<?php
/**
 * Admin trieda pre správu otázok
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Admin_Questions {

    public function __construct() {
        add_action('admin_post_mt_save_question', array($this, 'save_question'));
        add_action('admin_post_mt_delete_question', array($this, 'delete_question'));
        add_action('wp_ajax_mt_upload_question_image', array($this, 'upload_question_image'));
    }

    public static function render_page() {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

        if (!$test_id) {
            wp_die(__('Neplatný test ID.', 'maturitne-testy'));
        }

        $test = MT_Test::get($test_id);
        if (!$test) {
            wp_die(__('Test nenájdený.', 'maturitne-testy'));
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'new' || $action === 'edit') {
            self::edit_page($test_id, $test);
        } else {
            self::list_page($test_id, $test);
        }
    }

    private static function list_page($test_id, $test) {
        $questions = MT_Test::get_questions($test_id);

        ?>
        <div class="wrap">
            <h1>
                <?php printf(__('Otázky pre test: %s', 'maturitne-testy'), esc_html($test->title)); ?>
                <a href="<?php echo admin_url('admin.php?page=mt-questions&test_id=' . $test_id . '&action=new'); ?>" class="page-title-action">
                    <?php _e('Pridať otázku', 'maturitne-testy'); ?>
                </a>
            </h1>

            <p>
                <a href="<?php echo admin_url('admin.php?page=mt-tests'); ?>" class="button">
                    &larr; <?php _e('Späť na zoznam testov', 'maturitne-testy'); ?>
                </a>
            </p>

            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['message']) {
                            case 'created':
                                _e('Otázka bola úspešne vytvorená.', 'maturitne-testy');
                                break;
                            case 'updated':
                                _e('Otázka bola úspešne aktualizovaná.', 'maturitne-testy');
                                break;
                            case 'deleted':
                                _e('Otázka bola úspešne vymazaná.', 'maturitne-testy');
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (empty($questions)): ?>
                <p><?php _e('Tento test zatiaľ nemá žiadne otázky.', 'maturitne-testy'); ?></p>
            <?php else: ?>
                <div class="mt-questions-list">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="mt-question-item">
                            <div class="mt-question-header">
                                <strong><?php echo esc_html(($index + 1) . '. '); ?></strong>
                                <?php echo wp_kses_post($question->question_text); ?>
                                <span class="mt-question-points">(<?php echo esc_html($question->points); ?> bodov)</span>
                            </div>

                            <?php if ($question->image_url): ?>
                                <div class="mt-question-image">
                                    <img src="<?php echo esc_url($question->image_url); ?>" alt="" style="max-width: 300px; height: auto;">
                                </div>
                            <?php endif; ?>

                            <div class="mt-question-type">
                                <em>
                                    <?php
                                    if ($question->question_type === 'multiple_choice') {
                                        _e('Typ: Výber z možností', 'maturitne-testy');
                                    } else {
                                        _e('Typ: Textová odpoveď', 'maturitne-testy');
                                    }
                                    ?>
                                </em>
                            </div>

                            <?php if ($question->question_type === 'multiple_choice' && !empty($question->options)): ?>
                                <div class="mt-question-options">
                                    <ul>
                                        <?php foreach ($question->options as $option): ?>
                                            <li style="<?php echo $option->is_correct ? 'color: green; font-weight: bold;' : ''; ?>">
                                                <?php echo esc_html($option->option_text); ?>
                                                <?php if ($option->is_correct): ?>
                                                    <span style="color: green;">✓</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="mt-question-actions">
                                <a href="<?php echo admin_url('admin.php?page=mt-questions&test_id=' . $test_id . '&action=edit&question_id=' . $question->id); ?>">
                                    <?php _e('Upraviť', 'maturitne-testy'); ?>
                                </a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_delete_question&test_id=' . $test_id . '&question_id=' . $question->id), 'mt_delete_question_' . $question->id); ?>"
                                   onclick="return confirm('<?php _e('Naozaj chcete vymazať túto otázku?', 'maturitne-testy'); ?>');"
                                   style="color: red;">
                                    <?php _e('Vymazať', 'maturitne-testy'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function edit_page($test_id, $test) {
        $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
        $question = $question_id ? MT_Question::get($question_id) : null;

        ?>
        <div class="wrap">
            <h1>
                <?php echo $question_id ? __('Upraviť otázku', 'maturitne-testy') : __('Nová otázka', 'maturitne-testy'); ?>
            </h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="mt-question-form">
                <input type="hidden" name="action" value="mt_save_question">
                <?php wp_nonce_field('mt_save_question'); ?>
                <input type="hidden" name="test_id" value="<?php echo esc_attr($test_id); ?>">

                <?php if ($question_id): ?>
                    <input type="hidden" name="question_id" value="<?php echo esc_attr($question_id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="question_text"><?php _e('Text otázky', 'maturitne-testy'); ?> *</label></th>
                        <td>
                            <textarea name="question_text" id="question_text" rows="4" class="large-text" required><?php echo esc_textarea($question ? $question->question_text : ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="question_type"><?php _e('Typ otázky', 'maturitne-testy'); ?></label></th>
                        <td>
                            <select name="question_type" id="question_type">
                                <option value="multiple_choice" <?php selected($question ? $question->question_type : 'multiple_choice', 'multiple_choice'); ?>>
                                    <?php _e('Výber z možností', 'maturitne-testy'); ?>
                                </option>
                                <option value="text" <?php selected($question ? $question->question_type : '', 'text'); ?>>
                                    <?php _e('Textová odpoveď', 'maturitne-testy'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="image_url"><?php _e('Obrázok', 'maturitne-testy'); ?></label></th>
                        <td>
                            <input type="hidden" name="image_url" id="image_url" value="<?php echo esc_attr($question ? $question->image_url : ''); ?>">
                            <button type="button" class="button" id="mt-upload-image-btn">
                                <?php _e('Nahrať obrázok', 'maturitne-testy'); ?>
                            </button>
                            <button type="button" class="button" id="mt-remove-image-btn" style="<?php echo ($question && $question->image_url) ? '' : 'display:none;'; ?>">
                                <?php _e('Odstrániť obrázok', 'maturitne-testy'); ?>
                            </button>
                            <div id="mt-image-preview" style="margin-top: 10px;">
                                <?php if ($question && $question->image_url): ?>
                                    <img src="<?php echo esc_url($question->image_url); ?>" alt="" style="max-width: 300px; height: auto;">
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="points"><?php _e('Body', 'maturitne-testy'); ?></label></th>
                        <td>
                            <input type="number" name="points" id="points" min="1" value="<?php echo esc_attr($question ? $question->points : 1); ?>">
                        </td>
                    </tr>
                </table>

                <div id="text-answer-section" style="<?php echo ($question && $question->question_type === 'text') ? '' : 'display:none;'; ?>">
                    <h3><?php _e('Správna odpoveď', 'maturitne-testy'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="correct_answer"><?php _e('Správna odpoveď', 'maturitne-testy'); ?></label></th>
                            <td>
                                <textarea name="correct_answer" id="correct_answer" rows="2" class="large-text"><?php echo esc_textarea($question ? $question->correct_answer : ''); ?></textarea>
                                <p class="description"><?php _e('Odpoveď sa porovnáva bez ohľadu na veľkosť písmen a biele znaky.', 'maturitne-testy'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="multiple-choice-section" style="<?php echo (!$question || $question->question_type === 'multiple_choice') ? '' : 'display:none;'; ?>">
                    <h3><?php _e('Možnosti odpovede', 'maturitne-testy'); ?></h3>
                    <div id="options-container">
                        <?php
                        if ($question && !empty($question->options)) {
                            foreach ($question->options as $index => $option) {
                                echo self::get_option_html($index, $option->option_text, $option->is_correct, $option->id);
                            }
                        } else {
                            for ($i = 0; $i < 4; $i++) {
                                echo self::get_option_html($i, '', 0, 0);
                            }
                        }
                        ?>
                    </div>
                    <button type="button" class="button" id="add-option-btn"><?php _e('Pridať možnosť', 'maturitne-testy'); ?></button>
                </div>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $question_id ? __('Aktualizovať otázku', 'maturitne-testy') : __('Vytvoriť otázku', 'maturitne-testy'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=mt-questions&test_id=' . $test_id); ?>" class="button">
                        <?php _e('Zrušiť', 'maturitne-testy'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    private static function get_option_html($index, $text = '', $is_correct = 0, $option_id = 0) {
        ob_start();
        ?>
        <div class="mt-option-row" data-index="<?php echo esc_attr($index); ?>">
            <?php if ($option_id): ?>
                <input type="hidden" name="options[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr($option_id); ?>">
            <?php endif; ?>
            <input type="text" name="options[<?php echo esc_attr($index); ?>][text]" placeholder="<?php _e('Text možnosti', 'maturitne-testy'); ?>" class="regular-text" value="<?php echo esc_attr($text); ?>">
            <label>
                <input type="radio" name="correct_option" value="<?php echo esc_attr($index); ?>" <?php checked($is_correct, 1); ?>>
                <?php _e('Správna odpoveď', 'maturitne-testy'); ?>
            </label>
            <button type="button" class="button remove-option-btn"><?php _e('Odstrániť', 'maturitne-testy'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_question() {
        check_admin_referer('mt_save_question');

        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnenie na túto akciu.', 'maturitne-testy'));
        }

        $test_id = intval($_POST['test_id']);
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;

        $data = array(
            'test_id' => $test_id,
            'question_text' => wp_kses_post($_POST['question_text']),
            'question_type' => sanitize_text_field($_POST['question_type']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'points' => intval($_POST['points']),
            'correct_answer' => sanitize_textarea_field($_POST['correct_answer'] ?? '')
        );

        if ($question_id) {
            MT_Question::update($question_id, $data);
        } else {
            $question_id = MT_Question::create($data);
        }

        // Uloženie možností pre multiple choice
        if ($data['question_type'] === 'multiple_choice' && isset($_POST['options'])) {
            MT_Question::delete_options($question_id);

            $correct_option = isset($_POST['correct_option']) ? intval($_POST['correct_option']) : -1;

            foreach ($_POST['options'] as $index => $option) {
                if (!empty($option['text'])) {
                    $is_correct = ($index == $correct_option) ? 1 : 0;
                    MT_Question::add_option($question_id, $option['text'], $is_correct, $index);
                }
            }
        }

        wp_redirect(admin_url('admin.php?page=mt-questions&test_id=' . $test_id . '&message=' . ($question_id ? 'updated' : 'created')));
        exit;
    }

    public function delete_question() {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;

        check_admin_referer('mt_delete_question_' . $question_id);

        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnenie na túto akciu.', 'maturitne-testy'));
        }

        MT_Question::delete($question_id);

        wp_redirect(admin_url('admin.php?page=mt-questions&test_id=' . $test_id . '&message=deleted'));
        exit;
    }

    public function upload_question_image() {
        check_ajax_referer('mt_admin_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Nemáte oprávnenie nahrávať súbory.', 'maturitne-testy')));
        }

        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $movefile['error']
            ));
        }
    }
}
