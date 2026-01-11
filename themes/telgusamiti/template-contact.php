<?php
  /**
   * Template Name: Contact
   * Description: Custom contact page template for Telugu Samiti
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
    <div class="section get-in-touch-section">
      <div class="container">
        <div class="three-grid-section">
          <div class="row">
            <div class="col-md-4 col-lg-4 col">
              <div class="col-container">
                <h4>Our Address</h4>
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-map-pin.svg" alt="Location">
                </div>
                <div class="section-info">
                  <p>Telugu Samiti of Nebraska, P.O. Box 45224, Omaha, NE, 68145</p>
                </div>
                <span class="flower">&nbsp;</span>
              </div>
            </div>
            <div class="col-md-4 col-lg-4 col">
              <div class="col-container">
                <h4>Our Phone Number</h4>
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-phone.svg" alt="Phone">
                </div>
                <div class="section-info">
                  <p>402 819 9609</p>
                </div>
                <span class="flower">&nbsp;</span>
              </div>
            </div>
            <div class="col-md-4 col-lg-4 col">
              <div class="col-container">
                <h4>Our Email</h4>
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-email.svg" alt="Email">
                </div>
                <div class="section-info">
                  <p><a href="mailto:info@telugusamiti.org">info@telugusamiti.org</a></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="contact-form-section">
          <div class="section-title">
            <h3>Get in touch</h3>
            <h4>Star Mark (*) denotes required fields.</h4>
          </div>
          <div class="form-container">
            <?php echo do_shortcode('[contact-form-7 id="4b0fade" title="Get In Touch"]'); ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
<?php
  get_footer();
?>