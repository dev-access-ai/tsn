<?php
/**
 * Email utility class
 * 
 * Handles sending emails with templates
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}


class TSN_Email {

    /**
     * Initialize email hooks
     */
    public static function init() {
        add_action('phpmailer_init', array(__CLASS__, 'configure_smtp'));
    }

    /**
     * Configure PHPMailer for SMTP
     */
    public static function configure_smtp($phpmailer) {
        $smtp_enabled = get_option('tsn_smtp_enabled', false);
        
        if (!$smtp_enabled) {
            return;
        }

        $smtp_host = get_option('tsn_smtp_host');
        $smtp_port = get_option('tsn_smtp_port');
        $smtp_user = get_option('tsn_smtp_user');
        $smtp_pass = get_option('tsn_smtp_pass');
        $smtp_encryption = get_option('tsn_smtp_encryption', 'tls');
        
        if ($smtp_host && $smtp_port && $smtp_user && $smtp_pass) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $smtp_port;
            $phpmailer->Username = $smtp_user;
            $phpmailer->Password = $smtp_pass;
            
            if ($smtp_encryption !== 'none') {
                $phpmailer->SMTPSecure = $smtp_encryption;
            } else {
                 $phpmailer->SMTPSecure = '';
                 $phpmailer->SMTPAutoTLS = false;
            }
            
            // Force From address if set
            $from_email = get_option('tsn_email_from_address');
            $from_name = get_option('tsn_email_from_name');
            
            if ($from_email) {
                $phpmailer->From = $from_email;
            }
            if ($from_name) {
                $phpmailer->FromName = $from_name;
            }
        }
    }
    
    /**
     * Send email
     */
    public static function send($to, $subject, $message, $attachments = array(), $extra_headers = array()) {
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        
        $from_name = get_option('tsn_email_from_name', 'Telugu Samiti of Nebraska');
        if (empty($from_name)) { $from_name = get_bloginfo('name'); }

        $admin_email = get_option('admin_email');
        $from_email = get_option('tsn_email_from_address', $admin_email);
        if (empty($from_email) || !is_email($from_email)) { $from_email = $admin_email; }

        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        $headers[] = 'Reply-To: membership@telugusamiti.org';
        
        // Merge extra headers
        if (!empty($extra_headers)) {
            if (is_array($extra_headers)) {
                $headers = array_merge($headers, $extra_headers);
            } else {
                $headers[] = $extra_headers;
            }
        }
        
        $message = self::wrap_template($message);
        
        error_log("TSN Email Attempt: To: $to, Subject: $subject, From: $from_email");

        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        if (!$sent) {
            error_log('TSN Email ERROR: wp_mail returned false. Check server mail configuration.');
            global $ts_mail_errors;
            global $phpmailer;
            if (isset($ts_mail_errors)) error_log('TSN Mail Errors: ' . print_r($ts_mail_errors, true));
            if (isset($phpmailer) && isset($phpmailer->ErrorInfo)) error_log('TSN PHPMailer Info: ' . $phpmailer->ErrorInfo);
        } else {
             error_log('TSN Email Success: Mail sent to ' . $to);
        }
        
        return $sent;
    }
    
    /**
     * Wrap email content in template
     */
    private static function wrap_template($content) {
        $logo_url = get_option('tsn_email_logo_url', get_site_icon_url());
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .email-header { background-color: #0066cc; color: #ffffff; padding: 20px; text-align: center; }
                .email-header img { max-width: 200px; height: auto; }
                .email-body { padding: 30px; }
                .email-footer { background-color: #333333; padding: 30px; text-align: center; font-size: 14px; color: #ffffff; border-top: 3px solid #0066cc; }
                .email-footer a { color: #4da6ff; text-decoration: none; }
                .button { display: inline-block; padding: 12px 30px; background-color: #0066cc; color: #ffffff; text-decoration: none; border-radius: 3px; margin: 10px 0; }
                .button:hover { background-color: #0052a3; }
                h1, h2, h3 { color: #0066cc; }
                .highlight { background-color: #fff3cd; padding: 15px; border-left: 4px solid: #ffc107; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" width="200" style="width: 200px; max-width: 200px; height: auto;">
                    <?php else: ?>
                        <h1><?php echo esc_html($site_name); ?></h1>
                    <?php endif; ?>
                </div>
                <div class="email-body">
                    <?php echo $content; ?>
                </div>
                <div class="email-footer" style="background-color: #333333; padding: 30px; text-align: center; font-size: 14px; color: #ffffff; border-top: 3px solid #0066cc;">
                    <p style="color: #ffffff; margin: 5px 0;">&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
                    <p style="margin: 5px 0;"><a href="<?php echo esc_url($site_url); ?>" style="color: #4da6ff; text-decoration: none;"><?php echo esc_url($site_url); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Send membership welcome email
     */
    public static function send_membership_welcome($member_data) {
        $to = $member_data['email'];
        $subject = 'Welcome to Telugu Samiti of Nebraska - Membership Confirmed';
        
        $validity_text = $member_data['membership_type'] === 'lifetime' 
            ? 'Lifetime (No Expiry)' 
            : 'Valid until ' . date('F j, Y', strtotime($member_data['valid_to']));
        
        $message = '<h2>Welcome to Telugu Samiti of Nebraska!</h2>';
        $message .= '<p>Dear ' . esc_html($member_data['first_name']) . ' ' . esc_html($member_data['last_name']) . ',</p>';
        $message .= '<p>Thank you for becoming a member of Telugu Samiti of Nebraska. Your membership has been successfully activated!</p>';
        $message .= '<div class="highlight">';
        $message .= '<strong>Membership Details:</strong><br>';
        $message .= 'Member ID: <strong>' . esc_html($member_data['member_id']) . '</strong><br>';
        $message .= 'Membership Type: ' . esc_html(ucfirst($member_data['membership_type'])) . '<br>';
        $message .= 'Validity: ' . $validity_text . '<br>';
        $message .= '</div>';
        $message .= '<p>As a member, you now enjoy:</p>';
        $message .= '<ul>';
        $message .= '<li>Discounted rates on event registrations</li>';
        $message .= '<li>Access to member-only events</li>';
        $message .= '<li>TSN newsletters and community updates</li>';
        $message .= '<li>Voting rights in annual meetings</li>';
        $message .= '</ul>';
        $message .= '<p>You can log in anytime to view your membership details using OTP authentication.</p>';
        $message .= '<p><a href="' . home_url('/member-login/') . '" class="button">Login to Dashboard</a></p>';
        $message .= '<p>If you have any questions, please don\'t hesitate to contact us.</p>';
        $message .= '<p>Best regards,<br>Telugu Samiti of Nebraska Team</p>';
        
        // CC Admin
        $extra_headers = array('Cc: Membership@telugusamiti.org');
        
        return self::send($to, $subject, $message, array(), $extra_headers);
    }
    
    /**
     * Send OTP email
     */
    public static function send_otp($email, $otp, $expiry_minutes = 10) {
        $subject = 'Your Login OTP for Telugu Samiti of Nebraska';
        
        $message = '<h2>Your One-Time Password</h2>';
        $message .= '<p>You requested to log in to your Telugu Samiti of Nebraska member account.</p>';
        $message .= '<div class="highlight">';
        $message .= '<p style="font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 5px; margin: 20px 0;">' . esc_html($otp) . '</p>';
        $message .= '</div>';
        $message .= '<p>This OTP is valid for ' . $expiry_minutes . ' minutes.</p>';
        $message .= '<p><strong>Security Notice:</strong> If you did not request this code, please ignore this email.</p>';
        
        return self::send($email, $subject, $message);
    }
}
