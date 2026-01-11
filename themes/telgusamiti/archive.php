<?php
/**
 * The template for displaying archive pages
 *
 * @package TeluguSamiti
 */

get_header();
?>

<main id="main" class="site-main">
  <div class="container">
    <div class="content-area">
      <?php
      if (have_posts()) {
        ?>
        <header class="page-header">
          <?php
          the_archive_title('<h1 class="page-title">', '</h1>');
          the_archive_description('<div class="archive-description">', '</div>');
          ?>
        </header>
        <?php
      }

      if (have_posts()) :
        while (have_posts()) :
          the_post();
          get_template_part('template-parts/content', get_post_type());
        endwhile;

        the_posts_pagination(array(
          'mid_size' => 2,
          'prev_text' => __('&laquo; Previous', 'telugusmiti'),
          'next_text' => __('Next &raquo;', 'telugusmiti'),
        ));
      else :
        ?>
        <div class="no-posts">
          <h2><?php esc_html_e('Nothing Found', 'telugusmiti'); ?></h2>
          <p><?php esc_html_e('It seems we can\'t find what you\'re looking for.', 'telugusmiti'); ?></p>
        </div>
        <?php
      endif;
      ?>
    </div>
  </div>
</main>

<?php
get_footer();






