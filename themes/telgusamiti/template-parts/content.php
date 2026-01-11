<?php
/**
 * Template part for displaying posts
 *
 * @package TeluguSamiti
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
    <header class="entry-header">
        <h2 class="post-title">
            <?php
            if (is_singular()) {
                the_title('<h1 class="entry-title">', '</h1>');
            } else {
                the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
            }
            ?>
        </h2>

        <?php if ('post' === get_post_type()) : ?>
            <div class="post-meta">
                <span class="posted-on">
                    <?php echo get_the_date(); ?>
                </span>
                <span class="byline">
                    by <?php the_author(); ?>
                </span>
            </div>
        <?php endif; ?>
    </header>

    <?php if (has_post_thumbnail() && !is_singular()) : ?>
        <div class="post-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('large'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="post-content">
        <?php
        if (is_singular()) {
            the_content();

            wp_link_pages(array(
                'before' => '<div class="page-links">' . esc_html__('Pages:', 'telugusmiti'),
                'after'  => '</div>',
            ));
        } else {
            the_excerpt();
        }
        ?>
    </div>

    <?php if (is_singular()) : ?>
        <footer class="entry-footer">
            <div class="post-categories">
                <?php
                $categories = get_the_category();
                if (!empty($categories)) {
                    echo '<span>Categories: </span>';
                    foreach ($categories as $category) {
                        echo '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a> ';
                    }
                }
                ?>
            </div>
            <div class="post-tags">
                <?php the_tags('<span>Tags: </span>', ', '); ?>
            </div>
        </footer>
    <?php endif; ?>
</article>







