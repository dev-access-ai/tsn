<?php
/**
 * Template part for displaying search results
 *
 * @package TeluguSamiti
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
  <header class="entry-header">
    <h2 class="post-title">
      <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h2>

    <?php if ('post' === get_post_type()) : ?>
      <div class="post-meta">
        <span class="posted-on">
          <?php echo get_the_date(); ?>
        </span>
      </div>
    <?php endif; ?>
  </header>

  <div class="post-content">
    <?php the_excerpt(); ?>
  </div>






