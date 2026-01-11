<?php
/**
 * Membership email notifications class
 * 
 * Handles sending membership-related emails
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Membership_Emails {
    
    /**
     * Send welcome email
     */
    public static function send_welcome($member) {
        $member_data = array(
            'email' => $member->email,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'member_id' => $member->member_id,
            'membership_type' => $member->membership_type,
            'valid_to' => $member->valid_to
        );
        
        return TSN_Email::send_membership_welcome($member_data);
    }
    
    /**
     * Send renewal reminder
     */
    public static function send_renewal_reminder($member) {
        $to = $member->email;
        $subject = 'Renew Your TSN Membership';
        
        $message = '<h2>Time to Renew Your Membership!</h2>';
        $message .= '<p>Dear ' . esc_html($member->first_name) . ' ' . esc_html($member->last_name) . ',</p>';
        $message .= '<p>Your annual membership with Telugu Samiti of Nebraska has expired.</p>';
        $message .= '<div class="highlight">';
        $message .= '<strong>Membership ID:</strong> ' . esc_html($member->member_id) . '<br>';
        $message .= '<strong>Expired On:</strong> ' . date('F j, Y', strtotime($member->valid_to)) . '<br>';
        $message .= '</div>';
        $message .= '<p>Renew your membership today to continue enjoying:</p>';
        $message .= '<ul>';
        $message .= '<li>Discounted event rates</li>';
        $message .= '<li>Community newsletters</li>';
        $message .= '<li>Voting rights in meetings</li>';
        $message .= '<li>Access to member-only events</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . home_url('/renew-membership/') . '" class="button">Renew Now</a></p>';
        $message .= '<p>Questions? Contact us at membership@telugusamiti.org</p>';
        
        return TSN_Email::send($to, $subject, $message);
    }
    
    /**
     * Send expiry reminder (before expiry)
     */
    public static function send_expiry_reminder($member, $days_remaining) {
        $to = $member->email;
        $subject = 'Your TSN Membership Expires in ' . $days_remaining . ' Days';
        
        $message = '<h2>Membership Expiring Soon</h2>';
        $message .= '<p>Dear ' . esc_html($member->first_name) . ',</p>';
        $message .= '<p>This is a friendly reminder that your Telugu Samiti of Nebraska membership will expire in <strong>' . $days_remaining . ' days</strong>.</p>';
        $message .= '<div class="highlight">';
        $message .= '<strong>Expiry Date:</strong> ' . date('F j, Y', strtotime($member->valid_to)) . '<br>';
        $message .= '</div>';
        $message .= '<p>Renew now to ensure uninterrupted access to member benefits.</p>';
        $message .= '<p><a href="' . home_url('/renew-membership/') . '" class="button">Renew Membership</a></p>';
        
        return TSN_Email::send($to, $subject, $message);
    }
}
