<?php
/**
 * Trieda pre správu požičaní
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Lending {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_kk_return_book', array($this, 'ajax_return_book'));
        add_action('kk_daily_reminders', array($this, 'send_daily_reminders'));
    }

    /**
     * Vytvorenie požičania (bezplatné)
     */
    public function create_lending($book_id, $borrower_id) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'komunitna-kniznica'));
        }

        // Získanie knihy
        $book = KK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return new WP_Error('book_not_found', __('Kniha nebola nájdená.', 'komunitna-kniznica'));
        }

        if ($book->status !== 'available') {
            return new WP_Error('book_not_available', __('Kniha nie je dostupná.', 'komunitna-kniznica'));
        }

        if ($book->user_id == $borrower_id) {
            return new WP_Error('own_book', __('Nemôžete si požičať vlastnú knihu.', 'komunitna-kniznica'));
        }

        // Získanie dĺžky požičania
        $lending_days = get_option('kk_default_lending_days', 30);
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

        $lending_id = KK_Database::insert('lendings', $lending_data);

        if (!$lending_id) {
            return new WP_Error('insert_failed', __('Nepodarilo sa vytvoriť požičanie.', 'komunitna-kniznica'));
        }

        // Aktualizácia statusu knihy
        KK_Book::get_instance()->update_status($book_id, 'borrowed');

        // Odoslanie notifikácie majiteľovi
        KK_Notifications::get_instance()->create_notification(
            $book->user_id,
            'new_lending',
            __('Nové požičanie knihy', 'komunitna-kniznica'),
            sprintf(__('%s si požičal vašu knihu "%s".', 'komunitna-kniznica'),
                get_user_by('id', $borrower_id)->display_name,
                $book->title
            )
        );

        // Odoslanie emailu majiteľovi
        $this->send_lending_email($lending_id);

        return $lending_id;
    }

    /**
     * Vytvorenie požičania s WooCommerce objednávkou (platené)
     */
    public function create_paid_lending($book_id, $borrower_id, $order_id, $shipping_data) {
        // Získanie knihy
        $book = KK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return new WP_Error('book_not_found', __('Kniha nebola nájdená.', 'komunitna-kniznica'));
        }

        // Výpočet provízií
        $lending_price = $book->lending_price;
        $commission_rate = get_option('kk_commission_rate', 30);
        $community_commission = ($lending_price * $commission_rate) / 100;
        $owner_commission = $lending_price - $community_commission;

        // Získanie dĺžky požičania
        $lending_days = get_option('kk_default_lending_days', 30);
        $start_date = current_time('mysql');
        $due_date = date('Y-m-d H:i:s', strtotime("+{$lending_days} days"));

        // Vytvorenie požičania s doručovacími údajmi
        $lending_data = array(
            'book_id' => $book_id,
            'borrower_id' => $borrower_id,
            'lender_id' => $book->user_id,
            'order_id' => $order_id,
            'lending_price' => $lending_price,
            'owner_commission' => $owner_commission,
            'community_commission' => $community_commission,
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

        $lending_id = KK_Database::insert('lendings', $lending_data);

        if (!$lending_id) {
            return new WP_Error('insert_failed', __('Nepodarilo sa vytvoriť požičanie.', 'komunitna-kniznica'));
        }

        // Aktualizácia statusu knihy
        KK_Book::get_instance()->update_status($book_id, 'borrowed');

        // Odoslanie notifikácie majiteľovi
        KK_Notifications::get_instance()->create_notification(
            $book->user_id,
            'new_lending',
            __('Nové platené požičanie knihy', 'komunitna-kniznica'),
            sprintf(__('%s si požičal vašu knihu "%s" za %.2f €.', 'komunitna-kniznica'),
                get_user_by('id', $borrower_id)->display_name,
                $book->title,
                $lending_price
            )
        );

        // Odoslanie emailu s doručovacími údajmi majiteľovi
        $this->send_paid_lending_email($lending_id);

        return $lending_id;
    }

    /**
     * Vrátenie knihy
     */
    public function return_book($lending_id) {
        $lending = $this->get_lending($lending_id);

        if (!$lending) {
            return new WP_Error('lending_not_found', __('Požičanie nebolo nájdené.', 'komunitna-kniznica'));
        }

        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'komunitna-kniznica'));
        }

        $user_id = get_current_user_id();

        // Kontrola oprávnenia (iba požičiavajúci alebo majiteľ môže vrátiť knihu)
        if ($lending->borrower_id != $user_id && $lending->lender_id != $user_id && !current_user_can('manage_kk_library')) {
            return new WP_Error('permission_denied', __('Nemáte oprávnenie vrátiť túto knihu.', 'komunitna-kniznica'));
        }

        // Aktualizácia požičania
        $return_date = current_time('mysql');
        KK_Database::update('lendings',
            array(
                'return_date' => $return_date,
                'status' => 'returned'
            ),
            array('id' => $lending_id)
        );

        // Aktualizácia statusu knihy
        KK_Book::get_instance()->update_status($lending->book_id, 'available');

        // Notifikácie
        KK_Notifications::get_instance()->create_notification(
            $lending->lender_id,
            'book_returned',
            __('Kniha vrátená', 'komunitna-kniznica'),
            sprintf(__('Kniha "%s" bola vrátená.', 'komunitna-kniznica'),
                $this->get_book_title($lending->book_id)
            )
        );

        KK_Notifications::get_instance()->create_notification(
            $lending->borrower_id,
            'book_returned',
            __('Kniha vrátená', 'komunitna-kniznica'),
            sprintf(__('Vrátili ste knihu "%s". Môžete ju teraz ohodnotiť.', 'komunitna-kniznica'),
                $this->get_book_title($lending->book_id)
            )
        );

        return true;
    }

    /**
     * Získanie požičania
     */
    public function get_lending($lending_id) {
        return KK_Database::get_row('lendings', array('id' => $lending_id));
    }

    /**
     * Získanie požičaní pre požičiavajúceho
     */
    public function get_borrower_lendings($borrower_id, $status = null) {
        $where = array('borrower_id' => $borrower_id);

        if ($status) {
            $where['status'] = $status;
        }

        return KK_Database::get_results('lendings', $where, OBJECT, 'created_at DESC');
    }

    /**
     * Získanie požičaní pre majiteľa
     */
    public function get_lender_lendings($lender_id, $status = null) {
        $where = array('lender_id' => $lender_id);

        if ($status) {
            $where['status'] = $status;
        }

        return KK_Database::get_results('lendings', $where, OBJECT, 'created_at DESC');
    }

    /**
     * Získanie názvu knihy
     */
    private function get_book_title($book_id) {
        $book = KK_Database::get_row('books', array('id' => $book_id));
        return $book ? $book->title : '';
    }

    /**
     * Odoslanie emailu o novom požičaní (bezplatné)
     */
    private function send_lending_email($lending_id) {
        $lending = $this->get_lending($lending_id);
        if (!$lending) return;

        $lender = get_user_by('id', $lending->lender_id);
        $borrower = get_user_by('id', $lending->borrower_id);
        $book = KK_Database::get_row('books', array('id' => $lending->book_id));

        $subject = sprintf(__('[Komunitná Knižnica] Nové požičanie: %s', 'komunitna-kniznica'), $book->title);

        $message = sprintf(
            __("Dobrý deň %s,\n\n%s si požičal vašu knihu \"%s\".\n\nDátum požičania: %s\nDátum vrátenia: %s\n\nKontakt na požičiavajúceho: %s\n\nĎakujeme,\nKomunitná Knižnica", 'komunitna-kniznica'),
            $lender->display_name,
            $borrower->display_name,
            $book->title,
            date('d.m.Y', strtotime($lending->start_date)),
            date('d.m.Y', strtotime($lending->due_date)),
            $borrower->user_email
        );

        wp_mail($lender->user_email, $subject, $message);
    }

    /**
     * Odoslanie emailu o novom platenom požičaní s doručovacími údajmi
     */
    private function send_paid_lending_email($lending_id) {
        $lending = $this->get_lending($lending_id);
        if (!$lending) return;

        $lender = get_user_by('id', $lending->lender_id);
        $borrower = get_user_by('id', $lending->borrower_id);
        $book = KK_Database::get_row('books', array('id' => $lending->book_id));

        $subject = sprintf(__('[Komunitná Knižnica] Nové platené požičanie: %s', 'komunitna-kniznica'), $book->title);

        $message = sprintf(
            __("Dobrý deň %s,\n\n%s si požičal vašu knihu \"%s\" za %.2f €.\n\nVaša provízia: %.2f €\nProvízia komunity: %.2f €\n\nDORUČOVACIÉ ÚDAJE:\nMeno: %s\nAdresa: %s\nMesto: %s\nPSČ: %s\nKrajina: %s\nTelefón: %s\n\nDátum požičania: %s\nDátum vrátenia: %s\n\nProsím, pošlite knihu na uvedenú adresu.\n\nKontakt na požičiavajúceho: %s\n\nĎakujeme,\nKomunitná Knižnica", 'komunitna-kniznica'),
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

        wp_mail($lender->user_email, $subject, $message);
    }

    /**
     * Odoslanie denných upomienok
     */
    public function send_daily_reminders() {
        global $wpdb;
        $table = KK_Database::get_table_name('lendings');

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
        $book = KK_Database::get_row('books', array('id' => $lending->book_id));

        if ($type === 'upcoming') {
            $subject = __('[Komunitná Knižnica] Upomienka: Blíži sa termín vrátenia knihy', 'komunitna-kniznica');
            $message = sprintf(
                __("Dobrý deň %s,\n\nPripomíname, že knihu \"%s\" je potrebné vrátiť do 3 dní (do %s).\n\nĎakujeme,\nKomunitná Knižnica", 'komunitna-kniznica'),
                $borrower->display_name,
                $book->title,
                date('d.m.Y', strtotime($lending->due_date))
            );
        } else {
            $subject = __('[Komunitná Knižnica] NALIEHAVÉ: Omeškanie vrátenia knihy', 'komunitna-kniznica');
            $message = sprintf(
                __("Dobrý deň %s,\n\nKnihu \"%s\" ste mali vrátiť dňa %s. Prosím, vráťte ju čo najskôr.\n\nĎakujeme,\nKomunitná Knižnica", 'komunitna-kniznica'),
                $borrower->display_name,
                $book->title,
                date('d.m.Y', strtotime($lending->due_date))
            );
        }

        wp_mail($borrower->user_email, $subject, $message);

        // Vytvorenie notifikácie
        KK_Notifications::get_instance()->create_notification(
            $lending->borrower_id,
            $type === 'upcoming' ? 'due_soon' : 'overdue',
            $subject,
            $message
        );
    }

    /**
     * AJAX: Vrátenie knihy
     */
    public function ajax_return_book() {
        check_ajax_referer('kk_return_book_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $lending_id = absint($_POST['lending_id']);
        $result = $this->return_book($lending_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Kniha bola úspešne vrátená.', 'komunitna-kniznica')));
    }
}
