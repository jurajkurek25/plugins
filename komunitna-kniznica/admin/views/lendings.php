<?php
/**
 * Admin Lendings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$lendings = KK_Admin_Lendings::get_all_lendings();
?>

<div class="wrap kk-admin-wrap">
    <h1><?php _e('Všetky požičania', 'komunitna-kniznica'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Kniha', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Požičiavajúci', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Majiteľ', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Cena', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Provízia majiteľa', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Provízia komunity', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Dátum požičania', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Dátum vrátenia', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Status', 'komunitna-kniznica'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($lendings)): ?>
                <tr>
                    <td colspan="10"><?php _e('Žiadne požičania neboli nájdené.', 'komunitna-kniznica'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($lendings as $lending): ?>
                    <tr>
                        <td><?php echo esc_html($lending->id); ?></td>
                        <td><strong><?php echo esc_html($lending->book_title); ?></strong><br>
                            <small><?php echo esc_html($lending->book_author); ?></small>
                        </td>
                        <td><?php echo esc_html($lending->borrower_name); ?></td>
                        <td><?php echo esc_html($lending->lender_name); ?></td>
                        <td><?php echo esc_html(number_format($lending->lending_price, 2)); ?> €</td>
                        <td><?php echo esc_html(number_format($lending->owner_commission, 2)); ?> €</td>
                        <td><?php echo esc_html(number_format($lending->community_commission, 2)); ?> €</td>
                        <td><?php echo esc_html(date('d.m.Y', strtotime($lending->start_date))); ?></td>
                        <td><?php echo esc_html(date('d.m.Y', strtotime($lending->due_date))); ?></td>
                        <td>
                            <?php
                            $status_labels = array(
                                'active' => __('Aktívne', 'komunitna-kniznica'),
                                'returned' => __('Vrátené', 'komunitna-kniznica')
                            );
                            echo '<span class="kk-status kk-status-' . esc_attr($lending->status) . '">';
                            echo esc_html($status_labels[$lending->status]);
                            echo '</span>';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
