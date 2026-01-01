<footer class="site-footer">
    <div class="container">
        <?php if (is_active_sidebar('footer-1')): ?>
            <div class="footer-widgets">
                <?php dynamic_sidebar('footer-1'); ?>
            </div>
        <?php endif; ?>

        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php _e('All rights reserved.', 'patreon-theme'); ?></p>
            <p><?php _e('Powered by', 'patreon-theme'); ?> <a href="https://wordpress.org" target="_blank">WordPress</a> & <a href="#">Patreon Alternative</a></p>
        </div>

        <?php
        wp_nav_menu(array(
            'theme_location' => 'footer',
            'menu_class' => 'footer-menu',
            'container' => 'nav',
            'container_class' => 'footer-navigation',
            'depth' => 1,
            'fallback_cb' => false
        ));
        ?>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
