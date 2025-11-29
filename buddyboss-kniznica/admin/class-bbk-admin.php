<?php
/**
 * Admin trieda - WordPress admin rozhranie
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Pridanie admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('Komunitná Knižnica', 'buddyboss-kniznica'),
            __('Knižnica', 'buddyboss-kniznica'),
            'manage_bbk_library',
            'buddyboss-kniznica',
            array($this, 'dashboard_page'),
            'dashicons-book',
            30
        );

        add_submenu_page(
            'buddyboss-kniznica',
            __('Dashboard', 'buddyboss-kniznica'),
            __('Dashboard', 'buddyboss-kniznica'),
            'manage_bbk_library',
            'buddyboss-kniznica',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'buddyboss-kniznica',
            __('Knihy', 'buddyboss-kniznica'),
            __('Knihy', 'buddyboss-kniznica'),
            'manage_bbk_library',
            'bbk-books',
            array($this, 'books_page')
        );

        add_submenu_page(
            'buddyboss-kniznica',
            __('Požičania', 'buddyboss-kniznica'),
            __('Požičania', 'buddyboss-kniznica'),
            'manage_bbk_library',
            'bbk-lendings',
            array($this, 'lendings_page')
        );

        add_submenu_page(
            'buddyboss-kniznica',
            __('Nastavenia', 'buddyboss-kniznica'),
            __('Nastavenia', 'buddyboss-kniznica'),
            'manage_bbk_library',
            'bbk-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Dashboard stránka
     */
    public function dashboard_page() {
        // Štatistiky
        $total_books = BBK_Database::get_count('books');
        $available_books = BBK_Database::get_count('books', array('status' => 'available'));
        $borrowed_books = BBK_Database::get_count('books', array('status' => 'borrowed'));
        $active_lendings = BBK_Database::get_count('lendings', array('status' => 'active'));
        $total_members = count_users()['total_users'];

        echo '<div class="wrap">';
        echo '<h1>' . __('Dashboard - Komunitná Knižnica', 'buddyboss-kniznica') . '</h1>';

        echo '<div class="bbk-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">';

        echo '<div class="stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #d4af37;">';
        echo '<h3 style="margin: 0; color: #666;">📚 Celkom kníh</h3>';
        echo '<p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: #d4af37;">' . $total_books . '</p>';
        echo '</div>';

        echo '<div class="stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #4CAF50;">';
        echo '<h3 style="margin: 0; color: #666;">✅ Dostupné</h3>';
        echo '<p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: #4CAF50;">' . $available_books . '</p>';
        echo '</div>';

        echo '<div class="stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #FF9800;">';
        echo '<h3 style="margin: 0; color: #666;">📖 Požičané</h3>';
        echo '<p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: #FF9800;">' . $borrowed_books . '</p>';
        echo '</div>';

        echo '<div class="stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #2196F3;">';
        echo '<h3 style="margin: 0; color: #666;">🤝 Aktívne požičania</h3>';
        echo '<p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: #2196F3;">' . $active_lendings . '</p>';
        echo '</div>';

        echo '<div class="stat-box" style="background: #fff; padding: 20px; border-left: 4px solid #9C27B0;">';
        echo '<h3 style="margin: 0; color: #666;">👥 Členovia</h3>';
        echo '<p style="font-size: 32px; margin: 10px 0; font-weight: bold; color: #9C27B0;">' . $total_members . '</p>';
        echo '</div>';

        echo '</div>';

        echo '<h2>' . __('Posledné požičania', 'buddyboss-kniznica') . '</h2>';
        $recent_lendings = BBK_Database::get_results('lendings', array(), OBJECT, 'created_at DESC', 10);

        if ($recent_lendings) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Kniha', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Požičiavajúci', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Majiteľ', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Cena', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Status', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Dátum', 'buddyboss-kniznica') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($recent_lendings as $lending) {
                $book = BBK_Book::get_instance()->get_book($lending->book_id);
                $borrower = get_user_by('id', $lending->borrower_id);
                $lender = get_user_by('id', $lending->lender_id);

                echo '<tr>';
                echo '<td>' . esc_html($book->title) . '</td>';
                echo '<td>' . esc_html($borrower->display_name) . '</td>';
                echo '<td>' . esc_html($lender->display_name) . '</td>';
                echo '<td>' . ($lending->lending_price > 0 ? number_format($lending->lending_price, 2) . ' €' : 'Zadarmo') . '</td>';
                echo '<td>' . esc_html(ucfirst($lending->status)) . '</td>';
                echo '<td>' . date('d.m.Y', strtotime($lending->created_at)) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Žiadne požičania.', 'buddyboss-kniznica') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Knihy stránka
     */
    public function books_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Knihy', 'buddyboss-kniznica') . '</h1>';

        $books = BBK_Database::get_results('books', array(), OBJECT, 'created_at DESC');

        if ($books) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Názov', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Autor', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Žáner', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Majiteľ', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Cena', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Status', 'buddyboss-kniznica') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($books as $book) {
                $owner = get_user_by('id', $book->user_id);

                echo '<tr>';
                echo '<td><strong>' . esc_html($book->title) . '</strong></td>';
                echo '<td>' . esc_html($book->author) . '</td>';
                echo '<td>' . esc_html($book->genre) . '</td>';
                echo '<td>' . esc_html($owner->display_name) . '</td>';
                echo '<td>' . ($book->lending_price > 0 ? number_format($book->lending_price, 2) . ' €' : 'Zadarmo') . '</td>';
                echo '<td>' . esc_html(ucfirst($book->status)) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Žiadne knihy.', 'buddyboss-kniznica') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Požičania stránka
     */
    public function lendings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Požičania', 'buddyboss-kniznica') . '</h1>';

        $lendings = BBK_Database::get_results('lendings', array(), OBJECT, 'created_at DESC');

        if ($lendings) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Kniha', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Požičiavajúci', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Majiteľ', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Cena', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Provízia majiteľa', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Provízia komunity', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Status', 'buddyboss-kniznica') . '</th>';
            echo '<th>' . __('Dátum vrátenia', 'buddyboss-kniznica') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($lendings as $lending) {
                $book = BBK_Book::get_instance()->get_book($lending->book_id);
                $borrower = get_user_by('id', $lending->borrower_id);
                $lender = get_user_by('id', $lending->lender_id);

                echo '<tr>';
                echo '<td>' . esc_html($book->title) . '</td>';
                echo '<td>' . esc_html($borrower->display_name) . '</td>';
                echo '<td>' . esc_html($lender->display_name) . '</td>';
                echo '<td>' . ($lending->lending_price > 0 ? number_format($lending->lending_price, 2) . ' €' : 'Zadarmo') . '</td>';
                echo '<td>' . ($lending->owner_commission > 0 ? number_format($lending->owner_commission, 2) . ' € (70%)' : '-') . '</td>';
                echo '<td>' . ($lending->community_commission > 0 ? number_format($lending->community_commission, 2) . ' € (30%)' : '-') . '</td>';
                echo '<td>' . esc_html(ucfirst($lending->status)) . '</td>';
                echo '<td>' . date('d.m.Y', strtotime($lending->due_date)) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Žiadne požičania.', 'buddyboss-kniznica') . '</p>';
        }

        echo '</div>';
    }

    /**
     * Nastavenia stránka
     */
    public function settings_page() {
        if (isset($_POST['bbk_save_settings'])) {
            check_admin_referer('bbk_settings_nonce');

            update_option('bbk_commission_rate', absint($_POST['commission_rate']));
            update_option('bbk_default_lending_days', absint($_POST['default_lending_days']));

            echo '<div class="notice notice-success"><p>' . __('Nastavenia boli uložené.', 'buddyboss-kniznica') . '</p></div>';
        }

        $commission_rate = get_option('bbk_commission_rate', 30);
        $default_lending_days = get_option('bbk_default_lending_days', 30);

        echo '<div class="wrap">';
        echo '<h1>' . __('Nastavenia', 'buddyboss-kniznica') . '</h1>';

        echo '<form method="post">';
        wp_nonce_field('bbk_settings_nonce');

        echo '<table class="form-table">';

        echo '<tr>';
        echo '<th>' . __('Provízna sadzba komunity (%)', 'buddyboss-kniznica') . '</th>';
        echo '<td><input type="number" name="commission_rate" value="' . esc_attr($commission_rate) . '" min="0" max="100" /> %';
        echo '<p class="description">' . __('Percento z plateného požičania, ktoré ide komunite (default: 30%). Zvyšok ide majiteľovi knihy.', 'buddyboss-kniznica') . '</p>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th>' . __('Predvolená dĺžka požičania (dni)', 'buddyboss-kniznica') . '</th>';
        echo '<td><input type="number" name="default_lending_days" value="' . esc_attr($default_lending_days) . '" min="1" /> dní';
        echo '<p class="description">' . __('Počet dní, na ktoré sa kniha požičiava (default: 30 dní).', 'buddyboss-kniznica') . '</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        echo '<p class="submit"><input type="submit" name="bbk_save_settings" class="button button-primary" value="' . __('Uložiť nastavenia', 'buddyboss-kniznica') . '" /></p>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Načítanie admin skriptov
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'buddyboss-kniznica') === false && strpos($hook, 'bbk-') === false) {
            return;
        }

        wp_enqueue_style('bbk-admin-css', BBK_PLUGIN_URL . 'assets/css/admin.css', array(), BBK_VERSION);
        wp_enqueue_script('bbk-admin-js', BBK_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BBK_VERSION, true);
    }
}
