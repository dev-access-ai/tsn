<?php
/**
 * Edit Profile Template
 */

$member = TSN_Membership_OTP::get_logged_in_member();
if (!$member) return;

// Decode JSON fields
$spouse = !empty($member->spouse_details) ? json_decode($member->spouse_details, true) : array();
$children = !empty($member->children_details) ? json_decode($member->children_details, true) : array();
// $profile_photo = isset($member->profile_photo) ? $member->profile_photo : ''; 

// Note: Ensure DB column exists or handle fallback
?>

<div class="tsn-dashboard-container">
    <h2>Edit Profile</h2>
    
    <form id="tsn-edit-profile-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="tsn_update_profile">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('tsn_membership_nonce'); ?>">
        
        <!-- Basic Information -->
        <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" value="<?php echo esc_attr($member->first_name . ' ' . $member->last_name); ?>" disabled class="disabled-input">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo esc_attr($member->email); ?>" disabled class="disabled-input">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Member ID</label>
                    <input type="text" value="<?php echo esc_attr($member->member_id); ?>" disabled class="disabled-input">
                </div>
                <div class="form-group">
                    <label>Membership Type</label>
                    <input type="text" value="<?php echo esc_attr(ucfirst($member->membership_type)); ?>" disabled class="disabled-input">
                </div>
            </div>
            <p class="form-note" style="color: #666; font-size: 0.9em; font-style: italic;">Note: Name and email cannot be changed. Please contact support if you need to update these.</p>
        </div>

        <!-- Profile Photo -->
        <div class="form-section">
            <h3>Profile Photo</h3>
            <div class="profile-photo-preview">
                <!-- DEBUG PROFILE PHOTO: <?php var_dump($member->profile_photo); ?> -->
                <?php if (!empty($member->profile_photo)): ?>
                    <img src="<?php echo esc_url($member->profile_photo); ?>" alt="Profile Photo" style="max-width: 150px; border-radius: 50%;">
                <?php else: ?>
                    <div class="placeholder-photo" style="width: 100px; height: 100px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">No Photo</div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Upload New Photo</label>
                <input type="file" name="profile_photo" accept="image/*">
            </div>
        </div>

        <!-- Contact Info -->
        <div class="form-section">
            <h3>Contact Information</h3>
            
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" value="<?php echo esc_attr($member->phone); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address *</label>
                <textarea name="address" rows="2" required><?php echo esc_textarea($member->address); ?></textarea>
            </div>
            
            <div class="form-row three-col">
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" name="city" value="<?php echo esc_attr($member->city); ?>" required>
                </div>
                <div class="form-group">
                    <label>State *</label>
                    <input type="text" name="state" value="<?php echo esc_attr($member->state); ?>" required>
                </div>
                <div class="form-group">
                    <label>Zip Code *</label>
                    <input type="text" name="zip_code" value="<?php echo esc_attr($member->zip_code); ?>" required>
                </div>
            </div>
        </div>

        <!-- Family Info -->
        <div class="form-section">
            <h3>Family Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Spouse Name</label>
                    <input type="text" name="spouse_name" value="<?php echo esc_attr(isset($spouse['name']) ? $spouse['name'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label>Spouse Email</label>
                    <input type="email" name="spouse_email" value="<?php echo esc_attr(isset($spouse['email']) ? $spouse['email'] : ''); ?>">
                </div>
                <div class="form-group">
                    <label>Spouse Phone</label>
                    <input type="tel" name="spouse_phone" value="<?php echo esc_attr(isset($spouse['phone']) ? $spouse['phone'] : ''); ?>">
                </div>
            </div>
            
            <label>Children</label>
            <div id="children-container">
                <?php if ($children): ?>
                    <?php foreach ($children as $index => $child): 
                        $dob = isset($child['dob']) ? $child['dob'] : '';
                        $age = isset($child['age']) ? $child['age'] : '';
                    ?>
                        <div class="child-row form-row">
                            <input type="text" name="child_name[]" placeholder="Child Name" value="<?php echo esc_attr($child['name']); ?>">
                            <div style="flex:1;">
                                <input type="date" name="child_dob[]" class="child-dob-input" value="<?php echo esc_attr($dob); ?>" max="<?php echo date('Y-m-d'); ?>" style="width:100%;">
                            </div>
                            <div style="width: 80px;">
                                <input type="text" class="child-age-display" value="<?php echo esc_attr($age); ?>" readonly style="background:#eee; border:none; text-align:center; font-weight:bold; width:100%;" title="Age">
                                <input type="hidden" name="child_age[]" class="child-age-hidden" value="<?php echo esc_attr($age); ?>">
                            </div>
                            <select name="child_gender[]" style="width: 100px;">
                                <option value="">Gender</option>
                                <option value="Male" <?php selected(isset($child['gender']) ? $child['gender'] : '', 'Male'); ?>>Male</option>
                                <option value="Female" <?php selected(isset($child['gender']) ? $child['gender'] : '', 'Female'); ?>>Female</option>
                            </select>
                            <button type="button" class="remove-child btn-text text-danger">×</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="add-child-btn" class="button button-secondary">+ Add Child</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary button-large">Update Profile</button>
            <span class="spinner"></span>
        </div>
        <div class="form-message"></div>
    </form>
</div>

<style>
.tsn-dashboard-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.form-section { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #eee; }
.form-section h3 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
.form-row { display: flex; gap: 20px; margin-bottom: 15px; }
.form-group { flex: 1; margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.disabled-input { background: #eee; color: #666; cursor: not-allowed; }
.button-large { padding: 10px 20px; font-size: 16px; }
.child-row { align-items: center; }
.remove-child { background: none; border: none; font-size: 20px; cursor: pointer; color: red; }
</style>

<script>
jQuery(document).ready(function($) {
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

    // Add Child
    $('#add-child-btn').on('click', function() {
        var html = '<div class="child-row form-row">' +
                   '<input type="text" name="child_name[]" placeholder="Child Name">' +
                   '<div style="flex:1;"><input type="date" name="child_dob[]" class="child-dob-input" max="<?php echo date("Y-m-d"); ?>" style="width:100%;"></div>' +
                   '<div style="width: 80px;"><input type="text" class="child-age-display" readonly style="background:#eee; border:none; text-align:center; font-weight:bold; width:100%;" title="Age"><input type="hidden" name="child_age[]" class="child-age-hidden"></div>' +
                   '<select name="child_gender[]" style="width: 100px;">' +
                   '<option value="">Gender</option>' +
                   '<option value="Male">Male</option>' +
                   '<option value="Female">Female</option>' +
                   '</select>' +
                   '<button type="button" class="remove-child btn-text text-danger">×</button>' +
                   '</div>';
        $('#children-container').append(html);
    });

    // Remove Child
    $(document).on('click', '.remove-child', function() {
        $(this).closest('.child-row').remove();
    });

    // Submit Form
    $('#tsn-edit-profile-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        var msgDiv = form.find('.form-message');
        
        form.find('button[type="submit"]').prop('disabled', true);
        form.find('.spinner').addClass('is-active');
        msgDiv.html('').removeClass('error success');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    msgDiv.addClass('success').html(response.data.message);
                    // Optional: Refresh page to show new photo
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    msgDiv.addClass('error').html(response.data.message || 'Update failed');
                    form.find('button[type="submit"]').prop('disabled', false);
                }
                form.find('.spinner').removeClass('is-active');
            },
            error: function() {
                msgDiv.addClass('error').html('Connection error');
                form.find('button[type="submit"]').prop('disabled', false);
                form.find('.spinner').removeClass('is-active');
            }
        });
    });
});
</script>
