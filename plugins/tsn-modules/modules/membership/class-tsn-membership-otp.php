<?php
/**
 * Membership OTP authentication class
 * 
 * Handles OTP generation, verification, and member login sessions
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Membership_OTP {
    
    /**
     * Request OTP AJAX handler
     */
    /**
     * REST API OTP Request
     */
    public static function rest_request_otp($request) {
        $params = $request->get_params();
        $email = sanitize_email($params['email']);
        
        $result = self::process_otp_request($email);
        
        if ($result['success']) {
            return new WP_REST_Response(array('success' => true, 'data' => $result['data']), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'data' => $result['data']), 400);
        }
    }
    
    /**
     * REST API OTP Verify
     */
    public static function rest_verify_otp($request) {
        $params = $request->get_params();
        $email = sanitize_email($params['email']);
        $otp = sanitize_text_field($params['otp']);
        
        $result = self::process_otp_verify($email, $otp);
        
        if ($result['success']) {
            return new WP_REST_Response(array('success' => true, 'data' => $result['data']), 200);
        } else {
            return new WP_REST_Response(array('success' => false, 'data' => $result['data']), 400);
        }
    }

    /**
     * Common Logic: Request OTP
     */
    private static function process_otp_request($email) {
        if (!TSN_Security::validate_email($email)) {
            return array('success' => false, 'data' => array('message' => 'Invalid email address.'));
        }
        
        // Rate limiting
        if (!TSN_Security::check_rate_limit('otp_request_' . $email, 3, 600)) {
            $remaining = TSN_Security::get_rate_limit_remaining_time('otp_request_' . $email);
            return array('success' => false, 'data' => array('message' => 'Too many OTP requests. Please try again in ' . ceil($remaining / 60) . ' minutes.'));
        }
        
        // Check if member exists
        $member = TSN_Membership::get_member_by_email($email);
        
        if (!$member) {
            return array('success' => false, 'data' => array('message' => 'No membership found for this email. Please register first.'));
        }
        
        // Check if membership is active
        if ($member->status !== 'active') {
             // Allow expired members to login to renew? The original code said "contact support".Sticking to original logic.
            return array('success' => false, 'data' => array('message' => 'Your membership is not active. Please contact support.'));
        }
        
        // Check if membership has expired (Login should be allowed to renew? Original code blocked it)
        if (!empty($member->expiry_date)) {
            $expiry = strtotime($member->expiry_date);
            if ($expiry < time()) {
                 // The dashboard handles renewal, so we SHOULD allow login. 
                 // BUT the original code blocked it: "Your membership has expired. Please renew to continue."
                 // If they can't login, they can't use the dashboard renewal link.
                 // Ideally we allow login but show expired status. 
                 // adhere to previous logic for now to minimize regression risk during critical fix.
                return array('success' => false, 'data' => array('message' => 'Your membership has expired. Please renew to continue.'));
            }
        }
        
        // Generate OTP
        $otp = self::generate_otp();
        $expiry_minutes = get_option('tsn_otp_expiry_minutes', 10);
        
        // Store OTP in transient
        set_transient('tsn_otp_' . md5($email), array(
            'otp' => $otp,
            'member_id' => $member->id,
            'email' => $email
        ), $expiry_minutes * 60);
        
        // Check if in development mode
        $is_local = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || 
                    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
        
        if ($is_local) {
            // Development mode: Show OTP in response
            TSN_Security::log_security_event('otp_requested', 'OTP requested for: ' . $email . ' (Dev Mode)', $member->id);
            return array('success' => true, 'data' => array(
                'message' => 'OTP generated successfully!',
                'expiry_minutes' => $expiry_minutes,
                'dev_mode' => true,
                'otp_code' => $otp,
                'dev_message' => 'Development Mode: Your OTP is shown below (normally sent via email)'
            ));
        }
        
        // Production mode: Send OTP email
        $start_time = microtime(true);
        error_log('TSN Performance: Starting SMS/Email dispatch for ' . $email);
        
        $sent = TSN_Email::send_otp($email, $otp, $expiry_minutes);
        
        $duration = microtime(true) - $start_time;
        error_log('TSN Performance: Mail dispatch took ' . number_format($duration, 4) . ' seconds');
        
        if ($sent) {
            TSN_Security::log_security_event('otp_requested', 'OTP requested for: ' . $email, $member->id);
            return array('success' => true, 'data' => array(
                'message' => 'OTP has been sent to your email. Please check your inbox.',
                'expiry_minutes' => $expiry_minutes,
                'debug_timing' => ($duration > 2.0 ? 'Note: Email server took ' . number_format($duration, 1) . 's to respond.' : '')
            ));
        } else {
            return array('success' => false, 'data' => array('message' => 'Failed to send OTP. Please try again.'));
        }
    }

    /**
     * Common Logic: Verify OTP
     */
    private static function process_otp_verify($email, $otp_input) {
        // Rate limiting for verification attempts
        if (!TSN_Security::check_rate_limit('otp_verify_' . $email, 5, 600)) {
            return array('success' => false, 'data' => array('message' => 'Too many verification attempts. Please request a new OTP.'));
        }
        
        // Get stored OTP
        $otp_data = get_transient('tsn_otp_' . md5($email));
        
        if (!$otp_data) {
             return array('success' => false, 'data' => array('message' => 'OTP has expired. Please request a new one.'));
        }
        
        // Verify OTP
        if ($otp_data['otp'] !== $otp_input) {
            TSN_Security::log_security_event('otp_failed', 'Failed OTP attempt for: ' . $email);
             return array('success' => false, 'data' => array('message' => 'Invalid OTP. Please try again.'));
        }
        
        // OTP is valid - create session
        self::create_member_session($otp_data['member_id'], $email);
        
        // Delete used OTP
        delete_transient('tsn_otp_' . md5($email));
        
        TSN_Security::log_security_event('otp_verified', 'Successful login for: ' . $email, $otp_data['member_id']);
        
         return array('success' => true, 'data' => array(
            'message' => 'Login successful!',
            'redirect_url' => home_url('/member-dashboard/')
        ));
    }

    /**
     * Request OTP AJAX handler
     */
    public static function ajax_request_otp() {
        if (ob_get_length()) ob_clean();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tsn_membership_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page.'));
            exit;
        }
        
        $email = TSN_Security::sanitize_input($_POST['email'], 'email');
        $result = self::process_otp_request($email);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['data']);
        }
        exit;
    }
    
    /**
     * Verify OTP AJAX handler
     */
    public static function ajax_verify_otp() {
         if (ob_get_length()) ob_clean();
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tsn_membership_nonce')) {
             wp_send_json_error(array('message' => 'Security check failed. Please request a new OTP.'));
             exit;
        }
        
        $email = TSN_Security::sanitize_input($_POST['email'], 'email');
        $otp_input = TSN_Security::sanitize_input($_POST['otp'], 'text');
        
        $result = self::process_otp_verify($email, $otp_input);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['data']);
        }
        exit;
    }
    
    /**
     * Generate OTP code
     */
    private static function generate_otp() {
        $length = get_option('tsn_otp_length', 6);
        $otp = '';
        
        for ($i = 0; $i < $length; $i++) {
            $otp .= mt_rand(0, 9);
        }
        
        return $otp;
    }
    
    /**
     * Create member session
     */
    private static function create_member_session($member_id, $email) {
        // Use WordPress session/cookies approach
        $session_token = TSN_Security::generate_random_string(64);
        
        // Store session in user meta or transient
        set_transient('tsn_member_session_' . $session_token, array(
            'member_id' => $member_id,
            'email' => $email,
            'logged_in_at' => current_time('mysql')
        ), 24 * HOUR_IN_SECONDS); // 24 hour session
        
        // Set cookie
        setcookie('tsn_member_token', $session_token, time() + (24 * HOUR_IN_SECONDS), '/', '', is_ssl(), true);
        
        // Also set in PHP session for immediate access
        if (!session_id()) {
            session_start();
        }
        $_SESSION['tsn_member_id'] = $member_id;
        $_SESSION['tsn_member_email'] = $email;
    }
    
    /**
     * Check if member is logged in
     */
    public static function is_member_logged_in() {
        try {
            // Check session
            if (!session_id()) {
                @session_start();
            }
            
            if (isset($_SESSION['tsn_member_id'])) {
                return true;
            }
            
            // Check cookie
            if (isset($_COOKIE['tsn_member_token'])) {
                $session_data = get_transient('tsn_member_session_' . $_COOKIE['tsn_member_token']);
                
                if ($session_data) {
                    // Restore session
                    $_SESSION['tsn_member_id'] = $session_data['member_id'];
                    $_SESSION['tsn_member_email'] = $session_data['email'];
                    return true;
                }
            }
        } catch (Exception $e) {
            // Log error and return false
            error_log('TSN Session Error: ' . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Get logged-in member data
     */
    public static function get_logged_in_member() {
        if (!self::is_member_logged_in()) {
            return null;
        }
        
        if (!session_id()) {
            session_start();
        }
        
        $member_id = $_SESSION['tsn_member_id'];
        return TSN_Membership::get_member_by_id($member_id);
    }
    
    /**
     * Logout member
     */
    public static function logout_member() {
        if (!session_id()) {
            session_start();
        }
        
        // Delete transient if cookie exists
        if (isset($_COOKIE['tsn_member_token'])) {
            delete_transient('tsn_member_session_' . $_COOKIE['tsn_member_token']);
            setcookie('tsn_member_token', '', time() - 3600, '/', '', is_ssl(), true);
        }
        
        // Clear session
        unset($_SESSION['tsn_member_id']);
        unset($_SESSION['tsn_member_email']);
        
        session_destroy();
    }
}
