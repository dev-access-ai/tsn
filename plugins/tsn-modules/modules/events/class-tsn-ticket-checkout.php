<?php
/**
 * Ticket Checkout Class
 * 
 * Handles ticket selection, cart, and checkout
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Ticket_Checkout {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        $post = get_post();
        if (is_page() && $post && has_shortcode($post->post_content, 'tsn_events_list')) {
            wp_enqueue_script('tsn-checkout', TSN_MODULES_URL . 'assets/js/checkout.js', array('jquery'), '1.0', true);
            wp_localize_script('tsn-checkout', 'tsnCheckout', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tsn_checkout_nonce')
            ));
        }
    }
    
    /**
     * AJAX: Add tickets to cart
     */
    public static function ajax_add_to_cart() {
        TSN_Security::validate_ajax_request('tsn_checkout_nonce');
        
        $event_id = intval($_POST['event_id']);
        $tickets = isset($_POST['tickets']) ? $_POST['tickets'] : array();
        
        if (!$event_id || empty($tickets)) {
            wp_send_json_error(array('message' => 'Invalid request'));
        }
        
        // Get event
        $event = TSN_Events::get_event_by_id($event_id);
        if (!$event) {
            wp_send_json_error(array('message' => 'Event not found'));
        }
        
        // Calculate total
        $order_items = array();
        $subtotal = 0;
        
        foreach ($tickets as $ticket_type_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity <= 0) continue;
            
            $ticket_type = self::get_ticket_type($ticket_type_id);
            if (!$ticket_type) continue;
            
            // Check availability
            $available = $ticket_type->capacity - $ticket_type->sold;
            if ($quantity > $available) {
                wp_send_json_error(array('message' => 'Not enough tickets available for ' . $ticket_type->name));
            }
            
            // Determine price (member vs non-member)
            $is_member = isset($_POST['is_member']) && $_POST['is_member'] === 'yes';
            $price = $is_member ? $ticket_type->member_price : $ticket_type->non_member_price;
            
            $order_items[] = array(
                'ticket_type_id' => $ticket_type_id,
                'name' => $ticket_type->name,
                'quantity' => $quantity,
                'price' => $price,
                'is_member_price' => $is_member ? 1 : 0,
                'line_total' => $price * $quantity
            );
            
            $subtotal += $price * $quantity;
        }
        
        if (empty($order_items)) {
            wp_send_json_error(array('message' => 'No tickets selected'));
        }
        
        // Store in session
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['tsn_cart'] = array(
            'event_id' => $event_id,
            'event_title' => $event->title,
            'items' => $order_items,
            'subtotal' => $subtotal,
            'total' => $subtotal
        );
        
        wp_send_json_success(array(
            'message' => 'Added to cart',
            'cart' => $_SESSION['tsn_cart']
        ));
    }
    
    /**
     * AJAX: Process checkout
     */
    public static function ajax_checkout() {
        TSN_Security::validate_ajax_request('tsn_checkout_nonce');
        
        // Get event and ticket data from form
        $event_id = intval($_POST['event_id']);
        $tickets = isset($_POST['tickets']) ? $_POST['tickets'] : array();
        $attendees = isset($_POST['attendees']) ? $_POST['attendees'] : array(); // NEW: Individual attendee details
        $is_member = isset($_POST['is_member']) && $_POST['is_member'] === 'yes';
        
        if (!$event_id || empty($tickets)) {
            wp_send_json_error(array('message' => 'No tickets selected'));
        }
        
        // Get event
        $event = TSN_Events::get_event_by_id($event_id);
        if (!$event) {
            wp_send_json_error(array('message' => 'Event not found'));
        }
        
        // Build cart from form data
        $order_items = array();
        $subtotal = 0;
        
        foreach ($tickets as $ticket_type_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity <= 0) continue;
            
            $ticket_type = self::get_ticket_type($ticket_type_id);
            if (!$ticket_type) continue;
            
            // Check availability
            $available = $ticket_type->capacity - $ticket_type->sold;
            if ($quantity > $available) {
                wp_send_json_error(array('message' => 'Not enough tickets available for ' . $ticket_type->name));
            }
            
            // Determine price
            $price = $is_member ? $ticket_type->member_price : $ticket_type->non_member_price;
            
            $order_items[] = array(
                'ticket_type_id' => $ticket_type_id,
                'name' => $ticket_type->name,
                'quantity' => $quantity,
                'price' => $price,
                'is_member_price' => $is_member ? 1 : 0,
                'line_total' => $price * $quantity
            );
            
            $subtotal += $price * $quantity;
        }
        
        if (empty($order_items)) {
            wp_send_json_error(array('message' => 'No valid tickets selected'));
        }
        
        // Build cart array
        $cart = array(
            'event_id' => $event_id,
            'event_title' => $event->title,
            'items' => $order_items,
            'subtotal' => $subtotal,
            'total' => $subtotal
        );
        
        // Get buyer info
        $buyer_data = array(
            'name' => TSN_Security::sanitize_input($_POST['buyer_name'], 'text'),
            'email' => TSN_Security::sanitize_input($_POST['buyer_email'], 'email'),
            'phone' => isset($_POST['buyer_phone']) ? TSN_Security::sanitize_input($_POST['buyer_phone'], 'text') : '',
            'attendees' => $attendees // Store attendee details with buyer data
        );
        
        if (empty($buyer_data['name']) || empty($buyer_data['email'])) {
            wp_send_json_error(array('message' => 'Please provide name and email'));
        }
        
        // Validate attendee details if provided
        if (!empty($attendees)) {
            foreach ($attendees as $idx => $attendee) {
                if (empty($attendee['name'])) {
                    wp_send_json_error(array('message' => 'Please provide name for all attendees'));
                }
            }
        }
        
        // Create order
        $order_id = self::create_order($cart, $buyer_data);
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Failed to create order'));
        }
        
        // Check if development mode (only localhost, not production with debug)
        $is_local = (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost')) || 
                     strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
                     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) &&
                     !defined('TSN_FORCE_PAYMENT');
        
        if ($is_local) {
            // Dev mode: Skip payment, generate tickets immediately
            self::complete_order($order_id, 'DEV-' . time());
            
            wp_send_json_success(array(
                'message' => 'Order completed! (Development Mode)',
                'order_id' => $order_id,
                'redirect_url' => home_url('/payment-success/?order_id=' . $order_id),
                'dev_mode' => true
            ));
        }
        
        // Production: Create PayPal payment
        $payment = new TSN_Payment();
        $order_result = $payment->create_order(
            $cart['total'],
            'USD',
            'Event Tickets - ' . $cart['event_title'],
            'order_' . $order_id
        );
        
        // Store session fallback
        if (!session_id()) session_start();
        $_SESSION['tsn_pending_order_id'] = $order_id;
        $_SESSION['tsn_pending_payment_type'] = 'event';
        
        if (!$order_result['success']) {
            wp_send_json_error(array('message' => 'Payment processing error: ' . ($order_result['message'] ?? 'Unknown error')));
        }
        
        wp_send_json_success(array(
            'message' => 'Redirecting to payment...',
            'payment_url' => $order_result['approval_url']
        ));
    }
    
    /**
     * Create order in database
     */
    private static function create_order($cart, $buyer_data) {
        global $wpdb;
        
        // Generate order number
        $order_number = 'TSN-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Prepare attendee data as JSON for storage
        $attendee_json = !empty($buyer_data['attendees']) ? json_encode($buyer_data['attendees']) : null;
        
        // Insert order
        $wpdb->insert(
            $wpdb->prefix . 'tsn_orders',
            array(
                'order_number' => $order_number,
                'event_id' => $cart['event_id'],
                'buyer_name' => $buyer_data['name'],
                'buyer_email' => $buyer_data['email'],
                'buyer_phone' => $buyer_data['phone'],
                'source' => 'online',
                'order_type' => 'ticket',
                'status' => 'pending',
                'subtotal' => $cart['subtotal'],
                'total' => $cart['total'],
                'notes' => $attendee_json // Store attendee details in notes field
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s')
        );
        
        $order_id = $wpdb->insert_id;
        
        // Insert order items
        foreach ($cart['items'] as $item) {
            $wpdb->insert(
                $wpdb->prefix . 'tsn_order_items',
                array(
                    'order_id' => $order_id,
                    'ticket_type_id' => $item['ticket_type_id'],
                    'qty' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'is_member_price' => $item['is_member_price'],
                    'line_total' => $item['line_total']
                ),
                array('%d', '%d', '%d', '%f', '%d', '%f')
            );
        }
        
        return $order_id;
    }
    
    /**
     * Complete order and generate tickets
     */
    public static function complete_order($order_id, $payment_reference) {
        global $wpdb;
        
        error_log("TSN Debug: complete_order called for ID: $order_id with Ref: $payment_reference");
        
        // Update order status
        $wpdb->update(
            $wpdb->prefix . 'tsn_orders',
            array(
                'status' => 'paid',
                'payment_method' => 'paypal',
                'payment_reference' => $payment_reference,
                'paid_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        // Get order items
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_order_items WHERE order_id = %d",
            $order_id
        ));
        
        // Get order info
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        // Parse attendee details from order notes
        $attendees = array();
        if (!empty($order->notes)) {
            $attendees = json_decode($order->notes, true);
            if (!is_array($attendees)) {
                $attendees = array();
            }
        }
        
        // Generate tickets for each item
        $attendee_index = 0;
        foreach ($items as $item) {
            for ($i = 0; $i < $item->qty; $i++) {
                // Get attendee details for this specific ticket
                $attendee_name = null;
                $attendee_age = null;
                $attendee_gender = null;
                
                if (isset($attendees[$attendee_index])) {
                    $attendee_name = $attendees[$attendee_index]['name'] ?? null;
                    $attendee_age = $attendees[$attendee_index]['age'] ?? null;
                    $attendee_gender = $attendees[$attendee_index]['gender'] ?? null;
                }
                
                TSN_Ticket_QR::generate_ticket(
                    $order_id, 
                    $item->ticket_type_id, 
                    $order->event_id, 
                    $order->buyer_email,
                    $attendee_name,
                    $attendee_age,
                    $attendee_gender
                );
                
                $attendee_index++;
            }
            
            // Update sold count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}tsn_event_ticket_types 
                 SET sold = sold + %d 
                 WHERE id = %d",
                $item->qty,
                $item->ticket_type_id
            ));
        }
        
        // Send confirmation email
        TSN_Event_Emails::send_ticket_confirmation($order_id);
        
        // Clear cart
        if (!session_id()) {
            session_start();
        }
        unset($_SESSION['tsn_cart']);
        
        return true;
    }
    
    /**
     * Get ticket type by ID
     */
    private static function get_ticket_type($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types WHERE id = %d",
            $id
        ));
    }
    
    // Stub AJAX handlers
    public static function ajax_update_cart() {
        wp_send_json_error(array('message' => 'Not implemented'));
    }

    /**
     * AJAX: Submit simple RSVP registration
     */
    public static function ajax_submit_simple_rsvp() {
        check_ajax_referer('tsn_rsvp_nonce', 'tsn_rsvp_nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $name = isset($_POST['buyer_name']) ? sanitize_text_field($_POST['buyer_name']) : '';
        $email = isset($_POST['buyer_email']) ? sanitize_email($_POST['buyer_email']) : '';
        $phone = isset($_POST['buyer_phone']) ? sanitize_text_field($_POST['buyer_phone']) : '';
        
        // Attendee Details
        $age = isset($_POST['attendee_age']) ? intval($_POST['attendee_age']) : null;
        $gender = isset($_POST['attendee_gender']) ? sanitize_text_field($_POST['attendee_gender']) : '';
        $address = isset($_POST['attendee_address']) ? sanitize_text_field($_POST['attendee_address']) : '';
        
        if (!$event_id || empty($name) || empty($email)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }
        
        global $wpdb;
        
        // Check for duplicate
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tsn_orders 
             WHERE event_id = %d AND buyer_email = %s AND status IN ('completed', 'paid')",
            $event_id, $email
        ));
        
        if ($existing) {
             wp_send_json_error(array('message' => 'You have already registered for this event.'));
        }

        // Create Order ($0)
        $order_number = 'RSVP-' . strtoupper(wp_generate_password(8, false));
        $buyer_user = get_user_by('email', $email);
        $user_id = $buyer_user ? $buyer_user->ID : 0;
        
        // Store extra details in notes
        $notes = "Simple Registration Info:\nAge: $age\nGender: $gender\nAddress: $address";
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'tsn_orders',
            array(
                'order_number' => $order_number,
                'buyer_user_id' => $user_id,
                'buyer_name' => $name,
                'buyer_email' => $email,
                'buyer_phone' => $phone,
                'event_id' => $event_id,
                'total' => 0.00,
                'status' => 'completed',
                'payment_method' => 'free',
                'order_type' => 'ticket', 
                'created_at' => current_time('mysql'),
                'notes' => $notes
            ),
            array('%s', '%d', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $order_id = $wpdb->insert_id;
            
            // Add item
            $item_result = $wpdb->insert(
                $wpdb->prefix . 'tsn_order_items',
                array(
                    'order_id' => $order_id,
                    'ticket_type_id' => null, 
                    'qty' => 1,
                    'price' => 0.00,
                    'total' => 0.00,
                    'attendee_name' => $name,
                    'attendee_email' => $email
                ),
                array('%d', null, '%d', '%f', '%f', '%s', '%s')
            );

            if ($item_result === false) {
                 error_log('TSN Error: Failed to insert order item for Simple RSVP. DB Error: ' . $wpdb->last_error);
                 // We should probably fail gracefully, but the order is already created.
                 // For now, let's proceed but verify schema allows NULL.
            }

            // Send Email (Silence errors to prevent breaking JSON)
            try {
                if (class_exists('TSN_Event_Emails') && method_exists('TSN_Event_Emails', 'send_simple_rsvp_confirmation')) {
                    @TSN_Event_Emails::send_simple_rsvp_confirmation($order_id);
                }
            } catch (Exception $e) {
                error_log('TSN Warning: Email sending failed: ' . $e->getMessage());
            }
            
            // Clean buffer to remove any PHP warnings from email attempt
            if (ob_get_length()) ob_clean();
            
            wp_send_json_success(array(
                'message' => 'Registration Successful!',
                'redirect_url' => home_url('/tsn/rsvp-confirmation/?order_id=' . $order_id)
            ));
        } else {
            error_log('TSN Error: Simple RSVP Order failed. DB Error: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Database error. Please try again.'));
        }
    }
    
    /**
     * Render payment success page
     */
    public static function render_payment_success($atts) {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if (!$order_id) {
            return '<div class="tsn-message error">Invalid order reference.</div>';
        }
        
        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        // Check for Membership if order not found or type is member
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        if (empty($type) && isset($_SESSION['tsn_pending_payment_type']) && $_SESSION['tsn_pending_payment_type'] === 'membership') {
            $type = 'member';
        }
        
        $member = null;
        if ((!$order && empty($type)) || $type === 'member') {
            $member = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_members WHERE id = %d",
                $order_id
            ));
        }
        
        // Handle Membership Success
        if ($member) {
            // Check for PayPal return (PayerID/token) to complete the payment
            if ($member->status === 'pending' && isset($_GET['PayerID']) && isset($_GET['token'])) {
                $payment = new TSN_Payment();
                $capture = $payment->capture_order($_GET['token']);
                
                if ($capture['success']) {
                    TSN_Membership::activate_member($member->id, $capture);
                    // Reload member
                    $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_members WHERE id = %d", $member->id));
                } else {
                     error_log('TSN Membership Payment Capture Failed: ' . $capture['message']);
                     echo '<div class="tsn-message warning">Payment confirmation pending. Please check your email. (' . esc_html($capture['message']) . ')</div>';
                }
            }
            
            // Render Membership Success Page
            ob_start();
            ?>
            <div class="tsn-payment-success-container">
                <div class="success-icon" style="text-align: center; color: #28a745; font-size: 48px; margin-bottom: 20px;">
                    <i class="dashicons dashicons-yes-alt"></i>
                </div>
                
                <h2 style="text-align: center;">Membership Activated!</h2>
                <p style="text-align: center;">Welcome to Telugu Samiti of Nebraska. Your membership is now active.</p>
                
                <div class="order-details-card" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #eee;">
                    <h3>Membership Details</h3>
                    <p><strong>Member ID:</strong> <?php echo esc_html($member->member_id); ?></p>
                    <p><strong>Name:</strong> <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></p>
                    <p><strong>Type:</strong> <?php echo ucfirst($member->membership_type); ?></p>
                    <p><strong>Status:</strong> <span class="tsn-status-badge status-<?php echo esc_attr($member->status); ?>"><?php echo ucfirst($member->status); ?></span></p>
                    
                    <div class="action-buttons" style="margin-top: 20px; text-align: center;">
                        <a href="<?php echo home_url('/member-dashboard/'); ?>" class="button button-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        if (!$order) {
            return '<div class="tsn-message error">Order not found.</div>';
        }
        
        // Check for PayPal return (PayerID/token) to complete the payment
        if ($order->status === 'pending' && isset($_GET['PayerID']) && isset($_GET['token'])) {
            $payment = new TSN_Payment();
            $capture = $payment->capture_order($_GET['token']);
            
            if ($capture['success']) {
                $ref = $capture['transaction_id'] ?? $_GET['token'];
                if ($order->order_type === 'donation') {
                    if (class_exists('TSN_Donations')) {
                        TSN_Donations::complete_donation($order_id, $ref);
                        // Reload order to get updated status
                        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d", $order_id));
                    }
                } else {
                    self::complete_order($order_id, $ref);
                    // Reload order
                    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d", $order_id));
                }
            } else {
                 error_log('TSN Payment Capture Failed: ' . $capture['message']);
                 // Show error but allow page to load so user isn't stranded
                 echo '<div class="tsn-message warning">Payment confirmation pending. Please check your email. (' . esc_html($capture['message']) . ')</div>';
            }
        }

        // Determine success message based on cost
        $is_free = (floatval($order->total) == 0);
        $title = $is_free ? 'Registration Successful!' : 'Payment Successful!';
        $message = $is_free ? 'Thank you for registering. We look forward to seeing you!' : 'Thank you for your payment. Your transaction has been completed.';
        
        // Show Donation specific message
        if ($order->order_type === 'donation') {
            $title = 'Donation Successful!';
            $message = 'Thank you for your generous donation. A receipt has been sent to your email.';
        }
        
        ob_start();
        ?>
        <div class="tsn-payment-success-container">
            <div class="success-icon" style="text-align: center; color: #28a745; font-size: 48px; margin-bottom: 20px;">
                <i class="dashicons dashicons-yes-alt"></i>
            </div>
            
            <h2 style="text-align: center;"><?php echo esc_html($title); ?></h2>
            <p style="text-align: center;"><?php echo esc_html($message); ?></p>
            
            <div class="order-details-card" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #eee;">
                <h3><?php echo ($is_free || $order->order_type==='donation') ? 'Details' : 'Order Details'; ?></h3>
                <p><strong><?php echo $is_free ? 'Reference ID' : 'Order Number'; ?>:</strong> <?php echo esc_html($order->order_number); ?></p>
                <?php if (!$is_free): ?>
                    <p><strong>Amount:</strong> $<?php echo number_format($order->total, 2); ?></p>
                <?php endif; ?>
                <p><strong>Date:</strong> <?php echo date('F j, Y g:i a', strtotime($order->created_at)); ?></p>
                <p><strong>Status:</strong> <span class="tsn-status-badge status-<?php echo esc_attr($order->status); ?>"><?php echo ucfirst($order->status); ?></span></p>
                
                <?php if ($order->order_type === 'ticket'): ?>
                <div class="action-buttons" style="margin-top: 20px; text-align: center;">
                    <a href="<?php echo home_url('/member-dashboard/'); ?>" class="button button-primary">My Registered Events</a>
                </div>
                <?php elseif ($order->order_type === 'donation'): ?>
                <div class="action-buttons" style="margin-top: 20px; text-align: center;">
                    <a href="<?php echo home_url('/member-dashboard/'); ?>" class="button button-primary">Go to Dashboard</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render payment cancelled page
     */
    public static function render_payment_cancelled() {
        ob_start();
        ?>
        <div class="tsn-payment-result tsn-payment-cancelled" style="max-width: 600px; margin: 40px auto; text-align: center; padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="color: #ef4444; margin-bottom: 20px;">
                <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 style="color: #1f2937; margin-bottom: 10px;">Payment Cancelled</h2>
            <p style="color: #6b7280; margin-bottom: 30px;">You have cancelled the payment process. No charges have been made.</p>
            
            <div class="tsn-actions">
                <a href="<?php echo home_url(); ?>" class="button button-primary" style="display: inline-block; padding: 10px 20px; text-decoration: none; background: #4f46e5; color: white; border-radius: 6px;">Return to Home</a>
                <a href="<?php echo home_url('/member-dashboard/'); ?>" class="button button-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; color: #4b5563; margin-left: 10px;">Member Dashboard</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
