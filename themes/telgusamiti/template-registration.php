<?php

/**
 * Template Name: Registration
 * Description: Custom registration page template for Telugu Samiti
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
    <div class="section registraion-section">
      <div class="container">
        <div class="top-container">
          <div class="section-title">
            <h3>Join the Heart of Our Community</h3>
          </div>
          <div class="section-info">
            <h4>Please fill the form below and after submission, print the registration receipt for records.</h4>
            <p>Star Mark (*) denotes required fields.</p>
          </div>
        </div>
        <div class="form-container">
          <div class="row">
            <div class="col-md-6 col-lg-6 col">
              <div class="form-group">
                <span class="mdi mdi-wallet-membership"></span>
                <select class="form-select">
                  <option value="1">Membership Type</option>
                </select>
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="form-group">
                <span class="mdi mdi-account-circle-outline"></span>
                <input type="text" placeholder="Enter Full Name" class="form-control">
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="form-group">
                <span class="mdi mdi-account-circle-outline"></span>
                <input type="text" placeholder="Phone Number" class="form-control">
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="form-group">
                <span class="mdi mdi-account-circle-outline"></span>
                <input type="text" placeholder="Primary Email" class="form-control">
              </div>
            </div>
            <div class="col-md-4 col-lg-4 col">
              <div class="form-group">
                <span class="mdi mdi-account-circle-outline"></span>
                <input type="text" placeholder="District" class="form-control">
              </div>
            </div>
            <div class="col-md-4 col-lg-4 col">
              <div class="form-group">
                <span class="mdi mdi-form-textbox-password"></span>
                <input type="text" placeholder="Enter Password" class="form-control">
              </div>
            </div>
            <div class="col-md-4 col-lg-4 col">
              <div class="form-group">
                <span class="mdi mdi-form-textbox-password"></span>
                <input type="text" placeholder="Enter Password" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group form-links">
            <button class="btn btn-primary">Register</button>
            <a href="#" class="btn btn-link">Sign In</a>
          </div>
        </div>
        <div class="notes">
          <strong>Note:</strong> Membership Fees are non-refundable. Applicant must be above 18 years of age and must provide at least one valid telephone number and one email address, otherwise membership will be denied.
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
?>

