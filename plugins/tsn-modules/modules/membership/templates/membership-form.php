<?php
/**
 * Membership Form Template
 * 
 * Frontend membership registration form
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get membership prices
$annual_price = get_option('tsn_membership_annual_price', 35);
$lifetime_price = get_option('tsn_membership_lifetime_price', 150);
$student_price = get_option('tsn_membership_student_price', 5);

// Check if already logged in
if (TSN_Membership_OTP::is_member_logged_in()) {
    $member = TSN_Membership_OTP::get_logged_in_member();
    if ($member) {
        echo '<div class="tsn-membership-intro" style="max-width:800px; margin:40px auto; padding:20px; border:1px solid #ddd; border-radius:8px; text-align:center;">';
        echo '<h2>Welcome back, ' . esc_html($member->first_name) . '!</h2>';
        echo '<p style="font-size:16px;">You are already a registered member of Telugu Samiti of Nebraska.</p>';
        echo '<div style="margin-top:20px; display:flex; gap:15px; justify-content:center;">';
        echo '<a href="' . home_url('/member-dashboard/') . '" class="tsn-btn tsn-btn-primary" style="text-decoration:none;">Go to Dashboard</a>';
        echo '<a href="' . home_url('/renew-membership/') . '" class="tsn-btn tsn-btn-secondary" style="background:#6c757d; color:white; padding:12px 30px; border-radius:4px; text-decoration:none;">Renew Membership</a>';
        echo '</div>';
        echo '</div>';
        return;
    }
}
?>

<div class="tsn-membership-form-container">
    <div class="tsn-membership-intro">
        <h2>Become a Member – Join Telugu Samiti of Nebraska</h2>
        <p>Telugu Samiti of Nebraska (TSN) welcomes you to be a part of our vibrant Telugu community.
           By becoming a member, you support cultural events, educational initiatives, and social activities that bring our community together.</p>
    </div>

    <div class="tsn-membership-options">
        <h3>Membership Options</h3>
        <div class="membership-options-grid">
            <div class="membership-option" data-type="annual">
                <h4>Annual Membership</h4>
                <p class="price">$<?php echo esc_html($annual_price); ?> per year</p>
                <ul>
                    <li>Valid from Jan 1 – Dec 31</li>
                    <li>Access to member-only event discounts</li>
                    <li>Receive TSN newsletters and updates</li>
                    
                </ul>
                <p class="note"><strong>Note:</strong> All annual memberships expire on December 31, regardless of purchase date.</p>
            </div>

            <div class="membership-option" data-type="lifetime">
                <div class="popular-badge">Best Value</div>
                <h4>Lifetime Membership</h4>
                <p class="price">$<?php echo esc_html($lifetime_price); ?> one-time</p>
                <ul>
                    <li>One-time payment for lifetime privileges</li>
                    <li>Recognition as a lifetime supporter</li>
                    <li>Priority access to all events</li>
                    <li>Permanent listing in member directory</li>
                </ul>
            </div>

            <div class="membership-option" data-type="student">
                <h4>Student Membership</h4>
                <p class="price">$<?php echo esc_html($student_price); ?> per year</p>
                <ul>
                    <li>Valid till Dec 31st (current year)</li>
                    <li>Student ID required</li>
                    <li>Access to all member benefits</li>
                    <li>Supporting youth engagement</li>
                </ul>
            </div>
        </div>
        </div>
    </div>

    <!-- Membership Year Selection -->
    <div id="membership-year-section" class="tsn-membership-options" style="display:none; margin-bottom: 30px;">
        <h3>Select Membership Year</h3>
        <?php 
        $current_year = date('Y');
        $next_year = $current_year + 1;
        ?>
        <div class="membership-year-options" style="display:flex; gap:20px;">
            <label class="year-option" style="flex:1; padding:15px; border:2px solid #ddd; border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                <input type="radio" name="membership_year" value="<?php echo $current_year; ?>" checked>
                <div>
                    <strong>Current Year (<?php echo $current_year; ?>)</strong>
                    <div style="font-size:0.9em; color:#666;">Valid until Dec 31, <?php echo $current_year; ?></div>
                </div>
            </label>
            <label class="year-option" style="flex:1; padding:15px; border:2px solid #ddd; border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                <input type="radio" name="membership_year" value="<?php echo $next_year; ?>">
                <div>
                    <strong>Next Year (<?php echo $next_year; ?>)</strong>
                    <div style="font-size:0.9em; color:#666;">Valid until Dec 31, <?php echo $next_year; ?></div>
                </div>
            </label>
        </div>
    </div>

    <div class="tsn-membership-form-section">
        <h3>Complete Your Membership Application</h3>
        
        <div id="tsn-membership-messages"></div>

        <form id="tsn-membership-form" method="post">
            <!-- Hidden field to store selected membership type -->
            <input type="hidden" name="membership_type" id="membership_type" required>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Mobile Number <span class="required">*</span></label>
                    <input type="tel" name="phone" id="phone" required>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address <span class="required">*</span></label>
                <textarea name="address" id="address" rows="2" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" name="city" id="city" required>
                </div>
                <div class="form-group">
                    <label for="state">State <span class="required">*</span></label>
                    <select name="state" id="state" required class="state-select">
                        <option value="">Select State</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="zip_code">ZIP Code <span class="required">*</span></label>
                    <input type="text" name="zip_code" id="zip_code" required>
                </div>
                <!-- Country removed - defaults to USA in backend -->
            </div>

            <div class="form-actions">
                <button type="submit" id="tsn-membership-submit" class="tsn-btn tsn-btn-primary">
                    <span class="btn-text">Proceed to Payment</span>
                    <span class="btn-loader" style="display:none;">Processing...</span>
                </button>
            </div>

            <p class="security-note">
                <strong>🔒 Security Note:</strong> All payments are securely processed via PayPal. 
                No financial data is stored on our servers. Your membership information is confidential and protected.
            </p>
        </form>
    </div>

    <div class="tsn-membership-contact">
        <h4>Need Help?</h4>
        <p>📧 membership@telugusamiti.org<br>
           📞 (XXX) XXX-XXXX</p>
    </div>
</div>

<style>
.tsn-membership-form-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}
.tsn-membership-intro {
    margin-bottom: 30px;
}
.membership-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.membership-option {
    border: 2px solid #e0e0e0;
    padding: 20px;
    border-radius: 8px;
    position: relative;
    transition: all 0.3s;
    cursor: pointer;
    user-select: none;
}
.membership-option:hover {
    border-color: #0066cc;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.membership-option.selected {
    border-color: #0066cc;
    background: #f0f7ff;
    box-shadow: 0 4px 12px rgba(0,102,204,0.2);
}
.membership-option.selected::after {
    content: "✓ Selected";
    position: absolute;
    top: 10px;
    right: 10px;
    background: #0066cc;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.membership-option .price {
    font-size: 28px;
    font-weight: bold;
    color: #0066cc;
    margin: 10px 0;
}
.membership-option ul {
    list-style: none;
    padding: 0;
}
.membership-option ul li:before {
    content: "✓ ";
    color: #28a745;
    font-weight: bold;
}
.popular-badge {
    position: absolute;
    top: -10px;
    right: 10px;
    background: #ff9800;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    z-index: 1;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.required {
    color: #dc3545;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}
.tsn-btn {
    padding: 12px 30px;
    font-size: 18px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}
.tsn-btn-primary {
    background-color: #0066cc;
    color: white;
}
.tsn-btn-primary:hover {
    background-color: #0052a3;
}
.security-note {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-left: 4px solid #0066cc;
}
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Make membership cards clickable
    $('.membership-option').on('click', function() {
        // Remove selected class from all cards
        $('.membership-option').removeClass('selected');
        
        // Add selected class to clicked card
        $(this).addClass('selected');
        
        // Update hidden membership_type field
        var membershipType = $(this).data('type');
        $('#membership_type').val(membershipType);

        // Show/Hide Year Selection
        if (membershipType === 'annual' || membershipType === 'student') {
            $('#membership-year-section').slideDown();
        } else {
            $('#membership-year-section').slideUp();
        }
    });

    // Year selection styling (optional visual feedback)
    $('input[name="membership_year"]').on('change', function() {
        $('.year-option').css('border-color', '#ddd').css('background', '#fff');
        $(this).closest('.year-option').css('border-color', '#0066cc').css('background', '#f0f7ff');
    }).trigger('change'); // trigger on load
    
    // Form submission handler
    $('#tsn-membership-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#tsn-membership-submit');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnLoader = $submitBtn.find('.btn-loader');
        
        // Validate membership type is selected
        var membershipType = $('#membership_type').val();
        if (!membershipType) {
            $('#tsn-membership-messages').html(
                '<div class="alert alert-error">Please select a membership type by clicking on one of the cards above.</div>'
            );
            $('html, body').animate({
                scrollTop: $('.membership-options-grid').offset().top - 100
            }, 500);
            return false;
        }
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoader.show();
        
        // Clear previous messages
        $('#tsn-membership-messages').html('');
        
        // Prepare form data
        var formData = $form.serialize();
        formData += '&action=tsn_submit_membership';
        if (typeof tsn_obj !== 'undefined') {
            formData += '&nonce=' + tsn_obj.nonce;
        } else {
             console.error('TSN Error: tsn_obj is not defined. Script not loaded?');
             alert('System error: scripts not loaded. Please refresh.');
             $submitBtn.prop('disabled', false); $btnText.show(); $btnLoader.hide();
             return false;
        }
        formData += '&country=USA'; // Default country to USA
        
        $.ajax({
            url: tsn_obj.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#tsn-membership-messages').html(
                        '<div class="alert alert-success">' + response.data.message + '</div>'
                    );
                    
                    // Redirect to PayPal or dashboard
                    setTimeout(function() {
                        if (response.data.payment_url) {
                            window.location.href = response.data.payment_url;
                        } else if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    }, 2000);
                } else {
                    $('#tsn-membership-messages').html(
                        '<div class="alert alert-error">' + response.data.message + '</div>'
                    );
                    
                    // Re-enable button
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            },
            error: function() {
                $('#tsn-membership-messages').html(
                    '<div class="alert alert-error">An error occurred. Please try again.</div>'
                );
                
                // Re-enable button
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoader.hide();
            }
        });
    });
});
</script>
