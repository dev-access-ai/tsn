<?php
/**
 * Template Name: Member Login
 * Description: OTP-based member login page
 */

get_header();
?>

<div id="inner-banner">
  <div class="container">
    <div class="banner-content">
      <div class="section-title">
        <h3>Member Login</h3>
      </div>
      <?php if(function_exists('telugusmiti_breadcrumb')) telugusmiti_breadcrumb(); ?>
    </div>
  </div>
</div>

<main id="main" class="site-main">
  <div class="section login-section">
    <div class="container">
      <div class="row">
        <div class="col-md-6 col-lg-6 image-col">
            <figure>
              <img src="<?php echo get_template_directory_uri(); ?>/images/login-image.png" alt="Join the Heart of Our Community">
            </figure>
        </div>
        <div class="col-md-6 col-lg-6 content-col">
          <div class="tsn-login-container">
            <div class="section-title">
              <h3>Member Login</h3>
              <h5>Enter your registered email address to receive a one-time password (OTP).</h5>
            </div>
            
            <div id="tsn-login-messages">
            <?php if (isset($_GET['session_expired'])): ?>
                <div class="alert alert-info">
                    ⏱️ Your session has expired. Please log in again to continue.
                </div>
            <?php endif; ?>
            </div>

            <!-- Step 1: Request OTP -->
            <div id="otp-request-form" class="login-step">
              <form id="tsn-otp-request-form" class="form-container">
                <div class="form-group">
                  <span class="mdi mdi-email-variant"></span>
                  <input type="email" id="member_email" class="form-control" name="member_email" required placeholder="your-email@example.com">
                </div>

                <div class="form-group form-links">
                   <p>Need Help? If you don't have a membership yet, <a href="<?php echo home_url('/membership/'); ?>" class="btn btn-link">register here</a>.</p>
                </div>
                
                <div class="form-group">
                  <button type="submit" id="request-otp-btn" class="tsn-btn tsn-btn-primary btn-block btn btn-primary">
                    <span class="btn-text">Send OTP</span>
                    <span class="btn-loader" style="display:none;">Sending...</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- Step 2: Verify OTP -->
            <div id="otp-verify-form" class="login-step" style="display:none;">
              <div class="otp-sent-message">
                <p>✅ OTP has been sent to <em id="otp-email-display"></em>
                <span class="otp-expiry">Valid for <span id="otp-expiry-time">10</span> minutes</span></p>
              </div>

              <form id="tsn-otp-verify-form" class="form-container">
                <input type="hidden" id="verify_email" name="verify_email">
                
                <label for="otp_code">Enter OTP Code</label>
                <div class="form-group form-group-otp">
                  <span class="mdi mdi-form-textbox-password"></span>
                  <input type="text" id="otp_code" class="form-control" name="otp_code" required placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                </div>
                
                <div class="form-group">
                  <button type="submit" id="verify-otp-btn" class="tsn-btn tsn-btn-primary btn-block btn btn-primary">
                    <span class="btn-text">Verify & Login</span>
                    <span class="btn-loader" style="display:none;">Verifying...</span>
                  </button>
                </div>

                <div class="form-group">
                  <p class="field-hint">Check your email inbox for the 6-digit code</p>
                </div>
                
                <div class="form-group form-links">
                  <a href="#" id="resend-otp-link" class="btn btn-link">Didn't receive? Resend OTP</a>
                  <a href="#" id="change-email-link" class="btn btn-link">Change Email</a>
                </div>

              
              </form>
            </div>

            <div class="login-help">
              <p>For support, email us at membership@telugusamiti.org</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>


<script>
jQuery(document).ready(function($) {
    var otpExpiryMinutes = 10;
    
    // Check if tsnMembership is defined
    if (typeof tsnMembership === 'undefined') {
        console.error('tsnMembership not defined, using fallback');
        window.tsnMembership = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            restUrl: '<?php echo get_rest_url(null, 'tsn/v1/'); ?>',
            nonce: '<?php echo wp_create_nonce('tsn_membership_nonce'); ?>'
        };
    } else if (!tsnMembership.restUrl) {
         // Add restUrl if missing from localized script
         tsnMembership.restUrl = '<?php echo get_rest_url(null, 'tsn/v1/'); ?>';
    }
    
    console.log('TSN Login initialized', tsnMembership);

    // Request OTP Form
    $('#tsn-otp-request-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#member_email').val();
        var $btn = $('#request-otp-btn');
        var $btnText = $btn.find('.btn-text');
        var $btnLoader = $btn.find('.btn-loader');
        
        console.log('Requesting OTP for:', email);
        
        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoader.show();
        $('#tsn-login-messages').html('');
        
        // Use REST API
        $.ajax({
            url: tsnMembership.restUrl + 'otp/request',
            type: 'POST',
            data: {
                email: email
            },
            success: function(response) {
                console.log('OTP Request Response:', response);
                // REST API returns direct data, unlike admin-ajax which wraps in {success:true, data:...}
                // But my PHP implementation returns WP_REST_Response with the data directly.
                // Wait, if I returned new WP_REST_Response($data, 200), the response body IS $data.
                // My data structure was: array('message' => ..., 'expiry_minutes' => ...)
                // So response will just be that object.
                // It does NOT have a .success property at the top level usually unless I put it there.
                // In my PHP, I returned: array('success' => true, 'data' => ...)
                // So response.success should be true.
                
                if (response.success) {
                    // Check if in dev mode
                    if (response.data.dev_mode && response.data.otp_code) {
                        $('#tsn-login-messages').html(
                            '<div class="alert alert-info">' +
                            '<strong>🔧 ' + response.data.dev_message + '</strong><br>' +
                            '<div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 20px 0; font-family: monospace; color: #0066cc;">' + 
                            response.data.otp_code + 
                            '</div>' +
                            '<small>Copy this code and paste it in the verification field below.</small>' +
                            '</div>'
                        );
                    } else {
                        $('#tsn-login-messages').html(
                            '<div class="alert alert-success">' + response.data.message + '</div>'
                        );
                    }
                    
                    // Switch to OTP verification form
                    $('#otp-request-form').hide();
                    $('#otp-verify-form').show();
                    $('#otp-email-display').text(email);
                    $('#verify_email').val(email);
                    $('#otp-expiry-time').text(response.data.expiry_minutes || 10);
                    $('#otp_code').focus();
                } else {
                    $('#tsn-login-messages').html(
                        '<div class="alert alert-error">' + (response.data ? response.data.message : 'Error processing request') + '</div>'
                    );
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            },
            error: function(xhr) {
                // Parse error message from REST response
                var errorMsg = 'Network error. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                $('#tsn-login-messages').html(
                    '<div class="alert alert-error">' + errorMsg + '</div>'
                );
                $btn.prop('disabled', false);
                $btnText.show();
                $btnLoader.hide();
            }
        });
    });

    // Verify OTP Form
    $('#tsn-otp-verify-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#verify_email').val();
        var otp = $('#otp_code').val();
        var $btn = $('#verify-otp-btn');
        var $btnText = $btn.find('.btn-text');
        var $btnLoader = $btn.find('.btn-loader');
        
        console.log('Verifying OTP:', otp, 'for email:', email);
        
        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoader.show();
        $('#tsn-login-messages').html('');
        
        // Use REST API
        $.ajax({
            url: tsnMembership.restUrl + 'otp/verify',
            type: 'POST',
            data: {
                email: email,
                otp: otp
            },
            success: function(response) {
                console.log('OTP Verify Response:', response);
                if (response.success) {
                    $('#tsn-login-messages').html(
                        '<div class="alert alert-success">✅ ' + response.data.message + '</div>'
                    );
                    
                    // Redirect to dashboard
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1000);
                } else {
                    $('#tsn-login-messages').html(
                        '<div class="alert alert-error">' + (response.data ? response.data.message : 'Verification failed') + '</div>'
                    );
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                    $('#otp_code').val('').focus();
                }
            },
            error: function(xhr) {
                var errorMsg = 'Network error. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                
                $('#tsn-login-messages').html(
                    '<div class="alert alert-error">' + errorMsg + '</div>'
                );
                $btn.prop('disabled', false);
                $btnText.show();
                $btnLoader.hide();
            }
        });
    });

    // Resend OTP
    $('#resend-otp-link').on('click', function(e) {
        e.preventDefault();
        var email = $('#verify_email').val();
        $('#member_email').val(email);
        $('#tsn-otp-request-form').submit();
    });

    // Change Email
    $('#change-email-link').on('click', function(e) {
        e.preventDefault();
        $('#otp-verify-form').hide();
        $('#otp-request-form').show();
        $('#member_email').val('').focus();
        $('#tsn-login-messages').html('');
        $('#request-otp-btn').prop('disabled', false).find('.btn-text').show();
        $('#request-otp-btn').find('.btn-loader').hide();
    });
});
</script>

<?php
get_footer();
?>
