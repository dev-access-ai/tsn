<?php
/**
 * Update Profile Template
 * 
 * Form for members to update their profile and family details
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logged-in member data
$member = TSN_Membership_OTP::get_logged_in_member();

if (!$member) {
    echo '<p>Please log in to verify your account.</p>';
    return;
}

// Pre-fill family details (Robust: Handle both JSON and Serialized)
$spouse = array();
if (!empty($member->spouse_details)) {
    $spouse = json_decode($member->spouse_details, true);
    if (empty($spouse) && json_last_error() !== JSON_ERROR_NONE) {
       $spouse = maybe_unserialize($member->spouse_details);
    }
     if (!is_array($spouse)) $spouse = array();
}

$children = array();
if (!empty($member->children_details)) {
    $children = json_decode($member->children_details, true);
    if (empty($children) && json_last_error() !== JSON_ERROR_NONE) {
       $children = maybe_unserialize($member->children_details);
    }
// If no children, start with one empty row for better UX
if (empty($children)) {
    $children[] = array('name' => '', 'age' => '', 'gender' => '');
}

// Debug raw data (Optional, kept for verification if needed)
if (isset($_GET['debug_tsn']) && $_GET['debug_tsn'] == '1') {
    echo '<div style="background:#fff; border:2px solid purple; padding:20px; margin:20px auto; max-width:800px;">';
    echo '<h3>TSN Profile Debug Info</h3>';
    echo '<p><strong>Raw Spouse (DB):</strong> ' . esc_html(substr($member->spouse_details ?? '', 0, 100)) . '</p>';
    echo '<p><strong>Parsed Spouse:</strong> <pre>' . esc_html(print_r($spouse, true)) . '</pre></p>';
    echo '</div>';
}

// US States List
$us_states = array(
    'AL'=>'Alabama', 'AK'=>'Alaska', 'AZ'=>'Arizona', 'AR'=>'Arkansas', 'CA'=>'California',
    'CO'=>'Colorado', 'CT'=>'Connecticut', 'DE'=>'Delaware', 'DC'=>'District of Columbia',
    'FL'=>'Florida', 'GA'=>'Georgia', 'HI'=>'Hawaii', 'ID'=>'Idaho', 'IL'=>'Illinois',
    'IN'=>'Indiana', 'IA'=>'Iowa', 'KS'=>'Kansas', 'KY'=>'Kentucky', 'LA'=>'Louisiana',
    'ME'=>'Maine', 'MD'=>'Maryland', 'MA'=>'Massachusetts', 'MI'=>'Michigan', 'MN'=>'Minnesota',
    'MS'=>'Mississippi', 'MO'=>'Missouri', 'MT'=>'Montana', 'NE'=>'Nebraska', 'NV'=>'Nevada',
    'NH'=>'New Hampshire', 'NJ'=>'New Jersey', 'NM'=>'New Mexico', 'NY'=>'New York',
    'NC'=>'North Carolina', 'ND'=>'North Dakota', 'OH'=>'Ohio', 'OK'=>'Oklahoma', 'OR'=>'Oregon',
    'PA'=>'Pennsylvania', 'RI'=>'Rhode Island', 'SC'=>'South Carolina', 'SD'=>'South Dakota',
    'TN'=>'Tennessee', 'TX'=>'Texas', 'UT'=>'Utah', 'VT'=>'Vermont', 'VA'=>'Virginia',
    'WA'=>'Washington', 'WV'=>'West Virginia', 'WI'=>'Wisconsin', 'WY'=>'Wyoming'
);
?>

<div class="tsn-update-profile">
    <div class="profile-header">
        <h2>Update Profile</h2>
        <a href="<?php echo home_url('/member-dashboard/'); ?>" class="back-link">← Back to Dashboard</a>
    </div>

    <form id="tsn-profile-form" class="tsn-form">
        <?php wp_nonce_field('tsn_update_profile_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="tsn_update_profile">

        <div class="form-section">
            <h3>Personal Information</h3>
            <div class="form-row">
                <div class="form-group half">
                    <label>First Name (Read Only)</label>
                    <input type="text" value="<?php echo esc_attr($member->first_name); ?>" readonly disabled>
                </div>
                <div class="form-group half">
                    <label>Last Name (Read Only)</label>
                    <input type="text" value="<?php echo esc_attr($member->last_name); ?>" readonly disabled>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email (Read Only)</label>
                    <input type="email" value="<?php echo esc_attr($member->email); ?>" readonly disabled>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Spouse Details</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="spouse_name">Spouse Name</label>
                    <input type="text" id="spouse_name" name="spouse_name" value="<?php echo isset($spouse['name']) ? esc_attr($spouse['name']) : ''; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group half">
                    <label for="spouse_email">Spouse Email (Optional)</label>
                    <input type="email" id="spouse_email" name="spouse_email" value="<?php echo isset($spouse['email']) ? esc_attr($spouse['email']) : ''; ?>">
                </div>
                <div class="form-group half">
                    <label for="spouse_phone">Spouse Phone (Optional)</label>
                    <input type="tel" id="spouse_phone" name="spouse_phone" value="<?php echo isset($spouse['phone']) ? esc_attr($spouse['phone']) : ''; ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Children Details</h3>
            <div id="children-container">
                <?php if (!empty($children)): ?>
                    <?php foreach ($children as $index => $child): 
                        $dob = isset($child['dob']) ? $child['dob'] : '';
                        $age = isset($child['age']) ? $child['age'] : '';
                    ?>
                        <div class="child-row">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="child_name[]" value="<?php echo esc_attr($child['name']); ?>" placeholder="Child Name">
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="child_dob[]" class="child-dob-input" value="<?php echo esc_attr($dob); ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group small" style="width: 80px;">
                                <label>Age</label>
                                <input type="text" class="child-age-display" value="<?php echo esc_attr($age); ?>" readonly style="background:#f0f0f0; border:none; text-align:center; font-weight:bold;">
                                <input type="hidden" name="child_age[]" class="child-age-hidden" value="<?php echo esc_attr($age); ?>">
                            </div>
                            <div class="form-group small">
                                <label>Gender</label>
                                <select name="child_gender[]">
                                    <option value="">Select</option>
                                    <option value="Male" <?php selected($child['gender'], 'Male'); ?>>Male</option>
                                    <option value="Female" <?php selected($child['gender'], 'Female'); ?>>Female</option>
                                </select>
                            </div>
                            <button type="button" class="remove-child" onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-child-btn" class="secondary-btn">+ Add Child</button>
        </div>

        <div class="form-section">
            <h3>Contact Information</h3>
            <div class="form-row">
                 <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($member->phone); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo esc_textarea($member->address); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo esc_attr($member->city); ?>">
                </div>
                <div class="form-group half">
                    <label for="state">State</label>
                    <select id="state" name="state">
                        <option value="">Select State</option>
                        <?php foreach ($us_states as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($member->state, $code); ?>><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label for="zip_code">Zip Code</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo esc_attr($member->zip_code); ?>">
                </div>
                <div class="form-group half">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="USA" readonly disabled>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" id="save-profile-btn" class="primary-btn">Save Changes</button>
        </div>
        <div id="form-message"></div>
    </form>

    <script>
    jQuery(document).ready(function($) {
        console.log('TSN Profile Script Loaded');
        
        // Calculate Age Function
        function calculateAge(dob) {
            if(!dob) return '';
            var today = new Date();
            var birthDate = new Date(dob);
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age >= 0 ? age : 0;
        }

        // Event delegation for DOB change
        $('#children-container').on('change', '.child-dob-input', function() {
            var dob = $(this).val();
            var age = calculateAge(dob);
            var row = $(this).closest('.child-row');
            row.find('.child-age-display').val(age);
            row.find('.child-age-hidden').val(age);
        });

        // Add Child Row
        $('#add-child-btn').on('click', function() {
            console.log('TSN: Add child clicked');
            var row = `
                <div class="child-row">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="child_name[]" placeholder="Child Name">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="child_dob[]" class="child-dob-input" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group small" style="width: 80px;">
                        <label>Age</label>
                        <input type="text" class="child-age-display" readonly style="background:#f0f0f0; border:none; text-align:center; font-weight:bold;">
                        <input type="hidden" name="child_age[]" class="child-age-hidden">
                    </div>
                    <div class="form-group small">
                        <label>Gender</label>
                        <select name="child_gender[]">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <button type="button" class="remove-child" onclick="this.parentElement.remove()">×</button>
                </div>
            `;
            $('#children-container').append(row);
        });

        // AJAX Submission
        $('#tsn-profile-form').on('submit', function(e) {
            e.preventDefault();
            console.log('TSN: Submit clicked');
            
            var $form = $(this);
            var $btn = $('#save-profile-btn');
            var $msg = $('#form-message');

            $btn.prop('disabled', true).text('Saving...');
            $msg.html('').removeClass('error success');
            
            // Log serialized data
            console.log('TSN: Data', $form.serialize());

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    console.log('TSN: Response', response);
                    if (response.success) {
                        $msg.addClass('success').html(response.data.message);
                        setTimeout(function() {
                             if(response.data.redirect_url) {
                                 window.location.href = response.data.redirect_url;
                             }
                        }, 1500);
                    } else {
                        $msg.addClass('error').html(response.data.message);
                        $btn.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('TSN: Error', error);
                    $msg.addClass('error').html('Server error. Please check console.');
                    $btn.prop('disabled', false).text('Save Changes');
                }
            });
        });
    });
    </script>

    <style>
        .tsn-update-profile { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px dashed #ccc; } /* Debug border */
        .profile-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #F4A261; padding-bottom: 10px; }
        .back-link { text-decoration: none; color: #0066cc; font-weight: 500; }
        .form-section { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px; position:relative; }
        /* Debug labels for sections */
        .form-section::before { content: "Section OK"; position: absolute; top: 0; right: 0; background: #eee; font-size: 10px; padding: 2px 5px; color: green; }
        
        .form-section h3 { margin-top: 0; color: #4b0205; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .form-row, .child-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; margin-bottom: 15px; }
        .form-group.half { width: 48%; }
        .form-group.small { width: 100px; flex: none; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group input[readonly] { background: #f9f9f9; cursor: not-allowed; }
        .child-row { align-items: flex-end; background: #f9f9f9; padding: 10px; border-radius: 4px; }
        .remove-child { background: #ff4444; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; line-height: 1; margin-bottom: 17px; }
        .primary-btn { background: #0066cc; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 600; width: 100%; }
        .primary-btn:hover { background: #0052a3; }
        .secondary-btn { background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .secondary-btn:hover { background: #545b62; }
        #form-message { margin-top: 15px; padding: 10px; border-radius: 4px; text-align: center; }
        #form-message.success { background: #d4edda; color: #155724; }
        #form-message.error { background: #f8d7da; color: #721c24; }
        @media (max-width: 600px) { .form-row, .child-row { flex-direction: column; gap: 0; } .form-group.half, .form-group.small { width: 100%; } .remove-child { align-self: flex-end; margin-bottom: 0; margin-top: 10px; } }
    </style>
</div>
