<?php
/**
 * Patron Posts systém
 */

if (!defined('ABSPATH')) {
    exit;
}

class PA_Posts {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('save_post_pa_post', array($this, 'save_post_meta'), 10, 2);
        add_filter('the_content', array($this, 'filter_content'));
    }

    /**
     * Vytvorenie patron postu
     */
    public function create_post($data) {
        $defaults = array(
            'post_title' => '',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'pa_post',
            'tier_id' => 0,
            'has_video' => false,
            'video_url' => '',
            'is_public' => false
        );

        $data = wp_parse_args($data, $defaults);

        // Vytvoriť WordPress post
        $post_id = wp_insert_post(array(
            'post_title' => $data['post_title'],
            'post_content' => $data['post_content'],
            'post_status' => $data['post_status'],
            'post_type' => 'pa_post'
        ));

        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }

        // Uložiť patron post meta data
        global $wpdb;
        $table = PA_Database::get_table_name('patron_posts');

        $wpdb->insert($table, array(
            'post_id' => $post_id,
            'tier_id' => $data['tier_id'],
            'has_video' => $data['has_video'] ? 1 : 0,
            'video_url' => $data['video_url'],
            'is_public' => $data['is_public'] ? 1 : 0
        ));

        return $post_id;
    }

    /**
     * Získanie patron postu
     */
    public function get_patron_post($post_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('patron_posts');

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d",
            $post_id
        ));
    }

    /**
     * Skontrolovať prístup k postu
     */
    public function user_can_access_post($user_id, $post_id) {
        // Admin má vždy prístup
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        $patron_post = $this->get_patron_post($post_id);

        if (!$patron_post) {
            return false;
        }

        // Ak je post verejný
        if ($patron_post->is_public) {
            return true;
        }

        // Skontrolovať membership tier
        $membership = PA_Membership::get_instance();
        return $membership->user_has_tier_access($user_id, $patron_post->tier_id);
    }

    /**
     * Získanie všetkých patron postov
     */
    public function get_posts($args = array()) {
        global $wpdb;
        $table = PA_Database::get_table_name('patron_posts');

        $defaults = array(
            'posts_per_page' => 10,
            'page' => 1,
            'tier_id' => null,
            'has_video' => null,
            'is_public' => null,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');

        if ($args['tier_id']) {
            $where[] = $wpdb->prepare("tier_id = %d", $args['tier_id']);
        }

        if (!is_null($args['has_video'])) {
            $where[] = $wpdb->prepare("has_video = %d", $args['has_video'] ? 1 : 0);
        }

        if (!is_null($args['is_public'])) {
            $where[] = $wpdb->prepare("is_public = %d", $args['is_public'] ? 1 : 0);
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['posts_per_page'];

        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['posts_per_page']} OFFSET $offset";

        return $wpdb->get_results($query);
    }

    /**
     * Uloženie post meta
     */
    public function save_post_meta($post_id, $post) {
        // Skontrolovať nonce a autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Uložiť tier_id, video_url atď.
        if (isset($_POST['pa_tier_id'])) {
            global $wpdb;
            $table = PA_Database::get_table_name('patron_posts');

            $existing = $this->get_patron_post($post_id);

            $data = array(
                'tier_id' => intval($_POST['pa_tier_id']),
                'has_video' => isset($_POST['pa_has_video']) ? 1 : 0,
                'video_url' => sanitize_text_field($_POST['pa_video_url'] ?? ''),
                'is_public' => isset($_POST['pa_is_public']) ? 1 : 0
            );

            if ($existing) {
                $wpdb->update($table, $data, array('post_id' => $post_id));
            } else {
                $data['post_id'] = $post_id;
                $wpdb->insert($table, $data);
            }
        }
    }

    /**
     * Filtrovanie obsahu
     */
    public function filter_content($content) {
        if (!is_singular('pa_post')) {
            return $content;
        }

        $post_id = get_the_ID();
        $user_id = get_current_user_id();

        if (!$this->user_can_access_post($user_id, $post_id)) {
            $tiers = PA_Tiers::get_instance();
            $patron_post = $this->get_patron_post($post_id);
            $tier = $tiers->get_tier($patron_post->tier_id);

            ob_start();
            ?>
            <div class="pa-locked-content">
                <div class="pa-lock-icon">🔒</div>
                <h3><?php _e('This content is for patrons only', 'patreon-alt'); ?></h3>
                <p><?php printf(__('Become a %s member to unlock this content.', 'patreon-alt'), $tier->name); ?></p>
                <a href="<?php echo get_permalink(get_option('pa_page_become_patron')); ?>" class="pa-btn pa-btn-primary">
                    <?php _e('Become a Patron', 'patreon-alt'); ?>
                </a>
            </div>
            <?php
            return ob_get_clean();
        }

        // Pridať video player ak je potrebné
        $patron_post = $this->get_patron_post($post_id);
        if ($patron_post && $patron_post->has_video && $patron_post->video_url) {
            $video_player = PA_Video_Player::get_instance();
            $content = $video_player->render_player($patron_post->video_url) . $content;
        }

        return $content;
    }

    /**
     * Pridanie lajku
     */
    public function add_like($post_id, $user_id) {
        global $wpdb;
        $table = PA_Database::get_table_name('patron_posts');

        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET likes_count = likes_count + 1 WHERE post_id = %d",
            $post_id
        ));

        // Uložiť do user meta aby sme vedeli kto lajkol
        update_user_meta($user_id, 'pa_liked_' . $post_id, 1);

        return true;
    }
}
