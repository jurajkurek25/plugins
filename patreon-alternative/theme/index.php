<?php
/**
 * Main Template File
 */

get_header();
?>

<main class="site-content">
    <div class="container">
        <div class="content-area">
            <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
                        <header class="post-header">
                            <h2 class="post-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="post-meta">
                                <span class="post-date"><?php echo get_the_date(); ?></span>
                                <span class="post-author"><?php _e('by', 'patreon-theme'); ?> <?php the_author(); ?></span>
                            </div>
                        </header>

                        <?php if (has_post_thumbnail()): ?>
                            <div class="post-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('large'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="post-content">
                            <?php the_excerpt(); ?>
                        </div>

                        <footer class="post-footer">
                            <a href="<?php the_permalink(); ?>" class="btn">
                                <?php _e('Read More', 'patreon-theme'); ?>
                            </a>
                        </footer>
                    </article>
                <?php endwhile; ?>

                <div class="pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => __('Previous', 'patreon-theme'),
                        'next_text' => __('Next', 'patreon-theme'),
                    ));
                    ?>
                </div>

            <?php else: ?>
                <p><?php _e('No posts found.', 'patreon-theme'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
get_footer();
