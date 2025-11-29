<?php
/**
 * Template: Moje knihy
 */

if (!defined('ABSPATH')) {
    exit;
}

// $my_books je už definované v shortcode
?>

<div class="bbk-my-books-wrapper">
    <div class="bbk-my-books-header">
        <h2>📚 <?php _e('Moje knihy', 'buddyboss-kniznica'); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('action', 'add-book')); ?>" class="bbk-btn bbk-btn-primary">
            ➕ <?php _e('Pridať novú knihu', 'buddyboss-kniznica'); ?>
        </a>
    </div>

    <?php if (empty($my_books)): ?>
        <div class="bbk-empty-state">
            <p><?php _e('Nemáte žiadne knihy v knižnici.', 'buddyboss-kniznica'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('action', 'add-book')); ?>" class="bbk-btn bbk-btn-primary bbk-btn-large">
                ➕ <?php _e('Pridať prvú knihu', 'buddyboss-kniznica'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="bbk-my-books-grid">
            <?php foreach ($my_books as $book): ?>
                <?php
                $rating_data = BBK_Rating::get_instance()->get_average_rating($book->id);
                $detail_url = add_query_arg('book_id', $book->id, home_url('/detail-knihy/'));
                ?>
                <div class="bbk-my-book-card">
                    <div class="bbk-my-book-image">
                        <?php if ($book->image_url): ?>
                            <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
                        <?php else: ?>
                            <div class="bbk-book-placeholder">📚</div>
                        <?php endif; ?>
                        <span class="bbk-status-badge bbk-status-<?php echo esc_attr($book->status); ?>">
                            <?php
                            if ($book->status === 'available') echo '✅ ' . __('Dostupná', 'buddyboss-kniznica');
                            elseif ($book->status === 'reserved') echo '⏳ ' . __('Rezervovaná', 'buddyboss-kniznica');
                            else echo '📖 ' . __('Požičaná', 'buddyboss-kniznica');
                            ?>
                        </span>
                    </div>

                    <div class="bbk-my-book-info">
                        <h3><?php echo esc_html($book->title); ?></h3>
                        <p class="bbk-author">✍️ <?php echo esc_html($book->author); ?></p>
                        <p class="bbk-genre">📖 <?php echo esc_html($book->genre); ?></p>

                        <?php if ($rating_data['count'] > 0): ?>
                            <div class="bbk-rating">
                                <span class="bbk-stars"><?php echo str_repeat('⭐', round($rating_data['average'])); ?></span>
                                <span><?php echo esc_html($rating_data['average']); ?> (<?php echo esc_html($rating_data['count']); ?>)</span>
                            </div>
                        <?php endif; ?>

                        <div class="bbk-price">
                            <?php if ($book->lending_price > 0): ?>
                                💰 <?php echo number_format($book->lending_price, 2); ?> €
                                <small>(Vy: <?php echo number_format($book->lending_price * 0.7, 2); ?> €)</small>
                            <?php else: ?>
                                🎁 <?php _e('Zadarmo', 'buddyboss-kniznica'); ?>
                            <?php endif; ?>
                        </div>

                        <div class="bbk-my-book-actions">
                            <a href="<?php echo esc_url($detail_url); ?>" class="bbk-btn bbk-btn-secondary bbk-btn-small">
                                <?php _e('Detail', 'buddyboss-kniznica'); ?>
                            </a>
                            <button class="bbk-btn bbk-btn-secondary bbk-btn-small bbk-delete-book" data-book-id="<?php echo $book->id; ?>">
                                🗑️ <?php _e('Zmazať', 'buddyboss-kniznica'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
