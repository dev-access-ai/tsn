<?php
/**
 * The template for displaying all pages
 *
 * @package TeluguSamiti
 */

get_header();
?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3><?php the_title(); ?></h3>
      </div>
      <?php telugusmiti_breadcrumb(); ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
  <div class="container">
    <div class="content-area">
      <?php
      while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
          <header class="entry-header">
            <h1 class="post-title"><?php the_title(); ?></h1>
          </header>

          <div class="post-content">
            <?php
            the_content();

            wp_link_pages(array(
              'before' => '<div class="page-links">' . esc_html__('Pages:', 'telugusmiti'),
              'after' => '</div>',
            ));
            ?>
          </div>
        </article>
        <?php
        // If comments are open or we have at least one comment, load up the comment template.
        if (comments_open() || get_comments_number()) {
          comments_template();
        }
      endwhile;
      ?>
    </div>
  </div>
</main>

<?php
get_footer();






