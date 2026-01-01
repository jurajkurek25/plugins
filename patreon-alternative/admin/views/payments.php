<?php
/**
 * Admin Payments View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pa-admin-payments">
    <h1><?php _e('Payment History', 'patreon-alt'); ?></h1>

    <div class="pa-payments-list">
        <?php if (empty($payments)): ?>
            <div class="pa-empty-state">
                <p><?php _e('No payments yet.', 'patreon-alt'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'patreon-alt'); ?></th>
                        <th><?php _e('Member', 'patreon-alt'); ?></th>
                        <th><?php _e('Amount', 'patreon-alt'); ?></th>
                        <th><?php _e('Status', 'patreon-alt'); ?></th>
                        <th><?php _e('Method', 'patreon-alt'); ?></th>
                        <th><?php _e('Transaction ID', 'patreon-alt'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($payment->payment_date)); ?></td>
                            <td><?php echo esc_html($payment->display_name); ?></td>
                            <td><strong><?php echo $payment->currency; ?> <?php echo number_format($payment->amount, 2); ?></strong></td>
                            <td>
                                <span class="pa-badge pa-badge-<?php echo $payment->status; ?>">
                                    <?php echo ucfirst($payment->status); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($payment->payment_method); ?></td>
                            <td><code><?php echo esc_html($payment->transaction_id); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
