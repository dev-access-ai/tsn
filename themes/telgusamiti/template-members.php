<?php

/**
 * Template Name: Members
 * Description: Custom Members page template for Telugu Samiti
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
  <!-- Main Content Section -->
  <section class="main-content-section">
    <div class="section member-section">
      <div class="container">
        <div class="section-title">
          <h3>Join the Heart of Our Community</h3>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
?>

