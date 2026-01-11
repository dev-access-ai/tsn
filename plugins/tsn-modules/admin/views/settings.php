<?php
/**
 * Admin Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['tsn_save_settings']) && check_admin_referer('tsn_settings_nonce')) {
    update_option('tsn_paypal_mode', sanitize_text_field($_POST['paypal_mode']));
    update_option('tsn_paypal_client_id', sanitize_text_field($_POST['paypal_client_id']));
    update_option('tsn_paypal_secret', sanitize_text_field($_POST['paypal_secret']));
    update_option('tsn_paypal_business_email', sanitize_email($_POST['paypal_business_email']));
    
    update_option('tsn_membership_annual_price', floatval($_POST['annual_price']));
    update_option('tsn_membership_lifetime_price', floatval($_POST['lifetime_price']));
    update_option('tsn_membership_student_price', floatval($_POST['student_price']));
    
    update_option('tsn_email_from_name', sanitize_text_field($_POST['email_from_name']));
    update_option('tsn_email_from_address', sanitize_email($_POST['email_from_address']));
    
    // SMTP Settings
    update_option('tsn_smtp_enabled', isset($_POST['smtp_enabled']) ? true : false);
    update_option('tsn_smtp_host', sanitize_text_field($_POST['smtp_host']));
    update_option('tsn_smtp_port', absint($_POST['smtp_port']));
    update_option('tsn_smtp_user', sanitize_text_field($_POST['smtp_user']));
    update_option('tsn_smtp_pass', sanitize_text_field($_POST['smtp_pass']));
    update_option('tsn_smtp_encryption', sanitize_text_field($_POST['smtp_encryption']));
    
    update_option('tsn_otp_expiry_minutes', absint($_POST['otp_expiry']));
    update_option('tsn_otp_length', absint($_POST['otp_length']));
    
    update_option('tsn_cc_api_key', sanitize_text_field(trim($_POST['cc_api_key'])));
    
    // Strip "Bearer " if user accidentally copied it
    $token = sanitize_text_field(trim($_POST['cc_access_token']));
    if (stripos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }
    update_option('tsn_cc_access_token', $token);
    
    update_option('tsn_cc_list_id', sanitize_text_field(trim($_POST['cc_list_id'])));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current values
$paypal_mode = get_option('tsn_paypal_mode', 'sandbox');
$paypal_client_id = get_option('tsn_paypal_client_id', '');
$paypal_secret = get_option('tsn_paypal_secret', '');
$paypal_business_email = get_option('tsn_paypal_business_email', '');

$annual_price = get_option('tsn_membership_annual_price', 35);
$lifetime_price = get_option('tsn_membership_lifetime_price', 150);
$student_price = get_option('tsn_membership_student_price', 5);

$email_from_name = get_option('tsn_email_from_name', get_bloginfo('name'));
$email_from_address = get_option('tsn_email_from_address', get_bloginfo('admin_email'));

// SMTP Values
$smtp_enabled = get_option('tsn_smtp_enabled', false);
$smtp_host = get_option('tsn_smtp_host', '');
$smtp_port = get_option('tsn_smtp_port', 587);
$smtp_user = get_option('tsn_smtp_user', '');
$smtp_pass = get_option('tsn_smtp_pass', '');
$smtp_encryption = get_option('tsn_smtp_encryption', 'tls');

$otp_expiry = get_option('tsn_otp_expiry_minutes', 10);
$otp_length = get_option('tsn_otp_length', 6);

$cc_api_key = get_option('tsn_cc_api_key', '');
$cc_access_token = get_option('tsn_cc_access_token', '');
$cc_list_id = get_option('tsn_cc_list_id', '');
?>

<div class="wrap">
    <h1>TSN Modules Settings</h1>

    <form method="post" action="">
        <?php wp_nonce_field('tsn_settings_nonce'); ?>

        <h2 class="title">PayPal Configuration</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="paypal_mode">PayPal Mode</label></th>
                <td>
                    <select name="paypal_mode" id="paypal_mode">
                        <option value="sandbox" <?php selected($paypal_mode, 'sandbox'); ?>>Sandbox (Testing)</option>
                        <option value="live" <?php selected($paypal_mode, 'live'); ?>>Live (Production)</option>
                    </select>
                    <p class="description">Use Sandbox for testing, Live for production</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="paypal_client_id">Client ID</label></th>
                <td>
                    <input type="text" name="paypal_client_id" id="paypal_client_id" value="<?php echo esc_attr($paypal_client_id); ?>" class="regular-text">
                    <p class="description">Get from <a href="https://developer.paypal.com" target="_blank">PayPal Developer Dashboard</a></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="paypal_secret">Secret Key</label></th>
                <td>
                    <input type="password" name="paypal_secret" id="paypal_secret" value="<?php echo esc_attr($paypal_secret); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="paypal_business_email">Business Email</label></th>
                <td>
                    <input type="email" name="paypal_business_email" id="paypal_business_email" value="<?php echo esc_attr($paypal_business_email); ?>" class="regular-text">
                    <p class="description">Your PayPal account email for standard PayPal buttons</p>
                </td>
            </tr>
        </table>

        <h2 class="title">Membership Pricing</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="annual_price">Annual Membership ($)</label></th>
                <td>
                    <input type="number" step="0.01" name="annual_price" id="annual_price" value="<?php echo esc_attr($annual_price); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lifetime_price">Lifetime Membership ($)</label></th>
                <td>
                    <input type="number" step="0.01" name="lifetime_price" id="lifetime_price" value="<?php echo esc_attr($lifetime_price); ?>" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="student_price">Student Membership ($)</label></th>
                <td>
                    <input type="number" step="0.01" name="student_price" id="student_price" value="<?php echo esc_attr($student_price); ?>" class="small-text">
                </td>
            </tr>
        </table>

        <h2 class="title">Email & SMTP Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="email_from_name">From Name</label></th>
                <td>
                    <input type="text" name="email_from_name" id="email_from_name" value="<?php echo esc_attr($email_from_name); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="email_from_address">From Email</label></th>
                <td>
                    <input type="email" name="email_from_address" id="email_from_address" value="<?php echo esc_attr($email_from_address); ?>" class="regular-text">
                    <p class="description">Addresses matching your domain (e.g., info@telugusamiti.org) have better deliverability.</p>
                </td>
            </tr>
            
            <!-- SMTP Fields -->
            <tr>
                <th scope="row">SMTP Authentication</th>
                <td>
                    <label for="smtp_enabled">
                        <input type="checkbox" name="smtp_enabled" id="smtp_enabled" value="1" <?php checked($smtp_enabled); ?>>
                        Enable SMTP (Recommended for Bluehost)
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_host">SMTP Host</label></th>
                <td>
                    <input type="text" name="smtp_host" id="smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text" placeholder="e.g., mail.yourdomain.com">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_port">SMTP Port</label></th>
                <td>
                    <input type="number" name="smtp_port" id="smtp_port" value="<?php echo esc_attr($smtp_port); ?>" class="small-text">
                    <p class="description">Usually 465 (SSL) or 587 (TLS)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_encryption">Encryption</label></th>
                <td>
                    <select name="smtp_encryption" id="smtp_encryption">
                        <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
                        <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS (STARTTLS)</option>
                        <option value="none" <?php selected($smtp_encryption, 'none'); ?>>None</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_user">SMTP Username</label></th>
                <td>
                    <input type="text" name="smtp_user" id="smtp_user" value="<?php echo esc_attr($smtp_user); ?>" class="regular-text" autocomplete="off">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_pass">SMTP Password</label></th>
                <td>
                    <input type="password" name="smtp_pass" id="smtp_pass" value="<?php echo esc_attr($smtp_pass); ?>" class="regular-text" autocomplete="new-password">
                </td>
            </tr>
        </table>

        <h2 class="title">OTP Settings</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="otp_expiry">OTP Expiry (minutes)</label></th>
                <td>
                    <input type="number" name="otp_expiry" id="otp_expiry" value="<?php echo esc_attr($otp_expiry); ?>" class="small-text">
                    <p class="description">How long the OTP code remains valid</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="otp_length">OTP Code Length</label></th>
                <td>
                    <input type="number" name="otp_length" id="otp_length" value="<?php echo esc_attr($otp_length); ?>" class="small-text" min="4" max="8">
                    <p class="description">Number of digits in OTP code (4-8)</p>
                </td>
            </tr>
        </table>
        
        <h2 class="title">Constant Contact Integration</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="cc_api_key">API Key</label></th>
                <td>
                    <input type="text" name="cc_api_key" id="cc_api_key" value="<?php echo esc_attr($cc_api_key); ?>" class="regular-text">
                    <p class="description">Your Constant Contact V3 API Key</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cc_access_token">Access Token</label></th>
                <td>
                    <input type="password" name="cc_access_token" id="cc_access_token" value="<?php echo esc_attr($cc_access_token); ?>" class="regular-text">
                    <p class="description">Generate an Access Token via your Constant Contact Developer Portal or OAuth flow.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cc_list_id">Contact List ID</label></th>
                <td>
                    <input type="text" name="cc_list_id" id="cc_list_id" value="<?php echo esc_attr($cc_list_id); ?>" class="regular-text">
                    <p class="description">The UUID of the Constant Contact List you want to add subscribers to.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="tsn_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <hr>

    <h2>Development Mode Info</h2>
    <div class="notice notice-info inline">
        <p><strong>ℹ️ Development Mode Active:</strong> When accessing from localhost or with WP_DEBUG enabled, payments are skipped and members are activated immediately for testing purposes.</p>
        <p>Current environment: <strong><?php echo (WP_DEBUG || in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) ? '🔧 Development Mode' : '🌐 Production Mode'; ?></strong></p>
    </div>
</div>
