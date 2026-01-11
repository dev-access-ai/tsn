<?php
/**
 * Security utilities class
 * 
 * Provides security functions for CSRF protection, input sanitization,
 * rate limiting, and XSS prevention
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Security {
    
    /**
     * Verify nonce
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            return false;
        }
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return absint($data);
            case 'float':
                return floatval($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'html':
                return wp_kses_post($data);
            case 'text':
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Sanitize array of inputs
     */
    public static function sanitize_array($array, $type = 'text') {
        $sanitized = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sanitized[sanitize_key($key)] = self::sanitize_array($value, $type);
            } else {
                $sanitized[sanitize_key($key)] = self::sanitize_input($value, $type);
            }
        }
        return $sanitized;
    }
    
    /**
     * Check rate limiting
     * Prevents brute force attacks
     */
    public static function check_rate_limit($identifier, $max_attempts = 5, $time_window = 300) {
        $transient_key = 'tsn_rate_limit_' . md5($identifier);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            set_transient($transient_key, 1, $time_window);
            return true;
        }
        
        if ($attempts >= $max_attempts) {
            return false;
        }
        
        set_transient($transient_key, $attempts + 1, $time_window);
        return true;
    }
    
    /**
     * Get remaining rate limit time
     */
    public static function get_rate_limit_remaining_time($identifier) {
        $transient_key = 'tsn_rate_limit_' . md5($identifier);
        $timeout = get_option('_transient_timeout_' . $transient_key);
        
        if ($timeout === false) {
            return 0;
        }
        
        $remaining = $timeout - time();
        return max(0, $remaining);
    }
    
    /**
     * Validate email format
     */
    public static function validate_email($email) {
        return is_email($email);
    }
    
    /**
     * Validate phone number (basic US format)
     */
    public static function validate_phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    /**
     * Generate secure random string
     */
    public static function generate_random_string($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash data (for QR tokens, OTP, etc.)
     */
    public static function hash_data($data) {
        return hash('sha256', $data . wp_salt());
    }
    
    /**
     * Verify hashed data
     */
    public static function verify_hash($data, $hash) {
        return hash_equals($hash, self::hash_data($data));
    }
    
    /**
     * Get client IP address
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Log security event
     */
    public static function log_security_event($event_type, $description, $user_id = null) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'description' => $description,
            'user_id' => $user_id,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );
        
        error_log('TSN Security Event: ' . json_encode($log_entry));
        
        // Optional: Store in database for audit trail
        do_action('tsn_security_event_logged', $log_entry);
    }
    
    /**
     * Check if request is from admin
     */
    public static function is_admin_request() {
        return current_user_can('manage_options');
    }
    
    /**
     * Validate CSRF token for AJAX requests
     */
    public static function validate_ajax_request($nonce_action) {
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token'), 403);
            wp_die();
        }
    }
}
