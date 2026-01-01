<?php
/**
 * Admin Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pa-admin-settings">
    <h1><?php _e('Patreon Alternative Settings', 'patreon-alt'); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('pa_settings'); ?>

        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'patreon-alt'); ?></a>
            <a href="#stripe" class="nav-tab"><?php _e('Stripe Payment', 'patreon-alt'); ?></a>
            <a href="#video" class="nav-tab"><?php _e('Video Player', 'patreon-alt'); ?></a>
        </h2>

        <div id="general" class="pa-settings-tab">
            <h2><?php _e('General Settings', 'patreon-alt'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pa_profile_title"><?php _e('Profile Title', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="pa_profile_title" name="pa_profile_title" value="<?php echo esc_attr(get_option('pa_profile_title')); ?>" class="regular-text">
                        <p class="description"><?php _e('The main title displayed on your creator profile', 'patreon-alt'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_profile_tagline"><?php _e('Profile Tagline', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="pa_profile_tagline" name="pa_profile_tagline" value="<?php echo esc_attr(get_option('pa_profile_tagline')); ?>" class="regular-text">
                        <p class="description"><?php _e('A short description of what you create', 'patreon-alt'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_currency"><?php _e('Currency', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <select id="pa_currency" name="pa_currency">
                            <option value="EUR" <?php selected(get_option('pa_currency'), 'EUR'); ?>>EUR (€)</option>
                            <option value="USD" <?php selected(get_option('pa_currency'), 'USD'); ?>>USD ($)</option>
                            <option value="GBP" <?php selected(get_option('pa_currency'), 'GBP'); ?>>GBP (£)</option>
                            <option value="CZK" <?php selected(get_option('pa_currency'), 'CZK'); ?>>CZK (Kč)</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div id="stripe" class="pa-settings-tab" style="display:none;">
            <h2><?php _e('Stripe Payment Settings', 'patreon-alt'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pa_stripe_test_mode"><?php _e('Test Mode', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="pa_stripe_test_mode" name="pa_stripe_test_mode" value="1" <?php checked(get_option('pa_stripe_test_mode'), 1); ?>>
                            <?php _e('Enable test mode (use test API keys)', 'patreon-alt'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row" colspan="2">
                        <h3><?php _e('Test API Keys', 'patreon-alt'); ?></h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_stripe_test_public_key"><?php _e('Test Publishable Key', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="pa_stripe_test_public_key" name="pa_stripe_test_public_key" value="<?php echo esc_attr(get_option('pa_stripe_test_public_key')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_stripe_test_secret_key"><?php _e('Test Secret Key', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="pa_stripe_test_secret_key" name="pa_stripe_test_secret_key" value="<?php echo esc_attr(get_option('pa_stripe_test_secret_key')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row" colspan="2">
                        <h3><?php _e('Live API Keys', 'patreon-alt'); ?></h3>
                    </th>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_stripe_live_public_key"><?php _e('Live Publishable Key', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="pa_stripe_live_public_key" name="pa_stripe_live_public_key" value="<?php echo esc_attr(get_option('pa_stripe_live_public_key')); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_stripe_live_secret_key"><?php _e('Live Secret Key', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="pa_stripe_live_secret_key" name="pa_stripe_live_secret_key" value="<?php echo esc_attr(get_option('pa_stripe_live_secret_key')); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <div id="video" class="pa-settings-tab" style="display:none;">
            <h2><?php _e('Video Player Settings', 'patreon-alt'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pa_enable_video_player"><?php _e('Enable Video Player', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="pa_enable_video_player" name="pa_enable_video_player" value="1" <?php checked(get_option('pa_enable_video_player'), 1); ?>>
                            <?php _e('Enable HTML5 video player for patron posts', 'patreon-alt'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="pa_video_protection"><?php _e('Video Protection', 'patreon-alt'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="pa_video_protection" name="pa_video_protection" value="1" <?php checked(get_option('pa_video_protection'), 1); ?>>
                            <?php _e('Enable video watermark and download protection', 'patreon-alt'); ?>
                        </label>
                        <p class="description"><?php _e('Adds username watermark and disables right-click/download', 'patreon-alt'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.pa-settings-tab').hide();
        $(target).show();
    });
});
</script>
