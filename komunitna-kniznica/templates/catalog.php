<?php
/**
 * Template: Katalóg kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

// Získanie parametrov
$search = isset($_GET['kk_search']) ? sanitize_text_field($_GET['kk_search']) : '';
$genre = isset($_GET['kk_genre']) ? sanitize_text_field($_GET['kk_genre']) : '';
$user_id = is_user_logged_in() ? get_current_user_id() : null;

// Získanie kníh
$args = array(
    'limit' => isset($limit) ? $limit : 20,
    'exclude_user' => $user_id
);

if ($search) {
    $args['search'] = $search;
}

if ($genre) {
    $args['genre'] = $genre;
}

$books = KK_Book::get_instance()->get_available_books($args);
$genres = KK_Book::get_instance()->get_genres();
?>

<div class="kk-catalog-wrapper">
    <div class="kk-catalog-header">
        <h2><?php _e('Katalóg kníh', 'komunitna-kniznica'); ?></h2>
    </div>

    <!-- Filtre a vyhľadávanie -->
    <div class="kk-catalog-filters">
        <form method="get" class="kk-filter-form">
            <div class="kk-filter-group">
                <input type="text" name="kk_search" placeholder="<?php _e('Vyhľadať knihu, autora...', 'komunitna-kniznica'); ?>"
                       value="<?php echo esc_attr($search); ?>" class="kk-search-input">
            </div>

            <div class="kk-filter-group">
                <select name="kk_genre" class="kk-genre-select">
                    <option value=""><?php _e('Všetky žánre', 'komunitna-kniznica'); ?></option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo esc_attr($g); ?>" <?php selected($genre, $g); ?>>
                            <?php echo esc_html($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="kk-filter-group">
                <button type="submit" class="kk-btn kk-btn-primary"><?php _e('Filtrovať', 'komunitna-kniznica'); ?></button>
                <a href="<?php echo get_permalink(); ?>" class="kk-btn kk-btn-secondary"><?php _e('Reset', 'komunitna-kniznica'); ?></a>
            </div>
        </form>
    </div>

    <!-- Zoznam kníh -->
    <div class="kk-books-grid">
        <?php if (empty($books)): ?>
            <p class="kk-no-books"><?php _e('Žiadne knihy neboli nájdené.', 'komunitna-kniznica'); ?></p>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <?php
                $rating_data = KK_Rating::get_instance()->get_average_rating($book->id);
                $owner = get_user_by('id', $book->user_id);
                // KRITICKÉ: Použitie absolútnej URL pre detail knihy
                $detail_url = home_url('/detail-knihy-v-kniznici/?book_id=' . $book->id);
                ?>
                <div class="kk-book-card">
                    <div class="kk-book-image">
                        <?php if ($book->image_url): ?>
                            <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
                        <?php else: ?>
                            <div class="kk-book-placeholder">📚</div>
                        <?php endif; ?>
                    </div>

                    <div class="kk-book-info">
                        <h3 class="kk-book-title">
                            <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($book->title); ?></a>
                        </h3>
                        <p class="kk-book-author"><?php echo esc_html($book->author); ?></p>
                        <p class="kk-book-genre"><?php echo esc_html($book->genre); ?></p>

                        <?php if ($rating_data['count'] > 0): ?>
                            <div class="kk-book-rating">
                                <span class="kk-stars"><?php echo str_repeat('⭐', round($rating_data['average'])); ?></span>
                                <span class="kk-rating-text"><?php echo esc_html($rating_data['average']); ?> (<?php echo esc_html($rating_data['count']); ?>)</span>
                            </div>
                        <?php endif; ?>

                        <div class="kk-book-meta">
                            <span class="kk-book-condition"><?php _e('Stav:', 'komunitna-kniznica'); ?> <?php echo esc_html($book->book_condition); ?>/10</span>
                            <span class="kk-book-owner"><?php _e('Majiteľ:', 'komunitna-kniznica'); ?> <?php echo esc_html($owner->display_name); ?></span>
                        </div>

                        <div class="kk-book-price">
                            <?php if ($book->lending_price > 0): ?>
                                <strong><?php echo esc_html(number_format($book->lending_price, 2)); ?> €</strong>
                            <?php else: ?>
                                <strong class="kk-free"><?php _e('Zadarmo', 'komunitna-kniznica'); ?></strong>
                            <?php endif; ?>
                        </div>

                        <div class="kk-book-actions">
                            <a href="<?php echo esc_url($detail_url); ?>" class="kk-btn kk-btn-primary">
                                <?php _e('Detail knihy', 'komunitna-kniznica'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
