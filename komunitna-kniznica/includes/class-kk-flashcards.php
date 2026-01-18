<?php
/**
 * Flashcards trieda - core funkcionalita pre Maruritku
 * Implementuje SM-2 algoritmus pre inteligentné opakovanie
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Flashcards {

    private static $instance = null;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konštruktor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializácia hooks
     */
    private function init_hooks() {
        // AJAX handlery
        add_action('wp_ajax_kk_get_cards_for_review', array($this, 'ajax_get_cards_for_review'));
        add_action('wp_ajax_kk_submit_card_review', array($this, 'ajax_submit_card_review'));
        add_action('wp_ajax_kk_get_deck_stats', array($this, 'ajax_get_deck_stats'));
        add_action('wp_ajax_kk_reset_deck_progress', array($this, 'ajax_reset_deck_progress'));

        // Admin AJAX
        add_action('wp_ajax_kk_create_deck', array($this, 'ajax_create_deck'));
        add_action('wp_ajax_kk_update_deck', array($this, 'ajax_update_deck'));
        add_action('wp_ajax_kk_delete_deck', array($this, 'ajax_delete_deck'));
        add_action('wp_ajax_kk_create_card', array($this, 'ajax_create_card'));
        add_action('wp_ajax_kk_update_card', array($this, 'ajax_update_card'));
        add_action('wp_ajax_kk_delete_card', array($this, 'ajax_delete_card'));
    }

    // ========================================
    // DECK MANAGEMENT
    // ========================================

    /**
     * Vytvorenie nového deck
     */
    public function create_deck($data) {
        global $wpdb;

        $defaults = array(
            'title' => '',
            'description' => '',
            'category' => '',
            'subject' => '',
            'difficulty' => 'medium',
            'is_public' => 1,
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        // Validácia
        if (empty($data['title'])) {
            return new WP_Error('missing_title', __('Názov balíčka je povinný', 'komunitna-kniznica'));
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'kk_flashcard_decks',
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => wp_kses_post($data['description']),
                'category' => sanitize_text_field($data['category']),
                'subject' => sanitize_text_field($data['subject']),
                'difficulty' => sanitize_text_field($data['difficulty']),
                'is_public' => (int) $data['is_public'],
                'created_by' => (int) $data['created_by'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Chyba pri vytváraní balíčka', 'komunitna-kniznica'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Získanie deck podľa ID
     */
    public function get_deck($deck_id) {
        global $wpdb;

        $deck = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kk_flashcard_decks WHERE id = %d",
            $deck_id
        ));

        if (!$deck) {
            return new WP_Error('deck_not_found', __('Balíček nebol nájdený', 'komunitna-kniznica'));
        }

        return $deck;
    }

    /**
     * Získanie všetkých decks
     */
    public function get_decks($args = array()) {
        global $wpdb;

        $defaults = array(
            'category' => '',
            'subject' => '',
            'difficulty' => '',
            'is_public' => 1,
            'created_by' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['category'])) {
            $where[] = 'category = %s';
            $values[] = $args['category'];
        }

        if (!empty($args['subject'])) {
            $where[] = 'subject = %s';
            $values[] = $args['subject'];
        }

        if (!empty($args['difficulty'])) {
            $where[] = 'difficulty = %s';
            $values[] = $args['difficulty'];
        }

        if ($args['is_public'] !== '') {
            $where[] = 'is_public = %d';
            $values[] = (int) $args['is_public'];
        }

        if (!empty($args['created_by'])) {
            $where[] = 'created_by = %d';
            $values[] = (int) $args['created_by'];
        }

        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        $query = "SELECT * FROM {$wpdb->prefix}kk_flashcard_decks WHERE {$where_clause} ORDER BY {$orderby}";

        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Update deck
     */
    public function update_deck($deck_id, $data) {
        global $wpdb;

        $update_data = array();
        $update_format = array();

        $allowed_fields = array(
            'title' => '%s',
            'description' => '%s',
            'category' => '%s',
            'subject' => '%s',
            'difficulty' => '%s',
            'is_public' => '%d'
        );

        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                if ($field === 'description') {
                    $update_data[$field] = wp_kses_post($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
                $update_format[] = $format;
            }
        }

        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';

        $result = $wpdb->update(
            $wpdb->prefix . 'kk_flashcard_decks',
            $update_data,
            array('id' => $deck_id),
            $update_format,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Zmazanie deck (a všetkých jeho kartičiek)
     */
    public function delete_deck($deck_id) {
        global $wpdb;

        // Vymazanie progress záznamov
        $wpdb->delete(
            $wpdb->prefix . 'kk_flashcard_progress',
            array('deck_id' => $deck_id),
            array('%d')
        );

        // Vymazanie kartičiek
        $wpdb->delete(
            $wpdb->prefix . 'kk_flashcards',
            array('deck_id' => $deck_id),
            array('%d')
        );

        // Vymazanie deck
        $result = $wpdb->delete(
            $wpdb->prefix . 'kk_flashcard_decks',
            array('id' => $deck_id),
            array('%d')
        );

        return $result !== false;
    }

    // ========================================
    // CARD MANAGEMENT
    // ========================================

    /**
     * Vytvorenie novej kartičky
     */
    public function create_card($data) {
        global $wpdb;

        $defaults = array(
            'deck_id' => 0,
            'question' => '',
            'answer' => '',
            'hint' => '',
            'explanation' => '',
            'card_order' => 0
        );

        $data = wp_parse_args($data, $defaults);

        // Validácia
        if (empty($data['deck_id'])) {
            return new WP_Error('missing_deck_id', __('Deck ID je povinné', 'komunitna-kniznica'));
        }

        if (empty($data['question']) || empty($data['answer'])) {
            return new WP_Error('missing_data', __('Otázka a odpoveď sú povinné', 'komunitna-kniznica'));
        }

        // Ak nie je card_order, nastav na koniec
        if ($data['card_order'] == 0) {
            $max_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(card_order) FROM {$wpdb->prefix}kk_flashcards WHERE deck_id = %d",
                $data['deck_id']
            ));
            $data['card_order'] = ($max_order !== null) ? $max_order + 1 : 1;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'kk_flashcards',
            array(
                'deck_id' => (int) $data['deck_id'],
                'question' => wp_kses_post($data['question']),
                'answer' => wp_kses_post($data['answer']),
                'hint' => wp_kses_post($data['hint']),
                'explanation' => wp_kses_post($data['explanation']),
                'card_order' => (int) $data['card_order'],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Chyba pri vytváraní kartičky', 'komunitna-kniznica'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Získanie kartičky podľa ID
     */
    public function get_card($card_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kk_flashcards WHERE id = %d",
            $card_id
        ));
    }

    /**
     * Získanie všetkých kartičiek v deck
     */
    public function get_cards($deck_id, $orderby = 'card_order', $order = 'ASC') {
        global $wpdb;

        $orderby = sanitize_sql_orderby($orderby . ' ' . $order);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kk_flashcards
            WHERE deck_id = %d
            ORDER BY {$orderby}",
            $deck_id
        ));
    }

    /**
     * Update kartičky
     */
    public function update_card($card_id, $data) {
        global $wpdb;

        $update_data = array();
        $update_format = array();

        $allowed_fields = array(
            'question' => '%s',
            'answer' => '%s',
            'hint' => '%s',
            'explanation' => '%s',
            'card_order' => '%d'
        );

        foreach ($allowed_fields as $field => $format) {
            if (isset($data[$field])) {
                $update_data[$field] = wp_kses_post($data[$field]);
                $update_format[] = $format;
            }
        }

        $update_data['updated_at'] = current_time('mysql');
        $update_format[] = '%s';

        $result = $wpdb->update(
            $wpdb->prefix . 'kk_flashcards',
            $update_data,
            array('id' => $card_id),
            $update_format,
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Zmazanie kartičky
     */
    public function delete_card($card_id) {
        global $wpdb;

        // Vymazanie progress záznamov
        $wpdb->delete(
            $wpdb->prefix . 'kk_flashcard_progress',
            array('card_id' => $card_id),
            array('%d')
        );

        // Vymazanie kartičky
        $result = $wpdb->delete(
            $wpdb->prefix . 'kk_flashcards',
            array('id' => $card_id),
            array('%d')
        );

        return $result !== false;
    }

    // ========================================
    // SM-2 SPACED REPETITION ALGORITHM
    // ========================================

    /**
     * Získanie kartičiek na opakovanie pre používateľa
     *
     * @param int $deck_id Deck ID
     * @param int $user_id User ID
     * @param int $limit Počet kartičiek
     * @return array Kartičky na opakovanie
     */
    public function get_cards_for_review($deck_id, $user_id, $limit = 20) {
        global $wpdb;

        $current_time = current_time('mysql');

        // Priorita:
        // 1. Nové kartičky (status = 'new')
        // 2. Kartičky na opakovanie (next_review <= teraz)
        // 3. Všetky ostatné (učiť sa dopredu)

        $query = "
            SELECT
                c.*,
                p.id as progress_id,
                p.easiness_factor,
                p.interval_days,
                p.repetitions,
                p.last_reviewed,
                p.next_review,
                p.status as progress_status,
                p.total_reviews,
                p.correct_reviews,
                CASE
                    WHEN p.id IS NULL THEN 1
                    WHEN p.next_review IS NULL THEN 2
                    WHEN p.next_review <= %s THEN 3
                    ELSE 4
                END as priority
            FROM {$wpdb->prefix}kk_flashcards c
            LEFT JOIN {$wpdb->prefix}kk_flashcard_progress p
                ON c.id = p.card_id AND p.user_id = %d
            WHERE c.deck_id = %d
            ORDER BY priority ASC, c.card_order ASC
            LIMIT %d
        ";

        return $wpdb->get_results($wpdb->prepare(
            $query,
            $current_time,
            $user_id,
            $deck_id,
            $limit
        ));
    }

    /**
     * Spracovanie odpovede používateľa a update progress pomocou SM-2
     *
     * @param int $card_id Card ID
     * @param int $user_id User ID
     * @param int $quality Hodnotenie kvality (0-5)
     *   0 - Úplne som zabudol
     *   1 - Nevedel som, ale po zobrazení som si spomenul
     *   2 - Ťažké, s veľkou námahou
     *   3 - Stredné, s menšou námahou
     *   4 - Ľahké
     *   5 - Veľmi ľahké, perfektné
     * @return bool|WP_Error
     */
    public function submit_review($card_id, $user_id, $quality) {
        global $wpdb;

        // Validácia quality
        $quality = (int) $quality;
        if ($quality < 0 || $quality > 5) {
            return new WP_Error('invalid_quality', __('Neplatné hodnotenie', 'komunitna-kniznica'));
        }

        // Získanie kartičky
        $card = $this->get_card($card_id);
        if (!$card) {
            return new WP_Error('card_not_found', __('Kartička nebola nájdená', 'komunitna-kniznica'));
        }

        // Získanie aktuálneho progress
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kk_flashcard_progress
            WHERE user_id = %d AND card_id = %d",
            $user_id,
            $card_id
        ));

        // SM-2 algoritmus
        $sm2_result = $this->calculate_sm2($progress, $quality);

        $current_time = current_time('mysql');

        // Update alebo vytvorenie progress záznamu
        if ($progress) {
            // Update existujúceho
            $wpdb->update(
                $wpdb->prefix . 'kk_flashcard_progress',
                array(
                    'easiness_factor' => $sm2_result['easiness_factor'],
                    'interval_days' => $sm2_result['interval_days'],
                    'repetitions' => $sm2_result['repetitions'],
                    'last_reviewed' => $current_time,
                    'next_review' => $sm2_result['next_review'],
                    'quality_rating' => $quality,
                    'total_reviews' => $progress->total_reviews + 1,
                    'correct_reviews' => $progress->correct_reviews + ($quality >= 3 ? 1 : 0),
                    'status' => $sm2_result['status'],
                    'updated_at' => $current_time
                ),
                array('id' => $progress->id),
                array('%f', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Vytvorenie nového
            $wpdb->insert(
                $wpdb->prefix . 'kk_flashcard_progress',
                array(
                    'user_id' => $user_id,
                    'card_id' => $card_id,
                    'deck_id' => $card->deck_id,
                    'easiness_factor' => $sm2_result['easiness_factor'],
                    'interval_days' => $sm2_result['interval_days'],
                    'repetitions' => $sm2_result['repetitions'],
                    'last_reviewed' => $current_time,
                    'next_review' => $sm2_result['next_review'],
                    'quality_rating' => $quality,
                    'total_reviews' => 1,
                    'correct_reviews' => ($quality >= 3 ? 1 : 0),
                    'status' => $sm2_result['status'],
                    'created_at' => $current_time,
                    'updated_at' => $current_time
                ),
                array('%d', '%d', '%d', '%f', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
            );
        }

        return true;
    }

    /**
     * SM-2 algoritmus výpočet
     *
     * @param object|null $progress Aktuálny progress
     * @param int $quality Hodnotenie kvality (0-5)
     * @return array SM-2 výsledok
     */
    private function calculate_sm2($progress, $quality) {
        // Default hodnoty pre novú kartičku
        $easiness_factor = 2.5;
        $interval_days = 0;
        $repetitions = 0;

        if ($progress) {
            $easiness_factor = (float) $progress->easiness_factor;
            $interval_days = (int) $progress->interval_days;
            $repetitions = (int) $progress->repetitions;
        }

        // SM-2 vzorec pre easiness factor
        // EF' = EF + (0.1 - (5 - q) * (0.08 + (5 - q) * 0.02))
        $new_ef = $easiness_factor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

        // EF musí byť minimálne 1.3
        if ($new_ef < 1.3) {
            $new_ef = 1.3;
        }

        // Ak je quality < 3, reset repetitions
        if ($quality < 3) {
            $new_repetitions = 0;
            $new_interval = 0;
            $status = 'learning';
        } else {
            // Quality >= 3, pokračuj v opakovaniach
            $new_repetitions = $repetitions + 1;

            // Výpočet nového intervalu
            if ($new_repetitions == 1) {
                $new_interval = 1; // 1 deň
            } elseif ($new_repetitions == 2) {
                $new_interval = 6; // 6 dní
            } else {
                $new_interval = round($interval_days * $new_ef);
            }

            $status = 'reviewing';
        }

        // Výpočet next_review dátumu
        $next_review = date('Y-m-d H:i:s', strtotime("+{$new_interval} days"));

        return array(
            'easiness_factor' => $new_ef,
            'interval_days' => $new_interval,
            'repetitions' => $new_repetitions,
            'next_review' => $next_review,
            'status' => $status
        );
    }

    /**
     * Získanie štatistík deck pre používateľa
     */
    public function get_deck_stats($deck_id, $user_id) {
        global $wpdb;

        $total_cards = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kk_flashcards WHERE deck_id = %d",
            $deck_id
        ));

        $progress_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as reviewed_cards,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_cards,
                SUM(CASE WHEN status = 'learning' THEN 1 ELSE 0 END) as learning_cards,
                SUM(CASE WHEN status = 'reviewing' THEN 1 ELSE 0 END) as reviewing_cards,
                SUM(CASE WHEN next_review <= NOW() THEN 1 ELSE 0 END) as due_cards,
                SUM(total_reviews) as total_reviews,
                SUM(correct_reviews) as correct_reviews
            FROM {$wpdb->prefix}kk_flashcard_progress
            WHERE deck_id = %d AND user_id = %d",
            $deck_id,
            $user_id
        ));

        $new_cards = (int) $total_cards - (int) $progress_stats->reviewed_cards;

        return array(
            'total_cards' => (int) $total_cards,
            'new_cards' => $new_cards,
            'learning_cards' => (int) $progress_stats->learning_cards,
            'reviewing_cards' => (int) $progress_stats->reviewing_cards,
            'due_cards' => (int) $progress_stats->due_cards,
            'total_reviews' => (int) $progress_stats->total_reviews,
            'correct_reviews' => (int) $progress_stats->correct_reviews,
            'accuracy' => $progress_stats->total_reviews > 0
                ? round(($progress_stats->correct_reviews / $progress_stats->total_reviews) * 100, 1)
                : 0
        );
    }

    /**
     * Reset progress pre deck
     */
    public function reset_deck_progress($deck_id, $user_id) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'kk_flashcard_progress',
            array(
                'deck_id' => $deck_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }

    // ========================================
    // AJAX HANDLERS
    // ========================================

    /**
     * AJAX: Získanie kartičiek na opakovanie
     */
    public function ajax_get_cards_for_review() {
        check_ajax_referer('kk_flashcards_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Musíte byť prihlásený'));
        }

        $deck_id = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;
        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 20;

        if (!$deck_id) {
            wp_send_json_error(array('message' => 'Neplatné deck ID'));
        }

        $cards = $this->get_cards_for_review($deck_id, get_current_user_id(), $limit);

        wp_send_json_success(array('cards' => $cards));
    }

    /**
     * AJAX: Odoslanie odpovede
     */
    public function ajax_submit_card_review() {
        check_ajax_referer('kk_flashcards_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Musíte byť prihlásený'));
        }

        $card_id = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;
        $quality = isset($_POST['quality']) ? (int) $_POST['quality'] : 0;

        if (!$card_id) {
            wp_send_json_error(array('message' => 'Neplatné card ID'));
        }

        $result = $this->submit_review($card_id, get_current_user_id(), $quality);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Odpoveď uložená'));
    }

    /**
     * AJAX: Získanie štatistík
     */
    public function ajax_get_deck_stats() {
        check_ajax_referer('kk_flashcards_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Musíte byť prihlásený'));
        }

        $deck_id = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;

        if (!$deck_id) {
            wp_send_json_error(array('message' => 'Neplatné deck ID'));
        }

        $stats = $this->get_deck_stats($deck_id, get_current_user_id());

        wp_send_json_success(array('stats' => $stats));
    }

    /**
     * AJAX: Reset progress
     */
    public function ajax_reset_deck_progress() {
        check_ajax_referer('kk_flashcards_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Musíte byť prihlásený'));
        }

        $deck_id = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;

        if (!$deck_id) {
            wp_send_json_error(array('message' => 'Neplatné deck ID'));
        }

        $result = $this->reset_deck_progress($deck_id, get_current_user_id());

        if ($result === false) {
            wp_send_json_error(array('message' => 'Chyba pri resetovaní progress'));
        }

        wp_send_json_success(array('message' => 'Progress resetovaný'));
    }

    /**
     * AJAX: Vytvorenie deck (admin)
     */
    public function ajax_create_deck() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $data = array(
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
            'subject' => isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '',
            'difficulty' => isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : 'medium',
            'is_public' => isset($_POST['is_public']) ? (int) $_POST['is_public'] : 1
        );

        $result = $this->create_deck($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('deck_id' => $result, 'message' => 'Deck vytvorený'));
    }

    /**
     * AJAX: Update deck (admin)
     */
    public function ajax_update_deck() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $deck_id = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;

        if (!$deck_id) {
            wp_send_json_error(array('message' => 'Neplatné deck ID'));
        }

        $data = array(
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
            'subject' => isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '',
            'difficulty' => isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : '',
            'is_public' => isset($_POST['is_public']) ? (int) $_POST['is_public'] : 1
        );

        $result = $this->update_deck($deck_id, $data);

        if (!$result) {
            wp_send_json_error(array('message' => 'Chyba pri aktualizácii'));
        }

        wp_send_json_success(array('message' => 'Deck aktualizovaný'));
    }

    /**
     * AJAX: Zmazanie deck (admin)
     */
    public function ajax_delete_deck() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $deck_id = isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0;

        if (!$deck_id) {
            wp_send_json_error(array('message' => 'Neplatné deck ID'));
        }

        $result = $this->delete_deck($deck_id);

        if (!$result) {
            wp_send_json_error(array('message' => 'Chyba pri mazaní'));
        }

        wp_send_json_success(array('message' => 'Deck zmazaný'));
    }

    /**
     * AJAX: Vytvorenie kartičky (admin)
     */
    public function ajax_create_card() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $data = array(
            'deck_id' => isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0,
            'question' => isset($_POST['question']) ? wp_kses_post($_POST['question']) : '',
            'answer' => isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '',
            'hint' => isset($_POST['hint']) ? wp_kses_post($_POST['hint']) : '',
            'explanation' => isset($_POST['explanation']) ? wp_kses_post($_POST['explanation']) : '',
            'card_order' => isset($_POST['card_order']) ? (int) $_POST['card_order'] : 0
        );

        $result = $this->create_card($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('card_id' => $result, 'message' => 'Kartička vytvorená'));
    }

    /**
     * AJAX: Update kartičky (admin)
     */
    public function ajax_update_card() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $card_id = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;

        if (!$card_id) {
            wp_send_json_error(array('message' => 'Neplatné card ID'));
        }

        $data = array(
            'question' => isset($_POST['question']) ? wp_kses_post($_POST['question']) : '',
            'answer' => isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '',
            'hint' => isset($_POST['hint']) ? wp_kses_post($_POST['hint']) : '',
            'explanation' => isset($_POST['explanation']) ? wp_kses_post($_POST['explanation']) : '',
            'card_order' => isset($_POST['card_order']) ? (int) $_POST['card_order'] : 0
        );

        $result = $this->update_card($card_id, $data);

        if (!$result) {
            wp_send_json_error(array('message' => 'Chyba pri aktualizácii'));
        }

        wp_send_json_success(array('message' => 'Kartička aktualizovaná'));
    }

    /**
     * AJAX: Zmazanie kartičky (admin)
     */
    public function ajax_delete_card() {
        check_ajax_referer('kk_admin_nonce', 'nonce');

        if (!current_user_can('manage_kk_library')) {
            wp_send_json_error(array('message' => 'Nemáte oprávnenie'));
        }

        $card_id = isset($_POST['card_id']) ? (int) $_POST['card_id'] : 0;

        if (!$card_id) {
            wp_send_json_error(array('message' => 'Neplatné card ID'));
        }

        $result = $this->delete_card($card_id);

        if (!$result) {
            wp_send_json_error(array('message' => 'Chyba pri mazaní'));
        }

        wp_send_json_success(array('message' => 'Kartička zmazaná'));
    }
}
