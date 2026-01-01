<?php
/**
 * Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('pa_creator_profile', array($this, 'creator_profile'));
        add_shortcode('pa_membership_tiers', array($this, 'membership_tiers'));
        add_shortcode('pa_posts_feed', array($this, 'posts_feed'));
        add_shortcode('pa_my_membership', array($this, 'my_membership'));
    }

    /**
     * Creator profile shortcode
     */
    public function creator_profile($atts) {
        ob_start();
        include PA_PLUGIN_DIR . 'templates/creator-profile.php';
        return ob_get_clean();
    }

    /**
     * Membership tiers shortcode
     */
    public function membership_tiers($atts) {
        $tiers = PA_Tiers::get_instance()->get_all_tiers();

        ob_start();
        ?>
        <div class="pa-membership-tiers">
            <div class="pa-tiers-header">
                <h2><?php _e('Become a Patron', 'patreon-alt'); ?></h2>
                <p><?php _e('Choose your membership level and get exclusive access to premium content', 'patreon-alt'); ?></p>
            </div>

            <div class="pa-tiers-grid">
                <?php foreach ($tiers as $tier): ?>
                    <?php
                    $benefits = PA_Tiers::get_instance()->get_tier_benefits($tier->id);
                    $is_full = PA_Tiers::get_instance()->is_tier_full($tier->id);
                    ?>
                    <div class="pa-tier-card <?php echo $is_full ? 'pa-tier-full' : ''; ?>">
                        <div class="pa-tier-header">
                            <h3><?php echo esc_html($tier->name); ?></h3>
                            <div class="pa-tier-price">
                                <?php echo PA_Tiers::get_instance()->format_price($tier->price, $tier->currency); ?>
                                <span class="pa-tier-period">/<?php _e('month', 'patreon-alt'); ?></span>
                            </div>
                        </div>

                        <div class="pa-tier-description">
                            <?php echo wpautop(esc_html($tier->description)); ?>
                        </div>

                        <div class="pa-tier-benefits">
                            <h4><?php _e('Benefits', 'patreon-alt'); ?></h4>
                            <ul>
                                <?php foreach ($benefits as $benefit): ?>
                                    <li>✓ <?php echo esc_html($benefit); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="pa-tier-members">
                            <?php if ($tier->max_members): ?>
                                <?php echo sprintf(__('%d of %d members', 'patreon-alt'), $tier->current_members, $tier->max_members); ?>
                            <?php else: ?>
                                <?php echo sprintf(__('%d members', 'patreon-alt'), $tier->current_members); ?>
                            <?php endif; ?>
                        </div>

                        <div class="pa-tier-action">
                            <?php if ($is_full): ?>
                                <button class="pa-btn pa-btn-disabled" disabled>
                                    <?php _e('Tier Full', 'patreon-alt'); ?>
                                </button>
                            <?php elseif (is_user_logged_in()): ?>
                                <button class="pa-btn pa-btn-primary pa-join-tier" data-tier-id="<?php echo $tier->id; ?>">
                                    <?php _e('Join This Tier', 'patreon-alt'); ?>
                                </button>
                            <?php else: ?>
                                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="pa-btn pa-btn-primary">
                                    <?php _e('Login to Join', 'patreon-alt'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Posts feed shortcode
     */
    public function posts_feed($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
            'tier_id' => null
        ), $atts);

        $args = array(
            'post_type' => 'pa_post',
            'posts_per_page' => $atts['posts_per_page'],
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);

        ob_start();
        ?>
        <div class="pa-posts-feed">
            <?php if ($query->have_posts()): ?>
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <?php
                    $patron_post = PA_Posts::get_instance()->get_patron_post(get_the_ID());
                    $can_access = PA_Posts::get_instance()->user_can_access_post(get_current_user_id(), get_the_ID());
                    ?>
                    <article class="pa-post-item <?php echo $can_access ? '' : 'pa-post-locked'; ?>">
                        <div class="pa-post-header">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <div class="pa-post-meta">
                                <?php the_time(get_option('date_format')); ?>
                                <?php if (!$can_access): ?>
                                    <span class="pa-post-lock-icon">🔒</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pa-post-content">
                            <?php if ($can_access): ?>
                                <?php the_excerpt(); ?>
                            <?php else: ?>
                                <p><?php _e('This content is for patrons only.', 'patreon-alt'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="pa-post-footer">
                            <a href="<?php the_permalink(); ?>" class="pa-btn">
                                <?php echo $can_access ? __('Read More', 'patreon-alt') : __('Become a Patron', 'patreon-alt'); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else: ?>
                <p><?php _e('No posts found.', 'patreon-alt'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * My membership shortcode
     */
    public function my_membership($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to view your membership.', 'patreon-alt') . '</p>';
        }

        $user_id = get_current_user_id();
        $membership = PA_Membership::get_instance()->get_user_membership($user_id);

        ob_start();
        ?>
        <div class="pa-my-membership">
            <h2><?php _e('My Membership', 'patreon-alt'); ?></h2>

            <?php if ($membership): ?>
                <?php $tier = PA_Tiers::get_instance()->get_tier($membership->tier_id); ?>

                <div class="pa-membership-card">
                    <div class="pa-membership-header">
                        <h3><?php echo esc_html($tier->name); ?></h3>
                        <span class="pa-badge pa-badge-<?php echo $membership->status; ?>">
                            <?php echo ucfirst($membership->status); ?>
                        </span>
                    </div>

                    <div class="pa-membership-details">
                        <p><strong><?php _e('Price:', 'patreon-alt'); ?></strong> <?php echo PA_Tiers::get_instance()->format_price($tier->price, $tier->currency); ?>/<?php _e('month', 'patreon-alt'); ?></p>
                        <p><strong><?php _e('Member since:', 'patreon-alt'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($membership->start_date)); ?></p>
                        <p><strong><?php _e('Next billing:', 'patreon-alt'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($membership->next_billing_date)); ?></p>
                    </div>

                    <div class="pa-membership-benefits">
                        <h4><?php _e('Your Benefits', 'patreon-alt'); ?></h4>
                        <ul>
                            <?php foreach (PA_Tiers::get_instance()->get_tier_benefits($tier->id) as $benefit): ?>
                                <li>✓ <?php echo esc_html($benefit); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="pa-membership-actions">
                        <button class="pa-btn pa-btn-danger pa-cancel-membership">
                            <?php _e('Cancel Membership', 'patreon-alt'); ?>
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <div class="pa-no-membership">
                    <p><?php _e('You are not currently a patron.', 'patreon-alt'); ?></p>
                    <a href="<?php echo get_permalink(get_option('pa_page_become_patron')); ?>" class="pa-btn pa-btn-primary">
                        <?php _e('Become a Patron', 'patreon-alt'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
