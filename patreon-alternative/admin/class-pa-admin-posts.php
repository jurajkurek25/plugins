<?php
/**
 * Admin Posts Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Admin_Posts {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Pridanie meta boxov
     */
    public function add_meta_boxes() {
        add_meta_box(
            'pa_post_settings',
            __('Patron Post Settings', 'patreon-alt'),
            array($this, 'render_post_settings_meta_box'),
            'pa_post',
            'side',
            'high'
        );

        add_meta_box(
            'pa_post_video',
            __('Video Settings', 'patreon-alt'),
            array($this, 'render_video_meta_box'),
            'pa_post',
            'normal',
            'high'
        );
    }

    /**
     * Render post settings meta box
     */
    public function render_post_settings_meta_box($post) {
        wp_nonce_field('pa_post_settings', 'pa_post_settings_nonce');

        $patron_post = PA_Posts::get_instance()->get_patron_post($post->ID);
        $tier_id = $patron_post ? $patron_post->tier_id : 0;
        $is_public = $patron_post ? $patron_post->is_public : 0;

        $tiers = PA_Tiers::get_instance()->get_all_tiers(false);
        ?>
        <p>
            <label for="pa_tier_id"><strong><?php _e('Required Tier', 'patreon-alt'); ?></strong></label>
            <select id="pa_tier_id" name="pa_tier_id" style="width:100%;">
                <option value="0"><?php _e('Select Tier', 'patreon-alt'); ?></option>
                <?php foreach ($tiers as $tier): ?>
                    <option value="<?php echo $tier->id; ?>" <?php selected($tier_id, $tier->id); ?>>
                        <?php echo esc_html($tier->name); ?> (<?php echo PA_Tiers::get_instance()->format_price($tier->price, $tier->currency); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label>
                <input type="checkbox" name="pa_is_public" value="1" <?php checked($is_public, 1); ?>>
                <?php _e('Make this post public (free)', 'patreon-alt'); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Render video meta box
     */
    public function render_video_meta_box($post) {
        $patron_post = PA_Posts::get_instance()->get_patron_post($post->ID);
        $has_video = $patron_post ? $patron_post->has_video : 0;
        $video_url = $patron_post ? $patron_post->video_url : '';
        ?>
        <p>
            <label>
                <input type="checkbox" name="pa_has_video" id="pa_has_video" value="1" <?php checked($has_video, 1); ?>>
                <?php _e('This post includes video', 'patreon-alt'); ?>
            </label>
        </p>

        <div id="pa_video_settings" style="<?php echo $has_video ? '' : 'display:none;'; ?>">
            <p>
                <label for="pa_video_url"><strong><?php _e('Video URL', 'patreon-alt'); ?></strong></label>
                <input type="url" id="pa_video_url" name="pa_video_url" value="<?php echo esc_url($video_url); ?>" class="widefat">
                <span class="description"><?php _e('Enter direct video file URL (.mp4, .webm, etc.)', 'patreon-alt'); ?></span>
            </p>

            <p>
                <button type="button" class="button" id="pa_upload_video"><?php _e('Upload Video', 'patreon-alt'); ?></button>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#pa_has_video').on('change', function() {
                $('#pa_video_settings').toggle(this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Načítanie skriptov
     */
    public function enqueue_scripts($hook) {
        global $post_type;

        if ($post_type !== 'pa_post') {
            return;
        }

        wp_enqueue_media();
    }
}
