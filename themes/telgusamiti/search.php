<?php
/**
 * The template for displaying search results
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
          <h1 class="page-title">
            <?php
            printf(
              /* translators: %s: search query. */
              esc_html__('Search Results for: %s', 'telugusmiti'),
              '<span>' . get_search_query() . '</span>'
            );
            ?>
          </h1>
        </header>
        <?php
      }

      if (have_posts()) :
        while (have_posts()) :
          the_post();
          get_template_part('template-parts/content', 'search');
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
          <p>
            <?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with different keywords.', 'telugusmiti'); ?>
          </p>
          <?php get_search_form(); ?>
        </div>
        <?php
      endif;
      ?>
    </div>
  </div>
</main>

<?php
get_footer();






