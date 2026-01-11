<?php
/**
 * Template Name: Update Profile
 * 
 * Member profile update page
 */

get_header();

// Check if member is logged in
if (!TSN_Membership_OTP::is_member_logged_in()) {
    echo '<div class="container" style="padding: 40px 20px;">';
    echo '<p>Please <a href="' . home_url('/member-login/') . '">login</a> to update your profile.</p>';
    echo '</div>';
    get_footer();
    exit;
}

$member = TSN_Membership_OTP::get_logged_in_member();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_nonce'])) {
    if (wp_verify_nonce($_POST['update_profile_nonce'], 'update_profile')) {
        global $wpdb;
        
        $update_data = array(
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_text_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'country' => sanitize_text_field($_POST['country'])
        );
        
        $result = $wpdb->update(
            $wpdb->prefix . 'tsn_members',
            $update_data,
            array('id' => $member->id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $success_message = 'Profile updated successfully!';
            // Refresh member data
            $member = TSN_Membership_OTP::get_logged_in_member();
        } else {
            $error_message = 'Failed to update profile. Please try again.';
        }
    }
}
?>

<div class="container" style="padding: 40px 20px;">
    <div class="update-profile-container">
        <h1>Update Profile</h1>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-info">
            <h3>Basic Information</h3>
            <p><strong>Name:</strong> <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($member->email); ?></p>
            <p><strong>Member ID:</strong> <?php echo esc_html($member->member_id); ?></p>
            <p><strong>Membership Type:</strong> <?php echo esc_html(ucfirst($member->membership_type)); ?></p>
            <p class="note">Note: Name and email cannot be changed. Please contact support if you need to update these.</p>
        </div>
        
        <form method="post" class="update-profile-form">
            <?php wp_nonce_field('update_profile', 'update_profile_nonce'); ?>
            
            <h3>Contact Information</h3>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($member->phone); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo esc_attr($member->address); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo esc_attr($member->city); ?>">
                </div>
                
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo esc_attr($member->state); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="zip_code">ZIP Code</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo esc_attr($member->zip_code); ?>">
                </div>
                
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="<?php echo esc_attr($member->country); ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.update-profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.update-profile-container h1 {
    color: #4b0205;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid;
    border-image: linear-gradient(90deg, #F4A261 0%, #FEBF10 100%) 1;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 2px solid #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 2px solid #dc3545;
}

.profile-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.profile-info h3 {
    color: #4b0205;
    margin-top: 0;
}

.profile-info p {
    margin: 10px 0;
}

.profile-info .note {
    font-size: 13px;
    color: #856404;
    background: #fff3cd;
    padding: 10px;
    border-radius: 4px;
    margin-top: 15px;
}

.update-profile-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.update-profile-form h3 {
    color: #4b0205;
    margin-top: 0;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus {
    outline: none;
    border-color: #F4A261;
    box-shadow: 0 0 0 3px rgba(244, 162, 97, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, #F4A261 0%, #FEBF10 100%);
    color: #4b0205;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(244, 162, 97, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<?php get_footer(); ?>
