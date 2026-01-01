<?php
/**
 * Platobný systém (Stripe integrácia)
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Payments {

    private static $instance = null;
    private $stripe_secret_key;
    private $stripe_public_key;
    private $test_mode;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->test_mode = get_option('pa_stripe_test_mode', true);

        if ($this->test_mode) {
            $this->stripe_secret_key = get_option('pa_stripe_test_secret_key');
            $this->stripe_public_key = get_option('pa_stripe_test_public_key');
        } else {
            $this->stripe_secret_key = get_option('pa_stripe_live_secret_key');
            $this->stripe_public_key = get_option('pa_stripe_live_public_key');
        }

        add_action('wp_ajax_pa_create_checkout_session', array($this, 'create_checkout_session'));
        add_action('wp_ajax_pa_cancel_subscription', array($this, 'cancel_subscription'));
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }

    /**
     * Vytvorenie Stripe checkout session
     */
    public function create_checkout_session() {
        check_ajax_referer('pa_checkout', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login first', 'patreon-alt')));
        }

        $tier_id = isset($_POST['tier_id']) ? intval($_POST['tier_id']) : 0;

        if (!$tier_id) {
            wp_send_json_error(array('message' => __('Invalid tier', 'patreon-alt')));
        }

        $tier = PA_Tiers::get_instance()->get_tier($tier_id);

        if (!$tier) {
            wp_send_json_error(array('message' => __('Tier not found', 'patreon-alt')));
        }

        // Skontrolovať či tier nie je plný
        if (PA_Tiers::get_instance()->is_tier_full($tier_id)) {
            wp_send_json_error(array('message' => __('This tier is full', 'patreon-alt')));
        }

        try {
            // Vytvorenie Stripe checkout session
            // V production by sme použili Stripe PHP SDK
            $session_data = array(
                'tier_id' => $tier_id,
                'user_id' => get_current_user_id(),
                'amount' => $tier->price,
                'currency' => $tier->currency,
                'success_url' => home_url('/my-membership?success=1'),
                'cancel_url' => home_url('/become-patron?cancelled=1')
            );

            // Simulácia Stripe session ID (v production použiť skutočné Stripe API)
            $session_id = 'cs_test_' . wp_generate_password(24, false);

            // Uložiť pending payment
            $this->create_payment_record(
                get_current_user_id(),
                $tier_id,
                $tier->price,
                $tier->currency,
                'pending',
                'stripe',
                $session_id
            );

            wp_send_json_success(array(
                'session_id' => $session_id,
                'redirect_url' => $session_data['success_url']
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Vytvorenie payment záznamu
     */
    public function create_payment_record($user_id, $tier_id, $amount, $currency, $status, $method, $transaction_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('payments');

        // Získanie membership ID
        $membership = PA_Membership::get_instance()->get_user_membership($user_id);
        $membership_id = $membership ? $membership->id : 0;

        return $wpdb->insert($table, array(
            'user_id' => $user_id,
            'membership_id' => $membership_id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'payment_method' => $method,
            'transaction_id' => $transaction_id,
            'payment_date' => current_time('mysql')
        ));
    }

    /**
     * Aktualizácia payment statusu
     */
    public function update_payment_status($payment_id, $status) {
        global $wpdb;
        $table = PA_Database::get_table_name('payments');

        return $wpdb->update(
            $table,
            array('status' => $status),
            array('id' => $payment_id)
        );
    }

    /**
     * Zrušenie subscription
     */
    public function cancel_subscription() {
        check_ajax_referer('pa_cancel_subscription', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login first', 'patreon-alt')));
        }

        $user_id = get_current_user_id();
        $membership = PA_Membership::get_instance()->get_user_membership($user_id);

        if (!$membership) {
            wp_send_json_error(array('message' => __('No active membership found', 'patreon-alt')));
        }

        try {
            // Zrušiť v Stripe (ak je Stripe subscription ID)
            if ($membership->stripe_subscription_id) {
                // V production by sme zrušili cez Stripe API
                // \Stripe\Subscription::update($membership->stripe_subscription_id, ['cancel_at_period_end' => true]);
            }

            // Zrušiť membership
            PA_Membership::get_instance()->cancel_membership($membership->id);

            wp_send_json_success(array('message' => __('Membership cancelled successfully', 'patreon-alt')));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Registrácia webhook endpointu pre Stripe
     */
    public function register_webhook_endpoint() {
        register_rest_route('patreon-alt/v1', '/webhook/stripe', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_stripe_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Spracovanie Stripe webhookov
     */
    public function handle_stripe_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');

        try {
            // Validácia Stripe signature
            // V production by sme validovali cez Stripe SDK
            $event = json_decode($payload);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handle_checkout_completed($event->data->object);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handle_payment_succeeded($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handle_subscription_cancelled($event->data->object);
                    break;
            }

            return new WP_REST_Response(array('received' => true), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 400);
        }
    }

    /**
     * Spracovanie dokončeného checkout
     */
    private function handle_checkout_completed($session) {
        // Získať tier_id a user_id z metadata
        $tier_id = $session->metadata->tier_id ?? 0;
        $user_id = $session->metadata->user_id ?? 0;

        if ($tier_id && $user_id) {
            // Vytvoriť membership
            PA_Membership::get_instance()->create_membership(
                $user_id,
                $tier_id,
                'stripe',
                $session->subscription ?? null
            );

            // Aktualizovať payment status
            global $wpdb;
            $table = PA_Database::get_table_name('payments');
            $wpdb->update(
                $table,
                array('status' => 'completed'),
                array('transaction_id' => $session->id)
            );
        }
    }

    /**
     * Spracovanie úspešnej platby
     */
    private function handle_payment_succeeded($invoice) {
        // Obnoviť membership - posunúť next_billing_date
        if ($invoice->subscription) {
            global $wpdb;
            $table = PA_Database::get_table_name('memberships');

            $wpdb->update(
                $table,
                array('next_billing_date' => date('Y-m-d H:i:s', strtotime('+1 month'))),
                array('stripe_subscription_id' => $invoice->subscription)
            );
        }
    }

    /**
     * Spracovanie zrušenej subscription
     */
    private function handle_subscription_cancelled($subscription) {
        global $wpdb;
        $table = PA_Database::get_table_name('memberships');

        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE stripe_subscription_id = %s",
            $subscription->id
        ));

        if ($membership) {
            PA_Membership::get_instance()->cancel_membership($membership->id);
        }
    }

    /**
     * Získanie verejného Stripe kľúča
     */
    public function get_public_key() {
        return $this->stripe_public_key;
    }
}
