<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package TeluguSamiti
 */

get_header();
?>

<main id="main" class="site-main">
  <div class="container">
    <div class="content-area">
      <section class="error-404 not-found">
        <header class="page-header">
          <h1 class="page-title"><?php esc_html_e('Oops! That page can&rsquo;t be found.', 'telugusmiti'); ?></h1>
        </header>

        <div class="page-content">
          <p>
            <?php esc_html_e('It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'telugusmiti'); ?>
          </p>

          <?php
          get_search_form();

          the_widget('WP_Widget_Recent_Posts');
          ?>
        </div>
      </section>
    </div>
  </div>
</main>

<?php
get_footer();






