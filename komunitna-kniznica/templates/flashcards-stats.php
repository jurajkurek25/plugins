<?php
/**
 * Template: Flashcard štatistiky
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

// Výpočet pokroku
$progress_percent = $stats['total_cards'] > 0
    ? round(($stats['reviewing_cards'] / $stats['total_cards']) * 100)
    : 0;
?>

<div class="maruritka-flashcards-stats" data-deck-id="<?php echo esc_attr($deck_id); ?>">
    <!-- Header -->
    <div class="stats-header">
        <h2><?php echo esc_html($deck->title); ?> - Štatistiky</h2>
        <a href="<?php echo esc_url(remove_query_arg(array('deck_id', 'view'))); ?>" class="btn btn-secondary">
            ← Späť na zoznam
        </a>
    </div>

    <!-- Hlavné štatistiky -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['total_cards']); ?></span>
                <span class="stat-label">Celkovo kartičiek</span>
            </div>
        </div>

        <div class="stat-card stat-new">
            <div class="stat-icon">✨</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['new_cards']); ?></span>
                <span class="stat-label">Nové kartičky</span>
            </div>
        </div>

        <div class="stat-card stat-learning">
            <div class="stat-icon">📖</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['learning_cards']); ?></span>
                <span class="stat-label">Učím sa</span>
            </div>
        </div>

        <div class="stat-card stat-reviewing">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['reviewing_cards']); ?></span>
                <span class="stat-label">Naučené</span>
            </div>
        </div>

        <div class="stat-card stat-due">
            <div class="stat-icon">⏰</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['due_cards']); ?></span>
                <span class="stat-label">Na opakovanie dnes</span>
            </div>
        </div>

        <div class="stat-card stat-accuracy">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($stats['accuracy']); ?>%</span>
                <span class="stat-label">Úspešnosť</span>
            </div>
        </div>
    </div>

    <!-- Pokrok -->
    <div class="progress-section">
        <h3>Tvoj pokrok</h3>
        <div class="progress-visual">
            <div class="progress-circle">
                <svg viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="45" class="progress-bg"></circle>
                    <circle cx="50" cy="50" r="45" class="progress-bar"
                        style="stroke-dasharray: <?php echo esc_attr($progress_percent * 2.827); ?> 282.7"></circle>
                </svg>
                <div class="progress-text">
                    <span class="progress-percent"><?php echo esc_html($progress_percent); ?>%</span>
                    <span class="progress-label">naučené</span>
                </div>
            </div>

            <div class="progress-details">
                <div class="progress-bar-horizontal">
                    <div class="bar-segment bar-new" style="width: <?php
                        echo esc_attr($stats['total_cards'] > 0 ? ($stats['new_cards'] / $stats['total_cards']) * 100 : 0);
                    ?>%">
                        <span class="bar-label">Nové</span>
                    </div>
                    <div class="bar-segment bar-learning" style="width: <?php
                        echo esc_attr($stats['total_cards'] > 0 ? ($stats['learning_cards'] / $stats['total_cards']) * 100 : 0);
                    ?>%">
                        <span class="bar-label">Učím sa</span>
                    </div>
                    <div class="bar-segment bar-reviewing" style="width: <?php
                        echo esc_attr($stats['total_cards'] > 0 ? ($stats['reviewing_cards'] / $stats['total_cards']) * 100 : 0);
                    ?>%">
                        <span class="bar-label">Naučené</span>
                    </div>
                </div>

                <div class="progress-legend">
                    <div class="legend-item">
                        <span class="legend-color legend-new"></span>
                        <span class="legend-label">Nové: <?php echo esc_html($stats['new_cards']); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-learning"></span>
                        <span class="legend-label">Učím sa: <?php echo esc_html($stats['learning_cards']); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-reviewing"></span>
                        <span class="legend-label">Naučené: <?php echo esc_html($stats['reviewing_cards']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Učebná aktivita -->
    <div class="activity-section">
        <h3>Učebná aktivita</h3>
        <div class="activity-stats">
            <div class="activity-item">
                <span class="activity-label">Celkovo opakovaní:</span>
                <span class="activity-value"><?php echo esc_html($stats['total_reviews']); ?></span>
            </div>
            <div class="activity-item">
                <span class="activity-label">Správne odpovede:</span>
                <span class="activity-value"><?php echo esc_html($stats['correct_reviews']); ?></span>
            </div>
            <div class="activity-item">
                <span class="activity-label">Nesprávne odpovede:</span>
                <span class="activity-value"><?php echo esc_html($stats['total_reviews'] - $stats['correct_reviews']); ?></span>
            </div>
        </div>
    </div>

    <!-- Akcie -->
    <div class="stats-actions">
        <a href="<?php echo esc_url(add_query_arg('deck_id', $deck_id, remove_query_arg('view'))); ?>" class="btn btn-primary">
            <?php if ($stats['due_cards'] > 0): ?>
                Začať opakovanie (<?php echo esc_html($stats['due_cards']); ?>)
            <?php else: ?>
                Začať učenie
            <?php endif; ?>
        </a>

        <button type="button" class="btn btn-outline" id="reset-progress-btn">
            Resetovať progress
        </button>
    </div>
</div>
