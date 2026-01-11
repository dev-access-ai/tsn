<?php

/**
 * Template Name: About
 * Description: Custom about page template for Telugu Samiti
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
    <div class="section infomation-section">
      <div class="container">
        <div class="section-title">
          <h3>About Us — Telugu Samiti of Nebraska (TSN)</h3>
          <h4>Celebrating Telugu Heritage, Strengthening Community Bonds</h4>
        </div>
        <div class="row">
          <div class="col-md-6 col-lg-6 image-col">
            <figure>
              <img src="<?php echo get_template_directory_uri(); ?>/images/about-us-image.png" alt="Telugu Samiti Annual Ugadi Celebrations 2025">
            </figure>
          </div>
          <div class="col-md-6 col-lg-6 content-col">
            <div class="col-container">
              <h4>“Our culture connects us, our community strengthens us, and our service defines us.”</h4>
              <div class="section-info">
                <p>Telugu Samiti of Nebraska (TSN) was founded to bring together the Telugu-speaking families living across Nebraska & the Midwest. </p>
                <p>What began as a small cultural initiative has grown into a thriving organization that nurtures cultural, educational, literary, civic, social, and charitable activities for the Telugu diaspora.</p>
                <p>Through festivals, social gatherings, and service projects, TSN has helped build lasting friendships, preserve traditions, and create a sense of belonging among Telugus in Nebraska.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="line-with-dots">&nbsp;</div>
    <div class="section welcome-section">
      <div class="container">
        <div class="row">
          <div class="col-md-6 col-lg-6 image-col">
            <div class="col-container">
              <div class="section-title">
                <h3>Organization History</h3>
              </div>
              <div class="section-info">
                <p>The organization took birth in an informal manner around 2004.  As and when the community grew, what started as celebration of Telugu culture, and festival events at the temple, took shape and became a formal organization in 2012.</p>
                <p>Many of our community members who played a major and prominent part in the earlier years, are still active and serve as Permanent Members to the Executive Council.  The organization soon hopes to qualify as a non profit and serve the community better.</p>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-lg-6 image-col">
            <figure>
              <img src="<?php echo get_template_directory_uri(); ?>/images/welcome-image.png" alt="Welcome to Telugu Samiti of Nebraska">
            </figure>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
?>

