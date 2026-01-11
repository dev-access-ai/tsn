<?php
/**
 * Add/Edit Member Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = $member !== null;
$page_title = $is_edit ? 'Edit Member' : 'Add Offline Member';

// Parse family details if editing
$spouse = $is_edit && !empty($member->spouse_details) ? json_decode($member->spouse_details, true) : array();
$children = $is_edit && !empty($member->children_details) ? json_decode($member->children_details, true) : array();
?>

<div class="wrap">
    <h1><?php echo $page_title; ?></h1>

    <form method="post">
        <?php wp_nonce_field('tsn_member_nonce'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo $member->id; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th colspan="2"><h2>Personal Information</h2></th>
            </tr>
            <tr>
                <th><label for="first_name">First Name *</label></th>
                <td>
                    <input type="text" name="first_name" id="first_name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->first_name) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="last_name">Last Name *</label></th>
                <td>
                    <input type="text" name="last_name" id="last_name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->last_name) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="email">Email *</label></th>
                <td>
                    <input type="email" name="email" id="email" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->email) : ''; ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="phone">Phone</label></th>
                <td>
                    <input type="tel" name="phone" id="phone" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->phone) : ''; ?>">
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2>Address Information</h2></th>
            </tr>
            <tr>
                <th><label for="address">Address</label></th>
                <td>
                    <textarea name="address" id="address" rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($member->address) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="city">City</label></th>
                <td>
                    <input type="text" name="city" id="city" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->city) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="state">State</label></th>
                <td>
                    <select name="state" id="state" class="regular-text">
                        <option value="">Select State</option>
                        <option value="AL" <?php echo $is_edit && $member->state === 'AL' ? 'selected' : ''; ?>>Alabama</option>
                        <option value="AK" <?php echo $is_edit && $member->state === 'AK' ? 'selected' : ''; ?>>Alaska</option>
                        <option value="AZ" <?php echo $is_edit && $member->state === 'AZ' ? 'selected' : ''; ?>>Arizona</option>
                        <option value="AR" <?php echo $is_edit && $member->state === 'AR' ? 'selected' : ''; ?>>Arkansas</option>
                        <option value="CA" <?php echo $is_edit && $member->state === 'CA' ? 'selected' : ''; ?>>California</option>
                        <option value="CO" <?php echo $is_edit && $member->state === 'CO' ? 'selected' : ''; ?>>Colorado</option>
                        <option value="CT" <?php echo $is_edit && $member->state === 'CT' ? 'selected' : ''; ?>>Connecticut</option>
                        <option value="DE" <?php echo $is_edit && $member->state === 'DE' ? 'selected' : ''; ?>>Delaware</option>
                        <option value="FL" <?php echo $is_edit && $member->state === 'FL' ? 'selected' : ''; ?>>Florida</option>
                        <option value="GA" <?php echo $is_edit && $member->state === 'GA' ? 'selected' : ''; ?>>Georgia</option>
                        <option value="HI" <?php echo $is_edit && $member->state === 'HI' ? 'selected' : ''; ?>>Hawaii</option>
                        <option value="ID" <?php echo $is_edit && $member->state === 'ID' ? 'selected' : ''; ?>>Idaho</option>
                        <option value="IL" <?php echo $is_edit && $member->state === 'IL' ? 'selected' : ''; ?>>Illinois</option>
                        <option value="IN" <?php echo $is_edit && $member->state === 'IN' ? 'selected' : ''; ?>>Indiana</option>
                        <option value="IA" <?php echo $is_edit && $member->state === 'IA' ? 'selected' : ''; ?>>Iowa</option>
                        <option value="KS" <?php echo $is_edit && $member->state === 'KS' ? 'selected' : ''; ?>>Kansas</option>
                        <option value="KY" <?php echo $is_edit && $member->state === 'KY' ? 'selected' : ''; ?>>Kentucky</option>
                        <option value="LA" <?php echo $is_edit && $member->state === 'LA' ? 'selected' : ''; ?>>Louisiana</option>
                        <option value="ME" <?php echo $is_edit && $member->state === 'ME' ? 'selected' : ''; ?>>Maine</option>
                        <option value="MD" <?php echo $is_edit && $member->state === 'MD' ? 'selected' : ''; ?>>Maryland</option>
                        <option value="MA" <?php echo $is_edit && $member->state === 'MA' ? 'selected' : ''; ?>>Massachusetts</option>
                        <option value="MI" <?php echo $is_edit && $member->state === 'MI' ? 'selected' : ''; ?>>Michigan</option>
                        <option value="MN" <?php echo $is_edit && $member->state === 'MN' ? 'selected' : ''; ?>>Minnesota</option>
                        <option value="MS" <?php echo $is_edit && $member->state === 'MS' ? 'selected' : ''; ?>>Mississippi</option>
                        <option value="MO" <?php echo $is_edit && $member->state === 'MO' ? 'selected' : ''; ?>>Missouri</option>
                        <option value="MT" <?php echo $is_edit && $member->state === 'MT' ? 'selected' : ''; ?>>Montana</option>
                        <option value="NE" <?php echo $is_edit && $member->state === 'NE' ? 'selected' : ''; ?>>Nebraska</option>
                        <option value="NV" <?php echo $is_edit && $member->state === 'NV' ? 'selected' : ''; ?>>Nevada</option>
                        <option value="NH" <?php echo $is_edit && $member->state === 'NH' ? 'selected' : ''; ?>>New Hampshire</option>
                        <option value="NJ" <?php echo $is_edit && $member->state === 'NJ' ? 'selected' : ''; ?>>New Jersey</option>
                        <option value="NM" <?php echo $is_edit && $member->state === 'NM' ? 'selected' : ''; ?>>New Mexico</option>
                        <option value="NY" <?php echo $is_edit && $member->state === 'NY' ? 'selected' : ''; ?>>New York</option>
                        <option value="NC" <?php echo $is_edit && $member->state === 'NC' ? 'selected' : ''; ?>>North Carolina</option>
                        <option value="ND" <?php echo $is_edit && $member->state === 'ND' ? 'selected' : ''; ?>>North Dakota</option>
                        <option value="OH" <?php echo $is_edit && $member->state === 'OH' ? 'selected' : ''; ?>>Ohio</option>
                        <option value="OK" <?php echo $is_edit && $member->state === 'OK' ? 'selected' : ''; ?>>Oklahoma</option>
                        <option value="OR" <?php echo $is_edit && $member->state === 'OR' ? 'selected' : ''; ?>>Oregon</option>
                        <option value="PA" <?php echo $is_edit && $member->state === 'PA' ? 'selected' : ''; ?>>Pennsylvania</option>
                        <option value="RI" <?php echo $is_edit && $member->state === 'RI' ? 'selected' : ''; ?>>Rhode Island</option>
                        <option value="SC" <?php echo $is_edit && $member->state === 'SC' ? 'selected' : ''; ?>>South Carolina</option>
                        <option value="SD" <?php echo $is_edit && $member->state === 'SD' ? 'selected' : ''; ?>>South Dakota</option>
                        <option value="TN" <?php echo $is_edit && $member->state === 'TN' ? 'selected' : ''; ?>>Tennessee</option>
                        <option value="TX" <?php echo $is_edit && $member->state === 'TX' ? 'selected' : ''; ?>>Texas</option>
                        <option value="UT" <?php echo $is_edit && $member->state === 'UT' ? 'selected' : ''; ?>>Utah</option>
                        <option value="VT" <?php echo $is_edit && $member->state === 'VT' ? 'selected' : ''; ?>>Vermont</option>
                        <option value="VA" <?php echo $is_edit && $member->state === 'VA' ? 'selected' : ''; ?>>Virginia</option>
                        <option value="WA" <?php echo $is_edit && $member->state === 'WA' ? 'selected' : ''; ?>>Washington</option>
                        <option value="WV" <?php echo $is_edit && $member->state === 'WV' ? 'selected' : ''; ?>>West Virginia</option>
                        <option value="WI" <?php echo $is_edit && $member->state === 'WI' ? 'selected' : ''; ?>>Wisconsin</option>
                        <option value="WY" <?php echo $is_edit && $member->state === 'WY' ? 'selected' : ''; ?>>Wyoming</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="country">Country</label></th>
                <td>
                    <input type="text" name="country" id="country" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->country) : 'USA'; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="zip_code">Zip Code</label></th>
                <td>
                    <input type="text" name="zip_code" id="zip_code" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr($member->zip_code) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th colspan="2"><h2>Family Details</h2></th>
            </tr>
            <tr>
                <th><label for="spouse_name">Spouse Name</label></th>
                <td>
                    <input type="text" name="spouse_name" id="spouse_name" class="regular-text" 
                           value="<?php echo isset($spouse['name']) ? esc_attr($spouse['name']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="spouse_email">Spouse Email</label></th>
                <td>
                    <input type="email" name="spouse_email" id="spouse_email" class="regular-text" 
                           value="<?php echo isset($spouse['email']) ? esc_attr($spouse['email']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label for="spouse_phone">Spouse Phone</label></th>
                <td>
                    <input type="tel" name="spouse_phone" id="spouse_phone" class="regular-text" 
                           value="<?php echo isset($spouse['phone']) ? esc_attr($spouse['phone']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th><label>Children</label></th>
                <td>
                    <div id="children-wrapper">
                        <?php if (!empty($children)): ?>
                            <?php foreach ($children as $index => $child): ?>
                                <div class="child-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                    <input type="text" name="child_name[]" placeholder="Name" value="<?php echo esc_attr($child['name']); ?>">
                                    <input type="number" name="child_age[]" placeholder="Age" style="width: 60px" value="<?php echo esc_attr($child['age']); ?>">
                                    <select name="child_gender[]">
                                        <option value="">Gender</option>
                                        <option value="Male" <?php selected($child['gender'], 'Male'); ?>>Male</option>
                                        <option value="Female" <?php selected($child['gender'], 'Female'); ?>>Female</option>
                                    </select>
                                    <button type="button" class="button remove-child-row">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button" id="add-child-row">+ Add Child</button>
                    <script>
                    jQuery(document).ready(function($) {
                        $('#add-child-row').on('click', function() {
                            var row = `
                                <div class="child-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                    <input type="text" name="child_name[]" placeholder="Name">
                                    <input type="number" name="child_age[]" placeholder="Age" style="width: 60px">
                                    <select name="child_gender[]">
                                        <option value="">Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <button type="button" class="button remove-child-row">Remove</button>
                                </div>
                            `;
                            $('#children-wrapper').append(row);
                        });
                        
                        $(document).on('click', '.remove-child-row', function() {
                            $(this).closest('.child-row').remove();
                        });
                    });
                    </script>
                </td>
            </tr>

            <tr>
                <th colspan="2"><h2>Membership Details</h2></th>
            </tr>
            <tr>
                <th><label for="membership_type">Membership Type *</label></th>
                <td>
                    <select name="membership_type" id="membership_type" required>
                        <option value="annual" <?php echo $is_edit && $member->membership_type === 'annual' ? 'selected' : ''; ?>>Annual - $<?php echo TSN_Membership::get_membership_price('annual'); ?>/year</option>
                        <option value="lifetime" <?php echo $is_edit && $member->membership_type === 'lifetime' ? 'selected' : ''; ?>>Lifetime - $<?php echo TSN_Membership::get_membership_price('lifetime'); ?></option>
                        <option value="student" <?php echo $is_edit && $member->membership_type === 'student' ? 'selected' : ''; ?>>Student - $<?php echo TSN_Membership::get_membership_price('student'); ?>/year</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="membership_year">Membership Year</label></th>
                <td>
                    <?php 
                    $current_year = date('Y');
                    $next_year = $current_year + 1;
                    ?>
                    <select name="membership_year" id="membership_year">
                        <option value="<?php echo $current_year; ?>">Current Year (<?php echo $current_year; ?>)</option>
                        <option value="<?php echo $next_year; ?>">Next Year (<?php echo $next_year; ?>)</option>
                    </select>
                    <p class="description">Select the year this annual/student membership applies to.</p>
                </td>
            </tr>
            <tr>
                <th><label for="status">Status *</label></th>
                <td>
                    <select name="status" id="status" required>
                        <option value="active" <?php echo !$is_edit || $member->status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $is_edit && $member->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo $is_edit && $member->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="notes">Notes</label></th>
                <td>
                    <textarea name="notes" id="notes" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($member->notes) : ''; ?></textarea>
                    <p class="description">Internal notes about this member (e.g., payment details, special circumstances)</p>
                </td>
            </tr>
        </table>

        <?php if ($is_edit): ?>
            <div class="notice notice-info inline">
                <p><strong>Member ID:</strong> <?php echo esc_html($member->member_id); ?></p>
                <p><strong>Valid From:</strong> <?php echo esc_html($member->valid_from); ?></p>
                <p><strong>Valid To:</strong> <?php echo $member->valid_to ? esc_html($member->valid_to) : 'Lifetime'; ?></p>
                <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($member->created_at)); ?></p>
            </div>
        <?php endif; ?>

        <p class="submit">
            <input type="submit" name="tsn_save_member" class="button button-primary" value="<?php echo $is_edit ? 'Update Member' : 'Add Member'; ?>">
            <a href="<?php echo admin_url('admin.php?page=tsn-memberships'); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>

<style>
.form-table h2 {
    margin: 0;
    padding: 20px 0 10px 0;
    border-bottom: 1px solid #ddd;
}
.form-table h2:first-child {
    padding-top: 0;
}
</style>
