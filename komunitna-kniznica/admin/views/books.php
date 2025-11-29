<?php
/**
 * Admin Books View
 */

if (!defined('ABSPATH')) {
    exit;
}

$books = KK_Admin_Books::get_all_books();
?>

<div class="wrap kk-admin-wrap">
    <h1><?php _e('Všetky knihy', 'komunitna-kniznica'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Názov', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Autor', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Žáner', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Majiteľ', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Cena požičania', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Stav knihy', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Status', 'komunitna-kniznica'); ?></th>
                <th><?php _e('Dátum pridania', 'komunitna-kniznica'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($books)): ?>
                <tr>
                    <td colspan="9"><?php _e('Žiadne knihy neboli nájdené.', 'komunitna-kniznica'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><?php echo esc_html($book->id); ?></td>
                        <td><strong><?php echo esc_html($book->title); ?></strong></td>
                        <td><?php echo esc_html($book->author); ?></td>
                        <td><?php echo esc_html($book->genre); ?></td>
                        <td><?php echo esc_html($book->owner_name); ?></td>
                        <td><?php echo esc_html(number_format($book->lending_price, 2)); ?> €</td>
                        <td><?php echo esc_html($book->book_condition); ?>/10</td>
                        <td>
                            <?php
                            $status_labels = array(
                                'available' => __('Dostupná', 'komunitna-kniznica'),
                                'reserved' => __('Rezervovaná', 'komunitna-kniznica'),
                                'borrowed' => __('Požičaná', 'komunitna-kniznica')
                            );
                            echo '<span class="kk-status kk-status-' . esc_attr($book->status) . '">';
                            echo esc_html($status_labels[$book->status]);
                            echo '</span>';
                            ?>
                        </td>
                        <td><?php echo esc_html(date('d.m.Y', strtotime($book->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
