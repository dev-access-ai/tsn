<?php
/**
 * Template part for displaying single posts
 *
 * @package TeluguSamiti
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
  <header class="entry-header">
    <h1 class="post-title"><?php the_title(); ?></h1>

    <div class="post-meta">
      <span class="posted-on">
        <?php echo get_the_date(); ?>
      </span>
      <span class="byline">
        by <?php the_author(); ?>
      </span>
    </div>
  </header>

  <?php if (has_post_thumbnail()) : ?>
    <div class="post-thumbnail">
      <?php the_post_thumbnail('large'); ?>
    </div>
  <?php endif; ?>

  <div class="post-content">
    <?php
    the_content();

    wp_link_pages(array(
      'before' => '<div class="page-links">' . esc_html__('Pages:', 'telugusmiti'),
      'after' => '</div>',
    ));
    ?>
  </div>

  <footer class="entry-footer">
    <div class="post-categories">
      <?php
      $categories = get_the_category();
      if (! empty($categories)) {
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
</article>

<?php
// If comments are open or we have at least one comment, load up the comment template.
if (comments_open() || get_comments_number()) {
  comments_template();
}






