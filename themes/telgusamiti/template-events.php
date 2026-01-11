<?php

/**
 * Template Name: Events
 * Description: Custom events listing page template for Telugu Samiti
 *
 * @package TeluguSamiti
 */
get_header();

// Check if this is an event detail page (has event slug)
$event_slug = get_query_var('event_slug');
$event_title = null;
if (!empty($event_slug)) {
  // Get event data from database
  global $wpdb;
  $event = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tsn_events WHERE slug = %s AND status = 'published'",
    $event_slug
  ));
  
  if ($event) {
    $event_title = $event->title;
  }
}
?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3><?php echo $event_title ? esc_html($event_title) : get_the_title(); ?></h3>
      </div>
      <?php telugusmiti_breadcrumb(); ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
  <div class="events-listing-grid">
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
  </div> <!-- events-listing-grid -->
</main>

<?php
get_footer();
?>