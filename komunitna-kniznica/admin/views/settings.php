<?php
/**
 * Admin Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$commission_rate = get_option('kk_commission_rate', 30);
$lending_days = get_option('kk_default_lending_days', 30);
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<div class="wrap kk-admin-wrap">
    <h1><?php _e('Nastavenia Komunitnej Knižnice', 'komunitna-kniznica'); ?></h1>

    <?php if ($message === 'saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Nastavenia boli úspešne uložené.', 'komunitna-kniznica'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="kk_save_settings">
        <?php wp_nonce_field('kk_save_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="kk_commission_rate"><?php _e('Provízna sadzba komunity (%)', 'komunitna-kniznica'); ?></label>
                </th>
                <td>
                    <input type="number" id="kk_commission_rate" name="kk_commission_rate"
                           value="<?php echo esc_attr($commission_rate); ?>"
                           min="0" max="100" step="1" class="regular-text">
                    <p class="description">
                        <?php _e('Percento z ceny požičania, ktoré ide komunite. Zvyšok ide majiteľovi knihy.', 'komunitna-kniznica'); ?>
                        <br>
                        <?php printf(__('Príklad: Pri 30%% a cene 10€, komunita dostane 3€ a majiteľ 7€.', 'komunitna-kniznica')); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="kk_default_lending_days"><?php _e('Predvolená dĺžka požičania (dni)', 'komunitna-kniznica'); ?></label>
                </th>
                <td>
                    <input type="number" id="kk_default_lending_days" name="kk_default_lending_days"
                           value="<?php echo esc_attr($lending_days); ?>"
                           min="1" max="365" step="1" class="regular-text">
                    <p class="description">
                        <?php _e('Počet dní, na ktoré sa knihy požičiavajú.', 'komunitna-kniznica'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Uložiť nastavenia', 'komunitna-kniznica')); ?>
    </form>

    <hr>

    <h2><?php _e('Informácie o plugine', 'komunitna-kniznica'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Verzia pluginu', 'komunitna-kniznica'); ?></th>
            <td><?php echo KK_VERSION; ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Dummy produkt ID', 'komunitna-kniznica'); ?></th>
            <td><?php echo get_option('kk_dummy_product_id', __('Nenájdené', 'komunitna-kniznica')); ?></td>
        </tr>
    </table>
</div>
