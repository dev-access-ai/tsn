<?php
/**
 * Event Detail Template
 * 
 * Frontend display of single event with ticket booking
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get event from URL - support both slug and ID
$event = null;
$event_slug = get_query_var('event_slug');

if ($event_slug) {
    // Get event by slug (SEO-friendly URL)
    global $wpdb;
    $event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tsn_events WHERE slug = %s AND status = 'published'",
        $event_slug
    ));
} elseif (isset($_GET['event_id'])) {
    // Fallback to event_id for backward compatibility
    $event_id = intval($_GET['event_id']);
    $event = TSN_Events::get_event_by_id($event_id);
}

if (!$event || $event->status !== 'published') {
    echo '<p>This event is not available.</p>';
    return;
}

$event_id = $event->id;
$ticket_types = TSN_Events::get_event_ticket_types($event_id);
$event_date = strtotime($event->start_datetime);
$is_registration_open = (strtotime($event->reg_open_datetime) <= time()) &&
                        (strtotime($event->reg_close_datetime) >= time());

// Check if user is logged in as TSN member
$is_member_logged_in = TSN_Membership_OTP::is_member_logged_in();
$logged_in_member = $is_member_logged_in ? TSN_Membership_OTP::get_logged_in_member() : null;

// Check if member is logged in and has tickets for this event
$member_tickets = null;
if ($is_member_logged_in && $logged_in_member) {
    global $wpdb;
    
    // Debug Output
    if (isset($_GET['debug_tsn']) && $_GET['debug_tsn'] == '1') {
         echo '<div style="background:#fff; border:2px solid red; padding:20px; margin:20px 0;">';
         echo '<h3>TSN Event Debug Info</h3>';
         echo '<p><strong>Member Email:</strong> ' . $logged_in_member->email . '</p>';
         echo '<p><strong>Event ID:</strong> ' . $event_id . '</p>';
         
         // Direct check
         $check_tickets = $wpdb->get_results($wpdb->prepare(
             "SELECT * FROM {$wpdb->prefix}tsn_orders 
              WHERE buyer_email = %s AND event_id = %d",
             $logged_in_member->email,
             $event_id
         ));
         echo '<p><strong>Orders Found (Exact):</strong> ' . count($check_tickets) . '</p>';
         if (!empty($check_tickets)) {
             foreach($check_tickets as $t) echo "Order #{$t->order_number}: {$t->status}<br>";
         }
         echo '</div>';
    }

    $member_tickets = $wpdb->get_results($wpdb->prepare(
        "SELECT o.*, GROUP_CONCAT(CONCAT(tt.name, ' (', oi.qty, ')') SEPARATOR ', ') as ticket_summary
         FROM {$wpdb->prefix}tsn_orders o
         LEFT JOIN {$wpdb->prefix}tsn_order_items oi ON o.id = oi.order_id
         LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON oi.ticket_type_id = tt.id
         WHERE LOWER(o.buyer_email) = LOWER(%s) 
         AND o.event_id = %d
         AND o.status IN ('completed', 'paid')
         GROUP BY o.id
         ORDER BY o.created_at DESC",
        $logged_in_member->email,
        $event_id
    ));
}
?>

<div class="tsn-event-detail-container event-detail-section">
    <div class="container">
        <div class="event-header">
            <div class="section-title">
                <h3><?php echo esc_html($event->title); ?></h3>
            </div>
            
            <?php if ($event->status === 'sold_out'): ?>
                <div class="event-status-banner sold-out">
                    ⚠️ This event is sold out
                </div>
            <?php elseif (!$is_registration_open): ?>
                <div class="event-status-banner closed">
                    ℹ️ Registration is closed for this event
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($member_tickets)): ?>
        <div class="member-tickets-section">
            <h3>🎫 Your Tickets for This Event</h3>
            <?php foreach ($member_tickets as $order): ?>
            <div class="ticket-order-card">
                <div class="order-header">
                    <span class="order-number">Order #<?php echo esc_html($order->order_number); ?></span>
                    <span class="order-date"><?php echo date('M j, Y', strtotime($order->created_at)); ?></span>
                </div>
                <div class="order-details">
                    <p class="tickets-purchased"><strong>Tickets:</strong> <?php echo esc_html($order->ticket_summary); ?></p>
                    <p class="order-total"><strong>Total Paid:</strong> $<?php echo number_format($order->total, 2); ?></p>
                </div>
                <div class="order-actions">
                    <span class="status-badge status-<?php echo esc_attr($order->status); ?>"><?php echo ucfirst($order->status); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="event-content-grid">
            <div class="event-main-content">
                <div class="event-meta-bar event-meta">
                    <h5>Date: <?php echo date('l, F j, Y', $event_date); ?>, <?php echo date('g:i A', $event_date); ?></h5>
                    <?php if ($event->venue_name): ?>
                    <h5>Location: <?php echo esc_html($event->venue_name); ?></h5>
                    <?php endif; ?>
                </div>

                <?php if ($event->description): ?>
                <div class="event-information">
                    <h2>About This Event</h2>
                    <?php echo wpautop(wp_kses_post($event->description)); ?>
                </div>
                <?php endif; ?>

                <?php if ($event->venue_name || $event->address_line1): ?>
                <div class="event-venue">
                    <h2>Venue Information</h2>
                    <p><strong><?php echo esc_html($event->venue_name); ?></strong></p>
                    <?php if ($event->address_line1): ?>
                        <p><?php echo nl2br(esc_html($event->address_line1)); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="event-sidebar side-col">
                <?php 
                // Check if registration is open
                $now = current_time('timestamp'); // Local WP time
                
                // Convert DB strings (WP Local) to Timestamps
                $reg_open = !empty($event->reg_open_datetime) ? strtotime($event->reg_open_datetime) : 0;
                $reg_close = !empty($event->reg_close_datetime) ? strtotime($event->reg_close_datetime) : PHP_INT_MAX;
                
                // Simple robust check
                $is_open_time = ($now >= $reg_open && $now <= $reg_close);
                $registration_open = ($is_open_time && $event->status !== 'sold_out');
                
                // DEBUG OUTPUT
                // if (current_user_can('manage_options')) {
                //     echo '<div style="background:#f8f9fa; border:1px solid #ddd; padding:10px; margin-bottom:15px; font-size:12px;">';
                //     echo '<strong>Debug Info (Admins Only):</strong><br>';
                //     echo 'Now: ' . date('Y-m-d H:i:s', $now) . '<br>';
                //     echo 'Open: ' . $event->reg_open_datetime . ' (' . ($now >= $reg_open ? 'Past' : 'Future') . ')<br>';
                //     echo 'Close: ' . $event->reg_close_datetime . ' (' . ($now <= $reg_close ? 'Future' : 'Past') . ')<br>';
                //     echo 'Status: ' . $event->status . '<br>';
                //     echo 'Tickets Found: ' . (is_array($ticket_types) ? count($ticket_types) : '0') . '<br>';
                //     echo 'Registration Mode: ' . (isset($event->registration_mode) ? $event->registration_mode : 'N/A') . '<br>';
                //     echo 'Registration Open Condition: ' . ($registration_open ? 'TRUE' : 'FALSE') . '<br>';
                //     echo '</div>';
                // }
                
                $is_simple_rsvp = isset($event->registration_mode) && $event->registration_mode === 'simple_rsvp';
                $has_tickets = !empty($ticket_types);
                $should_show_registration = $registration_open && (($event->enable_ticketing && $has_tickets) || $is_simple_rsvp);

                if ($should_show_registration): ?>
                <div class="ticket-booking-section col-container" id="register">

                    <?php if (!isset($event->registration_mode) || $event->registration_mode === 'ticket'): ?>
                    <h3>Get Your Tickets</h3>
                    
                    <div class="ticket-types-list">
                        <?php foreach ($ticket_types as $ticket): ?>
                            <?php 
                            $available = $ticket->capacity - $ticket->sold;
                            $is_available = $available > 0 && $ticket->is_active;
                            ?>
                            <div class="ticket-type-card <?php echo !$is_available ? 'sold-out' : ''; ?>">
                                <div class="ticket-info">
                                    <h4><?php echo esc_html($ticket->name); ?></h4>
                                    <?php if (!empty($ticket->description)): ?>
                                        <p class="ticket-description"><?php echo esc_html($ticket->description); ?></p>
                                    <?php endif; ?>
                                    <div class="ticket-pricing">
                                        <div class="price-row">
                                            <span class="price-label">Members:</span>
                                            <span class="price">$<?php echo number_format($ticket->member_price, 2); ?></span>
                                        </div>
                                        <div class="price-row">
                                            <span class="price-label">Non-Members:</span>
                                            <span class="price">$<?php echo number_format($ticket->non_member_price, 2); ?></span>
                                        </div>
                                    </div>
                                    <?php if ($is_available): ?>
                                        <p class="availability"><?php echo $available; ?> tickets available</p>
                                    <?php else: ?>
                                        <p class="sold-out-label">Sold Out</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="ticket-booking-form" class="ticket-booking-form">
                        <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
                        
                        <?php if ($is_member_logged_in): ?>
                        <!-- Logged-in members: show only member pricing -->
                        <input type="hidden" name="is_member" value="yes">
                        <div class="member-pricing-notice">
                            <p><strong>✓ Member Pricing Applied</strong></p>
                            <p>You're logged in as a member. Enjoying discounted member rates!</p>
                        </div>
                        <?php else: ?>
                        <!-- Non-logged-in users: show both options with validation -->
                        <div class="member-toggle">
                            <label>
                                <input type="radio" name="is_member" value="yes">
                                <span>Member Pricing</span>
                            </label>
                            <label>
                                <input type="radio" name="is_member" value="no" checked>
                                <span>Non-Member Pricing</span>
                            </label>
                        </div>
                        <div class="member-validation-message" style="display: none;">
                            <p><strong>⚠️ Member Registration Required</strong></p>
                            <p>To purchase tickets at member prices, please <a href="<?php echo home_url('/member-login/'); ?>">login</a> if you're already a member, or <a href="<?php echo home_url('/membership-registration/'); ?>">register for membership</a> to enjoy discounted rates!</p>
                        </div>
                        <?php endif; ?>

                        <div class="ticket-quantities">
                            <?php foreach ($ticket_types as $ticket): ?>
                                <?php 
                                $available = $ticket->capacity - $ticket->sold;
                                $is_available = $available > 0 && $ticket->is_active;
                                ?>
                                <div class="ticket-qty-row" 
                                    data-ticket-id="<?php echo $ticket->id; ?>" 
                                    data-ticket-name="<?php echo esc_attr($ticket->name); ?>" 
                                    data-attendees-per-ticket="<?php echo isset($ticket->attendees_per_ticket) ? intval($ticket->attendees_per_ticket) : 1; ?>"
                                    data-member-price="<?php echo $ticket->member_price; ?>" 
                                    data-nonmember-price="<?php echo $ticket->non_member_price; ?>">
                                    <div class="ticket-name"><?php echo esc_html($ticket->name); ?></div>
                                    <div class="ticket-controls">
                                        <span class="current-price">$<span class="price-value"><?php echo number_format($is_member_logged_in ? $ticket->member_price : $ticket->non_member_price, 2); ?></span></span>
                                        <?php if ($is_available): ?>
                                            <div class="quantity-selector">
                                                <button type="button" class="qty-btn minus" data-ticket-id="<?php echo $ticket->id; ?>">-</button>
                                                <input type="number" 
                                                    class="ticket-quantity" 
                                                    name="tickets[<?php echo $ticket->id; ?>]" 
                                                    data-ticket-id="<?php echo $ticket->id; ?>"
                                                    value="0" 
                                                    min="0" 
                                                    max="<?php echo $available; ?>" 
                                                    readonly>
                                                <button type="button" class="qty-btn plus" data-ticket-id="<?php echo $ticket->id; ?>">+</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="sold-out-text">Sold Out</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Total Tickets:</span>
                                <span id="total-tickets">0</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total Amount:</span>
                                <span id="total-amount">$0.00</span>
                            </div>
                        </div>

                        <div class="buyer-info" style="display: none;">
                            <h4>Your Information</h4>
                            <input type="text" name="buyer_name" placeholder="Full Name *" required 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->first_name . ' ' . $logged_in_member->last_name) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                            
                            <input type="email" name="buyer_email" placeholder="Email Address *" required 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->email) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                            
                            <input type="tel" name="buyer_phone" placeholder="Phone Number" 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->phone) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                        </div>
                        
                        <div class="attendee-details" style="display: none;">
                            <h4>Attendee Details</h4>
                            <p class="info-note">Please provide details for each ticket holder</p>
                            <div id="attendee-forms-container"></div>
                        </div>

                        <button type="submit" class="btn-checkout btn btn-primary" disabled><span>Select Tickets to Continue</span></button>
                        <div class="checkout-message"></div>
                    </form>

                    <?php else: ?>
                    <!-- SIMPLE RSVP MODE -->
                    <h3>Event Registration (RSVP)</h3>
                    
                    <form id="simple-rsvp-form" class="ticket-booking-form">
                        <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
                        <?php wp_nonce_field('tsn_rsvp_nonce', 'tsn_rsvp_nonce'); ?>
                        
                        <div class="buyer-info" style="display: block;">
                            <h4>Your Information</h4>
                            <input type="text" name="buyer_name" placeholder="Full Name *" required 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->first_name . ' ' . $logged_in_member->last_name) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                            
                            <input type="email" name="buyer_email" placeholder="Email Address *" required 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->email) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                            
                            <input type="tel" name="buyer_phone" placeholder="Phone Number" 
                                value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->phone) : ''; ?>" 
                                <?php echo $is_member_logged_in ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                        </div>
                        
                        <div class="attendee-details" style="display: block; background:#fff; padding:0;">
                            <h4>Attendee Details</h4>
                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                <input type="number" name="attendee_age" placeholder="Age" min="1" max="120" required style="padding:10px; border:1px solid #ddd; border-radius:4px;">
                                <select name="attendee_gender" required style="padding:10px; border:1px solid #ddd; border-radius:4px; width:100%;">
                                    <option value="">Select Gender *</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <input type="text" name="attendee_address" placeholder="Address *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->address) : ''; ?>" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; margin-bottom:10px;">
                        </div>

                        <button type="submit" class="btn-checkout">Register for Event</button>
                        <div class="rsvp-message" style="margin-top: 15px; display: none;"></div>
                    </form>
                    <?php endif; ?>
                </div>
                <?php elseif (!$event->enable_ticketing): ?>
                    <!-- Ticketing disabled for this event -->
                <?php else: ?>
                <div class="registration-closed">
                    <p><strong>Registration Information:</strong></p>
                    <p>Opens: <?php echo date('M j, Y g:i A', strtotime($event->reg_open_datetime)); ?></p>
                    <p>Closes: <?php echo date('M j, Y g:i A', strtotime($event->reg_close_datetime)); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($event->enable_volunteering): ?>
                <div class="volunteer-section" id="volunteer">
                    <div class="ticket-booking-section col-container">
                        <h3>🤝 Register as Volunteer</h3>
                        <p>We need enthusiastic volunteers to help make this event a success!</p>
                        
                        <form id="volunteer-form" class="ticket-booking-form form-container">
                            <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
                            <?php wp_nonce_field('tsn_events_nonce', 'tsn_events_nonce'); ?>
                            
                            <div class="buyer-info" style="display: block;">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="name" placeholder="Full Name *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->first_name . ' ' . $logged_in_member->last_name) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <input type="email" class="form-control" name="email" placeholder="Email Address *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->email) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <input type="tel" class="form-control" name="phone" placeholder="Phone Number *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->phone) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <input type="number" class="form-control" name="age" placeholder="Age" min="15" max="99">
                                </div>

                                <div class="form-group">
                                    <select name="gender" class="form-select">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <textarea name="address" placeholder="Address" rows="2" class="form-control"><?php echo $is_member_logged_in ? esc_textarea($logged_in_member->address . ', ' . $logged_in_member->city) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <textarea name="notes" placeholder="Any specific skills or notes? (Optional)" rows="2" class="form-control"></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-checkout btn btn-primary"><span>Submit Registration</span></button>
                            <div class="volunteer-message" style="margin-top: 15px; display: none;"></div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($event->enable_donations): ?>
                <div class="donation-section" id="donate" style="margin-top: 30px;">
                    <div class="ticket-booking-section" style="border-color: #28a745;">
                        <h3 style="color: #28a745;">❤️ Donate to Event</h3>
                        <p>Support this event with a donation. Every contribution helps!</p>
                        
                        <form id="event-donation-form" class="ticket-booking-form">
                            <input type="hidden" name="action" value="tsn_submit_donation">
                            <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">
                            <?php wp_nonce_field('tsn_donation_nonce', 'nonce'); ?>
                            
                            <!-- Create a hidden cause for this event if it doesn't exist? For now, general donation linked to event via notes -->
                            <input type="hidden" name="message" value="Donation for Event: <?php echo esc_attr($event->title); ?>">
                            
                            <div class="donation-amount-group" style="margin-bottom: 15px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Amount ($)</label>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" class="amount-btn" data-value="25" style="padding: 8px 15px; border: 1px solid #ddd; background: #fff; cursor: pointer;">$25</button>
                                    <button type="button" class="amount-btn" data-value="50" style="padding: 8px 15px; border: 1px solid #ddd; background: #fff; cursor: pointer;">$50</button>
                                    <button type="button" class="amount-btn" data-value="100" style="padding: 8px 15px; border: 1px solid #ddd; background: #fff; cursor: pointer;">$100</button>
                                    <input type="number" name="amount" placeholder="Custom" style="width: 80px; padding: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>

                            <div class="buyer-info" style="display: block;">
                                <input type="text" name="donor_name" placeholder="Full Name *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->first_name . ' ' . $logged_in_member->last_name) : ''; ?>">
                                <input type="email" name="donor_email" placeholder="Email Address *" required value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->email) : ''; ?>">
                                <input type="tel" name="donor_phone" placeholder="Phone Number" value="<?php echo $is_member_logged_in ? esc_attr($logged_in_member->phone) : ''; ?>">
                            </div>
                            
                            <button type="submit" class="btn-checkout" style="background: #28a745;">Donate Now</button>
                            <div class="donation-message" style="margin-top: 15px; display: none;"></div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.tsn-event-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.event-header {
    margin-bottom: 40px;
}

.event-header h1 {
    margin-bottom: 20px;
    color: #0066cc;
}

.event-meta-bar {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    padding: 15px 0;
    border-top: 2px solid #eee;
    border-bottom: 2px solid #eee;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.event-status-banner {
    margin-top: 20px;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
}

.event-status-banner.sold-out {
    background: #f8d7da;
    color: #721c24;
}

.event-status-banner.closed {
    background: #fff3cd;
    color: #856404;
}

/* Member Tickets Section */
.member-tickets-section {
    background: linear-gradient(135deg, rgba(244, 162, 97, 0.1) 0%, rgba(254, 191, 16, 0.1) 100%);
    border: 2px solid #F4A261;
    border-radius: 12px;
    padding: 25px;
    margin: 30px 0;
}

.member-tickets-section h3 {
    margin: 0 0 20px 0;
    color: #4b0205;
    font-size: 20px;
}

.ticket-order-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ticket-order-card:last-child {
    margin-bottom: 0;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.order-number {
    font-weight: 700;
    color: #4b0205;
    font-size: 16px;
}

.order-date {
    color: #666;
    font-size: 14px;
}

.order-details p {
    margin: 8px 0;
    font-size: 15px;
}

.tickets-purchased {
    color: #333;
}

.order-total {
    color: #F4A261;
    font-size: 18px;
}

.order-actions {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-paid,
.status-completed {
    background: #d4edda;
    color: #155724;
}

.event-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
}

.event-description,
.event-venue {
    margin-bottom: 30px;
}

.event-description h2,
.event-venue h2 {
    margin-bottom: 15px;
    color: #333;
}

.ticket-booking-section {
    background: white;
    border: 2px solid #0066cc;
    border-radius: 8px;
    padding: 25px;
    position: sticky;
    top: 20px;
}

.ticket-booking-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #0066cc;
}

.ticket-types-list {
    margin-bottom: 20px;
}

.ticket-type-card {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.ticket-type-card.sold-out {
    opacity: 0.6;
    background: #f8f9fa;
}

.ticket-info h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.ticket-description {
    color: #666;
    font-size: 14px;
    margin: 5px 0 10px 0;
    line-height: 1.4;
}

.ticket-pricing {
    margin: 10px 0;
}

.price-row {
    display: flex;
    justify-content: space-between;
    margin: 5px 0;
}

.price {
    font-weight: bold;
    color: #0066cc;
    font-size: 18px;
}

.availability {
    margin: 10px 0 0 0;
    font-size: 14px;
    color: #28a745;
}

.sold-out-label {
    margin: 10px 0 0 0;
    font-size: 14px;
    color: #dc3545;
    font-weight: 600;
}

.booking-notice {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #0066cc;
}

.booking-notice p {
    margin: 5px 0;
}

.ticket-booking-form {
    margin-top: 20px;
}

.member-toggle {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.member-toggle label {
    display: block;
    margin: 8px 0;
    cursor: pointer;
}

.member-toggle input[type="radio"] {
    margin-right: 8px;
}

.non-member-notice {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.non-member-notice p {
    margin: 5px 0;
    color: #856404;
}

.non-member-notice strong {
    color: #856404;
}

.non-member-notice a {
    color: #0066cc;
    font-weight: 600;
    text-decoration: underline;
}

.non-member-notice a:hover {
    color: #004499;
}

.member-pricing-notice {
    background: #d4edda;
    border: 2px solid #28a745;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.member-pricing-notice p {
    margin: 5px 0;
    color: #155724;
}

.member-pricing-notice strong {
    color: #155724;
}

.member-validation-message {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.member-validation-message p {
    margin: 5px 0;
    color: #856404;
}

.member-validation-message strong {
    color: #856404;
}

.member-validation-message a {
    color: #0066cc;
    font-weight: 600;
    text-decoration: underline;
}

.member-validation-message a:hover {
    color: #004499;
}

.ticket-quantities {
    margin: 20px 0;
}

.ticket-qty-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
}

.ticket-name {
    font-weight: 600;
}

.ticket-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.current-price {
    color: #0066cc;
    font-weight: bold;
    font-size: 18px;
}

.ticket-quantity {
    width: 60px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.sold-out-text {
    color: #dc3545;
    font-weight: 600;
}

.cart-summary {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin: 8px 0;
}

.summary-row.total {
    font-size: 18px;
    font-weight: bold;
    color: #0066cc;
    border-top: 2px solid #ddd;
    padding-top: 10px;
    margin-top: 10px;
}

.buyer-info {
    margin: 20px 0;
}

.buyer-info h4 {
    margin-bottom: 15px;
}

.buyer-info input {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.attendee-details {
    margin: 20px 0;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 2px solid #0066cc;
}

.attendee-details h4 {
    margin: 0 0 10px 0;
    color: #0066cc;
}

.info-note {
    margin: 0 0 20px 0;
    color: #666;
    font-size: 14px;
}

.attendee-form-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
}

.attendee-form-card:last-child {
    margin-bottom: 0;
}

.attendee-form-card .ticket-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
    display: block;
    font-size: 15px;
}

.attendee-form-card .form-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
    margin-bottom: 0;
}

.attendee-form-card input,
.attendee-form-card select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .attendee-form-card .form-row {
        grid-template-columns: 1fr;
    }
}

.btn-checkout:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.checkout-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.checkout-message.success {
    background: #d4edda;
    color: #155724;
    display: block;
}

.checkout-message.error {
    background: #f8d7da;
    color: #721c24;
    display: block;
}

.registration-closed {
    background: #fff3cd;
    padding: 20px;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .event-content-grid {
        grid-template-columns: 1fr;
    }
    
    .event-meta-bar {
        flex-direction: column;
        gap: 10px;
    }
    
    .ticket-booking-section {
        position: static;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const form = $('#ticket-booking-form');
    const memberToggles = $('input[name="is_member"]');
    const ticketInputs = $('.ticket-quantity');
    const buyerInfo = $('.buyer-info');
    const checkoutBtn = $('.btn-checkout');
    const checkoutMsg = $('.checkout-message');
    const validationMsg = $('.member-validation-message');
    
    // Show validation message when non-logged-in user selects member pricing
    memberToggles.on('change', function() {
        const isMember = $('input[name="is_member"]:checked').val() === 'yes';
        const hasValidation = validationMsg.length > 0; // Check if validation message exists (means user not logged in)
        
        if (hasValidation && isMember) {
            validationMsg.slideDown();
        } else if (hasValidation) {
            validationMsg.slideUp();
        }
        
        $('.ticket-qty-row').each(function() {
            const row = $(this);
            const memberPrice = parseFloat(row.data('member-price'));
            const nonMemberPrice = parseFloat(row.data('nonmember-price'));
            const price = isMember ? memberPrice : nonMemberPrice;
            
            row.find('.price-value').text(price.toFixed(2));
        });
        
        updateCart();
    });
    
    // Handle quantity buttons
    $('.qty-btn').on('click', function() {
        const btn = $(this);
        const input = btn.siblings('.ticket-quantity');
        const currentVal = parseInt(input.val()) || 0;
        const max = parseInt(input.attr('max')) || 10;
        const min = parseInt(input.attr('min')) || 0;
        
        if (btn.hasClass('plus')) {
            if (currentVal < max) {
                input.val(currentVal + 1).trigger('input');
            }
        } else {
            if (currentVal > min) {
                input.val(currentVal - 1).trigger('input');
            }
        }
    });

    // Update cart when quantities change
    ticketInputs.on('input', updateCart);
    
    function updateCart() {
        // Check member status - handle both logged-in (hidden field) and not logged-in (radio buttons)
        const hiddenMemberField = $('input[type="hidden"][name="is_member"]');
        let isMember = false;
        
        if (hiddenMemberField.length > 0) {
            // Logged-in member - use hidden field value
            isMember = hiddenMemberField.val() === 'yes';
        } else {
            // Non-logged-in user - check radio button
            const memberToggle = $('input[name="is_member"]:checked');
            isMember = memberToggle.length > 0 && memberToggle.val() === 'yes';
        }
        
        let totalTickets = 0;
        let totalAmount = 0;
        
        $('.ticket-qty-row').each(function() {
            const row = $(this);
            const qty = parseInt(row.find('.ticket-quantity').val()) || 0;
            const memberPrice = parseFloat(row.data('member-price'));
            const nonMemberPrice = parseFloat(row.data('nonmember-price'));
            const price = isMember ? memberPrice : nonMemberPrice;
            
            totalTickets += qty;
            totalAmount += qty * price;
        });
        
        $('#total-tickets').text(totalTickets);
        $('#total-amount').text('$' + totalAmount.toFixed(2));
        
        if (totalTickets > 0) {
            buyerInfo.slideDown();
            generateAttendeeForm(totalTickets);
            $('.attendee-details').slideDown();
            checkoutBtn.prop('disabled', false).text('Proceed to Checkout ($' + totalAmount.toFixed(2) + ')');
        } else {
            buyerInfo.slideUp();
            $('.attendee-details').slideUp();
            checkoutBtn.prop('disabled', true).text('Select Tickets to Continue');
        }
    }
    
    function generateAttendeeForm(ticketCount) {
        const container = $('#attendee-forms-container');
        container.empty();
        
        let attendeeIndex = 0;
        
        // Loop through each ticket type that has quantity > 0
        $('.ticket-qty-row').each(function() {
            const row = $(this);
            const qty = parseInt(row.find('.ticket-quantity').val()) || 0;
            const ticketName = row.data('ticket-name');
            const attendeesPerTicket = parseInt(row.data('attendees-per-ticket')) || 1;
            
            // Generate forms for each ticket of this type
            for (let ticketNum = 0; ticketNum < qty; ticketNum++) {
                // Generate attendee forms based on how many people this ticket covers
                for (let attendeeNum = 0; attendeeNum < attendeesPerTicket; attendeeNum++) {
                    const formCard = $('<div class="attendee-form-card"></div>');
                    
                    // Label shows ticket type and attendee number within that ticket
                    let label = ticketName;
                    if (qty > 1) {
                        label += ' - Ticket ' + (ticketNum + 1);
                    }
                    if (attendeesPerTicket > 1) {
                        label += ' - Attendee ' + (attendeeNum + 1);
                    }
                    
                    formCard.html(`
                        <span class="ticket-label">${label}</span>
                        <div class="form-row">
                            <input type="text" name="attendees[${attendeeIndex}][name]" placeholder="Full Name *" required>
                            <input type="number" name="attendees[${attendeeIndex}][age]" placeholder="Age" min="1" max="120">
                            <select name="attendees[${attendeeIndex}][gender]">
                                <option value="">Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    `);
                    
                    container.append(formCard);
                    attendeeIndex++;
                }
            }
        });
    }
    
    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        checkoutMsg.removeClass('success error').hide();
        checkoutBtn.prop('disabled', true).text('Processing...');
        
        const formData = form.serialize();
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            method: 'POST',
            data: formData + '&action=tsn_checkout&nonce=<?php echo wp_create_nonce("tsn_checkout_nonce"); ?>',
            success: function(response) {
                if (response.success) {
                    checkoutMsg.addClass('success').text(response.data.message).show();
                    
                    if (response.data.redirect_url) {
                        // Redirect to confirmation page (dev mode)
                        window.location.href = response.data.redirect_url;
                    } else if (response.data.payment_url) {
                        // Redirect to PayPal (production)
                        window.location.href = response.data.payment_url;
                    }
                } else {
                    checkoutMsg.addClass('error').text(response.data.message || 'Checkout failed').show();
                    checkoutBtn.prop('disabled', false).text('Try Again');
                }
            },
            error: function() {
                checkoutMsg.addClass('error').text('Connection error. Please try again.').show();
                checkoutBtn.prop('disabled', false).text('Try Again');
            }
        });
    });

    // Volunteer Form Submission
    const volunteerForm = $('#volunteer-form');
    if (volunteerForm.length) {
        volunteerForm.on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const msg = $(this).find('.volunteer-message');
            
            btn.prop('disabled', true).text('Submitting...');
            msg.hide().removeClass('success error');
            
            const formData = new FormData(this);
            formData.append('action', 'tsn_submit_volunteer');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        msg.addClass('success').html(response.data.message).slideDown();
                        volunteerForm[0].reset();
                        btn.prop('disabled', false).text('Submit Registration');
                    } else {
                        msg.addClass('error').html(response.data.message || 'Submission failed').slideDown();
                        btn.prop('disabled', false).text('Try Again');
                    }
                },
                error: function() {
                    msg.addClass('error').html('Connection error. Please try again.').slideDown();
                    btn.prop('disabled', false).text('Try Again');
                }
            });
        });
    }

    // Simple RSVP Form Submission
    const rsvpForm = $('#simple-rsvp-form');
    if (rsvpForm.length) {
        rsvpForm.on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const msg = $(this).find('.rsvp-message');
            
            btn.prop('disabled', true).text('Registering...');
            msg.hide().removeClass('success error');
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                method: 'POST',
                data: formData + '&action=tsn_submit_simple_rsvp',
                success: function(response) {
                    if (response.success) {
                        msg.addClass('success').html(response.data.message).slideDown();
                        
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        msg.addClass('error').html(response.data.message || 'Registration failed').slideDown();
                        btn.prop('disabled', false).text('Try Again');
                    }
                },
                error: function() {
                    msg.addClass('error').html('Connection error. Please try again.').slideDown();
                    btn.prop('disabled', false).text('Try Again');
                }
            });
        });
    }

    // Donation Form Submission
    const donationForm = $('#event-donation-form');
    if (donationForm.length) {
        // Amount buttons
        $('.amount-btn').on('click', function() {
            const val = $(this).data('value');
            $('input[name="amount"]').val(val);
            $('.amount-btn').css('background', '#fff').css('color', '#333');
            $(this).css('background', '#28a745').css('color', '#fff');
        });
        
        $('input[name="amount"]').on('focus', function() {
            $('.amount-btn').css('background', '#fff').css('color', '#333');
        });

        donationForm.on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const msg = $(this).find('.donation-message');
            
            btn.prop('disabled', true).text('Processing...');
            msg.hide().removeClass('success error');
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        msg.addClass('success').html(response.data.message).slideDown();
                        
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else if (response.data.payment_url) {
                            window.location.href = response.data.payment_url;
                        }
                    } else {
                        msg.addClass('error').html(response.data.message || 'Donation failed').slideDown();
                        btn.prop('disabled', false).text('Try Again');
                    }
                },
                error: function() {
                    msg.addClass('error').html('Connection error. Please try again.').slideDown();
                    btn.prop('disabled', false).text('Try Again');
                }
            });
        });
    }
});
</script>
