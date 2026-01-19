<?php

/**
 * Template Name: Committe
 * Description: Custom committe page template for Telugu Samiti
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

    <div class="section three-column-section">
      <div class="container">
        <!-- Bootstrap Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="committeeTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="executive-tab" data-bs-toggle="tab" data-bs-target="#executive" type="button" role="tab" aria-controls="executive" aria-selected="true">
              Executive Committee
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="board-tab" data-bs-toggle="tab" data-bs-target="#board" type="button" role="tab" aria-controls="board" aria-selected="false">
              Board of Directors
            </button>
          </li>
        </ul>

        <!-- Bootstrap Tabs Content -->
        <div class="tab-content" id="committeeTabsContent">
          <!-- Executive Committee Tab -->
          <div class="tab-pane fade show active" id="executive" role="tabpanel" aria-labelledby="executive-tab">
            <div class="row">
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member.jpg" alt="Kolli Prasad">
                  </div>
                  <div class="content-container">
                    <h4>Kolli Prasad</h4>
                    <h5>President</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-01.jpg"
                      alt="TataRao Kosuri">
                  </div>
                  <div class="content-container">
                    <h4>TataRao Kosuri</h4>
                    <h5>Vice President</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-05.jpg"
                      alt="AlaganKancharla Chinnappa">
                  </div>
                  <div class="content-container">
                    <h4>AlaganKancharla Chinnappa</h4>
                    <h5>General Secretary</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-09.jpg"
                      alt="Ramesh Rayapati">
                  </div>
                  <div class="content-container">
                    <h4>Ramesh Rayapati</h4>
                    <h5>Joint Secretary</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-02.jpg" alt="Samba Divvela">
                  </div>
                  <div class="content-container">
                    <h4>Samba Divvela</h4>
                    <h5>Treasurer</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-03.jpg" alt="Yugandhar Panga">
                  </div>
                  <div class="content-container">
                    <h4>Yugandhar Panga</h4>
                    <h5>Joint Treasurer</h5>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-07.jpg"
                      alt="Ramya Ravipati">
                  </div>
                  <div class="content-container">
                    <h4>Ramya Ravipati</h4>
                    <h5>Cultural Secretary</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4 col-lg-4 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/team-member-08.jpg"
                      alt="Vineela Naidu">
                  </div>
                  <div class="content-container">
                    <h4>Vineela Naidu</h4>
                    <h5>Join Cultural Secretary</h5>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-title">
              <h3>Committee Members</h3>
            </div>

             <div class="row">
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-01.jpg"
                      alt="Anil Pothineni">
                  </div>
                  <div class="content-container">
                    <h4>Anil Pothineni</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-02.jpg"
                      alt="Avinash">
                  </div>
                  <div class="content-container">
                    <h4>Avinash</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-03.jpg"
                      alt="Dhana GottiPatti">
                  </div>
                  <div class="content-container">
                    <h4>Dhana GottiPatti</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-04.jpg"
                      alt="Veerendra Mupparaju">
                  </div>
                  <div class="content-container">
                    <h4>Veerendra Mupparaju</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-05.jpg"
                      alt="Bala Kamireddy">
                  </div>
                  <div class="content-container">
                    <h4>Bala Kamireddy</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/ec-member-06.jpg"
                      alt="Kranthi Sudha">
                  </div>
                  <div class="content-container">
                    <h4>Kranthi Sudha</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
         
          <!-- Board of Directors Tab -->
          <div class="tab-pane fade" id="board" role="tabpanel" aria-labelledby="board-tab">
            <div class="row">
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/default-user-image.svg"
                      alt="Anil Pothineni">
                  </div>
                  <div class="content-container">
                    <h4>Mrs. Lakshmi Madhuri Chinni</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/director-01.jpg"
                      alt="Venu Gopal Murakonda">
                  </div>
                  <div class="content-container">
                    <h4>Venu Gopal Murakonda</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/default-user-image.svg"
                      alt="Anil Pothineni">
                  </div>
                  <div class="content-container">
                    <h4>Anoop manikya</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/default-user-image.svg"
                      alt="Anil Pothineni">
                  </div>
                  <div class="content-container">
                    <h4>Mrs. Neelima</h4>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-lg-3 col">
                <div class="col-container">
                  <div class="image-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/board-director-05.jpg"
                      alt="Anil Pothineni">
                  </div>
                  <div class="content-container">
                    <h4>Chaitanya Ravipati</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
get_footer();
?>