<?php
/**
 * AJAX handler class
 * 
 * Registers all AJAX actions for the plugin
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Ajax {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Membership AJAX actions
        add_action('wp_ajax_tsn_submit_membership', array('TSN_Membership', 'ajax_submit_membership'));
        add_action('wp_ajax_nopriv_tsn_submit_membership', array('TSN_Membership', 'ajax_submit_membership'));
        
        add_action('wp_ajax_tsn_request_otp', array('TSN_Membership_OTP', 'ajax_request_otp'));
        add_action('wp_ajax_nopriv_tsn_request_otp', array('TSN_Membership_OTP', 'ajax_request_otp'));
        
        add_action('wp_ajax_tsn_verify_otp', array('TSN_Membership_OTP', 'ajax_verify_otp'));
        add_action('wp_ajax_nopriv_tsn_verify_otp', array('TSN_Membership_OTP', 'ajax_verify_otp'));
        
        // Member logout - TSN members use SESSION auth, not WP auth, so they need nopriv
        add_action('wp_ajax_tsn_member_logout', array('TSN_User_Nav', 'ajax_logout'));
        add_action('wp_ajax_nopriv_tsn_member_logout', array('TSN_User_Nav', 'ajax_logout'));

        // Dynamic User Nav (AJAX for cache bypassing)
        add_action('wp_ajax_tsn_get_user_nav', array('TSN_User_Nav', 'ajax_get_user_nav'));
        add_action('wp_ajax_nopriv_tsn_get_user_nav', array('TSN_User_Nav', 'ajax_get_user_nav'));
        
        // Event AJAX actions
        add_action('wp_ajax_tsn_add_to_cart', array('TSN_Ticket_Checkout', 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_tsn_add_to_cart', array('TSN_Ticket_Checkout', 'ajax_add_to_cart'));
        
        add_action('wp_ajax_tsn_update_cart', array('TSN_Ticket_Checkout', 'ajax_update_cart'));
        add_action('wp_ajax_nopriv_tsn_update_cart', array('TSN_Ticket_Checkout', 'ajax_update_cart'));
        
        add_action('wp_ajax_tsn_checkout', array('TSN_Ticket_Checkout', 'ajax_checkout'));
        add_action('wp_ajax_nopriv_tsn_checkout', array('TSN_Ticket_Checkout', 'ajax_checkout'));
        
        add_action('wp_ajax_tsn_submit_volunteer', array('TSN_Events', 'ajax_submit_volunteer'));
        add_action('wp_ajax_nopriv_tsn_submit_volunteer', array('TSN_Events', 'ajax_submit_volunteer'));
        
        add_action('wp_ajax_tsn_update_volunteer_status', array('TSN_Events_Admin', 'ajax_update_volunteer_status'));
        add_action('wp_ajax_tsn_export_donations', array('TSN_Events_Admin', 'ajax_export_donations'));
        add_action('wp_ajax_tsn_download_receipt', array('TSN_Events_Admin', 'ajax_download_receipt'));
        
        // Simple RSVP
        add_action('wp_ajax_tsn_submit_simple_rsvp', array('TSN_Ticket_Checkout', 'ajax_submit_simple_rsvp'));
        add_action('wp_ajax_nopriv_tsn_submit_simple_rsvp', array('TSN_Ticket_Checkout', 'ajax_submit_simple_rsvp'));
        
        add_action('wp_ajax_tsn_validate_qr', array('TSN_Ticket_QR', 'ajax_validate_qr'));
        add_action('wp_ajax_tsn_validate_qr_manual', array('TSN_Ticket_QR', 'ajax_validate_manual'));
        add_action('wp_ajax_tsn_get_scan_history', array('TSN_Ticket_QR', 'ajax_get_scan_history'));
        
        // Donation AJAX actions
        add_action('wp_ajax_tsn_submit_donation', array('TSN_Donations', 'ajax_submit_donation'));
        add_action('wp_ajax_nopriv_tsn_submit_donation', array('TSN_Donations', 'ajax_submit_donation'));
        
        // Admin AJAX actions
        add_action('wp_ajax_tsn_add_offline_member', array('TSN_Membership_Admin', 'ajax_add_offline_member'));
        add_action('wp_ajax_tsn_export_members', array('TSN_Membership_Admin', 'ajax_export_members'));
        
        add_action('wp_ajax_tsn_add_offline_ticket', array('TSN_Events_Admin', 'ajax_add_offline_ticket'));
        add_action('wp_ajax_tsn_export_attendees', array('TSN_Events_Admin', 'ajax_export_attendees'));
        add_action('wp_ajax_tsn_mark_sold_out', array('TSN_Events_Admin', 'ajax_mark_sold_out'));
        
        // Ticket Management
        add_action('wp_ajax_tsn_delete_ticket', array('TSN_Events_Admin', 'ajax_delete_ticket'));
        add_action('wp_ajax_tsn_update_ticket', array('TSN_Events_Admin', 'ajax_update_ticket'));
        
        add_action('wp_ajax_tsn_add_offline_donation', array('TSN_Donations_Admin', 'ajax_add_offline_donation'));
        add_action('wp_ajax_tsn_export_donations', array('TSN_Donations_Admin', 'ajax_export_donations'));
    }
}
