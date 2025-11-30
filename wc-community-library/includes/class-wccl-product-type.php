<?php
/**
 * Vlastný typ produktu pre knihy
 */

if (!defined('ABSPATH')) exit;

class WCCL_Product_Type {

    public static function init() {
        add_filter('product_type_selector', array(__CLASS__, 'add_product_type'));
        add_action('woocommerce_product_options_general_product_data', array(__CLASS__, 'add_book_fields'));
        add_action('woocommerce_process_product_meta', array(__CLASS__, 'save_book_fields'));
    }

    /**
     * Pridaj typ produktu "Kniha na požičanie"
     */
    public static function add_product_type($types) {
        $types['library_book'] = __('Kniha na požičanie', 'wc-community-library');
        return $types;
    }

    /**
     * Vlastné polia pre knihy
     */
    public static function add_book_fields() {
        global $post;

        echo '<div class="options_group show_if_library_book">';

        woocommerce_wp_text_input(array(
            'id' => '_book_author',
            'label' => __('Autor knihy', 'wc-community-library'),
            'placeholder' => 'J.R.R. Tolkien',
            'desc_tip' => true,
            'description' => __('Meno autora knihy', 'wc-community-library')
        ));

        woocommerce_wp_text_input(array(
            'id' => '_book_isbn',
            'label' => __('ISBN', 'wc-community-library'),
            'placeholder' => '978-80-123-4567-8',
            'desc_tip' => true,
            'description' => __('ISBN číslo knihy (nepovinné)', 'wc-community-library')
        ));

        woocommerce_wp_select(array(
            'id' => '_book_genre',
            'label' => __('Žáner', 'wc-community-library'),
            'options' => array(
                'fantasy' => 'Fantasy',
                'scifi' => 'Sci-Fi',
                'history' => 'História',
                'biography' => 'Biografia',
                'thriller' => 'Thriller',
                'romance' => 'Romantika',
                'other' => 'Iné'
            )
        ));

        woocommerce_wp_select(array(
            'id' => '_book_condition',
            'label' => __('Stav knihy', 'wc-community-library'),
            'options' => array(
                '5' => '⭐⭐⭐⭐⭐ Ako nová',
                '4' => '⭐⭐⭐⭐ Veľmi dobrý',
                '3' => '⭐⭐⭐ Dobrý',
                '2' => '⭐⭐ Použitá',
                '1' => '⭐ Opotrebovaná'
            ),
            'value' => get_post_meta($post->ID, '_book_condition', true) ?: '5'
        ));

        woocommerce_wp_text_input(array(
            'id' => '_lending_price',
            'label' => __('Cena požičania (€)', 'wc-community-library'),
            'placeholder' => '0.00',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0',
                'max' => '10'
            ),
            'desc_tip' => true,
            'description' => __('0 € = zadarmo, max. 10 €. Dostaneš 70%, komunita 30%', 'wc-community-library')
        ));

        echo '</div>';
    }

    /**
     * Uloženie custom polí
     */
    public static function save_book_fields($post_id) {
        update_post_meta($post_id, '_book_author', sanitize_text_field($_POST['_book_author'] ?? ''));
        update_post_meta($post_id, '_book_isbn', sanitize_text_field($_POST['_book_isbn'] ?? ''));
        update_post_meta($post_id, '_book_genre', sanitize_text_field($_POST['_book_genre'] ?? ''));
        update_post_meta($post_id, '_book_condition', sanitize_text_field($_POST['_book_condition'] ?? '5'));

        $price = floatval($_POST['_lending_price'] ?? 0);
        if ($price < 0) $price = 0;
        if ($price > 10) $price = 10;

        update_post_meta($post_id, '_lending_price', $price);
        update_post_meta($post_id, '_price', $price); // WooCommerce cena
        update_post_meta($post_id, '_regular_price', $price);
    }
}
