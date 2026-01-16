<?php
/**
 * Hlavná admin trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }

    public function add_menu_pages() {
        add_menu_page(
            __('Maturitné Testy', 'maturitne-testy'),
            __('Maturitné Testy', 'maturitne-testy'),
            'manage_options',
            'maturitne-testy',
            array($this, 'dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );

        add_submenu_page(
            'maturitne-testy',
            __('Prehľad', 'maturitne-testy'),
            __('Prehľad', 'maturitne-testy'),
            'manage_options',
            'maturitne-testy',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'maturitne-testy',
            __('Testy', 'maturitne-testy'),
            __('Testy', 'maturitne-testy'),
            'manage_options',
            'mt-tests',
            array('MT_Admin_Tests', 'render_page')
        );

        add_submenu_page(
            'maturitne-testy',
            __('Výsledky', 'maturitne-testy'),
            __('Výsledky', 'maturitne-testy'),
            'manage_options',
            'mt-results',
            array($this, 'results_page')
        );

        add_submenu_page(
            null,
            __('Upraviť test', 'maturitne-testy'),
            __('Upraviť test', 'maturitne-testy'),
            'manage_options',
            'mt-edit-test',
            array('MT_Admin_Tests', 'edit_page')
        );

        add_submenu_page(
            null,
            __('Otázky', 'maturitne-testy'),
            __('Otázky', 'maturitne-testy'),
            'manage_options',
            'mt-questions',
            array('MT_Admin_Questions', 'render_page')
        );
    }

    public function dashboard_page() {
        global $wpdb;

        $total_tests = $wpdb->get_var("SELECT COUNT(*) FROM " . MT_Database::get_table_tests());
        $total_results = $wpdb->get_var("SELECT COUNT(*) FROM " . MT_Database::get_table_results());
        $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM " . MT_Database::get_table_questions());

        ?>
        <div class="wrap">
            <h1><?php _e('Maturitné Testy - Prehľad', 'maturitne-testy'); ?></h1>

            <div class="mt-dashboard-stats">
                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo esc_html($total_tests); ?></div>
                    <div class="mt-stat-label"><?php _e('Celkom testov', 'maturitne-testy'); ?></div>
                </div>

                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo esc_html($total_questions); ?></div>
                    <div class="mt-stat-label"><?php _e('Celkom otázok', 'maturitne-testy'); ?></div>
                </div>

                <div class="mt-stat-box">
                    <div class="mt-stat-number"><?php echo esc_html($total_results); ?></div>
                    <div class="mt-stat-label"><?php _e('Celkom výsledkov', 'maturitne-testy'); ?></div>
                </div>
            </div>

            <h2><?php _e('Rýchly štart', 'maturitne-testy'); ?></h2>
            <p>
                <a href="<?php echo admin_url('admin.php?page=mt-tests&action=new'); ?>" class="button button-primary">
                    <?php _e('Vytvoriť nový test', 'maturitne-testy'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=mt-tests'); ?>" class="button">
                    <?php _e('Zobraziť všetky testy', 'maturitne-testy'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=mt-results'); ?>" class="button">
                    <?php _e('Zobraziť výsledky', 'maturitne-testy'); ?>
                </a>
            </p>

            <h2><?php _e('Ako používať plugin', 'maturitne-testy'); ?></h2>
            <ol>
                <li><?php _e('Vytvorte nový test pomocou tlačidla vyššie', 'maturitne-testy'); ?></li>
                <li><?php _e('Pridajte otázky do testu (výber z možností alebo textová odpoveď)', 'maturitne-testy'); ?></li>
                <li><?php _e('K otázkam môžete priložiť obrázky', 'maturitne-testy'); ?></li>
                <li><?php _e('Publikujte test', 'maturitne-testy'); ?></li>
                <li><?php _e('Použite shortcode [maturitny_test id="X"] na zobrazenie testu na stránke', 'maturitne-testy'); ?></li>
            </ol>

            <h2><?php _e('Anti-cheat ochrana', 'maturitne-testy'); ?></h2>
            <p>
                <?php _e('Plugin detekuje pokusy o podvádzanie (otvorenie nových okien/kariet). Pri podozrivej aktivite je používateľ upozornený a výsledok označený ako nerelevantný.', 'maturitne-testy'); ?>
            </p>
        </div>
        <?php
    }

    public function results_page() {
        global $wpdb;

        $results_table = MT_Database::get_table_results();
        $tests_table = MT_Database::get_table_tests();

        $results = $wpdb->get_results("
            SELECT r.*, t.title as test_title
            FROM {$results_table} r
            LEFT JOIN {$tests_table} t ON r.test_id = t.id
            ORDER BY r.completed_at DESC
            LIMIT 100
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Výsledky testov', 'maturitne-testy'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Test', 'maturitne-testy'); ?></th>
                        <th><?php _e('Používateľ', 'maturitne-testy'); ?></th>
                        <th><?php _e('Skóre', 'maturitne-testy'); ?></th>
                        <th><?php _e('Percento', 'maturitne-testy'); ?></th>
                        <th><?php _e('Čas', 'maturitne-testy'); ?></th>
                        <th><?php _e('Podvádzanie', 'maturitne-testy'); ?></th>
                        <th><?php _e('Dátum', 'maturitne-testy'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Zatiaľ žiadne výsledky', 'maturitne-testy'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo esc_html($result->test_title); ?></td>
                                <td>
                                    <?php
                                    if ($result->user_id > 0) {
                                        $user = get_userdata($result->user_id);
                                        echo esc_html($user ? $user->display_name : $result->user_name);
                                    } else {
                                        echo esc_html($result->user_name . ' (' . $result->user_email . ')');
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($result->score . ' / ' . $result->max_score); ?></td>
                                <td><?php echo esc_html(number_format($result->percentage, 2) . '%'); ?></td>
                                <td><?php echo esc_html(gmdate('i:s', $result->time_taken)); ?></td>
                                <td>
                                    <?php if ($result->cheating_detected): ?>
                                        <span style="color: red;">
                                            <?php echo esc_html(__('Áno', 'maturitne-testy') . ' (' . $result->cheating_count . 'x)'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: green;"><?php _e('Nie', 'maturitne-testy'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(mysql2date('d.m.Y H:i', $result->completed_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
