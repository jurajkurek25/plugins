<?php
/**
 * Admin trieda pre správu testov
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Admin_Tests {

    public function __construct() {
        add_action('admin_post_mt_save_test', array($this, 'save_test'));
        add_action('admin_post_mt_delete_test', array($this, 'delete_test'));
    }

    public static function render_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'new' || $action === 'edit') {
            self::edit_page();
        } else {
            self::list_page();
        }
    }

    public static function list_page() {
        $tests = MT_Test::get_all();

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Testy', 'maturitne-testy'); ?>
                <a href="<?php echo admin_url('admin.php?page=mt-tests&action=new'); ?>" class="page-title-action">
                    <?php _e('Pridať nový', 'maturitne-testy'); ?>
                </a>
            </h1>

            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['message']) {
                            case 'created':
                                _e('Test bol úspešne vytvorený.', 'maturitne-testy');
                                break;
                            case 'updated':
                                _e('Test bol úspešne aktualizovaný.', 'maturitne-testy');
                                break;
                            case 'deleted':
                                _e('Test bol úspešne vymazaný.', 'maturitne-testy');
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Názov', 'maturitne-testy'); ?></th>
                        <th><?php _e('Otázky', 'maturitne-testy'); ?></th>
                        <th><?php _e('Časový limit', 'maturitne-testy'); ?></th>
                        <th><?php _e('Percentuálny prah', 'maturitne-testy'); ?></th>
                        <th><?php _e('Stav', 'maturitne-testy'); ?></th>
                        <th><?php _e('Shortcode', 'maturitne-testy'); ?></th>
                        <th><?php _e('Akcie', 'maturitne-testy'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tests)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Zatiaľ žiadne testy', 'maturitne-testy'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tests as $test): ?>
                            <?php
                            $questions = MT_Test::get_questions($test->id);
                            $question_count = count($questions);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($test->title); ?></strong></td>
                                <td><?php echo esc_html($question_count); ?></td>
                                <td><?php echo esc_html($test->time_limit . ' min'); ?></td>
                                <td><?php echo esc_html($test->passing_score . '%'); ?></td>
                                <td>
                                    <?php
                                    if ($test->status === 'published') {
                                        echo '<span style="color: green;">' . __('Publikované', 'maturitne-testy') . '</span>';
                                    } else {
                                        echo '<span style="color: orange;">' . __('Koncept', 'maturitne-testy') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <input type="text" readonly value="[maturitny_test id=&quot;<?php echo esc_attr($test->id); ?>&quot;]" onclick="this.select();" style="width: 100%;">
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=mt-tests&action=edit&test_id=' . $test->id); ?>">
                                        <?php _e('Upraviť', 'maturitne-testy'); ?>
                                    </a> |
                                    <a href="<?php echo admin_url('admin.php?page=mt-questions&test_id=' . $test->id); ?>">
                                        <?php _e('Otázky', 'maturitne-testy'); ?>
                                    </a> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_delete_test&test_id=' . $test->id), 'mt_delete_test_' . $test->id); ?>"
                                       onclick="return confirm('<?php _e('Naozaj chcete vymazať tento test?', 'maturitne-testy'); ?>');"
                                       style="color: red;">
                                        <?php _e('Vymazať', 'maturitne-testy'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function edit_page() {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
        $test = $test_id ? MT_Test::get($test_id) : null;

        ?>
        <div class="wrap">
            <h1>
                <?php echo $test_id ? __('Upraviť test', 'maturitne-testy') : __('Nový test', 'maturitne-testy'); ?>
            </h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="mt_save_test">
                <?php wp_nonce_field('mt_save_test'); ?>

                <?php if ($test_id): ?>
                    <input type="hidden" name="test_id" value="<?php echo esc_attr($test_id); ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="title"><?php _e('Názov testu', 'maturitne-testy'); ?> *</label></th>
                        <td>
                            <input type="text" name="title" id="title" class="regular-text" required
                                   value="<?php echo esc_attr($test ? $test->title : ''); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Popis', 'maturitne-testy'); ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="5" class="large-text"><?php echo esc_textarea($test ? $test->description : ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="time_limit"><?php _e('Časový limit (minúty)', 'maturitne-testy'); ?></label></th>
                        <td>
                            <input type="number" name="time_limit" id="time_limit" min="0" value="<?php echo esc_attr($test ? $test->time_limit : 60); ?>">
                            <p class="description"><?php _e('0 = bez časového limitu', 'maturitne-testy'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="passing_score"><?php _e('Percentuálny prah na úspech (%)', 'maturitne-testy'); ?></label></th>
                        <td>
                            <input type="number" name="passing_score" id="passing_score" min="0" max="100" value="<?php echo esc_attr($test ? $test->passing_score : 50); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php _e('Stav', 'maturitne-testy'); ?></label></th>
                        <td>
                            <select name="status" id="status">
                                <option value="draft" <?php selected($test ? $test->status : 'draft', 'draft'); ?>>
                                    <?php _e('Koncept', 'maturitne-testy'); ?>
                                </option>
                                <option value="published" <?php selected($test ? $test->status : '', 'published'); ?>>
                                    <?php _e('Publikované', 'maturitne-testy'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo $test_id ? __('Aktualizovať test', 'maturitne-testy') : __('Vytvoriť test', 'maturitne-testy'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=mt-tests'); ?>" class="button">
                        <?php _e('Zrušiť', 'maturitne-testy'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    public function save_test() {
        check_admin_referer('mt_save_test');

        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnenie na túto akciu.', 'maturitne-testy'));
        }

        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;

        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'time_limit' => intval($_POST['time_limit']),
            'passing_score' => intval($_POST['passing_score']),
            'status' => sanitize_text_field($_POST['status'])
        );

        if ($test_id) {
            MT_Test::update($test_id, $data);
            $message = 'updated';
        } else {
            $test_id = MT_Test::create($data);
            $message = 'created';
        }

        wp_redirect(admin_url('admin.php?page=mt-tests&message=' . $message));
        exit;
    }

    public function delete_test() {
        $test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

        check_admin_referer('mt_delete_test_' . $test_id);

        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnenie na túto akciu.', 'maturitne-testy'));
        }

        MT_Test::delete($test_id);

        wp_redirect(admin_url('admin.php?page=mt-tests&message=deleted'));
        exit;
    }
}
