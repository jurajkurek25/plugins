<?php
/**
 * Admin Members View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pa-admin-members">
    <h1><?php _e('Patron Members', 'patreon-alt'); ?></h1>

    <div class="pa-members-list">
        <?php if (empty($members)): ?>
            <div class="pa-empty-state">
                <p><?php _e('No patrons yet. Share your membership page to get your first supporter!', 'patreon-alt'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Member', 'patreon-alt'); ?></th>
                        <th><?php _e('Email', 'patreon-alt'); ?></th>
                        <th><?php _e('Tier', 'patreon-alt'); ?></th>
                        <th><?php _e('Status', 'patreon-alt'); ?></th>
                        <th><?php _e('Member Since', 'patreon-alt'); ?></th>
                        <th><?php _e('Next Billing', 'patreon-alt'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <?php
                        $tier = PA_Tiers::get_instance()->get_tier($member->tier_id);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($member->display_name); ?></strong></td>
                            <td><?php echo esc_html($member->user_email); ?></td>
                            <td><?php echo $tier ? esc_html($tier->name) : '-'; ?></td>
                            <td>
                                <span class="pa-badge pa-badge-<?php echo $member->status; ?>">
                                    <?php echo ucfirst($member->status); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($member->start_date)); ?></td>
                            <td><?php echo $member->next_billing_date ? date_i18n(get_option('date_format'), strtotime($member->next_billing_date)) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
