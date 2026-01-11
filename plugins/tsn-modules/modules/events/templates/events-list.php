<?php
  /**
   * Events List Template
   * 
   * Frontend display of all events
   *
   * @package TSN_Modules
   */
  
  if (!defined('ABSPATH')) {
      exit;
  }
  
  // Get sort order
  $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'default';
  
  // Get all published events
  $events = TSN_Events::get_all_events('published', $orderby);
  $now = current_time('mysql');
  ?>
<div class="tsn-events-container">
  <div class="events-header">
    <div class="section-title">
      <h3>Upcoming Events</h3>
      <p>Join us for our cultural events and community gatherings</p>
    </div>
    <div class="events-filters form-container">
      <form action=""  method="GET" id="events-sort-form">
        <div class="form-group">
          <!-- <label for="orderby">Sort By:</label> -->
          <select class="form-select" name="orderby" id="orderby" onchange="this.form.submit()">
            <option value="default" <?php selected($orderby, 'default'); ?>>Recently Updated</option>
            <option value="reg_asc" <?php selected($orderby, 'reg_asc'); ?>>Registration: Earliest First</option>
            <option value="reg_desc" <?php selected($orderby, 'reg_desc'); ?>>Registration: Latest First</option>
            <option value="date_asc" <?php selected($orderby, 'date_asc'); ?>>Event Date: Earliest First</option>
            <option value="date_desc" <?php selected($orderby, 'date_desc'); ?>>Event Date: Latest First</option>
            <option value="title_asc" <?php selected($orderby, 'title_asc'); ?>>Title (A-Z)</option>
          </select>
        </div>
      </form>
    </div>
  </div>
  <div class="line-with-dots">&nbsp;</div>
  <?php if ($events): ?>
  <!-- Inline SVG for clip-path (hidden) -->
  <svg width="0" height="0" style="position: absolute;">
    <defs>
      <clipPath id="event-curve-clip" clipPathUnits="objectBoundingBox" transform="scale(0.00212314, 0.00212314)">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M235.509 0C235.509 0 251.342 28.0927 293.116 28.0928C334.08 28.0928 388.216 33.6302 390.278 80.7246C437.373 82.793 442.91 136.922 442.91 177.883C442.91 219.592 470.91 235.456 471 235.507C471 235.507 442.91 251.344 442.91 293.117C442.91 334.086 437.373 388.21 390.278 390.278C388.216 437.37 334.08 442.919 293.116 442.919C251.353 442.919 235.517 470.985 235.509 471C235.497 470.98 219.645 442.919 177.887 442.919C136.92 442.919 82.7927 437.37 80.7275 390.278C33.6244 388.21 28.0811 334.086 28.0811 293.117C28.0809 251.344 0 235.507 0 235.507C0.0867788 235.458 28.0809 219.594 28.0811 177.883C28.0811 136.922 33.6244 82.793 80.7275 80.7246C82.7927 33.6302 136.92 28.0928 177.887 28.0928C219.653 28.0927 235.503 0.0100289 235.509 0ZM235.509 9C235.509 9 220.266 36.0174 180.088 36.0176C140.687 36.0176 88.6296 41.3437 86.6387 86.6377C41.3384 88.6287 36.0059 140.687 36.0059 180.082C36.0059 220.263 9 235.506 9 235.506C9.01847 235.516 36.0059 250.753 36.0059 290.915C36.0059 330.319 41.3384 382.374 86.6387 384.362C88.6296 429.653 140.687 434.991 180.088 434.991C220.266 434.991 235.509 462 235.509 462C235.522 461.977 250.756 434.991 290.915 434.991C330.313 434.991 382.379 429.653 384.364 384.362C429.659 382.374 434.985 330.319 434.985 290.915C434.985 250.757 461.977 235.519 462 235.506C462 235.506 434.985 220.263 434.985 180.082C434.985 140.687 429.659 88.6287 384.364 86.6377C382.379 41.3437 330.313 36.0176 290.915 36.0176C250.754 36.0174 235.52 9.01941 235.509 9Z"/>
      </clipPath>
    </defs>
  </svg>
  
  <div class="events-grid">
    <?php 
    $total_events = count($events);
    $current_index = 0;
    foreach ($events as $event): 
      $current_index++;
    ?>
      <?php
        $event_date = strtotime($event->start_datetime);
        $is_past = $event_date < time();
        $is_registration_open = (strtotime($event->reg_open_datetime) <= time()) &&
                              (strtotime($event->reg_close_datetime) >= time());
      ?>
      <section class="events-listing-section <?php echo $is_past ? 'past-event' : ''; ?>">
        <div class="container">
          <div class="event-item">
            <div class="row">
              <div class="col-md-5 col-lg-5 event-image-col">
                <div class="event-image-wrapper"> 
                  <?php if (!empty($event->banner_url)): ?>
                    <img src="<?php echo esc_url($event->banner_url); ?>" alt="<?php echo esc_attr($event->title); ?>" class="event-featured-image">
                  <?php else: ?>
                    <div class="event-image-placeholder">&nbsp;</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-md-7 col-lg-7 event-content-col">
                <div class="event-content">
                  <div class="event-status">
                    <?php if ($event->status === 'sold_out'): ?>
                    <span class="status-badge sold-out">Sold Out</span>
                    <?php elseif ($is_past): ?>
                    <span class="status-badge past">Past Event</span>
                    <?php elseif (!$is_registration_open): ?>
                    <span class="status-badge closed">Registration Closed</span>
                    <?php else: ?>
                    <span class="status-badge open">Registration Open</span>
                    <?php endif; ?>
                  </div>
                  <h4 class="event-title"><?php echo esc_html($event->title); ?></h4>
                  
                  <div class="event-meta">
                    <p class="event-date">
                      <strong>Date:</strong> <?php echo date('l, F j, Y', $event_date); ?>, <?php echo date('g:i A', $event_date); ?>
                    </p>
                    <?php if ($event->venue_name): ?>
                      <p class="event-location"><strong>LOCATION:</strong> <?php echo esc_html($event->venue_name); ?></p>
                    <?php endif; ?>
                  </div>
                  <?php if ($event->description): ?>
                    <div class="event-description">
                      <p><?php echo esc_html(wp_trim_words($event->description, 20)); ?></p>
                    </div>
                  <?php endif; ?>
                  <div class="buttons">
                    <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn btn-primary btn-sm"><span>View Details</span>
                    </a>
                    <?php if ($is_registration_open && $event->status !== 'sold_out'): ?>
                    <a href="<?php echo home_url('/events/' . $event->slug . '#register'); ?>" class="btn-register btn btn-outline-primary btn-sm"><span>Register Now</span></a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <?php if ($current_index < $total_events): ?>
      <div class="line-with-dots">&nbsp;</div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="no-events">
    <p>No events scheduled at the moment. Check back soon!</p>
  </div>
  <?php endif; ?>
</div>
<style>
  .tsn-events-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  }
  .events-header {
  text-align: center;
  margin-bottom: 40px;
  }
  .events-header h2 {
  color: #0066cc;
  margin-bottom: 10px;
  }
  .events-filters {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  }
  .events-filters form {
  display: flex;
  align-items: center;
  gap: 10px;
  }
  .events-filters select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  min-width: 200px;
  cursor: pointer;
  }
  .events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 30px;
  }
  .event-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s;
  position: relative;
  }
  .event-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transform: translateY(-2px);
  }
  .past-event {
  opacity: 0.7;
  }
  .event-date-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: #0066cc;
  color: white;
  padding: 10px;
  border-radius: 8px;
  text-align: center;
  min-width: 60px;
  }
  .event-date-badge .month {
  font-size: 12px;
  text-transform: uppercase;
  }
  .event-date-badge .day {
  font-size: 24px;
  font-weight: bold;
  }
  .event-content {
  padding: 20px;
  }
  .event-content h3 {
  margin: 0 0 15px 0;
  color: #333;
  padding-right: 80px;
  }
  .event-meta {
  margin-bottom: 15px;
  }
  .meta-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  color: #666;
  font-size: 14px;
  }
  .meta-item .icon {
  margin-right: 8px;
  }
  .event-description {
  color: #666;
  line-height: 1.6;
  margin-bottom: 15px;
  }
  .event-status {
  margin-bottom: 15px;
  }
  .status-badge {
  display: inline-block;
  padding: 5px 12px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  }
  .status-badge.open {
  background: #d4edda;
  color: #155724;
  }
  .status-badge.sold-out,
  .status-badge.closed {
  background: #f8d7da;
  color: #721c24;
  }
  .status-badge.past {
  background: #e2e3e5;
  color: #383d41;
  }
  
  .no-events {
  text-align: center;
  padding: 60px 20px;
  color: #666;
  }
  @media (max-width: 768px) {
  .events-grid {
  grid-template-columns: 1fr;
  }
  }
</style>