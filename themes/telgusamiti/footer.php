<footer id="footer">
  <div class="top-footer">
    <div class="bg">&nbsp;</div>
    <div class="container">
      <h3>Together, we grow stronger — one event, one story, one connection at a time.</h3>
    </div>
  </div>
  <div class="newsletter-section">
    <span class="bg">BG</span>
    <div class="container">
      <div class="row">
        <div class="col-md-4 col-lg-4 title-col">
          <h3>Subscribe for Updates Stories & Impact</h3>
        </div>
        <div class="col-md-8 col-lg-8 form-col">
          <div class="form-container">
            <form id="tsn-newsletter-form" style="display: flex; width: 100%; gap: 10px;">
                <?php wp_nonce_field('tsn_newsletter_nonce', 'newsletter_nonce'); ?>
                <input type="email" name="email" id="tsn-newsletter-email" placeholder="Enter Your Email Address Here..." class="form-control" required>
                <input type="submit" class="btn btn-warning" value="Subscribe Now">
            </form>
            <div id="tsn-newsletter-msg" style="margin-top: 10px; font-weight: bold; display: none;"></div>
          </div>
          <script>
            jQuery(document).ready(function($) {
                $('#tsn-newsletter-form').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var msgDiv = $('#tsn-newsletter-msg');
                    var submitBtn = form.find('input[type="submit"]');
                    
                    submitBtn.prop('disabled', true).val('Subscribing...');
                    msgDiv.hide().removeClass('success error');
                    
                    var data = {
                        action: 'tsn_subscribe',
                        nonce: form.find('#newsletter_nonce').val(),
                        email: form.find('#tsn-newsletter-email').val()
                    };
                    
                    // Use a standardized object if available, else standard location
                    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    
                    $.post(ajaxUrl, data, function(response) {
                        if (response.success) {
                            msgDiv.html(response.data.message).addClass('text-success').css('color', '#155724').show();
                            form[0].reset();
                        } else {
                            msgDiv.html(response.data.message || 'Error subscribing').addClass('text-danger').css('color', '#F00').show();
                        }
                    }).fail(function() {
                         msgDiv.html('Server error. Please try again later.').css('color', '#F00').show();
                    }).always(function() {
                        submitBtn.prop('disabled', false).val('Subscribe Now');
                    });
                });
            });
          </script>
        </div>
      </div>
    </div>
  </div>
  <div class="logo-container">
    <div class="container">
      <a href="<?php echo home_url(); ?>" id="logo-footer">
        <img src="<?php echo get_template_directory_uri(); ?>/images/logo-footer.png" alt="Telugu Samiti">
      </a>
    </div>
  </div>
  <div class="middle-footer">
    <div class="container">
      <div class="row">
        <div class="col-md-5 col-lg-5 about-col">
          <h4>About us</h4>
          <div class="section-info">
            <p>An evening of culture, laughter, and connection! Join us for cultural performances, authentic Telugu food, and community awards.</p>
          </div>
        </div>
        <div class="col-md-4 col-lg-4 quick-col">
          <h4>Quick Links</h4>
          <nav id="fmenu" class="footer-navigation">
          <?php
          wp_nav_menu(array(
            'theme_location' => 'footer',
            'menu_id' => 'footer-menu',
            'fallback_cb' => false,
          ));
          ?>
          </nav>
        </div>
        <div class="col-md-3 col-lg-3 get-touch-col">
          <h4>Get in touch</h4>
          <ul>
            <li>Telugu Samiti of Nebraska, P.O. Box 45224, Omaha, NE, 68145</li>
            <li>402 819 9609</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="bottom-footer">
    <div class="container">
      <div class="row">
        <div class="col-md-6 col-lg-6 copyright-col">
          <p>&copy; <?php echo date('Y'); ?> Telugusamiti.
            <?php esc_html_e('All rights reserved.', 'telugusmiti'); ?>
          </p>
        </div>
        <div class="col-md-6 col-lg-6 social-col">
          <div class="socials">
            <ul>
              <li>
                <a href="https://www.facebook.com/telugusamiti" class="fb" title="Facebook" target="_blank">
                  <span class="mdi mdi-facebook"></span>
                </a>
              </li>
             <!-- <li>
                <a href="#" class="tw" title="Twitter">
                  <span class="mdi mdi-twitter-x"></span>
                </a>
              </li>-->
              <li>
                <a href="https://www.instagram.com/telugusamiti/" class="ins" title="Instagram" target="_blank">
                  <span class="mdi mdi-instagram"></span>
                </a>
              </li>
              <li>
                <a href="#" class="in" title="LinkedIn">
                  <span class="mdi mdi-linkedin"></span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>

