<?php
/**
 * Admin trieda pre Flashcards
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_Admin_Flashcards {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Constructor
    }

    /**
     * Zobrazenie admin stránky
     */
    public static function display_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'decks';

        ?>
        <div class="wrap kk-admin-wrap">
            <h1><?php _e('Maruritka Flashcards', 'komunitna-kniznica'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=kk-flashcards&tab=decks" class="nav-tab <?php echo $tab === 'decks' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Balíčky', 'komunitna-kniznica'); ?>
                </a>
                <a href="?page=kk-flashcards&tab=cards" class="nav-tab <?php echo $tab === 'cards' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Kartičky', 'komunitna-kniznica'); ?>
                </a>
            </nav>

            <div class="kk-admin-content">
                <?php
                if ($tab === 'decks') {
                    self::display_decks_tab();
                } elseif ($tab === 'cards') {
                    self::display_cards_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Balíčky
     */
    private static function display_decks_tab() {
        $flashcards = KK_Flashcards::get_instance();
        $decks = $flashcards->get_decks(array('orderby' => 'created_at', 'order' => 'DESC'));

        ?>
        <div class="kk-decks-admin">
            <div class="kk-admin-header">
                <h2><?php _e('Balíčky kartičiek', 'komunitna-kniznica'); ?></h2>
                <button type="button" class="button button-primary" id="add-deck-btn">
                    <?php _e('+ Pridať balíček', 'komunitna-kniznica'); ?>
                </button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php _e('Názov', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Kategória', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Predmet', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Obtiažnosť', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Kartičky', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Verejný', 'komunitna-kniznica'); ?></th>
                        <th><?php _e('Akcie', 'komunitna-kniznica'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($decks)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">
                                <?php _e('Zatiaľ nie sú žiadne balíčky.', 'komunitna-kniznica'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($decks as $deck):
                            $cards_count = $flashcards->get_cards($deck->id);
                            $cards_count = count($cards_count);
                        ?>
                        <tr data-deck-id="<?php echo esc_attr($deck->id); ?>">
                            <td><?php echo esc_html($deck->id); ?></td>
                            <td><strong><?php echo esc_html($deck->title); ?></strong></td>
                            <td><?php echo esc_html($deck->category); ?></td>
                            <td><?php echo esc_html($deck->subject); ?></td>
                            <td>
                                <span class="difficulty-badge difficulty-<?php echo esc_attr($deck->difficulty); ?>">
                                    <?php echo esc_html(ucfirst($deck->difficulty)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($cards_count); ?></td>
                            <td>
                                <?php echo $deck->is_public ? '✓' : '✗'; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small edit-deck-btn" data-deck-id="<?php echo esc_attr($deck->id); ?>">
                                    <?php _e('Upraviť', 'komunitna-kniznica'); ?>
                                </button>
                                <button type="button" class="button button-small delete-deck-btn" data-deck-id="<?php echo esc_attr($deck->id); ?>">
                                    <?php _e('Zmazať', 'komunitna-kniznica'); ?>
                                </button>
                                <a href="?page=kk-flashcards&tab=cards&deck_id=<?php echo esc_attr($deck->id); ?>" class="button button-small">
                                    <?php _e('Kartičky', 'komunitna-kniznica'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: Add/Edit Deck -->
        <div id="deck-modal" class="kk-modal" style="display: none;">
            <div class="kk-modal-content">
                <div class="kk-modal-header">
                    <h2 id="deck-modal-title"><?php _e('Pridať balíček', 'komunitna-kniznica'); ?></h2>
                    <button type="button" class="kk-modal-close">&times;</button>
                </div>
                <div class="kk-modal-body">
                    <form id="deck-form">
                        <input type="hidden" id="deck-id" name="deck_id" value="">

                        <div class="form-group">
                            <label for="deck-title"><?php _e('Názov balíčka', 'komunitna-kniznica'); ?> *</label>
                            <input type="text" id="deck-title" name="title" class="widefat" required>
                        </div>

                        <div class="form-group">
                            <label for="deck-description"><?php _e('Popis', 'komunitna-kniznica'); ?></label>
                            <textarea id="deck-description" name="description" class="widefat" rows="3"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="deck-category"><?php _e('Kategória', 'komunitna-kniznica'); ?></label>
                                <input type="text" id="deck-category" name="category" class="widefat" placeholder="napr. Slovenčina">
                            </div>

                            <div class="form-group">
                                <label for="deck-subject"><?php _e('Predmet', 'komunitna-kniznica'); ?></label>
                                <input type="text" id="deck-subject" name="subject" class="widefat" placeholder="napr. Gramatika">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="deck-difficulty"><?php _e('Obtiažnosť', 'komunitna-kniznica'); ?></label>
                                <select id="deck-difficulty" name="difficulty" class="widefat">
                                    <option value="easy"><?php _e('Ľahká', 'komunitna-kniznica'); ?></option>
                                    <option value="medium" selected><?php _e('Stredná', 'komunitna-kniznica'); ?></option>
                                    <option value="hard"><?php _e('Ťažká', 'komunitna-kniznica'); ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="deck-is-public">
                                    <input type="checkbox" id="deck-is-public" name="is_public" value="1" checked>
                                    <?php _e('Verejný balíček', 'komunitna-kniznica'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Uložiť', 'komunitna-kniznica'); ?>
                            </button>
                            <button type="button" class="button kk-modal-close">
                                <?php _e('Zrušiť', 'komunitna-kniznica'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Kartičky
     */
    private static function display_cards_tab() {
        $flashcards = KK_Flashcards::get_instance();
        $deck_id = isset($_GET['deck_id']) ? absint($_GET['deck_id']) : 0;

        // Získanie všetkých decks pre select
        $decks = $flashcards->get_decks(array('orderby' => 'title', 'order' => 'ASC'));

        $cards = array();
        $current_deck = null;

        if ($deck_id) {
            $current_deck = $flashcards->get_deck($deck_id);
            if (!is_wp_error($current_deck)) {
                $cards = $flashcards->get_cards($deck_id);
            }
        }

        ?>
        <div class="kk-cards-admin">
            <div class="kk-admin-header">
                <h2><?php _e('Kartičky', 'komunitna-kniznica'); ?></h2>

                <div class="deck-selector">
                    <label for="deck-select"><?php _e('Vyber balíček:', 'komunitna-kniznica'); ?></label>
                    <select id="deck-select" onchange="window.location.href='?page=kk-flashcards&tab=cards&deck_id=' + this.value">
                        <option value=""><?php _e('-- Vyber balíček --', 'komunitna-kniznica'); ?></option>
                        <?php foreach ($decks as $deck): ?>
                            <option value="<?php echo esc_attr($deck->id); ?>" <?php selected($deck_id, $deck->id); ?>>
                                <?php echo esc_html($deck->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($deck_id): ?>
                    <button type="button" class="button button-primary" id="add-card-btn" data-deck-id="<?php echo esc_attr($deck_id); ?>">
                        <?php _e('+ Pridať kartičku', 'komunitna-kniznica'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($current_deck && !is_wp_error($current_deck)): ?>
                <div class="deck-info">
                    <h3><?php echo esc_html($current_deck->title); ?></h3>
                    <p><?php echo esc_html($current_deck->description); ?></p>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th width="50"><?php _e('Poradie', 'komunitna-kniznica'); ?></th>
                            <th><?php _e('Otázka', 'komunitna-kniznica'); ?></th>
                            <th><?php _e('Odpoveď', 'komunitna-kniznica'); ?></th>
                            <th width="150"><?php _e('Akcie', 'komunitna-kniznica'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">
                                    <?php _e('Zatiaľ nie sú žiadne kartičky v tomto balíčku.', 'komunitna-kniznica'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cards as $card): ?>
                            <tr data-card-id="<?php echo esc_attr($card->id); ?>">
                                <td><?php echo esc_html($card->id); ?></td>
                                <td><?php echo esc_html($card->card_order); ?></td>
                                <td><?php echo wp_kses_post(wp_trim_words($card->question, 10)); ?></td>
                                <td><?php echo wp_kses_post(wp_trim_words($card->answer, 10)); ?></td>
                                <td>
                                    <button type="button" class="button button-small edit-card-btn" data-card-id="<?php echo esc_attr($card->id); ?>">
                                        <?php _e('Upraviť', 'komunitna-kniznica'); ?>
                                    </button>
                                    <button type="button" class="button button-small delete-card-btn" data-card-id="<?php echo esc_attr($card->id); ?>">
                                        <?php _e('Zmazať', 'komunitna-kniznica'); ?>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php elseif ($deck_id): ?>
                <p><?php _e('Balíček nebol nájdený.', 'komunitna-kniznica'); ?></p>
            <?php else: ?>
                <p><?php _e('Prosím, vyber balíček.', 'komunitna-kniznica'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Modal: Add/Edit Card -->
        <div id="card-modal" class="kk-modal" style="display: none;">
            <div class="kk-modal-content kk-modal-large">
                <div class="kk-modal-header">
                    <h2 id="card-modal-title"><?php _e('Pridať kartičku', 'komunitna-kniznica'); ?></h2>
                    <button type="button" class="kk-modal-close">&times;</button>
                </div>
                <div class="kk-modal-body">
                    <form id="card-form">
                        <input type="hidden" id="card-id" name="card_id" value="">
                        <input type="hidden" id="card-deck-id" name="deck_id" value="<?php echo esc_attr($deck_id); ?>">

                        <div class="form-group">
                            <label for="card-question"><?php _e('Otázka', 'komunitna-kniznica'); ?> *</label>
                            <textarea id="card-question" name="question" class="widefat" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="card-answer"><?php _e('Odpoveď', 'komunitna-kniznica'); ?> *</label>
                            <textarea id="card-answer" name="answer" class="widefat" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="card-hint"><?php _e('Tip (voliteľné)', 'komunitna-kniznica'); ?></label>
                            <textarea id="card-hint" name="hint" class="widefat" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="card-explanation"><?php _e('Vysvetlenie (voliteľné)', 'komunitna-kniznica'); ?></label>
                            <textarea id="card-explanation" name="explanation" class="widefat" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="card-order"><?php _e('Poradie', 'komunitna-kniznica'); ?></label>
                            <input type="number" id="card-order" name="card_order" class="widefat" min="0" value="0">
                            <p class="description"><?php _e('Nechaj 0 pre automatické poradie na koniec', 'komunitna-kniznica'); ?></p>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Uložiť', 'komunitna-kniznica'); ?>
                            </button>
                            <button type="button" class="button kk-modal-close">
                                <?php _e('Zrušiť', 'komunitna-kniznica'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
