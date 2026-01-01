<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container">
        <div class="site-logo">
            <?php
            if (has_custom_logo()) {
                the_custom_logo();
            } else {
                ?>
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php bloginfo('name'); ?>
                </a>
                <?php
            }
            ?>
        </div>

        <nav class="main-navigation">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_class' => 'primary-menu',
                'container' => false,
                'fallback_cb' => function() {
                    echo '<ul class="primary-menu">';
                    echo '<li><a href="' . home_url('/') . '">Home</a></li>';
                    echo '<li><a href="' . get_permalink(get_option('pa_page_creator_profile')) . '">Profile</a></li>';
                    echo '<li><a href="' . get_permalink(get_option('pa_page_become_patron')) . '">Become a Patron</a></li>';
                    if (is_user_logged_in()) {
                        echo '<li><a href="' . get_permalink(get_option('pa_page_my_membership')) . '">My Membership</a></li>';
                        echo '<li><a href="' . wp_logout_url(home_url('/')) . '">Logout</a></li>';
                    } else {
                        echo '<li><a href="' . wp_login_url() . '">Login</a></li>';
                    }
                    echo '</ul>';
                }
            ));
            ?>
        </nav>
    </div>
</header>

<?php if (is_front_page() || is_page_template('page-creator-profile.php')): ?>
    <div class="creator-header">
        <div class="container">
            <div class="creator-avatar">
                <?php echo get_avatar(1, 120); ?>
            </div>
            <h1><?php echo esc_html(get_option('pa_profile_title', get_bloginfo('name'))); ?></h1>
            <p class="creator-tagline"><?php echo esc_html(get_option('pa_profile_tagline', get_bloginfo('description'))); ?></p>
            <?php
            $stats = PA_Membership::get_instance()->get_statistics();
            ?>
            <div class="creator-stats">
                <span>
                    <strong><?php echo number_format($stats['active_patrons'] ?? 0); ?></strong>
                    <?php _e('Patrons', 'patreon-theme'); ?>
                </span>
                <span>
                    <strong><?php echo wp_count_posts('pa_post')->publish; ?></strong>
                    <?php _e('Posts', 'patreon-theme'); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>
