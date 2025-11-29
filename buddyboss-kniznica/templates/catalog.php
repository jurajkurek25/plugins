<?php
/**
 * Template: Katalóg kníh
 */

if (!defined('ABSPATH')) {
    exit;
}

// Získanie parametrov
$search = isset($_GET['bbk_search']) ? sanitize_text_field($_GET['bbk_search']) : '';
$genre = isset($_GET['bbk_genre']) ? sanitize_text_field($_GET['bbk_genre']) : '';
$price_type = isset($_GET['bbk_price']) ? sanitize_text_field($_GET['bbk_price']) : '';
$user_id = is_user_logged_in() ? get_current_user_id() : null;

// Získanie kníh
$args = array(
    'limit' => isset($atts['limit']) ? $atts['limit'] : 20,
    'exclude_user' => $user_id
);

if ($search) {
    $args['search'] = $search;
}

if ($genre) {
    $args['genre'] = $genre;
}

if ($price_type) {
    $args['price_type'] = $price_type;
}

$books = BBK_Book::get_instance()->get_available_books($args);
$genres = BBK_Book::get_instance()->get_genres();
?>

<div class="bbk-catalog-wrapper">
    <div class="bbk-catalog-header">
        <h2>📚 <?php _e('Katalóg kníh', 'buddyboss-kniznica'); ?></h2>
        <p><?php _e('Prehľadávaj knihy od ostatných členov komunity. Filtruj podľa žánru, autora alebo dostupnosti.', 'buddyboss-kniznica'); ?></p>
    </div>

    <!-- Filtre -->
    <div class="bbk-catalog-filters">
        <form method="get" class="bbk-filter-form">
            <div class="bbk-filter-group">
                <input type="text" name="bbk_search" placeholder="🔍 <?php _e('Vyhľadať knihu, autora...', 'buddyboss-kniznica'); ?>"
                       value="<?php echo esc_attr($search); ?>" class="bbk-input">
            </div>

            <div class="bbk-filter-group">
                <select name="bbk_genre" class="bbk-select">
                    <option value=""><?php _e('Všetky žánre', 'buddyboss-kniznica'); ?></option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo esc_attr($g); ?>" <?php selected($genre, $g); ?>>
                            <?php echo esc_html($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bbk-filter-group">
                <select name="bbk_price" class="bbk-select">
                    <option value=""><?php _e('Všetky ceny', 'buddyboss-kniznica'); ?></option>
                    <option value="free" <?php selected($price_type, 'free'); ?>><?php _e('🎁 Zadarmo', 'buddyboss-kniznica'); ?></option>
                    <option value="paid" <?php selected($price_type, 'paid'); ?>><?php _e('💰 Platené', 'buddyboss-kniznica'); ?></option>
                </select>
            </div>

            <div class="bbk-filter-group">
                <button type="submit" class="bbk-btn bbk-btn-primary"><?php _e('Filtrovať', 'buddyboss-kniznica'); ?></button>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="bbk-btn bbk-btn-secondary"><?php _e('Reset', 'buddyboss-kniznica'); ?></a>
            </div>
        </form>
    </div>

    <!-- Zoznam kníh -->
    <div class="bbk-books-grid">
        <?php if (empty($books)): ?>
            <p class="bbk-no-books"><?php _e('Žiadne knihy neboli nájdené.', 'buddyboss-kniznica'); ?></p>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <?php
                $rating_data = BBK_Rating::get_instance()->get_average_rating($book->id);
                $owner = get_user_by('id', $book->user_id);
                $detail_url = add_query_arg('book_id', $book->id, get_permalink());
                ?>
                <div class="bbk-book-card">
                    <div class="bbk-book-image">
                        <?php if ($book->image_url): ?>
                            <img src="<?php echo esc_url($book->image_url); ?>" alt="<?php echo esc_attr($book->title); ?>">
                        <?php else: ?>
                            <div class="bbk-book-placeholder">📚</div>
                        <?php endif; ?>
                    </div>

                    <div class="bbk-book-info">
                        <h3 class="bbk-book-title">
                            <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($book->title); ?></a>
                        </h3>
                        <p class="bbk-book-author">✍️ <?php echo esc_html($book->author); ?></p>
                        <p class="bbk-book-genre">📖 <?php echo esc_html($book->genre); ?></p>

                        <?php if ($rating_data['count'] > 0): ?>
                            <div class="bbk-book-rating">
                                <span class="bbk-stars"><?php echo str_repeat('⭐', round($rating_data['average'])); ?></span>
                                <span class="bbk-rating-text"><?php echo esc_html($rating_data['average']); ?> (<?php echo esc_html($rating_data['count']); ?>)</span>
                            </div>
                        <?php endif; ?>

                        <div class="bbk-book-meta">
                            <span class="bbk-book-condition"><?php _e('Stav:', 'buddyboss-kniznica'); ?> <?php echo esc_html($book->book_condition); ?>/10</span>
                            <span class="bbk-book-price">
                                <?php if ($book->lending_price > 0): ?>
                                    💰 <?php echo number_format($book->lending_price, 2); ?> €
                                <?php else: ?>
                                    🎁 <?php _e('Zadarmo', 'buddyboss-kniznica'); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="bbk-book-owner">
                            👤 <?php echo esc_html($owner->display_name); ?>
                        </div>

                        <a href="<?php echo esc_url($detail_url); ?>" class="bbk-btn bbk-btn-primary bbk-btn-block">
                            <?php _e('Zobraziť detail', 'buddyboss-kniznica'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
