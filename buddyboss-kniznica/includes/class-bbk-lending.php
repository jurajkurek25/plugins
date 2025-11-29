<?php
/**
 * Trieda pre správu požičaní
 */

if (!defined('ABSPATH')) {
    exit;
}

class BBK_Lending {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_bbk_borrow_book', array($this, 'ajax_borrow_book'));
        add_action('wp_ajax_bbk_return_book', array($this, 'ajax_return_book'));
        add_action('bbk_daily_reminders', array($this, 'send_daily_reminders'));
    }

    /**
     * Vytvorenie bezplatného požičania
     */
    public function create_free_lending($book_id, $borrower_id) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'buddyboss-kniznica'));
        }

        // Kontrola aktívneho členstva (len ak je BuddyBoss dostupný)
        if (class_exists('BBK_BuddyBoss') && !BBK_BuddyBoss::get_instance()->is_active_member($borrower_id)) {
            return new WP_Error('not_active_member', __('Prístup len pre aktívnych členov komunity.', 'buddyboss-kniznica'));
        }

        // Získanie knihy
        $book = BBK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return new WP_Error('book_not_found', __('Kniha nebola nájdená.', 'buddyboss-kniznica'));
        }

        if ($book->status !== 'available') {
            return new WP_Error('book_not_available', __('Kniha nie je dostupná.', 'buddyboss-kniznica'));
        }

        if ($book->user_id == $borrower_id) {
            return new WP_Error('own_book', __('Nemôžete si požičať vlastnú knihu.', 'buddyboss-kniznica'));
        }

        if ($book->lending_price > 0) {
            return new WP_Error('paid_book', __('Táto kniha je platená. Použite košík.', 'buddyboss-kniznica'));
        }

        // Dĺžka požičania (30 dní)
        $lending_days = get_option('bbk_default_lending_days', 30);
        $start_date = current_time('mysql');
        $due_date = date('Y-m-d H:i:s', strtotime("+{$lending_days} days"));

        // Vytvorenie požičania
        $lending_data = array(
            'book_id' => $book_id,
            'borrower_id' => $borrower_id,
            'lender_id' => $book->user_id,
            'lending_price' => 0,
            'owner_commission' => 0,
            'community_commission' => 0,
            'start_date' => $start_date,
            'due_date' => $due_date,
            'status' => 'active'
        );

        $lending_id = BBK_Database::insert('lendings', $lending_data);

        if (!$lending_id) {
            return new WP_Error('insert_failed', __('Nepodarilo sa vytvoriť požičanie.', 'buddyboss-kniznica'));
        }

        // Aktualizácia statusu knihy
        BBK_Book::get_instance()->update_status($book_id, 'borrowed');

        // Notifikácia majiteľovi
        BBK_Notifications::get_instance()->create_notification(
            $book->user_id,
            'new_lending',
            __('Nové požičanie knihy', 'buddyboss-kniznica'),
            sprintf(__('%s si požičal vašu knihu "%s".', 'buddyboss-kniznica'),
                get_user_by('id', $borrower_id)->display_name,
                $book->title
            )
        );

        // Email majiteľovi
        $this->send_lending_email($lending_id, 'free');

        return $lending_id;
    }

    /**
     * Vytvorenie plateného požičania s WooCommerce objednávkou
     * ROZDELENIE POPLATKOV: 70% majiteľ / 30% komunita
     */
    public function create_paid_lending($book_id, $borrower_id, $order_id, $shipping_data) {
        // Získanie knihy
        $book = BBK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return new WP_Error('book_not_found', __('Kniha nebola nájdená.', 'buddyboss-kniznica'));
        }

        // VÝPOČET PROVÍZIÍ 70/30
        $lending_price = $book->lending_price;
        $commission_rate = get_option('bbk_commission_rate', 30); // 30%
        $community_commission = ($lending_price * $commission_rate) / 100; // 30%
        $owner_commission = $lending_price - $community_commission; // 70%

        // Dĺžka požičania
        $lending_days = get_option('bbk_default_lending_days', 30);
        $start_date = current_time('mysql');
        $due_date = date('Y-m-d H:i:s', strtotime("+{$lending_days} days"));

        // Vytvorenie požičania s doručovacími údajmi
        $lending_data = array(
            'book_id' => $book_id,
            'borrower_id' => $borrower_id,
            'lender_id' => $book->user_id,
            'order_id' => $order_id,
            'lending_price' => $lending_price,
            'owner_commission' => $owner_commission, // 70%
            'community_commission' => $community_commission, // 30%
            'start_date' => $start_date,
            'due_date' => $due_date,
            'status' => 'active',
            'shipping_name' => $shipping_data['name'],
            'shipping_address' => $shipping_data['address'],
            'shipping_city' => $shipping_data['city'],
            'shipping_postcode' => $shipping_data['postcode'],
            'shipping_country' => $shipping_data['country'],
            'shipping_phone' => $shipping_data['phone']
        );

        $lending_id = BBK_Database::insert('lendings', $lending_data);

        if (!$lending_id) {
            return new WP_Error('insert_failed', __('Nepodarilo sa vytvoriť požičanie.', 'buddyboss-kniznica'));
        }

        // Aktualizácia statusu knihy
        BBK_Book::get_instance()->update_status($book_id, 'borrowed');

        // Notifikácia majiteľovi
        BBK_Notifications::get_instance()->create_notification(
            $book->user_id,
            'new_paid_lending',
            __('Nové platené požičanie', 'buddyboss-kniznica'),
            sprintf(__('%s si požičal vašu knihu "%s" za %.2f €. Vaša provízia: %.2f € (70%%).', 'buddyboss-kniznica'),
                get_user_by('id', $borrower_id)->display_name,
                $book->title,
                $lending_price,
                $owner_commission
            )
        );

        // Email s doručovacími údajmi
        $this->send_lending_email($lending_id, 'paid');

        return $lending_id;
    }

    /**
     * Vrátenie knihy
     */
    public function return_book($lending_id) {
        $lending = $this->get_lending($lending_id);

        if (!$lending) {
            return new WP_Error('lending_not_found', __('Požičanie nebolo nájdené.', 'buddyboss-kniznica'));
        }

        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'buddyboss-kniznica'));
        }

        $user_id = get_current_user_id();

        // Kontrola oprávnenia
        if ($lending->borrower_id != $user_id && $lending->lender_id != $user_id && !current_user_can('manage_bbk_library')) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie vrátiť túto knihu.', 'buddyboss-kniznica'));
        }

        // Aktualizácia požičania
        $return_date = current_time('mysql');
        BBK_Database::update('lendings',
            array(
                'return_date' => $return_date,
                'status' => 'returned'
            ),
            array('id' => $lending_id)
        );

        // Aktualizácia statusu knihy
        BBK_Book::get_instance()->update_status($lending->book_id, 'available');

        // Notifikácie
        $book = BBK_Book::get_instance()->get_book($lending->book_id);

        BBK_Notifications::get_instance()->create_notification(
            $lending->lender_id,
            'book_returned',
            __('Kniha vrátená', 'buddyboss-kniznica'),
            sprintf(__('Kniha "%s" bola vrátená.', 'buddyboss-kniznica'), $book->title)
        );

        BBK_Notifications::get_instance()->create_notification(
            $lending->borrower_id,
            'book_returned',
            __('Kniha vrátená', 'buddyboss-kniznica'),
            sprintf(__('Vrátili ste knihu "%s". Môžete ju teraz ohodnotiť.', 'buddyboss-kniznica'), $book->title)
        );

        return true;
    }

    /**
     * Získanie požičania
     */
    public function get_lending($lending_id) {
        return BBK_Database::get_row('lendings', array('id' => $lending_id));
    }

    /**
     * Získanie požičaní pre požičiavajúceho
     */
    public function get_borrower_lendings($borrower_id, $status = null) {
        $where = array('borrower_id' => $borrower_id);

        if ($status) {
            $where['status'] = $status;
        }

        return BBK_Database::get_results('lendings', $where, OBJECT, 'created_at DESC');
    }

    /**
     * Získanie požičaní pre majiteľa
     */
    public function get_lender_lendings($lender_id, $status = null) {
        $where = array('lender_id' => $lender_id);

        if ($status) {
            $where['status'] = $status;
        }

        return BBK_Database::get_results('lendings', $where, OBJECT, 'created_at DESC');
    }

    /**
     * Odoslanie emailu o požičaní
     */
    private function send_lending_email($lending_id, $type = 'free') {
        $lending = $this->get_lending($lending_id);
        if (!$lending) return;

        $lender = get_user_by('id', $lending->lender_id);
        $borrower = get_user_by('id', $lending->borrower_id);
        $book = BBK_Book::get_instance()->get_book($lending->book_id);

        if ($type === 'paid') {
            $subject = sprintf(__('[Komunitná Knižnica] Nové platené požičanie: %s', 'buddyboss-kniznica'), $book->title);

            $message = sprintf(
                __("Dobrý deň %s,\n\n%s si požičal vašu knihu \"%s\" za %.2f €.\n\n💰 ROZDELENIE POPLATKOV:\nVaša provízia (70%%): %.2f €\nProvízia komunity (30%%): %.2f €\n\n📦 DORUČOVACIE ÚDAJE:\nMeno: %s\nAdresa: %s\nMesto: %s\nPSČ: %s\nKrajina: %s\nTelefón: %s\n\nDátum požičania: %s\nDátum vrátenia: %s\n\nProsím, pošlite knihu na uvedenú adresu.\n\nKontakt: %s\n\nĎakujeme,\nKomunitná Knižnica", 'buddyboss-kniznica'),
                $lender->display_name,
                $borrower->display_name,
                $book->title,
                $lending->lending_price,
                $lending->owner_commission,
                $lending->community_commission,
                $lending->shipping_name,
                $lending->shipping_address,
                $lending->shipping_city,
                $lending->shipping_postcode,
                $lending->shipping_country,
                $lending->shipping_phone,
                date('d.m.Y', strtotime($lending->start_date)),
                date('d.m.Y', strtotime($lending->due_date)),
                $borrower->user_email
            );
        } else {
            $subject = sprintf(__('[Komunitná Knižnica] Nové požičanie: %s', 'buddyboss-kniznica'), $book->title);

            $message = sprintf(
                __("Dobrý deň %s,\n\n%s si požičal vašu knihu \"%s\".\n\nDátum požičania: %s\nDátum vrátenia: %s\n\nKontakt: %s\n\nĎakujeme,\nKomunitná Knižnica", 'buddyboss-kniznica'),
                $lender->display_name,
                $borrower->display_name,
                $book->title,
                date('d.m.Y', strtotime($lending->start_date)),
                date('d.m.Y', strtotime($lending->due_date)),
                $borrower->user_email
            );
        }

        wp_mail($lender->user_email, $subject, $message);
    }

    /**
     * Denné upomienky
     */
    public function send_daily_reminders() {
        global $wpdb;
        $table = BBK_Database::get_table_name('lendings');

        // Upozornenie 3 dni pred vrátením
        $reminder_date = date('Y-m-d', strtotime('+3 days'));
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'active' AND DATE(due_date) = %s",
            $reminder_date
        );
        $upcoming = $wpdb->get_results($query);

        foreach ($upcoming as $lending) {
            $this->send_reminder_email($lending->id, 'upcoming');
        }

        // Upozornenie na omeškanie
        $today = date('Y-m-d');
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'active' AND DATE(due_date) < %s",
            $today
        );
        $overdue = $wpdb->get_results($query);

        foreach ($overdue as $lending) {
            $this->send_reminder_email($lending->id, 'overdue');
        }
    }

    /**
     * Odoslanie upomienky
     */
    private function send_reminder_email($lending_id, $type) {
        $lending = $this->get_lending($lending_id);
        if (!$lending) return;

        $borrower = get_user_by('id', $lending->borrower_id);
        $book = BBK_Book::get_instance()->get_book($lending->book_id);

        if ($type === 'upcoming') {
            $subject = __('[Komunitná Knižnica] Upomienka: Blíži sa termín vrátenia', 'buddyboss-kniznica');
            $message = sprintf(
                __("Dobrý deň %s,\n\nPripomíname, že knihu \"%s\" je potrebné vrátiť do 3 dní (do %s).\n\nĎakujeme,\nKomunitná Knižnica", 'buddyboss-kniznica'),
                $borrower->display_name,
                $book->title,
                date('d.m.Y', strtotime($lending->due_date))
            );
        } else {
            $subject = __('[Komunitná Knižnica] NALIEHAVÉ: Omeškanie vrátenia', 'buddyboss-kniznica');
            $message = sprintf(
                __("Dobrý deň %s,\n\nKnihu \"%s\" ste mali vrátiť dňa %s. Prosím, vráťte ju čo najskôr.\n\nĎakujeme,\nKomunitná Knižnica", 'buddyboss-kniznica'),
                $borrower->display_name,
                $book->title,
                date('d.m.Y', strtotime($lending->due_date))
            );
        }

        wp_mail($borrower->user_email, $subject, $message);

        // Notifikácia
        BBK_Notifications::get_instance()->create_notification(
            $lending->borrower_id,
            $type === 'upcoming' ? 'due_soon' : 'overdue',
            $subject,
            $message
        );
    }

    /**
     * AJAX: Požičanie bezplatnej knihy
     */
    public function ajax_borrow_book() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $user_id = get_current_user_id();

        $result = $this->create_free_lending($book_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne požičaná!', 'buddyboss-kniznica')));
    }

    /**
     * AJAX: Vrátenie knihy
     */
    public function ajax_return_book() {
        check_ajax_referer('bbk_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'buddyboss-kniznica')));
        }

        $lending_id = absint($_POST['lending_id']);
        $result = $this->return_book($lending_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne vrátená!', 'buddyboss-kniznica')));
    }
}
