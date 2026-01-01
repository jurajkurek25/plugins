<?php
/**
 * HTML5 Video Player s ochranou obsahu
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Video_Player {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_pa_get_video_token', array($this, 'get_video_token'));
        add_action('wp_ajax_nopriv_pa_get_video_token', array($this, 'get_video_token'));
    }

    /**
     * Načítanie skriptov
     */
    public function enqueue_scripts() {
        wp_enqueue_style('pa-video-player', PA_PLUGIN_URL . 'assets/css/video-player.css', array(), PA_VERSION);
        wp_enqueue_script('pa-video-player', PA_PLUGIN_URL . 'assets/js/video-player.js', array('jquery'), PA_VERSION, true);

        wp_localize_script('pa-video-player', 'paVideoPlayer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pa_video_player')
        ));
    }

    /**
     * Renderovanie video playera
     */
    public function render_player($video_url, $options = array()) {
        $defaults = array(
            'width' => '100%',
            'height' => 'auto',
            'controls' => true,
            'autoplay' => false,
            'poster' => '',
            'disable_download' => true,
            'disable_right_click' => true
        );

        $options = wp_parse_args($options, $defaults);

        // Generovanie token pre video
        $token = $this->generate_video_token($video_url);

        ob_start();
        ?>
        <div class="pa-video-player-wrapper" data-disable-download="<?php echo $options['disable_download'] ? '1' : '0'; ?>">
            <video
                id="pa-video-<?php echo md5($video_url); ?>"
                class="pa-video-player"
                width="<?php echo esc_attr($options['width']); ?>"
                height="<?php echo esc_attr($options['height']); ?>"
                <?php echo $options['controls'] ? 'controls' : ''; ?>
                <?php echo $options['autoplay'] ? 'autoplay' : ''; ?>
                <?php echo $options['poster'] ? 'poster="' . esc_url($options['poster']) . '"' : ''; ?>
                controlsList="nodownload"
                oncontextmenu="return false;"
                data-video-token="<?php echo esc_attr($token); ?>"
            >
                <source src="<?php echo esc_url($video_url); ?>" type="<?php echo $this->get_video_mime_type($video_url); ?>">
                <?php _e('Your browser does not support the video tag.', 'patreon-alt'); ?>
            </video>

            <?php if ($options['disable_download']): ?>
            <div class="pa-video-overlay"></div>
            <?php endif; ?>

            <div class="pa-video-controls">
                <button class="pa-play-pause" aria-label="<?php _e('Play/Pause', 'patreon-alt'); ?>">
                    <span class="pa-icon-play">▶</span>
                    <span class="pa-icon-pause" style="display:none;">⏸</span>
                </button>
                <div class="pa-progress-bar">
                    <div class="pa-progress-filled"></div>
                </div>
                <div class="pa-time-display">
                    <span class="pa-current-time">0:00</span> / <span class="pa-duration">0:00</span>
                </div>
                <div class="pa-volume-control">
                    <button class="pa-mute-toggle" aria-label="<?php _e('Mute/Unmute', 'patreon-alt'); ?>">
                        <span class="pa-icon-volume">🔊</span>
                        <span class="pa-icon-mute" style="display:none;">🔇</span>
                    </button>
                    <input type="range" class="pa-volume-slider" min="0" max="100" value="100">
                </div>
                <button class="pa-fullscreen" aria-label="<?php _e('Fullscreen', 'patreon-alt'); ?>">⛶</button>
            </div>

            <?php if (get_option('pa_video_protection')): ?>
            <div class="pa-video-watermark">
                <?php echo esc_html(wp_get_current_user()->user_login); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Získanie MIME typu videa
     */
    private function get_video_mime_type($url) {
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        $mime_types = array(
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'm4v' => 'video/x-m4v'
        );

        return isset($mime_types[$extension]) ? $mime_types[$extension] : 'video/mp4';
    }

    /**
     * Generovanie video tokenu
     */
    private function generate_video_token($video_url) {
        $user_id = get_current_user_id();
        $timestamp = time();
        $data = $user_id . '|' . $video_url . '|' . $timestamp;

        return base64_encode($data);
    }

    /**
     * Validácia video tokenu (AJAX)
     */
    public function get_video_token() {
        check_ajax_referer('pa_video_player', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('User not logged in', 'patreon-alt')));
        }

        $video_url = isset($_POST['video_url']) ? sanitize_text_field($_POST['video_url']) : '';

        if (empty($video_url)) {
            wp_send_json_error(array('message' => __('Invalid video URL', 'patreon-alt')));
        }

        $token = $this->generate_video_token($video_url);

        wp_send_json_success(array('token' => $token));
    }

    /**
     * Shortcode pre video player
     */
    public function video_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'poster' => '',
            'autoplay' => 'false',
            'width' => '100%',
            'height' => 'auto'
        ), $atts);

        if (empty($atts['url'])) {
            return '<p>' . __('No video URL provided', 'patreon-alt') . '</p>';
        }

        return $this->render_player($atts['url'], array(
            'poster' => $atts['poster'],
            'autoplay' => $atts['autoplay'] === 'true',
            'width' => $atts['width'],
            'height' => $atts['height']
        ));
    }
}

// Registrácia shortcode
add_shortcode('pa_video', array(PA_Video_Player::get_instance(), 'video_shortcode'));
