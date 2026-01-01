<?php
/**
 * Patreon Creator Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Setup
 */
function patreon_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('custom-header');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'patreon-theme'),
        'footer' => __('Footer Menu', 'patreon-theme')
    ));

    // Set content width
    if (!isset($content_width)) {
        $content_width = 800;
    }
}
add_action('after_setup_theme', 'patreon_theme_setup');

/**
 * Enqueue scripts and styles
 */
function patreon_theme_scripts() {
    wp_enqueue_style('patreon-theme-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('patreon-theme-script', get_template_directory_uri() . '/js/script.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'patreon_theme_scripts');

/**
 * Register widget areas
 */
function patreon_theme_widgets_init() {
    register_sidebar(array(
        'name' => __('Sidebar', 'patreon-theme'),
        'id' => 'sidebar-1',
        'description' => __('Add widgets here to appear in your sidebar.', 'patreon-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));

    register_sidebar(array(
        'name' => __('Footer', 'patreon-theme'),
        'id' => 'footer-1',
        'description' => __('Add widgets here to appear in your footer.', 'patreon-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
add_action('widgets_init', 'patreon_theme_widgets_init');

/**
 * Custom excerpt length
 */
function patreon_theme_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'patreon_theme_excerpt_length');

/**
 * Custom excerpt more
 */
function patreon_theme_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'patreon_theme_excerpt_more');
