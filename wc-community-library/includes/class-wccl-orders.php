<?php
/**
 * Zobrazenie objednávok a adries majiteľom kníh
 */

if (!defined('ABSPATH')) exit;

class WCCL_Orders {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_orders_page'));
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'notify_book_owner'));
        add_action('woocommerce_order_status_processing', array(__CLASS__, 'notify_book_owner'));
    }

    /**
     * Pridaj stránku pre objednávky
     */
    public static function add_orders_page() {
        add_submenu_page(
            'wccl-my-books',
            __('Moje požičania', 'wc-community-library'),
            __('Moje požičania', 'wc-community-library'),
            'read',
            'wccl-my-lendings',
            array(__CLASS__, 'my_lendings_page')
        );
    }

    /**
     * Stránka - Moje požičania (objednávky mojich kníh)
     */
    public static function my_lendings_page() {
        $user_id = get_current_user_id();

        // Nájdi všetky moje knihy (produkty)
        $my_books = get_posts(array(
            'post_type' => 'product',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_book_author',
                    'compare' => 'EXISTS'
                )
            )
        ));

        if (empty($my_books)) {
            echo '<div class="wrap"><h1>Moje požičania</h1><p>Nemáte zatiaľ žiadne knihy v knižnici.</p></div>';
            return;
        }

        // Nájdi všetky objednávky ktoré obsahujú moje knihy
        $orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
        ));

        $my_orders = array();

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();

                if (in_array($product_id, $my_books)) {
                    $my_orders[] = array(
                        'order' => $order,
                        'item' => $item,
                        'product_id' => $product_id
                    );
                }
            }
        }

        ?>
        <div class="wrap">
            <h1>Moje požičania</h1>
            <p>Tu vidíš zoznam všetkých požičaní tvojich kníh a doručovacie adresy.</p>

            <?php if (!empty($my_orders)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Objednávka</th>
                            <th>Kniha</th>
                            <th>Požičal si</th>
                            <th>Doručovacia adresa</th>
                            <th>Telefón</th>
                            <th>Cena</th>
                            <th>Tvoja provízia (70%)</th>
                            <th>Stav</th>
                            <th>Dátum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_orders as $lending): ?>
                            <?php
                            $order = $lending['order'];
                            $item = $lending['item'];
                            $product = wc_get_product($lending['product_id']);

                            // Doručovacia adresa
                            $address = array(
                                $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                                $order->get_shipping_address_1(),
                                $order->get_shipping_address_2(),
                                $order->get_shipping_postcode() . ' ' . $order->get_shipping_city(),
                                $order->get_shipping_country()
                            );
                            $address = array_filter($address);
                            $address_str = implode('<br>', $address);

                            // Cena a provízia
                            $price = floatval($item->get_total());
                            $commission = $price * 0.70; // 70% pre majiteľa
                            ?>
                            <tr>
                                <td><a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_id(); ?></a></td>
                                <td><strong><?php echo $item->get_name(); ?></strong></td>
                                <td>
                                    <?php
                                    $user = $order->get_user();
                                    echo $user ? $user->display_name : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                    ?><br>
                                    <small><?php echo $order->get_billing_email(); ?></small>
                                </td>
                                <td><?php echo $address_str; ?></td>
                                <td><?php echo $order->get_shipping_phone() ?: $order->get_billing_phone(); ?></td>
                                <td><?php echo $price == 0 ? 'Zadarmo' : $price . ' €'; ?></td>
                                <td><?php echo $commission > 0 ? '<strong>' . number_format($commission, 2) . ' €</strong>' : '-'; ?></td>
                                <td><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                                <td><?php echo $order->get_date_created()->date('d.m.Y H:i'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-left: 4px solid #d4af37;">
                    <h3>ℹ️ Ako to funguje?</h3>
                    <ul>
                        <li>📬 Keď si niekto objedná tvoju knihu, príde ti email s adresou</li>
                        <li>📦 Pošli knihu na doručovaciu adresu uvedenú vyššie</li>
                        <li>💰 Ak bola kniha platená, dostaneš 70% ceny, komunita dostane 30%</li>
                        <li>⏰ Požičiavateľ má knihu 30 dní</li>
                        <li>📮 Po vrátení knihu znova sprístupni v systéme</li>
                    </ul>
                </div>
            <?php else: ?>
                <p>Zatiaľ si nikto nepožičal tvoje knihy.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Upozornenie majiteľa knihy po objednávke
     */
    public static function notify_book_owner($order_id) {
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // Skontroluj či je to kniha
            if (!get_post_meta($product_id, '_book_author', true)) {
                continue;
            }

            // Nájdi majiteľa knihy
            $book_post = get_post($product_id);
            $owner_id = $book_post->post_author;
            $owner = get_userdata($owner_id);

            if (!$owner) {
                continue;
            }

            // Email majiteľovi
            $subject = '📚 Niekto si požičal tvoju knihu: ' . $item->get_name();

            $address = array(
                $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                $order->get_shipping_address_1(),
                $order->get_shipping_address_2(),
                $order->get_shipping_postcode() . ' ' . $order->get_shipping_city(),
                $order->get_shipping_country()
            );
            $address = array_filter($address);

            $message = "Ahoj " . $owner->display_name . ",\n\n";
            $message .= "Niekto si práve požičal tvoju knihu:\n";
            $message .= "📖 " . $item->get_name() . "\n\n";
            $message .= "DORUČOVACIA ADRESA:\n";
            $message .= implode("\n", $address) . "\n\n";
            $message .= "Telefón: " . ($order->get_shipping_phone() ?: $order->get_billing_phone()) . "\n";
            $message .= "Email: " . $order->get_billing_email() . "\n\n";

            $price = floatval($item->get_total());
            if ($price > 0) {
                $commission = $price * 0.70;
                $message .= "Cena požičania: " . $price . " €\n";
                $message .= "Tvoja provízia (70%): " . number_format($commission, 2) . " €\n\n";
            } else {
                $message .= "Požičanie zadarmo.\n\n";
            }

            $message .= "Prosím, pošli knihu na uvedenú adresu.\n\n";
            $message .= "Viac informácií: " . admin_url('admin.php?page=wccl-my-lendings') . "\n\n";
            $message .= "Ďakujeme,\nKomunitná knižnica potrebnymuz.sk";

            wp_mail($owner->user_email, $subject, $message);

            // Zníž stock (kniha už nie je dostupná)
            $product = wc_get_product($product_id);
            $product->set_stock_quantity(0);
            $product->set_stock_status('outofstock');
            $product->save();
        }
    }
}
