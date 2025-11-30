<?php
/**
 * Frontend zobrazenie katalógu
 */

if (!defined('ABSPATH')) exit;

class WCCL_Frontend {

    public static function init() {
        add_shortcode('wccl_catalog', array(__CLASS__, 'catalog_shortcode'));
        add_shortcode('wccl_add_book', array(__CLASS__, 'add_book_shortcode'));
        add_shortcode('wccl_my_books', array(__CLASS__, 'my_books_shortcode'));
        add_shortcode('wccl_my_lendings', array(__CLASS__, 'my_lendings_shortcode'));
        add_filter('woocommerce_product_tabs', array(__CLASS__, 'add_book_info_tab'), 98);
        add_action('wp_ajax_wccl_add_book_ajax', array(__CLASS__, 'handle_add_book_ajax'));
        add_action('wp_ajax_wccl_delete_book', array(__CLASS__, 'handle_delete_book'));
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

    /**
     * Shortcode [wccl_add_book] - formulár na pridanie knihy
     */
    public static function add_book_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Musíte byť prihlásený, aby ste mohli pridať knihu. <a href="' . wp_login_url(get_permalink()) . '">Prihlásiť sa</a></p>';
        }

        ob_start();
        ?>
        <div class="wccl-add-book">
            <style>
                .wccl-add-book { max-width: 800px; margin: 0 auto; padding: 20px; background: #fff; border-radius: 8px; }
                .wccl-form-field { margin-bottom: 20px; }
                .wccl-form-field label { display: block; font-weight: bold; margin-bottom: 5px; }
                .wccl-form-field input[type="text"],
                .wccl-form-field input[type="number"],
                .wccl-form-field select,
                .wccl-form-field textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .wccl-form-field textarea { min-height: 100px; }
                .wccl-submit-btn { background: #d4af37; color: #fff; padding: 12px 30px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
                .wccl-submit-btn:hover { background: #b8941f; }
                .wccl-message { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
                .wccl-message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
                .wccl-message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            </style>

            <h2>📚 Pridať knihu do knižnice</h2>

            <div id="wccl-message"></div>

            <form id="wccl-add-book-form" enctype="multipart/form-data">
                <?php wp_nonce_field('wccl_add_book', 'wccl_nonce'); ?>

                <div class="wccl-form-field">
                    <label for="book_title">Názov knihy *</label>
                    <input type="text" id="book_title" name="book_title" required>
                </div>

                <div class="wccl-form-field">
                    <label for="book_author">Autor *</label>
                    <input type="text" id="book_author" name="book_author" required>
                </div>

                <div class="wccl-form-field">
                    <label for="book_isbn">ISBN</label>
                    <input type="text" id="book_isbn" name="book_isbn" placeholder="978-80-123-4567-8">
                </div>

                <div class="wccl-form-field">
                    <label for="book_genre">Žáner *</label>
                    <select id="book_genre" name="book_genre" required>
                        <option value="fantasy">Fantasy</option>
                        <option value="scifi">Sci-Fi</option>
                        <option value="history">História</option>
                        <option value="biography">Biografia</option>
                        <option value="thriller">Thriller</option>
                        <option value="romance">Romantika</option>
                        <option value="other">Iné</option>
                    </select>
                </div>

                <div class="wccl-form-field">
                    <label for="book_condition">Stav knihy *</label>
                    <select id="book_condition" name="book_condition" required>
                        <option value="5">⭐⭐⭐⭐⭐ Ako nová</option>
                        <option value="4">⭐⭐⭐⭐ Veľmi dobrý</option>
                        <option value="3" selected>⭐⭐⭐ Dobrý</option>
                        <option value="2">⭐⭐ Použitá</option>
                        <option value="1">⭐ Opotrebovaná</option>
                    </select>
                </div>

                <div class="wccl-form-field">
                    <label for="lending_price">Cena požičania (€)</label>
                    <input type="number" id="lending_price" name="lending_price" value="0" step="0.01" min="0" max="10">
                    <small>0 € = zadarmo, max. 10 €. Dostaneš 70%, komunita 30%</small>
                </div>

                <div class="wccl-form-field">
                    <label for="book_description">Popis knihy</label>
                    <textarea id="book_description" name="book_description" placeholder="Krátky popis knihy..."></textarea>
                </div>

                <div class="wccl-form-field">
                    <label for="book_image">Obrázok knihy</label>
                    <input type="file" id="book_image" name="book_image" accept="image/*">
                </div>

                <button type="submit" class="wccl-submit-btn">✅ Pridať knihu</button>
            </form>

            <script>
            jQuery(document).ready(function($) {
                $('#wccl-add-book-form').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    formData.append('action', 'wccl_add_book_ajax');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#wccl-message').html('<div class="wccl-message success">' + response.data.message + '</div>');
                                $('#wccl-add-book-form')[0].reset();
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 1500);
                            } else {
                                $('#wccl-message').html('<div class="wccl-message error">' + response.data.message + '</div>');
                            }
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler pre pridanie knihy
     */
    public static function handle_add_book_ajax() {
        check_ajax_referer('wccl_add_book', 'wccl_nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Musíte byť prihlásený.'));
        }

        $user_id = get_current_user_id();

        // Vytvor produkt
        $product = new WC_Product_Simple();
        $product->set_name(sanitize_text_field($_POST['book_title']));
        $product->set_description(sanitize_textarea_field($_POST['book_description'] ?? ''));
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');

        $price = floatval($_POST['lending_price'] ?? 0);
        if ($price < 0) $price = 0;
        if ($price > 10) $price = 10;
        $product->set_regular_price($price);
        $product->set_price($price);

        $product->set_virtual(false);
        $product->set_downloadable(false);
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1);
        $product->set_stock_status('instock');

        $product_id = $product->save();

        wp_update_post(array('ID' => $product_id, 'post_author' => $user_id));

        update_post_meta($product_id, '_book_author', sanitize_text_field($_POST['book_author']));
        update_post_meta($product_id, '_book_isbn', sanitize_text_field($_POST['book_isbn'] ?? ''));
        update_post_meta($product_id, '_book_genre', sanitize_text_field($_POST['book_genre']));
        update_post_meta($product_id, '_book_condition', sanitize_text_field($_POST['book_condition']));
        update_post_meta($product_id, '_lending_price', $price);

        if (!empty($_FILES['book_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('book_image', $product_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($product_id, $attachment_id);
            }
        }

        wp_send_json_success(array(
            'message' => '✅ Kniha bola úspešne pridaná!',
            'redirect' => add_query_arg('view', 'my-books', get_permalink())
        ));
    }

    /**
     * Shortcode [wccl_my_books] - moje knihy
     */
    public static function my_books_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Musíte byť prihlásený. <a href="' . wp_login_url(get_permalink()) . '">Prihlásiť sa</a></p>';
        }

        $user_id = get_current_user_id();

        $args = array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'meta_query' => array(
                array('key' => '_book_author', 'compare' => 'EXISTS')
            )
        );

        $books = new WP_Query($args);

        ob_start();
        ?>
        <div class="wccl-my-books">
            <style>
                .wccl-my-books { max-width: 1200px; margin: 0 auto; }
                .wccl-my-books table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .wccl-my-books th, .wccl-my-books td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                .wccl-my-books th { background: #f5f5f5; font-weight: bold; }
                .wccl-my-books .status-available { color: #28a745; }
                .wccl-my-books .status-unavailable { color: #dc3545; }
                .wccl-add-new-btn { display: inline-block; background: #d4af37; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-bottom: 20px; }
            </style>

            <h2>📚 Moje knihy</h2>

            <?php if ($books->have_posts()): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Názov</th>
                            <th>Autor</th>
                            <th>Žáner</th>
                            <th>Cena</th>
                            <th>Stav</th>
                            <th>Akcie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($books->have_posts()): $books->the_post(); ?>
                            <?php
                            $product = wc_get_product(get_the_ID());
                            $author = get_post_meta(get_the_ID(), '_book_author', true);
                            $genre = get_post_meta(get_the_ID(), '_book_genre', true);
                            $price = get_post_meta(get_the_ID(), '_lending_price', true);
                            ?>
                            <tr>
                                <td><strong><?php the_title(); ?></strong></td>
                                <td><?php echo esc_html($author); ?></td>
                                <td><?php echo esc_html(ucfirst($genre)); ?></td>
                                <td><?php echo $price == 0 ? 'Zadarmo' : $price . ' €'; ?></td>
                                <td class="<?php echo $product->is_in_stock() ? 'status-available' : 'status-unavailable'; ?>">
                                    <?php echo $product->is_in_stock() ? '✅ Dostupná' : '🔴 Požičaná'; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_permalink(); ?>">Zobraziť</a> |
                                    <a href="<?php echo get_edit_post_link(); ?>">Upraviť</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Zatiaľ si nepridali žiadnu knihu.</p>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Shortcode [wccl_my_lendings] - moje požičania (objednávky s adresami)
     */
    public static function my_lendings_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Musíte byť prihlásený. <a href="' . wp_login_url(get_permalink()) . '">Prihlásiť sa</a></p>';
        }

        $user_id = get_current_user_id();

        // Moje knihy
        $my_books = get_posts(array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array('key' => '_book_author', 'compare' => 'EXISTS')
            )
        ));

        if (empty($my_books)) {
            return '<p>Nemáte zatiaľ žiadne knihy v knižnici.</p>';
        }

        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
        ));

        $my_orders = array();
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if (in_array($item->get_product_id(), $my_books)) {
                    $my_orders[] = array('order' => $order, 'item' => $item, 'product_id' => $item->get_product_id());
                }
            }
        }

        ob_start();
        ?>
        <div class="wccl-my-lendings">
            <style>
                .wccl-my-lendings { max-width: 1400px; margin: 0 auto; }
                .wccl-my-lendings table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
                .wccl-my-lendings th, .wccl-my-lendings td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                .wccl-my-lendings th { background: #f5f5f5; font-weight: bold; }
                .wccl-info-box { background: #f0f0f0; border-left: 4px solid #d4af37; padding: 15px; margin-top: 20px; }
            </style>

            <h2>📬 Moje požičania</h2>
            <p>Tu vidíš všetky požičania tvojich kníh a doručovacie adresy.</p>

            <?php if (!empty($my_orders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Objednávka</th>
                            <th>Kniha</th>
                            <th>Požičal si</th>
                            <th>Adresa</th>
                            <th>Telefón</th>
                            <th>Cena</th>
                            <th>Tvoja provízia</th>
                            <th>Dátum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_orders as $lending): ?>
                            <?php
                            $order = $lending['order'];
                            $item = $lending['item'];
                            $address = array(
                                $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                                $order->get_shipping_address_1(),
                                $order->get_shipping_address_2(),
                                $order->get_shipping_postcode() . ' ' . $order->get_shipping_city(),
                                $order->get_shipping_country()
                            );
                            $address = array_filter($address);
                            $price = floatval($item->get_total());
                            $commission = $price * 0.70;
                            ?>
                            <tr>
                                <td>#<?php echo $order->get_id(); ?></td>
                                <td><strong><?php echo $item->get_name(); ?></strong></td>
                                <td>
                                    <?php
                                    $user = $order->get_user();
                                    echo $user ? $user->display_name : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                    ?><br>
                                    <small><?php echo $order->get_billing_email(); ?></small>
                                </td>
                                <td><?php echo implode('<br>', $address); ?></td>
                                <td><?php echo $order->get_shipping_phone() ?: $order->get_billing_phone(); ?></td>
                                <td><?php echo $price == 0 ? 'Zadarmo' : $price . ' €'; ?></td>
                                <td><strong><?php echo $commission > 0 ? number_format($commission, 2) . ' €' : '-'; ?></strong></td>
                                <td><?php echo $order->get_date_created()->date('d.m.Y'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="wccl-info-box">
                    <h3>ℹ️ Ako to funguje?</h3>
                    <ul>
                        <li>📬 Keď si niekto objedná tvoju knihu, príde ti email s adresou</li>
                        <li>📦 Pošli knihu na doručovaciu adresu uvedenú vyššie</li>
                        <li>💰 Ak bola kniha platená, dostaneš 70% ceny, komunita 30%</li>
                        <li>⏰ Požičiavateľ má knihu 30 dní</li>
                    </ul>
                </div>
            <?php else: ?>
                <p>Zatiaľ si nikto nepožičal tvoje knihy.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
