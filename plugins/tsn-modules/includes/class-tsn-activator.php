<?php
/**
 * Plugin activation class
 * 
 * Creates database tables and sets default options on activation
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Activator {
    
    /**
     * Activation function
     */
    public static function activate() {
        // Create database tables
        require_once TSN_MODULES_PATH . 'includes/class-tsn-database.php';
        TSN_Database::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('TSN Modules plugin activated successfully');
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'tsn_membership_annual_price' => 35,
            'tsn_membership_lifetime_price' => 150,
            'tsn_membership_student_price' => 5,
            'tsn_paypal_mode' => 'sandbox', // sandbox or live
            'tsn_paypal_client_id' => '',
            'tsn_paypal_secret' => '',
            'tsn_otp_expiry_minutes' => 10,
            'tsn_otp_length' => 6,
            'tsn_email_from_name' => get_bloginfo('name'),
            'tsn_email_from_address' => get_bloginfo('admin_email'),
            'tsn_member_id_prefix' => 'TSN',
            'tsn_ticket_qr_expiry_hours' => 24,
            'tsn_donation_min_amount' => 5,
            'tsn_donation_id_prefix' => 'TSN-D',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
