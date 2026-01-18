<?php
/**
 * Template: Flashcard deck - učenie
 * Variables: $deck_id
 */

if (!defined('ABSPATH')) {
    exit;
}

// Získanie flashcards instance
$flashcards = KK_Flashcards::get_instance();

// Získanie deck
$deck = $flashcards->get_deck($deck_id);
if (is_wp_error($deck)) {
    echo '<p>' . esc_html($deck->get_error_message()) . '</p>';
    return;
}

$user_id = get_current_user_id();

// Získanie štatistík
$stats = $flashcards->get_deck_stats($deck_id, $user_id);

// Získanie kartičiek na opakovanie
$cards = $flashcards->get_cards_for_review($deck_id, $user_id, 50);
?>

<div class="maruritka-flashcards-deck" data-deck-id="<?php echo esc_attr($deck_id); ?>">
    <!-- Header -->
    <div class="deck-header">
        <div class="deck-title">
            <h2><?php echo esc_html($deck->title); ?></h2>
            <?php if ($deck->description): ?>
                <p class="deck-description"><?php echo esc_html($deck->description); ?></p>
            <?php endif; ?>
        </div>

        <div class="deck-progress-header">
            <div class="progress-stats">
                <span class="stat">
                    <strong><?php echo esc_html($stats['new_cards']); ?></strong> nové
                </span>
                <span class="stat">
                    <strong><?php echo esc_html($stats['learning_cards']); ?></strong> učím sa
                </span>
                <span class="stat">
                    <strong><?php echo esc_html($stats['reviewing_cards']); ?></strong> naučené
                </span>
                <span class="stat">
                    <strong><?php echo esc_html($stats['due_cards']); ?></strong> na opakovanie
                </span>
            </div>
        </div>
    </div>

    <?php if (empty($cards)): ?>
        <!-- Žiadne kartičky na opakovanie -->
        <div class="no-cards-due">
            <div class="success-icon">✓</div>
            <h3>Výborne! Máte všetko naučené!</h3>
            <p>Nemáte žiadne kartičky na opakovanie práve teraz.</p>
            <p>Ďalšia kartička bude k dispozícii neskôr.</p>

            <div class="stats-summary">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['total_cards']); ?></span>
                    <span class="stat-label">Celkovo kartičiek</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['reviewing_cards']); ?></span>
                    <span class="stat-label">Naučené</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['accuracy']); ?>%</span>
                    <span class="stat-label">Úspešnosť</span>
                </div>
            </div>

            <div class="actions">
                <a href="<?php echo esc_url(remove_query_arg('deck_id')); ?>" class="btn btn-secondary">
                    ← Späť na zoznam
                </a>
                <button type="button" class="btn btn-outline" id="reset-progress-btn">
                    Resetovať progress
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Flashcard učenie interface -->
        <div class="flashcard-container">
            <!-- Progress bar -->
            <div class="session-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="session-progress-fill"></div>
                </div>
                <div class="progress-text">
                    <span id="current-card-num">0</span> / <span id="total-cards-num"><?php echo count($cards); ?></span>
                </div>
            </div>

            <!-- Flashcard -->
            <div class="flashcard" id="flashcard">
                <div class="flashcard-inner">
                    <!-- Predná strana - otázka -->
                    <div class="flashcard-front">
                        <div class="card-label">Otázka</div>
                        <div class="card-content" id="card-question"></div>
                        <div class="card-hint" id="card-hint" style="display: none;">
                            <strong>Tip:</strong> <span id="card-hint-text"></span>
                        </div>
                        <button type="button" class="btn btn-primary btn-show-answer" id="show-answer-btn">
                            Zobraziť odpoveď
                        </button>
                    </div>

                    <!-- Zadná strana - odpoveď -->
                    <div class="flashcard-back">
                        <div class="card-label">Odpoveď</div>
                        <div class="card-content" id="card-answer"></div>
                        <div class="card-explanation" id="card-explanation" style="display: none;">
                            <strong>Vysvetlenie:</strong>
                            <div id="card-explanation-text"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating buttons (SM-2 quality) -->
            <div class="rating-buttons" id="rating-buttons" style="display: none;">
                <p class="rating-question">Ako dobre si pamätáš odpoveď?</p>
                <div class="rating-options">
                    <button type="button" class="rating-btn rating-0" data-quality="0">
                        <span class="rating-label">Zabudol som</span>
                        <span class="rating-desc">Úplne som zabudol</span>
                    </button>
                    <button type="button" class="rating-btn rating-2" data-quality="2">
                        <span class="rating-label">Ťažké</span>
                        <span class="rating-desc">S veľkou námahou</span>
                    </button>
                    <button type="button" class="rating-btn rating-3" data-quality="3">
                        <span class="rating-label">Stredné</span>
                        <span class="rating-desc">S menšou námahou</span>
                    </button>
                    <button type="button" class="rating-btn rating-4" data-quality="4">
                        <span class="rating-label">Ľahké</span>
                        <span class="rating-desc">Bez problémov</span>
                    </button>
                    <button type="button" class="rating-btn rating-5" data-quality="5">
                        <span class="rating-label">Perfektné</span>
                        <span class="rating-desc">Hneď a presne</span>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="card-navigation">
                <button type="button" class="btn btn-text" id="show-hint-btn">
                    💡 Zobraziť tip
                </button>
                <button type="button" class="btn btn-text" id="show-explanation-btn" style="display: none;">
                    📖 Zobraziť vysvetlenie
                </button>
            </div>
        </div>

        <!-- Session complete -->
        <div class="session-complete" id="session-complete" style="display: none;">
            <div class="success-icon">🎉</div>
            <h3>Session dokončená!</h3>
            <p>Skvelá práca! Precvičil si <strong id="completed-count">0</strong> kartičiek.</p>

            <div class="session-stats" id="session-stats">
                <!-- Stats will be filled by JavaScript -->
            </div>

            <div class="actions">
                <a href="<?php echo esc_url(remove_query_arg('deck_id')); ?>" class="btn btn-secondary">
                    ← Späť na zoznam
                </a>
                <button type="button" class="btn btn-primary" id="continue-learning-btn">
                    Pokračovať v učení
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hidden data for JavaScript -->
    <script type="application/json" id="flashcard-data">
        <?php echo wp_json_encode(array(
            'deck_id' => $deck_id,
            'cards' => $cards,
            'stats' => $stats
        )); ?>
    </script>
</div>
