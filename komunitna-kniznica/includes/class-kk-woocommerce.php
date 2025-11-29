<?php
/**
 * WooCommerce integrácia
 * KRITICKÉ: Používa FYZICKÝ dummy produkt pre získanie doručovacích údajov
 */

if (!defined('ABSPATH')) {
    exit;
}

class KK_WooCommerce {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Pridanie knihy do košíka
        add_action('wp_ajax_kk_add_to_cart', array($this, 'ajax_add_to_cart'));

        // WooCommerce hooks
        add_action('woocommerce_before_calculate_totals', array($this, 'set_cart_item_price'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 3);
        add_filter('woocommerce_cart_item_name', array($this, 'cart_item_name'), 10, 3);
        add_action('woocommerce_cart_item_removed', array($this, 'cart_item_removed'), 10, 2);

        // Spracovanie po dokončení objednávky
        add_action('woocommerce_order_status_completed', array($this, 'process_order_completed'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'process_order_completed'), 10, 1);

        // Zabránenie viacnásobnému pridaniu tej istej knihy
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
    }

    /**
     * Pridanie knihy do košíka (platené požičanie)
     */
    public function add_book_to_cart($book_id) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('Musíte byť prihlásený.', 'komunitna-kniznica'));
        }

        // Získanie knihy
        $book = KK_Database::get_row('books', array('id' => $book_id));

        if (!$book) {
            return new WP_Error('book_not_found', __('Kniha nebola nájdená.', 'komunitna-kniznica'));
        }

        if ($book->status !== 'available') {
            return new WP_Error('book_not_available', __('Kniha nie je dostupná.', 'komunitna-kniznica'));
        }

        $user_id = get_current_user_id();

        if ($book->user_id == $user_id) {
            return new WP_Error('own_book', __('Nemôžete si požičať vlastnú knihu.', 'komunitna-kniznica'));
        }

        if ($book->lending_price <= 0) {
            return new WP_Error('free_book', __('Táto kniha je zadarmo, použite priame požičanie.', 'komunitna-kniznica'));
        }

        // Získanie ID dummy produktu
        $product_id = get_option('kk_dummy_product_id');

        if (!$product_id) {
            return new WP_Error('product_not_found', __('Chyba systému: produkt nenájdený.', 'komunitna-kniznica'));
        }

        // Pridanie do košíka s custom dátami
        $cart_item_data = array(
            'kk_book_id' => $book_id,
            'kk_book_title' => $book->title,
            'kk_book_author' => $book->author,
            'kk_lending_price' => $book->lending_price,
            'kk_book_owner_id' => $book->user_id
        );

        // Rezervácia knihy
        KK_Book::get_instance()->update_status($book_id, 'reserved');

        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if ($cart_item_key) {
            return true;
        }

        // Ak sa nepodarilo pridať, zruš rezerváciu
        KK_Book::get_instance()->update_status($book_id, 'available');

        return new WP_Error('cart_error', __('Nepodarilo sa pridať knihu do košíka.', 'komunitna-kniznica'));
    }

    /**
     * Pridanie custom dát do košíka
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Custom dáta sú už pridané v add_book_to_cart()
        return $cart_item_data;
    }

    /**
     * Obnovenie custom dát zo session
     */
    public function get_cart_item_from_session($cart_item, $values, $key) {
        if (isset($values['kk_book_id'])) {
            $cart_item['kk_book_id'] = $values['kk_book_id'];
            $cart_item['kk_book_title'] = $values['kk_book_title'];
            $cart_item['kk_book_author'] = $values['kk_book_author'];
            $cart_item['kk_lending_price'] = $values['kk_lending_price'];
            $cart_item['kk_book_owner_id'] = $values['kk_book_owner_id'];
        }

        return $cart_item;
    }

    /**
     * Nastavenie ceny v košíku
     */
    public function set_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['kk_lending_price'])) {
                $cart_item['data']->set_price($cart_item['kk_lending_price']);
            }
        }
    }

    /**
     * Zobrazenie názvu knihy v košíku
     */
    public function cart_item_name($name, $cart_item, $cart_item_key) {
        if (isset($cart_item['kk_book_title'])) {
            $book_name = sprintf(
                __('Požičanie: %s', 'komunitna-kniznica'),
                $cart_item['kk_book_title']
            );

            if (isset($cart_item['kk_book_author'])) {
                $book_name .= sprintf(' <small>(%s)</small>', $cart_item['kk_book_author']);
            }

            return $book_name;
        }

        return $name;
    }

    /**
     * Uvoľnenie knihy pri odstránení z košíka
     */
    public function cart_item_removed($cart_item_key, $cart) {
        $cart_item = $cart->removed_cart_contents[$cart_item_key];

        if (isset($cart_item['kk_book_id'])) {
            // Uvoľnenie rezervácie
            KK_Book::get_instance()->update_status($cart_item['kk_book_id'], 'available');
        }
    }

    /**
     * Validácia pridania do košíka - zabránenie viacnásobného pridania
     */
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        // Kontrola či už nie je v košíku iná kniha
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['kk_book_id'])) {
                // V košíku už je kniha z knižnice
                // Povoliť len ak je to ten istý produkt (update quantity)
                if ($cart_item['data']->get_id() == $product_id) {
                    // Je to update existujúcej položky
                    return $passed;
                }
            }
        }

        return $passed;
    }

    /**
     * Spracovanie dokončenej objednávky
     * KRITICKÉ: Extrahovanie doručovacích údajov a vytvorenie požičania
     */
    public function process_order_completed($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Kontrola či už bolo spracované
        if ($order->get_meta('_kk_processed')) {
            return;
        }

        $borrower_id = $order->get_user_id();

        // Získanie doručovacích údajov z objednávky
        $shipping_data = array(
            'name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
            'phone' => $order->get_billing_phone()
        );

        // Spracovanie každej položky v objednávke
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $dummy_product_id = get_option('kk_dummy_product_id');

            // Kontrola či je to náš dummy produkt
            if ($product_id == $dummy_product_id) {
                // Získanie book_id z item meta (uložené pri pridaní do košíka)
                $book_id = wc_get_order_item_meta($item_id, '_kk_book_id', true);

                if (!$book_id) {
                    // Pokus získať z cart item data (ak bola objednávka práve vytvorená)
                    continue;
                }

                // Vytvorenie požičania s doručovacími údajmi
                $result = KK_Lending::get_instance()->create_paid_lending(
                    $book_id,
                    $borrower_id,
                    $order_id,
                    $shipping_data
                );

                if (!is_wp_error($result)) {
                    // Uloženie lending_id k order item
                    wc_update_order_item_meta($item_id, '_kk_lending_id', $result);
                }
            }
        }

        // Označenie objednávky ako spracovanej
        $order->update_meta_data('_kk_processed', true);
        $order->save();
    }

    /**
     * AJAX: Pridanie knihy do košíka
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('kk_add_to_cart_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Musíte byť prihlásený.', 'komunitna-kniznica')));
        }

        $book_id = absint($_POST['book_id']);
        $result = $this->add_book_to_cart($book_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Kniha bola pridaná do košíka.', 'komunitna-kniznica'),
            'cart_url' => wc_get_cart_url()
        ));
    }
}

// Hook pre uloženie cart item meta do order item meta
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
    if (isset($values['kk_book_id'])) {
        $item->add_meta_data('_kk_book_id', $values['kk_book_id'], true);
        $item->add_meta_data('_kk_book_title', $values['kk_book_title'], true);
        $item->add_meta_data('_kk_book_author', $values['kk_book_author'], true);
        $item->add_meta_data('_kk_book_owner_id', $values['kk_book_owner_id'], true);
    }
}, 10, 4);
