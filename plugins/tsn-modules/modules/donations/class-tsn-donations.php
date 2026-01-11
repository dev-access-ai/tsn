<?php
/**
 * Donations Class
 * 
 * Handles donation functionality
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Donations {
    
    public function __construct() {
        // Register shortcode for donation form
        add_shortcode('tsn_donations', array($this, 'render_donations_page'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX Handlers
        add_action('wp_ajax_tsn_download_receipt', array('TSN_Donation_Receipt', 'ajax_download_receipt'));
        add_action('wp_ajax_nopriv_tsn_download_receipt', array('TSN_Donation_Receipt', 'ajax_download_receipt'));
        add_action('wp_ajax_tsn_resend_receipt', array('TSN_Donation_Receipt', 'ajax_resend_receipt'));
        add_action('wp_ajax_nopriv_tsn_resend_receipt', array('TSN_Donation_Receipt', 'ajax_resend_receipt'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        $post = get_post();
        if (is_page() && $post && has_shortcode($post->post_content, 'tsn_donations')) {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * Render donations page
     */
    public function render_donations_page() {
        ob_start();
        include TSN_MODULES_PATH . 'modules/donations/templates/donations.php';
        return ob_get_clean();
    }
    
    /**
     * Get all active donation causes
     */
    public static function get_active_causes() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT *, short_description as description, goal_amount as goal, raised_amount as total_raised
             FROM {$wpdb->prefix}tsn_donation_causes 
             WHERE is_active = 1 
             ORDER BY display_order ASC, created_at DESC"
        );
    }
    
    /**
     * Get cause by ID
     */
    public static function get_cause_by_id($cause_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT *, 
             short_description as description, 
             goal_amount as goal, 
             raised_amount as total_raised,
             display_order as sort_order
             FROM {$wpdb->prefix}tsn_donation_causes WHERE id = %d",
            $cause_id
        ));
    }
    
    /**
     * Process donation
     */
    public static function process_donation($data) {
        global $wpdb;
        
        // Validate data
        if (empty($data['donor_name']) || empty($data['donor_email']) || empty($data['amount'])) {
            return array('success' => false, 'message' => 'Please fill in all required fields');
        }
        
        $amount = floatval($data['amount']);
        if ($amount <= 0) {
            return array('success' => false, 'message' => 'Please enter a valid donation amount');
        }
        
        // Create order
        $order_number = 'DON-' . date('Ymd') . '-' . rand(1000, 9999);
        $event_id = isset($data['event_id']) ? intval($data['event_id']) : null;
        
        $order_data = array(
            'order_number' => $order_number,
            'order_type' => 'donation',
            'event_id' => $event_id,
            'buyer_name' => sanitize_text_field($data['donor_name']),
            'buyer_email' => sanitize_email($data['donor_email']),
            'buyer_phone' => isset($data['donor_phone']) ? sanitize_text_field($data['donor_phone']) : '',
            'subtotal' => $amount,
            'total' => $amount,
            'status' => 'pending',
            'payment_method' => isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : 'paypal',
            'notes' => isset($data['message']) ? sanitize_textarea_field($data['message']) : ''
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'tsn_orders',
            $order_data,
            array('%s', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s')
        );
        
        if (!$result) {
            error_log('TSN Donation - Order insert failed: ' . $wpdb->last_error);
            return array('success' => false, 'message' => 'Failed to process donation');
        }
        
        $order_id = $wpdb->insert_id;
        
        // Create donation record
        $cause_id = isset($data['cause_id']) ? intval($data['cause_id']) : null;
        
        $wpdb->insert(
            $wpdb->prefix . 'tsn_donations',
            array(
                'order_id' => $order_id,
                'donation_id' => $order_number,
                'cause_id' => $cause_id,
                'event_id' => $event_id,
                'amount' => $amount,
                'donor_name' => $order_data['buyer_name'],
                'donor_email' => $order_data['buyer_email'],
                'donor_phone' => $order_data['buyer_phone'],
                'anonymous' => isset($data['is_anonymous']) ? 1 : 0,
                'comments' => $order_data['notes']
            ),
            array('%d', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%s')
        );
        
        // Check if in development mode (localhost)
        $is_local = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || 
                    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
        
        if ($is_local) {
            // Dev mode: Complete immediately
            self::complete_donation($order_id, 'DEV-' . time());
            
            return array(
                'success' => true,
                'message' => 'Donation processed successfully! (Development Mode)',
                'order_id' => $order_id,
                'redirect_url' => home_url('/tsn/payment-confirmation/?order_id=' . $order_id)
            );
        }
        
        // Production: Create PayPal payment
        $payment = new TSN_Payment();
        $payment_result = $payment->create_order(
            $amount,
            'USD',
            'Donation - Telugu Samiti',
            'donation_' . $order_id
        );
        
        if (!$payment_result['success']) {
            return array('success' => false, 'message' => 'Payment processing error');
        }
        
        return array(
            'success' => true,
            'message' => 'Redirecting to payment...',
            'payment_url' => $payment_result['approval_url']
        );
    }
    
    /**
     * Complete donation after payment
     */
    public static function complete_donation($order_id, $transaction_id) {
        global $wpdb;
        
        // Update order status
        $wpdb->update(
            $wpdb->prefix . 'tsn_orders',
            array(
                'status' => 'completed',
                'payment_reference' => $transaction_id,
                'paid_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        // Update donation cause total
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_donations WHERE order_id = %d",
            $order_id
        ));
        
        if ($donation && $donation->cause_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}tsn_donation_causes 
                 SET raised_amount = raised_amount + %f 
                 WHERE id = %d",
                $donation->amount,
                $donation->cause_id
            ));
        }
        
        // Send receipt email
        TSN_Donation_Receipt::send_receipt($order_id);
        TSN_Donation_Receipt::send_admin_notification($order_id);
        
        return true;
    }
    
    /**
     * AJAX: Submit donation
     */
    public static function ajax_submit_donation() {
        // Prevent random output from breaking JSON
        ob_start();
        
        try {
            check_ajax_referer('tsn_donation_nonce', 'nonce');
            
            $result = self::process_donation($_POST);
            
            // Clean any previous output (warnings, notices)
            ob_clean();
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } catch (Exception $e) {
            ob_clean();
            wp_send_json_error(array('message' => 'System error: ' . $e->getMessage()));
        }
    }
}
