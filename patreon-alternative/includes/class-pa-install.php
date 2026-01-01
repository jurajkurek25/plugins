<?php
/**
 * Inštalačná trieda
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Install {

    /**
     * Aktivácia pluginu
     */
    public static function activate() {
        // Vytvorenie databázových tabuliek
        PA_Database::create_tables();

        // Vytvorenie základných stránok
        self::create_pages();

        // Vytvorenie default tier-ov
        self::create_default_tiers();

        // Nastavenie default options
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Nastavenie transient pre welcome notice
        set_transient('pa_activation_notice', true, 60);

        // Log aktivácie
        update_option('pa_activation_date', current_time('mysql'));
        update_option('pa_version', PA_VERSION);
    }

    /**
     * Deaktivácia pluginu
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Vymazanie transientov
        delete_transient('pa_activation_notice');
    }

    /**
     * Vytvorenie základných stránok
     */
    private static function create_pages() {
        $pages = array(
            'creator-profile' => array(
                'title' => 'Creator Profile',
                'content' => '[pa_creator_profile]',
                'template' => 'page-creator-profile.php'
            ),
            'become-patron' => array(
                'title' => 'Become a Patron',
                'content' => '[pa_membership_tiers]',
                'template' => ''
            ),
            'patron-posts' => array(
                'title' => 'Patron Posts',
                'content' => '[pa_posts_feed]',
                'template' => ''
            ),
            'my-membership' => array(
                'title' => 'My Membership',
                'content' => '[pa_my_membership]',
                'template' => ''
            )
        );

        foreach ($pages as $slug => $page) {
            // Skontrolovať či stránka už existuje
            $page_check = get_page_by_path($slug);

            if (!$page_check) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'comment_status' => 'closed'
                ));

                // Uloženie ID stránky do options
                update_option('pa_page_' . str_replace('-', '_', $slug), $page_id);
            }
        }
    }

    /**
     * Vytvorenie základných membership tiers
     */
    private static function create_default_tiers() {
        global $wpdb;
        $table = PA_Database::get_table_name('tiers');

        // Skontrolovať či už existujú nejaké tiers
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if ($existing == 0) {
            $default_tiers = array(
                array(
                    'name' => 'Bronze Patron',
                    'description' => 'Základná podpora pre začínajúcich patrónovnov',
                    'price' => 5.00,
                    'currency' => 'EUR',
                    'benefits' => json_encode(array(
                        'Prístup k exkluzívnym postom',
                        'Mesačný newsletter',
                        'Discord prístup'
                    )),
                    'sort_order' => 1
                ),
                array(
                    'name' => 'Silver Patron',
                    'description' => 'Stredná úroveň podpory s dodatočnými výhodami',
                    'price' => 10.00,
                    'currency' => 'EUR',
                    'benefits' => json_encode(array(
                        'Všetky Bronze výhody',
                        'Prístup k video obsahu',
                        'Hlasovanie v polls',
                        'Early access k novému obsahu'
                    )),
                    'sort_order' => 2
                ),
                array(
                    'name' => 'Gold Patron',
                    'description' => 'Premium úroveň s maximálnymi výhodami',
                    'price' => 25.00,
                    'currency' => 'EUR',
                    'benefits' => json_encode(array(
                        'Všetky Silver výhody',
                        'Personalizované poďakovanie',
                        '1-on-1 video call mesačne',
                        'Prístup k behind-the-scenes obsahu',
                        'Merchandise discount 20%'
                    )),
                    'sort_order' => 3
                )
            );

            foreach ($default_tiers as $tier) {
                $wpdb->insert($table, $tier);
            }
        }
    }

    /**
     * Nastavenie default options
     */
    private static function set_default_options() {
        $default_options = array(
            'pa_currency' => 'EUR',
            'pa_currency_symbol' => '€',
            'pa_payment_gateway' => 'stripe',
            'pa_enable_video_player' => '1',
            'pa_video_protection' => '1',
            'pa_allow_comments' => '1',
            'pa_allow_likes' => '1',
            'pa_theme_enabled' => '1',
            'pa_profile_title' => get_bloginfo('name'),
            'pa_profile_tagline' => 'Support my creative work',
            'pa_stripe_test_mode' => '1'
        );

        foreach ($default_options as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
    }
}
