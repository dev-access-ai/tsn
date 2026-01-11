<?php

/**
 * Template Name: Home Page
 * Description: Custom home page template for Telugu Samiti
 *
 * @package TeluguSamiti
 */
get_header();
?>

<div id="main-banner" class="swiper banner-swiper">
  <div class="swiper-wrapper">
    <div class="swiper-slide">
      <div class="item">
        <div class="banner-container">
          <div class="container">
            <div class="banner-content">
              <h4>Telugu Samiti of Nebraska</h4>
              <h1>Celebrating Telugu Culture, Connecting Hearts, and Creating Impact.</h1>
              <h5>Join us in preserving our heritage, celebrating our community, and inspiring the next generation.</h5>
              <!--<div class="buttons">
                <a href="#" class="btn btn-secondary"><span>Attend an Event</span></a>
                <a href="#" class="btn btn-info"><span>Donate Now</span></a>
                <a href="#" class="btn btn-secondary"><span>Become a Member</span></a>
              </div>-->
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="swiper-slide">
      <div class="item">
        <div class="banner-container">
          <div class="container">
            <div class="banner-content">
              <h4>Telugu Samiti of Nebraska</h4>
              <h1>Celebrating Telugu Culture, Connecting Hearts, and Creating Impact.</h1>
              <h5>Join us in preserving our heritage, celebrating our community, and inspiring the next generation.</h5>
              <!--<div class="buttons">
                <a href="#" class="btn btn-secondary"><span>Attend an Event</span></a>
                <a href="#" class="btn btn-info"><span>Donate Now</span></a>
                <a href="#" class="btn btn-secondary"><span>Become a Member</span></a>
              </div>-->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Navigation -->
  <div class="swiper-button-next"></div>
  <div class="swiper-button-prev"></div>
</div>
<div class="line-with-dots">&nbsp;</div>

<main id="main" class="site-main home-template">
  <!-- Main Content Section -->
  <section class="main-content-section">
    <div class="section welcome-section">
      <div class="container">
        <div class="row">
          <div class="col-md-6 col-lg-6 image-col">
            <figure>
              <img src="<?php echo get_template_directory_uri(); ?>/images/welcome-image.png" alt="Welcome to Telugu Samiti of Nebraska">
            </figure>
          </div>

          <div class="col-md-6 col-lg-6 content-col">
            <div class="col-container">
              <div class="section-title">
                <h3>Welcome to Telugu Samiti of Nebraska</h3>
              </div>
              <div class="section-info">
                <p>We are a non-profit cultural organization dedicated to uniting the Telugu-speaking community across Nebraska and beyond.</p>
                <p>Our mission is to promote Telugu culture, language, traditions, & social welfare through community programs, cultural festivals, and service initiatives.</p>
                <p>From vibrant Telugu festival celebrations to youth development programs, Telugu Samiti is the heartbeat of Telugu identity in the Nebraska state.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section what-we-do-section">
      <div class="container">
        <div class="section-title">
          <h3>What We Do</h3>
        </div>
        <div class="section-info">
          <p>Telugu Samiti of Nebraska is more than a cultural group — it’s a community movement driven by togetherness, service, and pride.</p>
        </div>
        <div class="section-grid">
          <div class="row">
            <div class="col-md-6 col-lg-6 col">
              <div class="col-container">
                <div class="grid-icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-culture.svg" alt="Cultural Celebrations">
                </div>
                <div class="grid-info">
                  <h4>Cultural Celebrations</h4>
                  <p>We organize grand Telugu festivals, music & dance programs, and traditional events that keep our roots alive.</p>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="col-container">
                <div class="grid-icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-community.svg" alt="Community Service">
                </div>
                <div class="grid-info">
                  <h4>Community Service</h4>
                  <p>We participate in local charity drives, educational sponsorship's, and volunteer initiatives that uplift society.</p>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="col-container">
                <div class="grid-icon">
                <img src="<?php echo get_template_directory_uri(); ?>/images/icon-youth.svg" alt="Youth & Education">
                </div>
                <div class="grid-info">
                  <h4>Youth & Education</h4>
                  <p>We mentor young Telugu Americans to embrace their heritage while excelling in modern education and leadership.</p>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-lg-6 col">
              <div class="col-container">
                <div class="grid-icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-network.svg" alt="Networking & Collaboration">
                </div>
                <div class="grid-info">
                  <h4>Networking & Collaboration</h4>
                  <p>We create meaningful spaces for professionals and families to connect, share, and grow together.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section events-section">
      <div class="container">
        <div class="section-title">
          <h3>Upcoming Event</h3>
        </div>
        <div class="row">
          <div class="col-md-6 col-lg-6 image-col">
            <figure>
              <img src="<?php echo get_template_directory_uri(); ?>/images/sankaranti_event.png" alt="Sankranthi Cultural Event">
            </figure>
          </div>
          <div class="col-md-6 col-lg-6 content-col">
            <div class="col-container">
              <h4>Sankranthi Cultural Event</h4>
              <h5>Date: January 17th, 2026</h5>
              <h5>Location: Omaha Community Center</h5>
              <div class="event-info">
                <p>Programs related to Telugu Culture, Heritage and Traditions that resonate with the spirit of Sankranthi are encouraged.</p>
              </div>
              <div class="buttons">
                <a href="https://telugusamiti.org/tsn/membership/" target="_blank" class="btn btn-primary btn-sm"><span>Register now</span></a>
                <a href="https://telugusamiti.org/tsn/membership/" target="_blank" class="btn btn-outline-primary btn-sm"><span>Volunteer</span></a>
                <a href="https://telugusamiti.org/tsn/membership/" target="_blank" class="btn btn-outline-primary btn-sm"><span>Attend</span></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="line-with-dots">&nbsp;</div>

    <div class="section our-journey-section">
      <div class="container">
        <div class="two-grid-section">
          <div class="row">
            <div class="col-md-6 col-lg-6 image-col">
              <figure>
                <img src="<?php echo get_template_directory_uri(); ?>/images/our-journey-image.png" alt="Our Journey — Why Join Us">
              </figure>
            </div>
            <div class="col-md-6 col-lg-6 content-col">
              <div class="col-container">
                <div class="section-title">
                  <h3>Our Journey — Why Join Us</h3>
                </div>
                <div class="section-info">
                  <p>From humble beginnings to a thriving cultural family, Telugu Samiti of Nebraska has evolved with one mission — to connect every Telugu heart under one roof.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="four-grid-section">
          <div class="section-title">
            <h3>Our Targets Ahead</h3>
          </div>
          <div class="row">
            <div class="col-md-3 col-lg-3 col">
              <div class="col-container">
                <h4>Expand our membership base across Nebraska state.</h4>
                <span class="flower">&nbsp;</span>
              </div>
            </div>
            <div class="col-md-3 col-lg-3 col">
              <div class="col-container">
                <h4>Establish an annual youth cultural festival.</h4>
                <span class="flower">&nbsp;</span>
              </div>
            </div>
            <div class="col-md-3 col-lg-3 col">
              <div class="col-container">
                <h4>Launch community service & charity programs.</h4>
                <span class="flower">&nbsp;</span>
              </div>
            </div>
            <div class="col-md-3 col-lg-3 col">
              <div class="col-container">
                <h4>Build a sustainable fund for educational scholarships.</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section our-community-section">
      <span class="section-shape">&nbsp;</span>
      <div class="container">
        <div class="section-title">
          <h3>Our Value to the Community</h3>
        </div>
        <div class="swiper community-swiper">
          <div class="swiper-wrapper">
            <div class="swiper-slide">
              <div class="col-container">
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-culture-continutiy.svg" alt="Cultural Continuity">
                </div>
                <h4>Cultural Continuity</h4>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="col-container">
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-coumminty-services.svg" alt="Community Service">
                </div>
                <h4>Community Service</h4>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="col-container">
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-fingure.svg" alt="Identity & Belonging">
                </div>
                <h4>Identity & Belonging</h4>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="col-container">
                <div class="icon">
                  <img src="<?php echo get_template_directory_uri(); ?>/images/icon-empowerment.svg" alt="Empowerment">
                </div>
                <h4>Empowerment</h4>
              </div>
            </div>
          </div>
          <!-- Navigation -->
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
          <!-- Pagination -->
          <div class="swiper-pagination"></div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
?>

