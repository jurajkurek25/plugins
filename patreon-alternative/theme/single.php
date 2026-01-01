<?php
/**
 * Single Post Template
 */

get_header();
?>

<main class="site-content">
    <div class="container">
        <div class="content-area">
            <?php while (have_posts()): the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
                    <header class="post-header">
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        <div class="post-meta">
                            <span class="post-date"><?php echo get_the_date(); ?></span>
                            <span class="post-author"><?php _e('by', 'patreon-theme'); ?> <?php the_author(); ?></span>
                            <span class="post-comments"><?php comments_number('0 comments', '1 comment', '% comments'); ?></span>
                        </div>
                    </header>

                    <?php if (has_post_thumbnail()): ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="post-content">
                        <?php the_content(); ?>
                    </div>

                    <footer class="post-footer">
                        <?php
                        the_tags('<div class="post-tags">', ' ', '</div>');
                        ?>
                    </footer>
                </article>

                <?php
                if (comments_open() || get_comments_number()):
                    comments_template();
                endif;
                ?>

                <nav class="post-navigation">
                    <div class="nav-previous">
                        <?php previous_post_link('%link', '&larr; %title'); ?>
                    </div>
                    <div class="nav-next">
                        <?php next_post_link('%link', '%title &rarr;'); ?>
                    </div>
                </nav>

            <?php endwhile; ?>
        </div>
    </div>
</main>

<?php
get_footer();
