<?php
/**
 * Template Name: Payment Success
 * Description: Payment success handler for PayPal returns
 */

// Start session before headers
if (!session_id()) {
    session_start();
}

get_header();

// Get PayPal token from URL
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
$payer_id = isset($_GET['PayerID']) ? sanitize_text_field($_GET['PayerID']) : '';

if (!$token || !$payer_id) {
    ?>
    <div id="inner-banner">
      <div class="container">
        <div class="banner-content">
          <div class="section-title">
            <h3>Payment Error</h3>
          </div>
        </div>
      </div>
    </div>
    
    <main id="main" class="site-main">
      <div class="section">
        <div class="container">
          <div class="payment-error">
            <h2>Invalid Payment</h2>
            <p>Missing payment information. Please try again or contact support.</p>
            <a href="<?php echo home_url(); ?>" class="btn">Return to Home</a>
          </div>
        </div>
      </div>
    </main>
    <?php
    get_footer();
    exit;
}

// Capture the payment
$payment = new TSN_Payment();
$result = $payment->capture_order($token);

?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3><?php echo $result['success'] ? 'Payment Successful' : 'Payment Failed'; ?></h3>
      </div>
    </div>
  </div>
</div>

<main id="main" class="site-main">
  <div class="section">
    <div class="container">
      <div class="payment-result">
        <?php if ($result['success']): ?>
          <div class="success-message">
            <div class="success-icon">✓</div>
            <h2>Thank You! Payment Successful</h2>
            <p>Your payment has been processed successfully.</p>
            
            <?php
            // Get the custom data to determine what was purchased
            $custom_data = isset($result['custom']) ? $result['custom'] : '';
            
            // SESSION FALLBACK: If PayPal dropped the custom_id, check our session
            if (empty($custom_data)) {
                
                if (isset($_SESSION['tsn_pending_member_id'])) {
                    $custom_data = 'member_' . $_SESSION['tsn_pending_member_id'];
                    error_log('TSN Payment Success: Used SESSION fallback for member ID: ' . $_SESSION['tsn_pending_member_id']);
                    // Clear it so it doesn't persist forever
                    unset($_SESSION['tsn_pending_member_id']);
                } elseif (isset($_SESSION['tsn_pending_order_id'])) {
                    $custom_data = 'order_' . $_SESSION['tsn_pending_order_id'];
                    error_log('TSN Payment Success: Used SESSION fallback for order ID: ' . $_SESSION['tsn_pending_order_id']);
                    unset($_SESSION['tsn_pending_order_id']);
                }
            }
            
            // Debug logging
            error_log('TSN Payment Success: Full result: ' . print_r($result, true));
            error_log('TSN Payment Success: Custom data extracted: ' . $custom_data);
            error_log('TSN Payment Success: Custom data empty? ' . (empty($custom_data) ? 'YES' : 'NO'));
            
            // Check if it's a membership or event order
            if (strpos($custom_data, 'member_') === 0 || strpos($custom_data, 'membership_') === 0) {
                // Membership registration
                // Handle both prefixes carefully
                $member_id = str_replace('membership_', '', $custom_data);
                $member_id = str_replace('member_', '', $member_id); // In case it was just member_
                
                // Activate member
                $activation_result = TSN_Membership::activate_member($member_id, $result);
                
                if (!$activation_result) {
                    echo '<div style="background:#ffebeel; color:red; padding:10px; margin:10px 0;">Warning: Activation function returned FALSE. Check error logs.</div>';
                }
                ?>
                <div class="order-details">
                  <h3>Membership Activated!</h3>
                  <p>Your membership has been activated. You can now log in to your member dashboard.</p>
                  <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn btn-primary">Go to Dashboard</a>
                </div>
                <?php
            } elseif (strpos($custom_data, 'order_') === 0) {
                // Event ticket order
                $order_id = str_replace('order_', '', $custom_data);
                
                // Log payment capture
                error_log('TSN Payment Success: Processing order #' . $order_id);
                error_log('TSN Payment Success: Custom data: ' . $custom_data);
                
                // Check if class exists
                if (!class_exists('TSN_Ticket_Checkout')) {
                    error_log('TSN CRITICAL: TSN_Ticket_Checkout class not found!');
                    echo '<div class="payment-error">System Error: Ticket Checkout processor missing.</div>';
                }
                
                // Check if order already completed
                global $wpdb;
                $order = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
                    $order_id
                ));
                
                if (!$order) {
                    error_log('TSN Payment Success: ERROR - Order not found: ' . $order_id);
                    ?>
                    <div class="order-details">
                      <h3>Order Not Found</h3>
                      <p>Order ID: <?php echo esc_html($order_id); ?> could not be found in the system.</p>
                      <a href="<?php echo home_url(); ?>" class="btn btn-primary">Return to Home</a>
                    </div>
                    <?php
                } elseif ($order->status === 'paid' || $order->status === 'completed') {
                    error_log('TSN Payment Success: Order already completed: ' . $order_id);
                    ?>
                    <div class="order-details">
                      <h3>Order Already Processed</h3>
                      <p>This order has already been completed.</p>
                      <p>Order Number: <strong><?php echo esc_html($order->order_number); ?></strong></p>
                      <p>Check your email for your tickets.</p>
                      <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                    <?php
                } else {
                    // Complete the order and generate tickets
                    error_log('TSN Payment Success: Completing order ' . $order_id);
                    $complete_result = TSN_Ticket_Checkout::complete_order($order_id, $token);
                    
                    if ($complete_result) {
                        error_log('TSN Payment Success: Order completed successfully: ' . $order_id);
                        ?>
                        <div class="order-details">
                          <h3>Tickets Confirmed!</h3>
                          <p>Your tickets have been generated and sent to your email.</p>
                          <p>Order Number: <strong><?php echo esc_html($order->order_number); ?></strong></p>
                          <p>Email: <strong><?php echo esc_html($order->buyer_email); ?></strong></p>
                          <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn btn-primary">View Dashboard</a>
                        </div>
                        <?php
                    } else {
                        error_log('TSN Payment Success: ERROR - Failed to complete order: ' . $order_id);
                        ?>
                        <div class="order-details">
                          <h3>Processing Error</h3>
                          <p>Your payment was successful but there was an error generating your tickets.</p>
                          <p>Order Number: <strong><?php echo esc_html($order->order_number); ?></strong></p>
                          <p>Please contact support with this order number.</p>
                          <a href="<?php echo home_url('/contact/'); ?>" class="btn btn-primary">Contact Support</a>
                        </div>
                        <?php
                    }
                }

            } elseif (strpos($custom_data, 'donation_') === 0) {
                // Donation
                $donation_id = str_replace('donation_', '', $custom_data);
                ?>
                <div class="order-details">
                  <h3>Donation Received!</h3>
                  <p>Thank you for your generous donation. A receipt has been sent to your email.</p>
                  <a href="<?php echo home_url(); ?>" class="btn btn-primary">Return to Home</a>
                </div>
                <?php
            } else {
                ?>
                <div class="order-details">
                  <h3>Payment Complete</h3>
                  <p>Your transaction has been completed successfully.</p>
                  <?php if (!empty($custom_data)): ?>
                      <p><small>Debug Info: Type=<?php echo esc_html($custom_data); ?> (Not recognized)</small></p>
                  <?php else: ?>
                      <p><small>Warning: No order type data returned from PayPal.</small></p>
                  <?php endif; ?>
                  <a href="<?php echo home_url(); ?>" class="btn btn-primary">Return to Home</a>
                </div>
                <?php
            }
            ?>
            
            <div class="payment-info">
              <p><small>Transaction ID: <?php echo esc_html($token); ?></small></p>
              <p><small>You will receive a confirmation email shortly.</small></p>
            </div>
          </div>
          
        <?php else: ?>
          <div class="error-message">
            <div class="error-icon">✗</div>
            <h2>Payment Failed</h2>
            <p><?php echo esc_html($result['message']); ?></p>
            <p>Please try again or contact support if the problem persists.</p>
            <a href="<?php echo home_url(); ?>" class="btn">Return to Home</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<style>
.payment-result {
    max-width: 600px;
    margin: 40px auto;
    padding: 40px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}

.success-message {
    color: #155724;
}

.success-icon {
    width: 80px;
    height: 80px;
    line-height: 80px;
    border-radius: 50%;
    background: #d4edda;
    color: #155724;
    font-size: 48px;
    margin: 0 auto 20px;
}

.error-message {
    color: #721c24;
}

.error-icon {
    width: 80px;
    height: 80px;
    line-height: 80px;
    border-radius: 50%;
    background: #f8d7da;
    color: #721c24;
    font-size: 48px;
    margin: 0 auto 20px;
}

.payment-result h2 {
    margin: 20px 0;
}

.order-details {
    margin: 30px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
}

.payment-info {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    font-size: 14px;
    color: #666;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    margin: 10px 5px;
    background: #0066cc;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn:hover {
    background: #0052a3;
    color: white;
}

.btn-primary {
    background: linear-gradient(135deg, #F4A261 0%, #FEBF10 100%);
    color: #4b0205;
    font-weight: 600;
}

.btn-primary:hover {
    box-shadow: 0 4px 12px rgba(244, 162, 97, 0.4);
}
</style>

<?php get_footer(); ?>
