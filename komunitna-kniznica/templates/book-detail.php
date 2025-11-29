<?php
/**
 * Template: Detail knihy
 */

if (!defined('ABSPATH')) {
    exit;
}

$book = KK_Book::get_instance()->get_book($book_id);

if (!$book) {
    echo '<p>' . __('Kniha nebola nájdená.', 'komunitna-kniznica') . '</p>';
    return;
}

$owner = get_user_by('id', $book->user_id);
$rating_data = KK_Rating::get_instance()->get_average_rating($book->id);
$ratings = KK_Rating::get_instance()->get_book_ratings_with_users($book->id);
$user_id = is_user_logged_in() ? get_current_user_id() : null;
$can_borrow = KK_Auth::can_borrow_book($book->id, $user_id);
?>

<div class="kk-book-detail-wrapper">
    <div class="kk-book-detail-header">
        <div class="kk-book-detail-image">
            <?php if ($book->image_url): ?>
                <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
            <?php else: ?>
                <div class="kk-book-placeholder-large">📚</div>
            <?php endif; ?>
        </div>

        <div class="kk-book-detail-info">
            <h1><?php echo esc_html($book->title); ?></h1>
            <h3 class="kk-book-detail-author"><?php _e('Autor:', 'komunitna-kniznica'); ?> <?php echo esc_html($book->author); ?></h3>

            <?php if ($rating_data['count'] > 0): ?>
                <div class="kk-book-detail-rating">
                    <span class="kk-stars-large"><?php echo str_repeat('⭐', round($rating_data['average'])); ?></span>
                    <span class="kk-rating-text-large"><?php echo esc_html($rating_data['average']); ?> / 5.0 (<?php echo esc_html($rating_data['count']); ?> <?php _e('hodnotení', 'komunitna-kniznica'); ?>)</span>
                </div>
            <?php endif; ?>

            <div class="kk-book-detail-meta">
                <div class="kk-meta-item">
                    <strong><?php _e('Žáner:', 'komunitna-kniznica'); ?></strong>
                    <span><?php echo esc_html($book->genre); ?></span>
                </div>

                <?php if ($book->isbn): ?>
                    <div class="kk-meta-item">
                        <strong><?php _e('ISBN:', 'komunitna-kniznica'); ?></strong>
                        <span><?php echo esc_html($book->isbn); ?></span>
                    </div>
                <?php endif; ?>

                <div class="kk-meta-item">
                    <strong><?php _e('Stav knihy:', 'komunitna-kniznica'); ?></strong>
                    <span><?php echo esc_html($book->book_condition); ?> / 10</span>
                </div>

                <div class="kk-meta-item">
                    <strong><?php _e('Majiteľ:', 'komunitna-kniznica'); ?></strong>
                    <span><?php echo esc_html($owner->display_name); ?></span>
                </div>

                <div class="kk-meta-item">
                    <strong><?php _e('Status:', 'komunitna-kniznica'); ?></strong>
                    <?php
                    $status_labels = array(
                        'available' => __('Dostupná', 'komunitna-kniznica'),
                        'reserved' => __('Rezervovaná', 'komunitna-kniznica'),
                        'borrowed' => __('Požičaná', 'komunitna-kniznica')
                    );
                    ?>
                    <span class="kk-status kk-status-<?php echo esc_attr($book->status); ?>">
                        <?php echo esc_html($status_labels[$book->status]); ?>
                    </span>
                </div>
            </div>

            <div class="kk-book-detail-price">
                <?php if ($book->lending_price > 0): ?>
                    <h2><?php echo esc_html(number_format($book->lending_price, 2)); ?> €</h2>
                    <p><?php _e('Cena požičania', 'komunitna-kniznica'); ?></p>
                <?php else: ?>
                    <h2 class="kk-free"><?php _e('Zadarmo', 'komunitna-kniznica'); ?></h2>
                <?php endif; ?>
            </div>

            <!-- Akčné tlačidlá -->
            <div class="kk-book-detail-actions">
                <?php if ($can_borrow): ?>
                    <?php if ($book->lending_price > 0): ?>
                        <!-- Platené požičanie - pridať do košíka -->
                        <button class="kk-btn kk-btn-primary kk-btn-large kk-add-to-cart"
                                data-book-id="<?php echo esc_attr($book->id); ?>">
                            <?php _e('Pridať do košíka', 'komunitna-kniznica'); ?>
                        </button>
                    <?php else: ?>
                        <!-- Bezplatné požičanie -->
                        <button class="kk-btn kk-btn-primary kk-btn-large kk-borrow-free"
                                data-book-id="<?php echo esc_attr($book->id); ?>">
                            <?php _e('Požičať knihu', 'komunitna-kniznica'); ?>
                        </button>
                    <?php endif; ?>
                <?php elseif (!is_user_logged_in()): ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="kk-btn kk-btn-primary kk-btn-large">
                        <?php _e('Prihláste sa na požičanie', 'komunitna-kniznica'); ?>
                    </a>
                <?php elseif ($book->user_id == $user_id): ?>
                    <p class="kk-notice"><?php _e('Toto je vaša kniha.', 'komunitna-kniznica'); ?></p>
                <?php else: ?>
                    <p class="kk-notice"><?php _e('Kniha nie je dostupná na požičanie.', 'komunitna-kniznica'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Popis -->
    <?php if ($book->description): ?>
        <div class="kk-book-detail-description">
            <h3><?php _e('Popis', 'komunitna-kniznica'); ?></h3>
            <p><?php echo nl2br(esc_html($book->description)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Hodnotenia a recenzie -->
    <div class="kk-book-detail-reviews">
        <h3><?php _e('Hodnotenia a recenzie', 'komunitna-kniznica'); ?></h3>

        <?php if (empty($ratings)): ?>
            <p><?php _e('Táto kniha zatiaľ nemá žiadne hodnotenia.', 'komunitna-kniznica'); ?></p>
        <?php else: ?>
            <div class="kk-reviews-list">
                <?php foreach ($ratings as $rating): ?>
                    <div class="kk-review-card">
                        <div class="kk-review-header">
                            <div class="kk-review-user">
                                <strong><?php echo esc_html($rating->display_name); ?></strong>
                            </div>
                            <div class="kk-review-rating">
                                <span class="kk-stars"><?php echo str_repeat('⭐', $rating->rating); ?></span>
                            </div>
                        </div>
                        <?php if ($rating->review): ?>
                            <div class="kk-review-text">
                                <p><?php echo nl2br(esc_html($rating->review)); ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="kk-review-date">
                            <small><?php echo esc_html(date('d.m.Y', strtotime($rating->created_at))); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
