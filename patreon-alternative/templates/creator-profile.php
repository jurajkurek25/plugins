<?php
/**
 * Creator Profile Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = PA_Membership::get_instance()->get_statistics();
$tiers = PA_Tiers::get_instance()->get_all_tiers();
?>

<div class="pa-creator-profile">
    <div class="pa-profile-header">
        <div class="pa-profile-cover">
            <?php if (has_header_image()): ?>
                <img src="<?php header_image(); ?>" alt="Cover">
            <?php endif; ?>
        </div>

        <div class="pa-profile-info">
            <div class="pa-profile-avatar">
                <?php echo get_avatar(get_current_user_id(), 120); ?>
            </div>
            <div class="pa-profile-text">
                <h1><?php echo esc_html(get_option('pa_profile_title', get_bloginfo('name'))); ?></h1>
                <p class="pa-tagline"><?php echo esc_html(get_option('pa_profile_tagline')); ?></p>
                <div class="pa-profile-stats">
                    <span><strong><?php echo number_format($stats['active_patrons'] ?? 0); ?></strong> <?php _e('Patrons', 'patreon-alt'); ?></span>
                    <span><strong><?php echo wp_count_posts('pa_post')->publish; ?></strong> <?php _e('Posts', 'patreon-alt'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="pa-profile-content">
        <div class="pa-profile-main">
            <div class="pa-profile-about">
                <h2><?php _e('About', 'patreon-alt'); ?></h2>
                <?php echo wpautop(get_bloginfo('description')); ?>
            </div>

            <div class="pa-recent-posts">
                <h2><?php _e('Recent Posts', 'patreon-alt'); ?></h2>
                <?php echo do_shortcode('[pa_posts_feed posts_per_page="5"]'); ?>
            </div>
        </div>

        <div class="pa-profile-sidebar">
            <div class="pa-membership-cta">
                <h3><?php _e('Support This Creator', 'patreon-alt'); ?></h3>
                <p><?php _e('Become a patron and get exclusive access to premium content', 'patreon-alt'); ?></p>
                <a href="<?php echo get_permalink(get_option('pa_page_become_patron')); ?>" class="pa-btn pa-btn-primary pa-btn-block">
                    <?php _e('Become a Patron', 'patreon-alt'); ?>
                </a>
            </div>

            <div class="pa-tiers-preview">
                <h3><?php _e('Membership Tiers', 'patreon-alt'); ?></h3>
                <?php foreach ($tiers as $tier): ?>
                    <div class="pa-tier-preview">
                        <h4><?php echo esc_html($tier->name); ?></h4>
                        <p class="pa-tier-price"><?php echo PA_Tiers::get_instance()->format_price($tier->price, $tier->currency); ?>/<?php _e('mo', 'patreon-alt'); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
