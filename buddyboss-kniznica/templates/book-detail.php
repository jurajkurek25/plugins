<?php
/**
 * Template: Detail knihy
 */

if (!defined('ABSPATH')) {
    exit;
}

$book = BBK_Book::get_instance()->get_book($book_id);

if (!$book) {
    echo '<p>' . __('Kniha nebola nájdená.', 'buddyboss-kniznica') . '</p>';
    return;
}

$owner = get_user_by('id', $book->user_id);
$rating_data = BBK_Rating::get_instance()->get_average_rating($book->id);
$ratings = BBK_Rating::get_instance()->get_book_ratings($book->id, 10);
$is_logged_in = is_user_logged_in();
$user_id = get_current_user_id();
$is_owner = ($user_id == $book->user_id);
?>

<div class="bbk-book-detail-wrapper">
    <div class="bbk-book-detail-header">
        <div class="bbk-book-detail-image">
            <?php if ($book->image_url): ?>
                <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
            <?php else: ?>
                <div class="bbk-book-placeholder-large">📚</div>
            <?php endif; ?>
        </div>

        <div class="bbk-book-detail-info">
            <h1><?php echo esc_html($book->title); ?></h1>
            <p class="bbk-author">✍️ <?php _e('Autor:', 'buddyboss-kniznica'); ?> <strong><?php echo esc_html($book->author); ?></strong></p>
            <p class="bbk-genre">📖 <?php _e('Žáner:', 'buddyboss-kniznica'); ?> <?php echo esc_html($book->genre); ?></p>

            <?php if ($book->isbn): ?>
                <p class="bbk-isbn">📄 ISBN: <?php echo esc_html($book->isbn); ?></p>
            <?php endif; ?>

            <div class="bbk-meta-row">
                <span class="bbk-condition">
                    <?php _e('Stav knihy:', 'buddyboss-kniznica'); ?> <strong><?php echo esc_html($book->book_condition); ?>/10</strong>
                </span>

                <span class="bbk-status bbk-status-<?php echo esc_attr($book->status); ?>">
                    <?php
                    if ($book->status === 'available') echo '✅ ' . __('Dostupná', 'buddyboss-kniznica');
                    elseif ($book->status === 'reserved') echo '⏳ ' . __('Rezervovaná', 'buddyboss-kniznica');
                    else echo '📖 ' . __('Požičaná', 'buddyboss-kniznica');
                    ?>
                </span>
            </div>

            <?php if ($rating_data['count'] > 0): ?>
                <div class="bbk-rating-summary">
                    <div class="bbk-stars-large"><?php echo str_repeat('⭐', round($rating_data['average'])); ?></div>
                    <p><?php echo esc_html($rating_data['average']); ?> / 5 (<?php echo esc_html($rating_data['count']); ?> <?php _e('hodnotení', 'buddyboss-kniznica'); ?>)</p>
                </div>
            <?php endif; ?>

            <div class="bbk-price-box">
                <?php if ($book->lending_price > 0): ?>
                    <div class="bbk-price-paid">
                        <span class="bbk-price-label">💰 <?php _e('Cena požičania:', 'buddyboss-kniznica'); ?></span>
                        <span class="bbk-price-value"><?php echo number_format($book->lending_price, 2); ?> €</span>
                        <small class="bbk-commission-info">
                            (70% = <?php echo number_format($book->lending_price * 0.7, 2); ?> € majiteľovi,
                            30% = <?php echo number_format($book->lending_price * 0.3, 2); ?> € komunite)
                        </small>
                    </div>
                <?php else: ?>
                    <div class="bbk-price-free">
                        <span class="bbk-price-label">🎁 <?php _e('Požičanie:', 'buddyboss-kniznica'); ?></span>
                        <span class="bbk-price-value"><?php _e('ZADARMO', 'buddyboss-kniznica'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bbk-owner-info">
                <p>👤 <?php _e('Majiteľ:', 'buddyboss-kniznica'); ?> <strong><?php echo esc_html($owner->display_name); ?></strong></p>
            </div>

            <!-- Akčné tlačidlá -->
            <div class="bbk-actions">
                <?php if (!$is_logged_in): ?>
                    <p class="bbk-login-notice">🔐 <?php _e('Pre požičanie knihy sa musíte prihlásiť.', 'buddyboss-kniznica'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="bbk-btn bbk-btn-primary bbk-btn-large">
                        <?php _e('Prihlásiť sa', 'buddyboss-kniznica'); ?>
                    </a>
                <?php elseif ($is_owner): ?>
                    <p class="bbk-owner-notice">📚 <?php _e('Toto je vaša kniha.', 'buddyboss-kniznica'); ?></p>
                <?php elseif ($book->status !== 'available'): ?>
                    <p class="bbk-unavailable-notice">❌ <?php _e('Kniha nie je dostupná na požičanie.', 'buddyboss-kniznica'); ?></p>
                <?php else: ?>
                    <?php if ($book->lending_price > 0): ?>
                        <!-- Platené požičanie cez košík -->
                        <button class="bbk-btn bbk-btn-primary bbk-btn-large bbk-add-to-cart" data-book-id="<?php echo $book->id; ?>">
                            🛒 <?php _e('Pridať do košíka', 'buddyboss-kniznica'); ?>
                        </button>
                    <?php else: ?>
                        <!-- Bezplatné požičanie -->
                        <button class="bbk-btn bbk-btn-primary bbk-btn-large bbk-borrow-book" data-book-id="<?php echo $book->id; ?>">
                            🤝 <?php _e('Požičať zadarmo', 'buddyboss-kniznica'); ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Popis knihy -->
    <?php if ($book->description): ?>
        <div class="bbk-book-description">
            <h3><?php _e('Popis', 'buddyboss-kniznica'); ?></h3>
            <p><?php echo nl2br(esc_html($book->description)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Hodnotenia a recenzie -->
    <div class="bbk-ratings-section">
        <h3>⭐ <?php _e('Hodnotenia a recenzie', 'buddyboss-kniznica'); ?></h3>

        <?php if (empty($ratings)): ?>
            <p><?php _e('Táto kniha zatiaľ nemá žiadne hodnotenia.', 'buddyboss-kniznica'); ?></p>
        <?php else: ?>
            <div class="bbk-ratings-list">
                <?php foreach ($ratings as $rating): ?>
                    <?php $reviewer = get_user_by('id', $rating->user_id); ?>
                    <div class="bbk-rating-item">
                        <div class="bbk-rating-header">
                            <span class="bbk-rating-author">👤 <?php echo esc_html($reviewer->display_name); ?></span>
                            <span class="bbk-rating-stars"><?php echo str_repeat('⭐', $rating->rating); ?></span>
                            <span class="bbk-rating-date"><?php echo date('d.m.Y', strtotime($rating->created_at)); ?></span>
                        </div>
                        <?php if ($rating->review): ?>
                            <div class="bbk-rating-review">
                                <p><?php echo esc_html($rating->review); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
