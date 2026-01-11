<?php

/**
 * Template Name: Event Detail
 * Description: Custom event detail page template for Telugu Samiti
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
       <div class="image-container">
        <img src="<?php echo get_template_directory_uri(); ?>/images/telgusamiti_sankranti_event.jpg" alt="Telugu Samiti Annual Sankranthi Celebrations 2026">
      </div> 
      <div class="row">
        <div class="col-md-8 col-lg-8 content-col">
          <div class="event-meta">
            <h5>Date: January 17th, 2026</h5>
            <h5>Location: Omaha Community Center</h5>
          </div>
          <div class="event-information">
            <p>We warmly invite our Telugu community to join us in celebrating the joyous festival of Sankranti! It’s a wonderful time to come together, honor our traditions, and cherish our rich cultural heritage.</p>
            <p>Our celebration will be filled with vibrant cultural activities, traditional games, and delicious festive food. Watch our children shine through art, music, dance, and creative performances and express themselves bringing the spirit of Sankranti to life.</p>
            <p>Come, be part of this heartwarming celebrations to strengthen our bonds, create joyful memories and embrace the fun, tradition, and community spirit!</p>
            <h4>General Guidelines For Event:</h4>
            <ol>
              <li>Cultural Event Participants must holds an active TSN membership (either annual or lifetime).</li>
              <li>Kindly note that, the TSN membership fee will be $35.</li>
              <li>TSN membership Registration link here: (Membership Link given here: <a href="https://telugusamiti.org/tsn/membership/" target="_blank">https://telugusamiti.org/tsn/membership/</a>)</li>
              <li>Pls join TSN communications whatsup_link for Events and Notifications.</li>
            </ol>
            <h4>Cultural Program Guidelines:</h4>
            <p>We are working to improve previous formats, kindly request you to avoid any misunderstandings of unclear rules. TSN Cultural will be happy to assist you in ensuring the event runs smoothly.</p>
            <ol>
              <li>Programs related to Telugu Culture, Heritage and Traditions that resonate with the spirit of Sankranthi are encouraged.</li>
              <li>Program confirmation is based on priority, not on a first-come, first-served basis. Priority will be given to the categories mentioned below.</li>
              <li>Dance (Classical, Semi Classical and Folk), Singing (Folk and Classical), musical instruments are encouraged and Skits that showcase our traditions or highlighting the rich history are encouraged.</li>
              <li>Song repetitions are strictly not encouraged for any dance entry.</li>
              <li>The selection priority of a program is solely based on the songs chosen and the performance review conducted before the rehearsals by cultural team (For details contact Cultural Secretary).</li>
              <li>Choreographers and Coordinators are responsible to ensure that all participants are holding active TSN membership Id. (Membership Link given here: <a href="https://telugusamiti.org/tsn/membership/" target="_blank">https://telugusamiti.org/tsn/membership/</a>)</li>
              <li>TSN reserves the right to cancel the registered program if requirements are not met.</li>
            </ol>
            <h4>Program Entry Guidelines:</h4>
            <ol>
              <li>1. Programs need to be registered with all the details specified in the registration form.
              <li>The minimum number of participants for any dance entry is 6 or Participant must be at least 6 years or older by January 17, 2026.</li>
              <li>Participants can register for ONLY ONE entry — Classical or Non-Classical Dance.</li>
              <li>A Dance Entry may include maximum of 3 songs (Telugu Songs).</li>
              <li>Time Allotted for Classical Dance entry is 10 min and Non-Classical entry is 5 min. All Programs should adhere to the allotted time.</li>
            </ol>
            <p>Note : To ensure on-time program completion and uphold cleanliness in the green room and premises, participants are limited to one entry to help maintain the premises.</p>
            <h4>Sankranthi Family Fest:</h4>
            <p>We are thrilled to announce the very first TSN Sankranthi Family Fest! – A Celebration for Everyone. As we recreate the vibrant festive atmosphere of our hometowns—with beautiful décor, colorful rangoli, traditional stalls, delicious food, and plenty of fun activities, come join us in this joyous celebration of togetherness, culture, and tradition, and make the TSN Sankranthi Fest a truly memorable experience!</p>
            <h4>Activities:</h4>
            <ol>
              <li>Drawing (For Kids)</li>
              <li>Telugu Quiz (Adults & Kids)</li>
              <li>Sankranthi Pindi Vantalu (Women)</li>
              <li>Fashion Show (Kids – Traditional / Devotional / Historical Attires )</li>
              <li>Kite Making</li>
              <li>Rangoli Competetion at your place (Women)</li>
            </ol>
            <h4>More details ON THE WAY !!</h4>
            <p>Your enthusiasm, creativity, and support will not only bring joy to the event but also strengthen our bonds as a community. Let’s come together to celebrate, create lasting memories with family and friends making this event a grand success.</p>
            <p>Please reach out to TSN cultural committee (culturalsec@telugusamiti.org) for any questions, suggestions or concerns.</p>
          </div>
          <div class="event-buttons">
            <a href="https://telugusamiti.org/tsn/membership/"  target="_blank" class="btn btn-primary btn-sm"><span>REGISTER NOW</span></a>
            <a href="https://telugusamiti.org/tsn/membership/"  target="_blank" class="btn btn-outline-primary"><span>VOLUNTEER</span></a>
            <a href="https://telugusamiti.org/tsn/membership/"  target="_blank" class="btn btn-outline-primary"><span>ATTEND</span></a>
          </div>
        </div>
        <div class="col-md-4 col-lg-4 side-col">
          <div class="col-container">
            <ul>
              <li><h5>Sankranthi Sambaralu </h5><h6>January 17<sup>th</sup> , 2026</h6></li>
              <li><h5>Final Rehearsal</h5><h6>January 16<sup>th</sup> , 2026</h6></li>
              <li><h5>Guidelines and Details </h5><h6>November 29<sup>th</sup> , 2026</h6></li>
              <li><h5>Cultural Registration open</h5><h6>December 5<sup>th</sup> , 2026</h6></li>
              <li><h5>Cultural Registration ends </h5><h6>December 7<sup>th</sup> , 2026</h6></li>
              <li><h5>Last Day for Audio Submission  </h5><h6>January 7<sup>th</sup> ,  2027</h6></li>
              <li><h5>Games & Activities </h5><h6>January 10<sup>th</sup> & 11<sup>th</sup>, 2027</h6></li>
              <li><h5>Program Review before rehearsal  </h5><h6>(contact cultural secretary)</h6></li>
            </ul>
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