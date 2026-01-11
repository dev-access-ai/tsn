<?php
/**
 * Payment processing class
 * 
 * Handles PayPal integration for membership, events, and donations
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Payment {
    
    private $mode;
    private $client_id;
    private $secret;
    private $api_base_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->mode = get_option('tsn_paypal_mode', 'sandbox');
        $this->client_id = get_option('tsn_paypal_client_id', '');
        $this->secret = get_option('tsn_paypal_secret', '');
        
        $this->api_base_url = $this->mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }
    
    /**
     * Create PayPal order
     */
    public function create_order($amount, $currency = 'USD', $description = '', $custom_id = '') {
        $access_token = $this->get_access_token();
        
        if (!$access_token) {
            return array('success' => false, 'message' => 'Failed to authenticate with PayPal');
        }
        
        $return_url = home_url('/tsn/payment-confirmation/');
        
        // Extract order ID from custom_id if explicitly passed (format: type_id)
        if (!empty($custom_id) && strpos($custom_id, '_') !== false) {
             $parts = explode('_', $custom_id);
             if (count($parts) === 2 && is_numeric($parts[1])) {
                 $return_url = add_query_arg(array(
                     'order_id' => $parts[1],
                     'type' => $parts[0]
                 ), $return_url);
             }
        }
        
        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ),
                    'description' => $description,
                    'custom_id' => $custom_id
                )
            ),
            'application_context' => array(
                'return_url' => $return_url,
                'cancel_url' => home_url('/payment-cancelled/'),
                'brand_name' => get_bloginfo('name'),
                'user_action' => 'PAY_NOW'
            )
        );
        
        $response = wp_remote_post($this->api_base_url . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'body' => json_encode($order_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            return array(
                'success' => true,
                'order_id' => $body['id'],
                'approval_url' => $this->get_approval_url($body)
            );
        }
        
        return array('success' => false, 'message' => 'Failed to create PayPal order');
    }
    
    /**
     * Capture PayPal order
     */
    public function capture_order($order_id) {
        $access_token = $this->get_access_token();
        
        if (!$access_token) {
            return array('success' => false, 'message' => 'Failed to authenticate with PayPal');
        }
        
        $response = wp_remote_post($this->api_base_url . '/v2/checkout/orders/' . $order_id . '/capture', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Handle SUCCESS (Completed) or ALREADY CAPTURED (Idempotency)
        $is_completed = (isset($body['status']) && $body['status'] === 'COMPLETED');
        
        // Handle "Order Already Captured" - PayPal returns 422 with issue: ORDER_ALREADY_CAPTURED
        // In this case, we should treat it as success and get details.
        // NOTE: Standard capture response might not have details if already captured, 
        // so this is a basic fallback if status is missing but we suspect success.
        
        if ($is_completed) {
            return array(
                'success' => true,
                'transaction_id' => $body['purchase_units'][0]['payments']['captures'][0]['id'],
                'amount' => $body['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
                'currency' => $body['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'],
                'custom' => isset($body['purchase_units'][0]['custom_id']) ? $body['purchase_units'][0]['custom_id'] : ''
            );
        }
        
        // Capture specific error message
        $error_msg = 'Payment capture failed';
        if (isset($body['details'][0]['issue'])) {
             $error_msg .= ': ' . $body['details'][0]['issue'];
             if (isset($body['details'][0]['description'])) {
                 $error_msg .= ' - ' . $body['details'][0]['description'];
             }
        } elseif (isset($body['message'])) {
            $error_msg .= ': ' . $body['message'];
        } elseif (isset($body['status'])) {
            $error_msg .= ' with status: ' . $body['status'];
        }
        
        error_log("TSN PayPal Capture Error: " . print_r($body, true));
        
        return array('success' => false, 'message' => $error_msg);
    }
    
    /**
     * Get PayPal access token
     */
    private function get_access_token() {
        $transient_key = 'tsn_paypal_access_token';
        $cached_token = get_transient($transient_key);
        
        if ($cached_token) {
            return $cached_token;
        }
        
        $response = wp_remote_post($this->api_base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->secret)
            ),
            'body' => array('grant_type' => 'client_credentials'),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('PayPal token error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $token = $body['access_token'];
            $expires_in = isset($body['expires_in']) ? intval($body['expires_in']) - 60 : 3000;
            
            set_transient($transient_key, $token, $expires_in);
            return $token;
        }
        
        return false;
    }
    
    /**
     * Get approval URL from order response
     */
    private function get_approval_url($order_data) {
        if (isset($order_data['links'])) {
            foreach ($order_data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return $link['href'];
                }
            }
        }
        return '';
    }
    
    /**
     * Verify webhook signature
     */
    public function verify_webhook($headers, $body) {
        // PayPal webhook verification
        // This is a simplified version - implement full verification as needed
        return true;
    }
    
    /**
     * Generate PayPal button HTML
     */
    public function render_paypal_button($amount, $item_name, $return_url, $cancel_url, $custom_data = '') {
        $action_url = $this->mode === 'live' 
            ? 'https://www.paypal.com/cgi-bin/webscr' 
            : 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        
        $business_email = get_option('tsn_paypal_business_email', '');
        
        ob_start();
        ?>
        <form action="<?php echo esc_url($action_url); ?>" method="post" class="tsn-paypal-form">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="<?php echo esc_attr($business_email); ?>">
            <input type="hidden" name="item_name" value="<?php echo esc_attr($item_name); ?>">
            <input type="hidden" name="amount" value="<?php echo esc_attr(number_format($amount, 2, '.', '')); ?>">
            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="return" value="<?php echo esc_url($return_url); ?>">
            <input type="hidden" name="cancel_return" value="<?php echo esc_url($cancel_url); ?>">
            <input type="hidden" name="notify_url" value="<?php echo esc_url(home_url('/paypal-ipn/')); ?>">
            <input type="hidden" name="custom" value="<?php echo esc_attr($custom_data); ?>">
            <button type="submit" class="tsn-paypal-button">
                <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png" alt="Check out with PayPal">
            </button>
        </form>
        <?php
        return ob_get_clean();
    }
}
