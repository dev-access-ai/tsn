<?php
/**
 * Renew Membership Template
 * 
 * Form for existing members to renew their membership
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logged-in member
if (!TSN_Membership_OTP::is_member_logged_in()) {
    echo '<div style="text-align:center; padding:40px;">';
    echo '<h3>Please Log In</h3>';
    echo '<p>You must be logged in to renew your membership.</p>';
    echo '<a href="' . home_url('/member-login/') . '" class="tsn-btn tsn-btn-primary">Log In</a>';
    echo '</div>';
    return;
}

$member = TSN_Membership_OTP::get_logged_in_member();

// Get membership prices
$annual_price = get_option('tsn_membership_annual_price', 35);
$lifetime_price = get_option('tsn_membership_lifetime_price', 150);

// Calculate current status
$is_expired = false;
$expiry_text = "Lifetime Membership (No Expiry)";

if ($member->membership_type !== 'lifetime') {
    if ($member->valid_to) {
        $expiry_date = strtotime($member->valid_to);
        $expiry_text = date('F j, Y', $expiry_date);
        if ($expiry_date < time()) {
            $is_expired = true;
            $expiry_text .= ' <span style="color:red; font-weight:bold;">(Expired)</span>';
        } else {
             $expiry_text .= ' <span style="color:green; font-weight:bold;">(Active)</span>';
        }
    }
}
?>

<div class="tsn-renewal-container">
    <div class="renewal-header">
        <h2>Renew Membership</h2>
        <a href="<?php echo home_url('/member-dashboard/'); ?>" class="back-link">← Back to Dashboard</a>
    </div>

    <div class="current-status-card">
        <h3>Current Status</h3>
        <p><strong>Name:</strong> <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></p>
        <p><strong>Member ID:</strong> <?php echo esc_html($member->member_id); ?></p>
        <p><strong>Current Plan:</strong> <?php echo ucfirst($member->membership_type); ?></p>
        <p><strong>Valid Until:</strong> <?php echo $expiry_text; ?></p>
    </div>
    
    <?php if ($member->membership_type === 'lifetime'): ?>
        <div class="alert alert-info" style="margin-top:20px; text-align:center;">
            <h3>You are a Lifetime Member!</h3>
            <p>You do not need to renew your membership. Thank you for your support!</p>
        </div>
    <?php else: ?>

    <form id="tsn-renewal-form" class="tsn-form">
        <?php wp_nonce_field('tsn_membership_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="tsn_submit_membership"> <!-- Reusing main handler -->
        <input type="hidden" name="is_renewal" value="1">
        <input type="hidden" name="member_id" value="<?php echo esc_attr($member->id); ?>">
        <input type="hidden" name="email" value="<?php echo esc_attr($member->email); ?>">
        <!-- Hidden personal fields required by validator? We might need to adjust backend validation to skip these if renewal -->
        <input type="hidden" name="first_name" value="<?php echo esc_attr($member->first_name); ?>">
        <input type="hidden" name="last_name" value="<?php echo esc_attr($member->last_name); ?>">
        <input type="hidden" name="phone" value="<?php echo esc_attr($member->phone); ?>">
        
        <h3 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">Select a Renewal Plan</h3>
        
        <div class="membership-options-grid">
            <!-- Annual Renewal -->
            <div class="membership-option selected" data-type="annual">
                <div class="option-header">
                    <h4>Annual Renewal</h4>
                    <span class="price">$<?php echo esc_html($annual_price); ?></span>
                </div>
                <div class="option-body">
                    <ul>
                        <li>Extends validity by 1 Year</li>
                        <li>Event Discounts</li>
                        <li>Voting Rights</li>
                    </ul>
                </div>
                <div class="option-footer">
                    <span class="select-btn">Selected</span>
                </div>
            </div>
            
            <!-- Upgrade to Lifetime -->
            <div class="membership-option" data-type="lifetime">
                <div class="option-header">
                    <h4>Upgrade to Lifetime</h4>
                    <span class="price">$<?php echo esc_html($lifetime_price); ?></span>
                </div>
                <div class="option-body">
                    <ul>
                        <li>Never renew again!</li>
                        <li>Permanent benefits</li>
                        <li>Special recognition</li>
                    </ul>
                </div>
                <div class="option-footer">
                    <span class="select-btn">Select</span>
                </div>
            </div>
        </div>
        <input type="hidden" name="membership_type" id="membership_type" value="annual">

        <h3 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">Payment Method</h3>
        <div class="payment-methods">
            <label class="payment-method">
                <input type="radio" name="payment_method" value="paypal" checked>
                <div class="method-details">
                    <span class="method-name">PayPal / Credit Card</span>
                    <span class="method-desc">Secure online payment</span>
                </div>
            </label>
            <label class="payment-method">
                <input type="radio" name="payment_method" value="offline">
                <div class="method-details">
                    <span class="method-name">Pay Offline (Zelle/Check/Cash)</span>
                    <span class="method-desc">Pay later to activate renewal</span>
                </div>
            </label>
        </div>

        <div class="form-actions" style="margin-top:30px;">
            <button type="submit" id="renew-btn" class="tsn-btn tsn-btn-primary full-width" style="font-size:18px; padding:15px;">
                Proceed to Pay
            </button>
        </div>
        <div id="renewal-message"></div>
    </form>
    <?php endif; ?>

    <script>
    jQuery(document).ready(function($) {
        // Option selection
        $('.membership-option').on('click', function() {
            $('.membership-option').removeClass('selected');
            $(this).addClass('selected');
            $('#membership_type').val($(this).data('type'));
            
            // Visual Update for button text
            $('.membership-option .select-btn').text('Select');
            $(this).find('.select-btn').text('Selected');
        });

        // Form Submission
        $('#tsn-renewal-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#renew-btn');
            var $msg = $('#renewal-message');
            
            $btn.prop('disabled', true).text('Processing...');
            $msg.html('').removeClass('success error');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $msg.addClass('success').html(response.data.message);
                        setTimeout(function() {
                            if (response.data.payment_url) {
                                window.location.href = response.data.payment_url;
                            } else if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            }
                        }, 1500);
                    } else {
                        $msg.addClass('error').html(response.data.message);
                        $btn.prop('disabled', false).text('Proceed to Pay');
                    }
                },
                error: function() {
                    $msg.addClass('error').html('Server error. Please try again.');
                    $btn.prop('disabled', false).text('Proceed to Pay');
                }
            });
        });
    });
    </script>
    
    <style>
        .tsn-renewal-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .renewal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #F4A261; padding-bottom: 10px; }
        .back-link { text-decoration: none; color: #0066cc; }
        .current-status-card { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px; }
        .current-status-card p { margin: 5px 0; }
        
        .membership-options-grid { display: flex; gap: 20px; margin-top: 20px; }
        .membership-option { flex: 1; border: 2px solid #e0e0e0; border-radius: 8px; padding: 0; cursor: pointer; transition: all 0.3s ease; background: #fff; display: flex; flex-direction: column; }
        .membership-option:hover { border-color: #b0b0b0; transform: translateY(-2px); }
        .membership-option.selected { border-color: #F4A261; background: #fffcf8; box-shadow: 0 4px 12px rgba(244, 162, 97, 0.2); }
        .option-header { background: #f4f4f4; padding: 15px; text-align: center; border-radius: 6px 6px 0 0; }
        .membership-option.selected .option-header { background: #ffeacc; }
        .option-header h4 { margin: 0; font-size: 18px; color: #333; }
        .option-header .price { display: block; font-size: 24px; font-weight: 700; color: #4b0205; margin-top: 5px; }
        .option-body { padding: 20px; flex: 1; }
        .option-body ul { list-style: none; padding: 0; margin: 0; }
        .option-body li { padding: 5px 0; border-bottom: 1px dashed #eee; font-size: 14px; position: relative; padding-left: 20px; color: #555; }
        .option-body li:before { content: "✓"; color: #28a745; position: absolute; left: 0; }
        .option-footer { padding: 15px; text-align: center; border-top: 1px solid #eee; }
        .select-btn { background: #eee; color: #333; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-block; }
        .membership-option.selected .select-btn { background: #F4A261; color: white; }
        
        .payment-methods { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; }
        .payment-method { display: flex; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; }
        .payment-method:hover { background: #f9f9f9; }
        .payment-method input { margin-right: 15px; transform: scale(1.2); }
        .method-name { display: block; font-weight: 600; color: #333; }
        .method-desc { display: block; font-size: 12px; color: #777; }
        
        .full-width { width: 100%; display: block; }
        #renewal-message { margin-top: 15px; padding: 10px; border-radius: 4px; text-align: center; }
        #renewal-message.success { background: #d4edda; color: #155724; }
        #renewal-message.error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 600px) { .membership-options-grid { flex-direction: column; } }
    </style>
</div>
