<?php
/**
 * Admin Tiers View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pa-admin-tiers">
    <h1>
        <?php _e('Membership Tiers', 'patreon-alt'); ?>
        <a href="#" class="page-title-action pa-add-tier"><?php _e('Add New Tier', 'patreon-alt'); ?></a>
    </h1>

    <div class="pa-tiers-list">
        <?php if (empty($tiers)): ?>
            <div class="pa-empty-state">
                <p><?php _e('No membership tiers found. Create your first tier to get started!', 'patreon-alt'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'patreon-alt'); ?></th>
                        <th><?php _e('Price', 'patreon-alt'); ?></th>
                        <th><?php _e('Members', 'patreon-alt'); ?></th>
                        <th><?php _e('Status', 'patreon-alt'); ?></th>
                        <th><?php _e('Actions', 'patreon-alt'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tiers as $tier): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($tier->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="#" data-tier-id="<?php echo $tier->id; ?>" class="pa-edit-tier"><?php _e('Edit', 'patreon-alt'); ?></a></span>
                                </div>
                            </td>
                            <td><?php echo PA_Tiers::get_instance()->format_price($tier->price, $tier->currency); ?>/<?php _e('month', 'patreon-alt'); ?></td>
                            <td>
                                <?php echo $tier->current_members; ?>
                                <?php if ($tier->max_members): ?>
                                    / <?php echo $tier->max_members; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($tier->is_active): ?>
                                    <span class="pa-badge pa-badge-success"><?php _e('Active', 'patreon-alt'); ?></span>
                                <?php else: ?>
                                    <span class="pa-badge pa-badge-inactive"><?php _e('Inactive', 'patreon-alt'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button pa-edit-tier" data-tier-id="<?php echo $tier->id; ?>"><?php _e('Edit', 'patreon-alt'); ?></button>
                                <button class="button pa-delete-tier" data-tier-id="<?php echo $tier->id; ?>"><?php _e('Delete', 'patreon-alt'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
