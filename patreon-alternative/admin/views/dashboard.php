<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pa-admin-dashboard">
    <h1><?php _e('Patreon Alternative Dashboard', 'patreon-alt'); ?></h1>

    <div class="pa-dashboard-welcome">
        <h2>🎉 <?php _e('Welcome to Your Creator Platform!', 'patreon-alt'); ?></h2>
        <p><?php _e('Build and monetize your community with membership tiers, exclusive content, and video streaming.', 'patreon-alt'); ?></p>
    </div>

    <div class="pa-stats-grid">
        <div class="pa-stat-card">
            <div class="pa-stat-icon">👥</div>
            <div class="pa-stat-content">
                <div class="pa-stat-label"><?php _e('Active Patrons', 'patreon-alt'); ?></div>
                <div class="pa-stat-value"><?php echo number_format($stats['active_patrons'] ?? 0); ?></div>
            </div>
        </div>

        <div class="pa-stat-card">
            <div class="pa-stat-icon">💰</div>
            <div class="pa-stat-content">
                <div class="pa-stat-label"><?php _e('Monthly Revenue', 'patreon-alt'); ?></div>
                <div class="pa-stat-value">€<?php echo number_format($stats['monthly_revenue'] ?? 0, 2); ?></div>
            </div>
        </div>

        <div class="pa-stat-card">
            <div class="pa-stat-icon">📊</div>
            <div class="pa-stat-content">
                <div class="pa-stat-label"><?php _e('Total Revenue', 'patreon-alt'); ?></div>
                <div class="pa-stat-value">€<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
            </div>
        </div>

        <div class="pa-stat-card">
            <div class="pa-stat-icon">📝</div>
            <div class="pa-stat-content">
                <div class="pa-stat-label"><?php _e('Total Posts', 'patreon-alt'); ?></div>
                <div class="pa-stat-value"><?php echo wp_count_posts('pa_post')->publish; ?></div>
            </div>
        </div>
    </div>

    <div class="pa-quick-actions">
        <h2><?php _e('Quick Actions', 'patreon-alt'); ?></h2>
        <div class="pa-actions-grid">
            <a href="<?php echo admin_url('post-new.php?post_type=pa_post'); ?>" class="pa-action-card">
                <span class="dashicons dashicons-edit-large"></span>
                <span><?php _e('Create New Post', 'patreon-alt'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=pa-tiers'); ?>" class="pa-action-card">
                <span class="dashicons dashicons-awards"></span>
                <span><?php _e('Manage Tiers', 'patreon-alt'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=pa-members'); ?>" class="pa-action-card">
                <span class="dashicons dashicons-groups"></span>
                <span><?php _e('View Members', 'patreon-alt'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=pa-settings'); ?>" class="pa-action-card">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php _e('Settings', 'patreon-alt'); ?></span>
            </a>
        </div>
    </div>

    <div class="pa-setup-checklist">
        <h2><?php _e('Setup Checklist', 'patreon-alt'); ?></h2>
        <ul class="pa-checklist">
            <li class="<?php echo get_option('pa_stripe_test_public_key') ? 'completed' : ''; ?>">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Configure Stripe payment settings', 'patreon-alt'); ?>
                <?php if (!get_option('pa_stripe_test_public_key')): ?>
                    <a href="<?php echo admin_url('admin.php?page=pa-settings'); ?>"><?php _e('Configure now', 'patreon-alt'); ?></a>
                <?php endif; ?>
            </li>
            <li class="<?php echo PA_Tiers::get_instance()->get_all_tiers() ? 'completed' : ''; ?>">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Create membership tiers', 'patreon-alt'); ?>
                <?php if (!PA_Tiers::get_instance()->get_all_tiers()): ?>
                    <a href="<?php echo admin_url('admin.php?page=pa-tiers'); ?>"><?php _e('Create tiers', 'patreon-alt'); ?></a>
                <?php endif; ?>
            </li>
            <li class="<?php echo wp_count_posts('pa_post')->publish > 0 ? 'completed' : ''; ?>">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Publish your first patron post', 'patreon-alt'); ?>
                <?php if (wp_count_posts('pa_post')->publish == 0): ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=pa_post'); ?>"><?php _e('Create post', 'patreon-alt'); ?></a>
                <?php endif; ?>
            </li>
            <li>
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Activate Patreon-style theme', 'patreon-alt'); ?>
                <a href="<?php echo admin_url('themes.php'); ?>"><?php _e('Go to themes', 'patreon-alt'); ?></a>
            </li>
        </ul>
    </div>
</div>
