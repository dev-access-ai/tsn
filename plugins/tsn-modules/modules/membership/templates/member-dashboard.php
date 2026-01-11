<?php
/**
 * Member Dashboard Template
 * 
 * Display member profile and membership details
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
  exit;
}

// Handle logout from member dashboard - MUST be before any output
if (isset($_GET['tsn_logout']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'tsn_logout')) {
  error_log('TSN: Dashboard logout triggered');
  TSN_Membership_OTP::logout_member();
  error_log('TSN: Dashboard logout successful, redirecting');
  wp_safe_redirect(home_url('/'));
  exit;
}

// Check login status before headers
$member = TSN_Membership_OTP::get_logged_in_member();

if (!$member) {
  // Session expired or not logged in - redirect to login page with message
  $login_url = home_url('/member-login/');
  $redirect_url = add_query_arg('session_expired', '1', $login_url);
  
  // If AJAX request, return JSON
  if (wp_doing_ajax() || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    wp_send_json_error(['message' => 'Session expired. Please log in again.', 'redirect' => $login_url]);
  }
  
  wp_safe_redirect($redirect_url);
  exit;
}

// Load header
get_header();
?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3>Member Dashboard</h3>
      </div>
      <?php if(function_exists('telugusmiti_breadcrumb')) telugusmiti_breadcrumb(); ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
  <div class="section">
    <div class="container">
      <?php
      // Calculate membership status
      $is_expired = false;
      $days_remaining = null;
      
      if ($member->membership_type !== 'lifetime' && $member->valid_to) {
        $expiry_date = strtotime($member->valid_to);
        $today = strtotime(current_time('Y-m-d'));
        $days_remaining = ceil(($expiry_date - $today) / (60 * 60 * 24));
        $is_expired = $days_remaining < 0;
      }
      
      $status_class = $is_expired ? 'expired' : ($days_remaining !== null && $days_remaining < 30 ? 'expiring-soon' : 'active');
      
      // Parse family details (Robust: Handle both JSON and Serialized)
      $spouse = array();
      if (!empty($member->spouse_details)) {
          $spouse = json_decode($member->spouse_details, true);
          if (empty($spouse) && json_last_error() !== JSON_ERROR_NONE) {
             $spouse = maybe_unserialize($member->spouse_details);
          }
           if (!is_array($spouse)) $spouse = array();
      }

      $children = array();
      if (!empty($member->children_details)) {
          $children = json_decode($member->children_details, true);
          if (empty($children) && json_last_error() !== JSON_ERROR_NONE) {
             $children = maybe_unserialize($member->children_details);
          }
          if (!is_array($children)) $children = array();
      }
      ?>
      
      <div class="tsn-member-dashboard">
        <div class="dashboard-header">
          <h2>Welcome, <?php echo esc_html($member->first_name)?>!</h2>
          <div class="header-actions">
            <a href="<?php echo home_url('/edit-profile/'); ?>" class="icon-btn" title="Edit Profile">
              <span class="icon">👤</span>
            </a>
            <a href="<?php echo home_url('/events/'); ?>" class="icon-btn" title="Browse Events">
              <span class="icon">📅</span>
            </a>
            <a href="<?php echo home_url('/donations/'); ?>" class="icon-btn" title="Make a Donation">
              <span class="icon">💝</span>
            </a>
            <a href="<?php echo home_url('/contact/'); ?>" class="icon-btn" title="Contact Us">
              <span class="icon">📧</span>
            </a>
            <a href="<?php echo wp_nonce_url(add_query_arg('tsn_logout', '1'), 'tsn_logout'); ?>" class="logout-link">Logout</a>
          </div>
        </div>

        <?php
        // Get member's registered events (moved here to check before grid)
        global $wpdb;
        $current_datetime = current_time('mysql');
        
        // Debug logging
        error_log('TSN Dashboard: Looking for events for email: ' . $member->email);
        error_log('TSN Dashboard: Current datetime: ' . $current_datetime);
        
        // Debug Output on Frontend
        if (isset($_GET['debug_tsn']) && $_GET['debug_tsn'] == '1') {
            echo '<div style="background:#fff; border:2px solid red; padding:20px; margin:20px 0;">';
            echo '<h3>TSN Debug Info</h3>';
            echo '<p><strong>Member Email:</strong> ' . $member->email . '</p>';
            echo '<p><strong>Current Time:</strong> ' . $current_datetime . '</p>';
            
            // Query 1: All orders by email (Check for case sensitivity)
            $all_orders = $wpdb->get_results($wpdb->prepare(
              "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE buyer_email = %s",
              $member->email
            ));
            
            echo '<p><strong>Total Orders (Exact Match):</strong> ' . count($all_orders) . '</p>';
            if (!empty($all_orders)) {
                echo '<table border="1" style="width:100%; border-collapse:collapse;">';
                echo '<tr><th>ID</th><th>Order #</th><th>Event ID</th><th>Status</th><th>Email</th><th>Created</th></tr>';
                foreach ($all_orders as $ord) {
                    echo "<tr>";
                    echo "<td>{$ord->id}</td>";
                    echo "<td>{$ord->order_number}</td>";
                    echo "<td>{$ord->event_id}</td>";
                    echo "<td>{$ord->status}</td>";
                    echo "<td>{$ord->buyer_email}</td>";
                    echo "<td>{$ord->created_at}</td>";
                    echo "</tr>";
                }
                echo '</table>';
            }
            
            // Query 2: Case Insensitive Check
            $all_orders_insensitive = $wpdb->get_results($wpdb->prepare(
              "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE LOWER(buyer_email) = LOWER(%s)",
              $member->email
            ));
             echo '<p><strong>Total Orders (Case Insensitive):</strong> ' . count($all_orders_insensitive) . '</p>';
             
             echo '</div>';
        }

        // Query 1: Upcoming & Current Events (Fix: use end_datetime if available)
        $registered_events = $wpdb->get_results($wpdb->prepare(
          "SELECT DISTINCT e.*, o.id as order_id, o.order_number, o.created_at as registration_date, o.total as order_total,
           GROUP_CONCAT(CONCAT(tt.name, ' (', oi.qty, ')') SEPARATOR ', ') as ticket_summary
           FROM {$wpdb->prefix}tsn_orders o
           JOIN {$wpdb->prefix}tsn_events e ON o.event_id = e.id
           LEFT JOIN {$wpdb->prefix}tsn_order_items oi ON o.id = oi.order_id
           LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON oi.ticket_type_id = tt.id
           WHERE LOWER(o.buyer_email) = LOWER(%s) 
           AND o.status IN ('completed', 'paid')
           AND (e.end_datetime >= %s OR (e.end_datetime IS NULL AND e.start_datetime >= %s))
           AND e.status = 'published'
           GROUP BY e.id, o.id
           ORDER BY e.start_datetime ASC",
          $member->email,
          $current_datetime,
          $current_datetime
        ));
        
        // Query 2: Past Events
        $past_events = $wpdb->get_results($wpdb->prepare(
          "SELECT DISTINCT e.*, o.id as order_id, o.order_number, o.created_at as registration_date, o.total as order_total,
           GROUP_CONCAT(CONCAT(tt.name, ' (', oi.qty, ')') SEPARATOR ', ') as ticket_summary
           FROM {$wpdb->prefix}tsn_orders o
           JOIN {$wpdb->prefix}tsn_events e ON o.event_id = e.id
           LEFT JOIN {$wpdb->prefix}tsn_order_items oi ON o.id = oi.order_id
           LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON oi.ticket_type_id = tt.id
           WHERE LOWER(o.buyer_email) = LOWER(%s) 
           AND o.status IN ('completed', 'paid')
           AND (e.end_datetime < %s OR (e.end_datetime IS NULL AND e.start_datetime < %s))
           GROUP BY e.id, o.id
           ORDER BY e.start_datetime DESC
           LIMIT 5",
          $member->email,
          $current_datetime,
          $current_datetime
        ));

        if ($wpdb->last_error) {
           if (isset($_GET['debug_tsn'])) echo '<p style="color:red">SQL Error: ' . $wpdb->last_error . '</p>';
        }

        // Fetch Donations (Fix: case insensitive email)
        $donations = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, c.title as cause_title 
             FROM {$wpdb->prefix}tsn_donations d
             LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
             LEFT JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
             WHERE LOWER(d.donor_email) = LOWER(%s) AND (o.status = 'completed' OR o.status = 'paid')
             ORDER BY d.created_at DESC",
            $member->email
        ));

        // Debug output for Donations
        if (isset($_GET['debug_tsn']) && $_GET['debug_tsn'] == '1') {
            echo '<div style="background:#fff; border:2px solid orange; padding:20px; margin:20px 0;">';
            echo '<h3>TSN Donation Debug Info</h3>';
            
            // Query all donations for email
            $all_donations = $wpdb->get_results($wpdb->prepare(
                "SELECT d.*, o.status as order_status FROM {$wpdb->prefix}tsn_donations d 
                 LEFT JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
                 WHERE LOWER(d.donor_email) = LOWER(%s)",
                $member->email
            ));
            
            echo '<p><strong>Total Donations Found (Any Status):</strong> ' . count($all_donations) . '</p>';
            if (!empty($all_donations)) {
                echo '<table border="1" style="width:100%; border-collapse:collapse;">';
                echo '<tr><th>ID</th><th>Donation ID</th><th>Amount</th><th>Order Status</th><th>Email</th></tr>';
                foreach ($all_donations as $don) {
                     echo "<tr>";
                     echo "<td>{$don->id}</td>";
                     echo "<td>{$don->donation_id}</td>";
                     echo "<td>{$don->amount}</td>";
                     echo "<td>{$don->order_status}</td>";
                     echo "<td>{$don->donor_email}</td>";
                     echo "</tr>";
                }
                echo '</table>';
            }
            echo '</div>';
        }
        ?>

        <div class="dashboard-grid">
          <!-- Membership Card -->
          <div class="dashboard-primary-container">
            <div class="dashboard-card membership-card">
              <div class="section-title">
                <h3>Your Membership</h3>
              </div>
              <div class="membership-details">
                <div class="member-id detail-row">
                  <span class="label">Member ID:</span>
                  <span class="value"><?php echo esc_html($member->member_id); ?></span>
                </div>
                <div class="detail-row">
                  <span class="label">Type:</span>
                  <span class="value"><strong><?php echo esc_html(ucfirst($member->membership_type)); ?> Membership</strong></span>
                </div>
                <div class="detail-row">
                  <span class="label">Status:</span>
                  <span class="status-badge status-<?php echo $status_class; ?>">
                    <?php 
                    if ($is_expired) {
                      echo 'Expired';
                    } elseif ($member->membership_type === 'lifetime') {
                      echo 'Active (Lifetime)';
                    } else {
                      echo 'Active';
                    }
                    ?>
                  </span>
                </div>
                <?php if ($member->membership_type !== 'lifetime'): ?>
                  <div class="detail-row">
                    <span class="label">Validity:</span>
                    <span class="value">
                      <?php echo date('M j, Y', strtotime($member->valid_from)); ?> - 
                      <?php echo date('M j, Y', strtotime($member->valid_to)); ?>
                    </span>
                  </div>
                  <?php if (!$is_expired && $days_remaining !== null): ?>
                    <div class="detail-row">
                      <span class="label">Days Remaining:</span>
                      <span class="value <?php echo $days_remaining < 30 ? 'text-warning' : ''; ?>">
                        <?php echo $days_remaining; ?> days
                      </span>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>

            <!-- Personal Information -->
            <div class="dashboard-card contact-card">
              <div class="section-title">
                <h3>Contact Information</h3>
              </div>
              <div class="contact-details">
                <div class="detail-row">
                  <span class="label">Name:</span>
                  <span class="value"><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></span>
                </div>
                <div class="detail-row">
                  <span class="label">Email:</span>
                  <span class="value"><?php echo esc_html($member->email); ?></span>
                </div>
                <?php if ($member->phone): ?>
                  <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo esc_html($member->phone); ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($member->address): ?>
                  <div class="detail-row">
                    <span class="label">Address:</span>
                    <span class="value">
                      <?php echo esc_html($member->address); ?><br>
                      <?php echo esc_html($member->city . ', ' . $member->state . ' ' . $member->zip_code); ?><br>
                      <?php echo esc_html($member->country); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Family Information -->
            <div class="dashboard-card family-card">
              <div class="section-title">
                <h3>Family Information</h3>
              </div>
              <div class="family-details">
                <div class="detail-group">
                    <strong>Spouse</strong>
                    <?php if (!empty($spouse['name'])): ?>
                        <div class="detail-row">
                            <span class="label">Name:</span>
                            <span class="value"><?php echo esc_html($spouse['name']); ?></span>
                        </div>
                        <?php if (!empty($spouse['email'])): ?>
                            <div class="detail-row">
                                <span class="label">Email:</span>
                                <span class="value"><?php echo esc_html($spouse['email']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($spouse['phone'])): ?>
                            <div class="detail-row">
                                <span class="label">Phone:</span>
                                <span class="value"><?php echo esc_html($spouse['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-data">No spouse details added.</p>
                    <?php endif; ?>
                </div>

                <div class="detail-group" style="margin-top: 15px; border-top: 1px solid #f0f0f0; padding-top: 15px;">
                    <strong>Children</strong>
                    <?php if (!empty($children)): ?>
                        <ul class="children-list" style="list-style: none; padding: 0; margin-top: 10px;">
                            <?php foreach ($children as $child): ?>
                                <li style="margin-bottom: 8px; font-size: 14px;">
                                    👶 <strong><?php echo esc_html($child['name']); ?></strong> 
                                    (<?php echo esc_html($child['age']); ?> yrs, <?php echo esc_html($child['gender']); ?>)
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data">No children details added.</p>
                    <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Member Benefits -->
            <div class="dashboard-card benefits-card">
              <div class="section-title">
                <h3>Member Benefits</h3>
              </div>
              <ul class="benefits-list">
                <li>✓ Discounted event registration rates</li>
                <li>✓ Access to member-only events</li>
                <li>✓ Monthly community newsletters</li>
                <li>✓ Priority event seating</li>
                <li>✓ Recognition in member directory</li>
              </ul>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="dashboard-sidebar">
            <div class="dashboard-card actions-card">
              <div class="section-title">
                <h3>Quick Actions</h3>
              </div>
              <div class="tsn-member-actions">
                <a href="<?php echo home_url('/events/'); ?>" class="tsn-btn tsn-btn-primary btn btn-primary btn-sm">Browse Events</a>
                <a href="<?php echo home_url('/donations/'); ?>" class="tsn-btn tsn-btn-secondary btn btn-outline-primary btn-sm">Make a Donation</a>
                <a href="<?php echo home_url('/edit-profile/'); ?>" class="tsn-btn tsn-btn-secondary btn btn-outline-primary btn-sm">Edit Profile</a>
                <a href="<?php echo home_url('/contact/'); ?>" class="tsn-btn tsn-btn-secondary btn btn-outline-primary btn-sm">Contact Us</a>
              </div>
            </div>
            <div class="dashboard-card renewal-card">
               <?php if ($is_expired || ($days_remaining !== null && $days_remaining < 30)): ?>
                <div class="renewal-alert">
                  <?php if ($is_expired): ?>
                    <p><strong>⚠️ Your membership has expired!</strong></p>
                    <p>Renew now to continue enjoying member benefits.</p>
                  <?php else: ?>
                    <p><strong>⏰ Your membership expires soon!</strong></p>
                    <p>Renew early to avoid any interruption in services.</p>
                  <?php endif; ?>
                  <a href="<?php echo home_url('/renew-membership/'); ?>" class="tsn-btn tsn-btn-primary btn btn-primary btn-sm">Renew Membership</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- My Registered Events Section - Full Width -->
        <?php if (!empty($registered_events)): ?>
          <div class="dashboard-events-fullwidth">
            <h2>📅 My Registered Events</h2>
            <div class="events-grid">
              <?php foreach ($registered_events as $event): 
                $event_date = date('M j, Y', strtotime($event->start_datetime));
                $event_time = date('g:i A', strtotime($event->start_datetime));
              ?>
                <div class="event-card-large">
                  <div class="event-card-header">
                    <div>
                      <h3 class="event-title"><?php echo esc_html($event->title); ?></h3>
                      <p class="event-date">📆 <?php echo esc_html($event_date); ?> at <?php echo esc_html($event_time); ?></p>
                    </div>
                    <div class="event-status-badge">✓ Registered</div>
                  </div>
                  <div class="event-card-body">
                    <div class="event-info-grid">
                      <div class="info-item">
                        <span class="info-label">Venue:</span>
                        <span class="info-value">📍 <?php echo esc_html($event->venue_name); ?></span>
                      </div>
                      <?php if ($event->ticket_summary): ?>
                        <div class="info-item">
                          <span class="info-label">Tickets:</span>
                          <span class="info-value">🎫 <?php echo esc_html($event->ticket_summary); ?></span>
                        </div>
                      <?php endif; ?>
                      <div class="info-item">
                        <span class="info-label">Order:</span>
                        <span class="info-value">#<?php echo esc_html($event->order_number); ?></span>
                      </div>
                      <div class="info-item">
                        <span class="info-label">Total Paid:</span>
                        <span class="info-value amount">$<?php echo number_format($event->order_total, 2); ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="event-card-footer" style="display: flex; gap: 10px; justify-content: space-between; align-items: center;">
                    <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn-view-event-large" style="flex: 1;">View Event →</a>
                    <button onclick="tsnMembership.printTicket(<?php echo $event->order_id; ?>)" class="icon-btn" title="Print Tickets" style="width: 40px; height: 40px; border-radius: 4px; border: 1px solid #ddd; background: #fff;">🖨️</button>
                    <button onclick="tsnMembership.emailTicket(<?php echo $event->order_id; ?>)" class="icon-btn" title="Email Tickets" style="width: 40px; height: 40px; border-radius: 4px; border: 1px solid #ddd; background: #fff;">✉️</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="events-footer">
              <a href="<?php echo home_url('/events/'); ?>" class="browse-all-events">Browse All Events →</a>
            </div>
          </div>
        <?php endif; ?>

        <!-- Past Events Section -->
        <?php if (!empty($past_events)): ?>
          <div class="dashboard-events-fullwidth" style="margin-top: 30px; border-color: #6c757d; background: linear-gradient(135deg, rgba(108, 117, 125, 0.05) 0%, rgba(108, 117, 125, 0.05) 100%);">
            <h2 style="color: #495057; border-bottom-color: #6c757d;">🕰️ Past Events</h2>
            <div class="events-grid">
              <?php foreach ($past_events as $event): 
                $event_date = date('M j, Y', strtotime($event->start_datetime));
                $event_time = date('g:i A', strtotime($event->start_datetime));
              ?>
                <div class="event-card-large" style="opacity: 0.8; border-color: #dee2e6;">
                  <div class="event-card-header" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                    <div>
                      <h3 class="event-title"><?php echo esc_html($event->title); ?></h3>
                      <p class="event-date">📆 <?php echo esc_html($event_date); ?> at <?php echo esc_html($event_time); ?></p>
                    </div>
                    <div class="event-status-badge" style="background: #e9ecef; color: #6c757d;">Past Event</div>
                  </div>
                  <div class="event-card-body">
                    <div class="event-info-grid">
                      <div class="info-item">
                        <span class="info-label">Venue:</span>
                        <span class="info-value">📍 <?php echo esc_html($event->venue_name); ?></span>
                      </div>
                      <?php if ($event->ticket_summary): ?>
                        <div class="info-item">
                          <span class="info-label">Tickets:</span>
                          <span class="info-value">🎫 <?php echo esc_html($event->ticket_summary); ?></span>
                        </div>
                      <?php endif; ?>
                      <div class="info-item">
                        <span class="info-label">Order:</span>
                        <span class="info-value">#<?php echo esc_html($event->order_number); ?></span>
                      </div>
                      <div class="info-item">
                        <span class="info-label">Total Paid:</span>
                        <span class="info-value amount">$<?php echo number_format($event->order_total, 2); ?></span>
                      </div>
                    </div>
                  </div>
                  <div class="event-card-footer" style="display: flex; gap: 10px; justify-content: space-between; align-items: center;">
                    <a href="<?php echo home_url('/events/' . $event->slug); ?>" class="btn-view-event-large" style="flex: 1; background: #6c757d; color: white;">View Event →</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- My Donations Section -->
        <?php if (!empty($donations)): ?>
          <div class="dashboard-events-fullwidth" style="margin-top: 30px; border-color: #28a745; background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(32, 201, 151, 0.05) 100%);">
            <h2 style="color: #1e7e34; border-bottom-color: #28a745;">💝 My Donations</h2>
            <div class="table-responsive" style="overflow-x: auto;">
              <table class="wp-list-table widefat fixed striped" style="width: 100%; border: 1px solid #e0e0e0; border-radius: 8px;">
                <thead>
                  <tr>
                    <th style="padding: 12px; text-align: left; font-weight: 600;">Date</th>
                    <th style="padding: 12px; text-align: left; font-weight: 600;">Donation ID</th>
                    <th style="padding: 12px; text-align: left; font-weight: 600;">Cause</th>
                    <th style="padding: 12px; text-align: right; font-weight: 600;">Amount</th>
                    <th style="padding: 12px; text-align: center; font-weight: 600;">Status</th>
                    <th style="padding: 12px; text-align: center; font-weight: 600;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($donations as $donation): ?>
                    <tr>
                      <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($donation->created_at)); ?></td>
                      <td style="padding: 12px;">#<?php echo esc_html($donation->donation_id); ?></td>
                      <td style="padding: 12px;"><?php echo $donation->cause_title ? esc_html($donation->cause_title) : 'General Donation'; ?></td>
                      <td style="padding: 12px; text-align: right; font-weight: bold; color: #28a745;">$<?php echo number_format($donation->amount, 2); ?></td>
                      <td style="padding: 12px; text-align: center;"><span class="event-status-badge" style="background: #28a745;">Completed</span></td>
                      <td style="padding: 12px; text-align: center;">
                        <button onclick="tsnMembership.printReceipt(<?php echo $donation->order_id; ?>)" class="tsn-btn tsn-btn-secondary btn-sm" style="padding: 4px 8px; font-size: 12px; display: inline-block;">🖨️</button>
                        <button onclick="tsnMembership.emailReceipt(<?php echo $donation->order_id; ?>)" class="tsn-btn tsn-btn-secondary btn-sm" style="padding: 4px 8px; font-size: 12px; display: inline-block;">✉️</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="events-footer" style="border-top-color: rgba(40, 167, 69, 0.2);">
                <a href="<?php echo home_url('/donations/'); ?>" class="browse-all-events" style="color: #28a745;">Make Another Donation →</a>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <style>
        .tsn-member-dashboard {
          max-width: 1200px;
          margin: 0 auto;
          padding: 20px;
        }
        .dashboard-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 30px;
          padding-bottom: 20px;
          border-bottom: 3px solid;
          border-image: linear-gradient(90deg, #F4A261 0%, #FEBF10 100%) 1;
        }
        .dashboard-header h2 {
          margin: 0;
          color: #4b0205;
        }
        .header-actions {
          display: flex;
          align-items: center;
          gap: 10px;
        }
        .icon-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 45px;
          height: 45px;
          background: linear-gradient(135deg, rgba(244, 162, 97, 0.1) 0%, rgba(254, 191, 16, 0.1) 100%);
          border: 2px solid #F4A261;
          border-radius: 50%;
          text-decoration: none;
          transition: all 0.3s ease;
          cursor: pointer;
        }
        .icon-btn:hover {
          background: linear-gradient(135deg, #F4A261 0%, #FEBF10 100%);
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(244, 162, 97, 0.4);
        }
        .icon-btn .icon {
          font-size: 20px;
        }
        .logout-link {
          padding: 10px 20px;
          background: #d32f2f;
          color: white;
          text-decoration: none;
          border-radius: 25px;
          font-weight: 600;
          font-size: 14px;
          transition: all 0.3s ease;
        }
        .logout-link:hover {
          background: #b71c1c;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(211, 47, 47, 0.4);
        }
        .dashboard-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
          gap: 20px;
        }
        .dashboard-card {
          background: white;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          padding: 25px;
          box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .dashboard-card h3 {
          margin-top: 0;
          color: #333;
          border-bottom: 2px solid #0066cc;
          padding-bottom: 10px;
        }
        .member-id {
          background: #f8f9fa;
          padding: 15px;
          border-radius: 4px;
          margin-bottom: 20px;
          text-align: center;
        }
        .member-id .label {
          display: block;
          font-size: 12px;
          color: #666;
          margin-bottom: 5px;
        }
        .member-id .value {
          font-size: 24px;
          font-weight: bold;
          color: #0066cc;
          font-family: monospace;
        }
        .detail-row {
          display: flex;
          justify-content: space-between;
          padding: 10px 0;
          border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child {
          border-bottom: none;
        }
        .detail-row .label {
          font-weight: 600;
          color: #666;
        }
        .status-badge {
          padding: 4px 12px;
          border-radius: 12px;
          font-size: 14px;
          font-weight: 600;
        }
        .status-active {
          background: #d4edda;
          color: #155724;
        }
        .status-expiring-soon {
          background: #fff3cd;
          color: #856404;
        }
        .status-expired {
          background: #f8d7da;
          color: #721c24;
        }
        .renewal-alert {
          margin-top: 20px;
          padding: 15px;
          background: #fff3cd;
          border-left: 4px solid #ffc107;
          border-radius: 4px;
        }
        .renewal-alert p {
          margin: 5px 0;
        }
        .text-warning {
          color: #ff6b00;
          font-weight: bold;
        }
        .benefits-list {
          list-style: none;
          padding: 0;
        }
        .benefits-list li {
          padding: 8px 0;
          color: #28a745;
        }
        .action-buttons {
          display: flex;
          flex-direction: column;
          gap: 10px;
        }
        .tsn-btn {
          display: block;
          padding: 10px 20px;
          text-align: center;
          text-decoration: none;
          border-radius: 4px;
          transition: all 0.3s;
        }
        .tsn-btn-primary {
          background: #0066cc;
          color: white;
        }
        .tsn-btn-primary:hover {
          background: #0052a3;
        }
        .tsn-btn-secondary {
          background: #6c757d;
          color: white;
        }
        .tsn-btn-secondary:hover {
          background: #545b62;
        }
        .tsn-btn-secondary {
          grid-template-columns: 1fr;
          gap: 16px;
        }
        .dashboard-card.benefits-card li {
          margin-bottom: 10px;
          color: #555;
        }
        /* Full Width Events Section */
        .dashboard-events-fullwidth {
          background: linear-gradient(135deg, rgba(244, 162, 97, 0.05) 0%, rgba(254, 191, 16, 0.05) 100%);
          border: 2px solid #F4A261;
          border-radius: 12px;
          padding: 30px;
          margin-bottom: 30px;
        }
        .dashboard-events-fullwidth h2 {
          color: #4b0205;
          font-size: 24px;
          margin: 0 0 25px 0;
          border-bottom: 2px solid #F4A261;
          padding-bottom: 15px;
        }
        .events-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
          gap: 20px;
          margin-bottom: 20px;
        }
        .event-card-large {
          background: white;
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
          transition: all 0.3s ease;
          border: 1px solid #e0e0e0;
        }
        .event-card-large:hover {
          transform: translateY(-3px);
          box-shadow: 0 5px 20px rgba(244, 162, 97, 0.3);
          border-color: #F4A261;
        }
        .event-card-header {
          background: linear-gradient(135deg, #4b0205 0%, #6b0307 100%);
          padding: 20px;
          color: white;
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
        }
        .event-card-header h3 {
          margin: 0 0 8px 0;
          font-size: 18px;
          color: white;
        }
        .event-card-header .event-date {
          margin: 0;
          font-size: 13px;
          color: rgba(255, 255, 255, 0.9);
        }
        .event-status-badge {
          background: #28a745;
          color: white;
          padding: 6px 12px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: 600;
          white-space: nowrap;
        }
        .event-card-body {
          padding: 20px;
        }
        .event-info-grid {
          display: grid;
          gap: 12px;
        }
        .info-item {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 10px;
          background: #f8f9fa;
          border-radius: 6px;
        }
        .info-label {
          font-weight: 600;
          color: #666;
          min-width: 90px;
        }
        .info-value {
          color: #333;
          flex: 1;
        }
        .info-value.amount {
          color: #F4A261;
          font-weight: 700;
          font-size: 16px;
        }
        .event-card-footer {
          padding: 15px 20px;
          background: #f8f9fa;
          border-top: 1px solid #e0e0e0;
        }
        .btn-view-event-large {
          display: block;
          text-align: center;
          padding: 12px 20px;
          background: linear-gradient(135deg, #F4A261 0%, #FEBF10 100%);
          color: #4b0205;
          text-decoration: none;
          border-radius: 25px;
          font-weight: 600;
          font-size: 14px;
          text-transform: uppercase;
          transition: all 0.3s ease;
        }
        .btn-view-event-large:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(244, 162, 97, 0.4);
        }
        .events-footer {
          text-align: center;
          padding-top: 20px;
          border-top: 2px solid rgba(244, 162, 97, 0.2);
        }
        .browse-all-events {
          color: #F4A261;
          text-decoration: none;
          font-weight: 600;
          font-size: 16px;
          transition: color 0.3s ease;
        }
        .browse-all-events:hover {
          color: #4b0205;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
          .dashboard-grid {
            grid-template-columns: 1fr;
          }
          .event-item {
            flex-direction: column;
            text-align: center;
          }
          .event-actions {
            width: 100%;
            justify-content: center;
          }
        }
      </style>
    </div><!-- .container -->
  </div><!-- .section -->
</main><!-- #main -->

<?php get_footer(); ?>