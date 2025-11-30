<?php
/**
 * Admin rozhranie pre pridávanie kníh
 */

if (!defined('ABSPATH')) exit;

class WCCL_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('admin_post_wccl_add_book', array(__CLASS__, 'handle_add_book'));
    }

    /**
     * Pridaj menu položku
     */
    public static function add_menu() {
        add_menu_page(
            __('Moje knihy', 'wc-community-library'),
            __('Moje knihy', 'wc-community-library'),
            'read', // Každý prihlásený používateľ
            'wccl-my-books',
            array(__CLASS__, 'my_books_page'),
            'dashicons-book',
            56
        );

        add_submenu_page(
            'wccl-my-books',
            __('Pridať knihu', 'wc-community-library'),
            __('Pridať knihu', 'wc-community-library'),
            'read',
            'wccl-add-book',
            array(__CLASS__, 'add_book_page')
        );
    }

    /**
     * Stránka - Moje knihy
     */
    public static function my_books_page() {
        $user_id = get_current_user_id();

        $args = array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_book_author',
                    'compare' => 'EXISTS'
                )
            )
        );

        $books = new WP_Query($args);

        ?>
        <div class="wrap">
            <h1>Moje knihy <a href="<?php echo admin_url('admin.php?page=wccl-add-book'); ?>" class="page-title-action">Pridať novú knihu</a></h1>

            <?php if ($books->have_posts()): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Názov</th>
                            <th>Autor</th>
                            <th>Žáner</th>
                            <th>Cena požičania</th>
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
                                <td><?php echo esc_html($genre); ?></td>
                                <td><?php echo $price == 0 ? 'Zadarmo' : $price . ' €'; ?></td>
                                <td><?php echo $product->get_stock_status() == 'instock' ? '✅ Dostupná' : '🔴 Požičaná'; ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link(); ?>">Upraviť</a> |
                                    <a href="<?php echo admin_url('admin.php?page=wccl-my-books&book_id=' . get_the_ID()); ?>">Objednávky</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Zatiaľ si nepridali žiadnu knihu. <a href="<?php echo admin_url('admin.php?page=wccl-add-book'); ?>">Pridať prvú knihu</a></p>
            <?php endif; ?>
        </div>
        <?php

        wp_reset_postdata();
    }

    /**
     * Stránka - Pridať knihu
     */
    public static function add_book_page() {
        ?>
        <div class="wrap">
            <h1>Pridať knihu do knižnice</h1>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('wccl_add_book', 'wccl_nonce'); ?>
                <input type="hidden" name="action" value="wccl_add_book">

                <table class="form-table">
                    <tr>
                        <th><label for="book_title">Názov knihy *</label></th>
                        <td><input type="text" id="book_title" name="book_title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="book_author">Autor *</label></th>
                        <td><input type="text" id="book_author" name="book_author" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="book_isbn">ISBN</label></th>
                        <td><input type="text" id="book_isbn" name="book_isbn" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="book_genre">Žáner *</label></th>
                        <td>
                            <select id="book_genre" name="book_genre" required>
                                <option value="fantasy">Fantasy</option>
                                <option value="scifi">Sci-Fi</option>
                                <option value="history">História</option>
                                <option value="biography">Biografia</option>
                                <option value="thriller">Thriller</option>
                                <option value="romance">Romantika</option>
                                <option value="other">Iné</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="book_condition">Stav knihy *</label></th>
                        <td>
                            <select id="book_condition" name="book_condition" required>
                                <option value="5">⭐⭐⭐⭐⭐ Ako nová</option>
                                <option value="4">⭐⭐⭐⭐ Veľmi dobrý</option>
                                <option value="3" selected>⭐⭐⭐ Dobrý</option>
                                <option value="2">⭐⭐ Použitá</option>
                                <option value="1">⭐ Opotrebovaná</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="lending_price">Cena požičania (€)</label></th>
                        <td>
                            <input type="number" id="lending_price" name="lending_price" value="0" step="0.01" min="0" max="10">
                            <p class="description">0 € = zadarmo, max. 10 €. Dostaneš 70%, komunita dostane 30%</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="book_description">Popis</label></th>
                        <td><textarea id="book_description" name="book_description" rows="5" class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="book_image">Obrázok knihy</label></th>
                        <td><input type="file" id="book_image" name="book_image" accept="image/*"></td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button button-primary" value="Pridať knihu">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Spracovanie pridania knihy
     */
    public static function handle_add_book() {
        if (!isset($_POST['wccl_nonce']) || !wp_verify_nonce($_POST['wccl_nonce'], 'wccl_add_book')) {
            wp_die('Neplatný bezpečnostný token');
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('Musíte byť prihlásený');
        }

        // Vytvor produkt
        $product = new WC_Product_Simple();
        $product->set_name(sanitize_text_field($_POST['book_title']));
        $product->set_description(sanitize_textarea_field($_POST['book_description'] ?? ''));
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');

        // Cena
        $price = floatval($_POST['lending_price'] ?? 0);
        if ($price < 0) $price = 0;
        if ($price > 10) $price = 10;
        $product->set_regular_price($price);
        $product->set_price($price);

        // Fyzický produkt (pre doručovaciu adresu)
        $product->set_virtual(false);
        $product->set_downloadable(false);
        $product->set_manage_stock(true);
        $product->set_stock_quantity(1); // Len 1 ks
        $product->set_stock_status('instock');

        // Uložiť
        $product_id = $product->save();

        // Nastav autora
        wp_update_post(array(
            'ID' => $product_id,
            'post_author' => $user_id
        ));

        // Meta fields
        update_post_meta($product_id, '_book_author', sanitize_text_field($_POST['book_author']));
        update_post_meta($product_id, '_book_isbn', sanitize_text_field($_POST['book_isbn'] ?? ''));
        update_post_meta($product_id, '_book_genre', sanitize_text_field($_POST['book_genre']));
        update_post_meta($product_id, '_book_condition', sanitize_text_field($_POST['book_condition']));
        update_post_meta($product_id, '_lending_price', $price);

        // Upload obrázka
        if (!empty($_FILES['book_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('book_image', $product_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($product_id, $attachment_id);
            }
        }

        // Redirect
        wp_redirect(admin_url('admin.php?page=wccl-my-books&added=1'));
        exit;
    }
}
