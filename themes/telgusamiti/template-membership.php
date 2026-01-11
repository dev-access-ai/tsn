<?php

/**
 * Template Name: Membership
 * Description: Custom membership page template for Telugu Samiti
 *
 * @package TeluguSamiti
 */
get_header();

// Get event meta data
$event_date = get_post_meta(get_the_ID(), 'event_date', true);
$event_location = get_post_meta(get_the_ID(), 'event_location', true);
$event_time = get_post_meta(get_the_ID(), 'event_time', true);
$event_registration_fee = get_post_meta(get_the_ID(), 'event_registration_fee', true);
$event_category = get_post_meta(get_the_ID(), 'event_category', true);
$event_mission = get_post_meta(get_the_ID(), 'event_mission', true);
$event_requirements = get_post_meta(get_the_ID(), 'event_requirements', true);
$event_requirements_list = get_post_meta(get_the_ID(), 'event_requirements_list', true);
$event_header_image = get_post_meta(get_the_ID(), 'event_header_image', true);
$register_link = get_post_meta(get_the_ID(), 'register_link', true);
$volunteer_link = get_post_meta(get_the_ID(), 'volunteer_link', true);
$attend_link = get_post_meta(get_the_ID(), 'attend_link', true);

// Format date
if ($event_date) {
  $formatted_date = date('F Y', strtotime($event_date));
  $full_date = date('d M Y', strtotime($event_date));
} else {
  $formatted_date = get_the_date('F Y');
  $full_date = get_the_date('d M Y');
}

// Default values
if (empty($event_location)) {
  $event_location = 'Omaha Community Center';
}
if (empty($event_time)) {
  $event_time = '10:00 AM TO 2:00 PM';
}
if (empty($event_registration_fee)) {
  $event_registration_fee = '$35';
}
if (empty($event_category)) {
  $event_category = 'HEALTH, CHILDREN, FOOD';
}
if (empty($register_link)) {
  $register_link = '#';
}
if (empty($volunteer_link)) {
  $volunteer_link = '#';
}
if (empty($attend_link)) {
  $attend_link = '#';
}
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
  <div class="section event-detail-section">
    <div class="container">
      <div class="row">
        <div class="col-md-12 col-lg-12 content-col">
          <div class="event-information">
            <h4>Types of Membership offered</h4>
            <p>As per TSN <a href="https://telugusamiti.org/tsn/about/by-laws/" target="_blank">Bylaws</a>, we offer three kinds of membership:</p>
            <ol>
              <li>Life Membership—$150</li>
              <li>Annual Membership (Both Individual or family)—$35</li>
              <li>Student—$5</li>
            </ol>
            <h4>How to apply for Membership?</h4>
            <h4>STEP 1 Membership form:</h4>
            <p>Please fill out this <a href="https://tsn.telugusamiti.org/wp-content/uploads/2024/11/TSNMembershipApplicationForm2024.pdf" target="_blank">physical membership form</a> or <a href="https://forms.gle/zKzTJgQb94DKnAbt60" target="_blank">membership-google-Form</a> and email it to <a href="mailto:membership@telugusamiti.org" target="_blank">membership@telugusamiti.org</a></p>
            <h4>Instructions:</h4>
            <ul>
              
                You can open the form and fill it out electronically
                <ul>
                  <li>Mac users : You can use the preview app to edit and enter text in PDF files.</li>
                  <li>Windows users : You may use <a href="http://www.nitropdf.com/pdf-reader" target="_blank">Nitro Reader</a> (a free application) to edit and enter text in PDF files.</li>
                </ul>
              
            </ul>
            <h4>STEP 2 Payment:</h4>
            <p>Make a payment for the type of membership you are applying.</p>
            <h4>Instructions:</h4>
            <ul>
              <li>Please pay by using the PayPal form below.</li>
            </ul>
            <h4 style="margin-bottom:0px;">TSN Membership</h4>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="paypal"><input name="cmd" type="hidden" value="_s-xclick" /> <input name="hosted_button_id" type="hidden" value="GUK694J69HZBC" />
              <table>
                <tbody>
                <tr>
                  <td><input name="on0" type="hidden" value="TSN Membership" />&nbsp;</td>
                </tr>
                <tr>
                  <td>
                    <select name="os0">
                      <option value="Life Membership">Life Membership $150.00 USD</option>
                      <option value="Annual">Annual $35.00 USD</option>
                      <option value="Student">Student $5.00 USD</option>
                      </select>
                  </td>
                </tr>
                </tbody>
              </table>
              <br/>
              <input name="currency_code" type="hidden" value="USD" /> 
              <input alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypalobjects.com/en_US/i/btn/btn_cart_LG.gif" type="image" /> 
              <img src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" alt="" width="1" height="1" border="0" />
            </form>
			  
            <p>Once both the above steps are complete, we shall get in touch with you.  Please do not hesitate to ask any questions at any stage of the process.  Our contact information is in the footer of this page.</p>
          </div>
          <div class="socials">
            <ul>
              <li>
                <a href="#" class="fb" title="Facebook">
                  <span class="mdi mdi-facebook"></span>
                </a>
              </li>
             <!-- <li>
                <a href="#" class="tw" title="Twitter">
                  <span class="mdi mdi-twitter"></span>
                </a>
              </li>-->
              <li>
                <a href="#" class="ins" title="Instagram">
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
</main>

<?php
get_footer();
?>

