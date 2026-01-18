<?php
/**
 * Template: Zoznam flashcard decks
 * Variables: $category, $subject, $difficulty
 */

if (!defined('ABSPATH')) {
    exit;
}

// Získanie flashcards instance
$flashcards = KK_Flashcards::get_instance();

// Získanie decks
$args = array(
    'category' => $category ?? '',
    'subject' => $subject ?? '',
    'difficulty' => $difficulty ?? '',
    'is_public' => 1
);

$decks = $flashcards->get_decks($args);
$user_id = get_current_user_id();
?>

<div class="maruritka-flashcards-list">
    <div class="flashcards-header">
        <h2>Balíčky kartičiek</h2>
        <p>Vyber si balíček a začni sa učiť s inteligentným opakovaním</p>
    </div>

    <?php if (empty($decks)): ?>
        <div class="no-decks">
            <p>Zatiaľ nie sú k dispozícii žiadne balíčky kartičiek.</p>
        </div>
    <?php else: ?>
        <div class="decks-grid">
            <?php foreach ($decks as $deck):
                // Získanie štatistík pre prihláseného používateľa
                $stats = null;
                if ($user_id) {
                    $stats = $flashcards->get_deck_stats($deck->id, $user_id);
                }

                // Výpočet progress %
                $progress_percent = 0;
                if ($stats && $stats['total_cards'] > 0) {
                    $learned = $stats['reviewing_cards'];
                    $progress_percent = round(($learned / $stats['total_cards']) * 100);
                }
            ?>
            <div class="deck-card" data-deck-id="<?php echo esc_attr($deck->id); ?>">
                <div class="deck-card-header">
                    <h3><?php echo esc_html($deck->title); ?></h3>
                    <?php if ($deck->difficulty): ?>
                        <span class="difficulty-badge difficulty-<?php echo esc_attr($deck->difficulty); ?>">
                            <?php echo esc_html(ucfirst($deck->difficulty)); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($deck->description): ?>
                    <div class="deck-description">
                        <?php echo wp_kses_post($deck->description); ?>
                    </div>
                <?php endif; ?>

                <div class="deck-meta">
                    <?php if ($deck->category): ?>
                        <span class="meta-item">
                            <strong>Kategória:</strong> <?php echo esc_html($deck->category); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($deck->subject): ?>
                        <span class="meta-item">
                            <strong>Predmet:</strong> <?php echo esc_html($deck->subject); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($stats): ?>
                    <div class="deck-stats">
                        <div class="stat-item">
                            <span class="stat-label">Celkovo kartičiek:</span>
                            <span class="stat-value"><?php echo esc_html($stats['total_cards']); ?></span>
                        </div>

                        <div class="stat-item">
                            <span class="stat-label">Nové:</span>
                            <span class="stat-value stat-new"><?php echo esc_html($stats['new_cards']); ?></span>
                        </div>

                        <div class="stat-item">
                            <span class="stat-label">Na opakovanie:</span>
                            <span class="stat-value stat-due"><?php echo esc_html($stats['due_cards']); ?></span>
                        </div>

                        <div class="stat-item">
                            <span class="stat-label">Úspešnosť:</span>
                            <span class="stat-value"><?php echo esc_html($stats['accuracy']); ?>%</span>
                        </div>

                        <?php if ($progress_percent > 0): ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
                                <span class="progress-text"><?php echo esc_html($progress_percent); ?>% naučené</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="deck-actions">
                    <?php if ($user_id): ?>
                        <a href="<?php echo esc_url(add_query_arg('deck_id', $deck->id, get_permalink())); ?>" class="btn btn-primary">
                            <?php if ($stats && $stats['due_cards'] > 0): ?>
                                Opakovať (<?php echo esc_html($stats['due_cards']); ?>)
                            <?php else: ?>
                                Začať učenie
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(array('deck_id' => $deck->id, 'view' => 'stats'), get_permalink())); ?>" class="btn btn-secondary">
                            Štatistiky
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">
                            Prihláste sa na učenie
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
