<?php
/**
 * Frontend zobrazenie katalógu
 */

if (!defined('ABSPATH')) exit;

class WCCL_Frontend {

    public static function init() {
        add_shortcode('wccl_catalog', array(__CLASS__, 'catalog_shortcode'));
        add_filter('woocommerce_product_tabs', array(__CLASS__, 'add_book_info_tab'), 98);
    }

    /**
     * Shortcode [wccl_catalog] - katalóg kníh
     */
    public static function catalog_shortcode($atts) {
        $atts = shortcode_atts(array(
            'genre' => '',
            'limit' => 20
        ), $atts);

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_book_author',
                    'compare' => 'EXISTS'
                )
            )
        );

        if (!empty($atts['genre'])) {
            $args['meta_query'][] = array(
                'key' => '_book_genre',
                'value' => sanitize_text_field($atts['genre']),
                'compare' => '='
            );
        }

        $books = new WP_Query($args);

        ob_start();
        ?>
        <div class="wccl-catalog">
            <style>
                .wccl-catalog { max-width: 1200px; margin: 0 auto; }
                .wccl-books { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
                .wccl-book { border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fff; }
                .wccl-book img { width: 100%; height: 300px; object-fit: cover; border-radius: 4px; }
                .wccl-book h3 { margin: 10px 0 5px; font-size: 18px; }
                .wccl-book-meta { font-size: 14px; color: #666; margin: 5px 0; }
                .wccl-book-price { font-size: 20px; font-weight: bold; color: #d4af37; margin: 10px 0; }
                .wccl-book-btn { display: inline-block; background: #d4af37; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 10px; }
                .wccl-book-btn:hover { background: #b8941f; }
            </style>

            <div class="wccl-books">
                <?php if ($books->have_posts()): ?>
                    <?php while ($books->have_posts()): $books->the_post(); ?>
                        <?php
                        $product = wc_get_product(get_the_ID());
                        $author = get_post_meta(get_the_ID(), '_book_author', true);
                        $genre = get_post_meta(get_the_ID(), '_book_genre', true);
                        $condition = get_post_meta(get_the_ID(), '_book_condition', true);
                        $price = get_post_meta(get_the_ID(), '_lending_price', true);
                        ?>
                        <div class="wccl-book">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium'); ?>
                            <?php else: ?>
                                <img src="<?php echo WCCL_PLUGIN_URL; ?>assets/placeholder-book.png" alt="<?php the_title(); ?>">
                            <?php endif; ?>

                            <h3><?php the_title(); ?></h3>
                            <div class="wccl-book-meta">📚 <?php echo esc_html($author); ?></div>
                            <div class="wccl-book-meta">🏷️ <?php echo esc_html(ucfirst($genre)); ?></div>
                            <div class="wccl-book-meta">
                                <?php
                                $stars = str_repeat('⭐', intval($condition));
                                echo $stars;
                                ?>
                            </div>
                            <div class="wccl-book-price">
                                <?php echo $price == 0 ? '✨ ZADARMO' : $price . ' €'; ?>
                            </div>

                            <?php if ($product->is_in_stock()): ?>
                                <a href="<?php echo get_permalink(); ?>" class="wccl-book-btn">📖 Požičať si</a>
                            <?php else: ?>
                                <span style="color: #999;">🔴 Momentálne požičaná</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>V knižnici zatiaľ nie sú žiadne knihy.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Pridaj tab "Informácie o knihe" na product page
     */
    public static function add_book_info_tab($tabs) {
        global $post;

        if (get_post_meta($post->ID, '_book_author', true)) {
            $tabs['book_info'] = array(
                'title' => __('Informácie o knihe', 'wc-community-library'),
                'priority' => 10,
                'callback' => array(__CLASS__, 'book_info_tab_content')
            );
        }

        return $tabs;
    }

    /**
     * Obsah tabu "Informácie o knihe"
     */
    public static function book_info_tab_content() {
        global $post;

        $author = get_post_meta($post->ID, '_book_author', true);
        $isbn = get_post_meta($post->ID, '_book_isbn', true);
        $genre = get_post_meta($post->ID, '_book_genre', true);
        $condition = get_post_meta($post->ID, '_book_condition', true);

        echo '<h2>Informácie o knihe</h2>';
        echo '<table class="woocommerce-product-attributes shop_attributes">';

        if ($author) {
            echo '<tr><th>Autor</th><td>' . esc_html($author) . '</td></tr>';
        }

        if ($isbn) {
            echo '<tr><th>ISBN</th><td>' . esc_html($isbn) . '</td></tr>';
        }

        if ($genre) {
            echo '<tr><th>Žáner</th><td>' . esc_html(ucfirst($genre)) . '</td></tr>';
        }

        if ($condition) {
            $stars = str_repeat('⭐', intval($condition));
            echo '<tr><th>Stav knihy</th><td>' . $stars . '</td></tr>';
        }

        echo '</table>';

        echo '<p><strong>ℹ️ Požičanie:</strong> Po objednávke dostanete adresu na doručenie. Knihu vráťte do 30 dní v rovnakom stave.</p>';
    }
}
