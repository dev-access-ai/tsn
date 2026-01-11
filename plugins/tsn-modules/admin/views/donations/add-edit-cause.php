<?php
/**
 * Add/Edit Donation Cause Admin View
 */

if (!defined('ABSPATH')) exit;

$cause = null;
if (isset($_GET['cause_id'])) {
    $cause = TSN_Donations::get_cause_by_id($_GET['cause_id']);
}
?>

<div class="wrap">
    <h1><?php echo $cause ? 'Edit Cause' : 'Add New Cause'; ?></h1>

    <form method="post" style="max-width: 800px;">
        <?php wp_nonce_field('tsn_cause_nonce'); ?>
        <?php if ($cause): ?>
            <input type="hidden" name="cause_id" value="<?php echo $cause->id; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="title">Title *</label></th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text" 
                           value="<?php echo $cause ? esc_attr($cause->title) : ''; ?>" required>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="description">Description *</label></th>
                <td>
                    <textarea name="description" id="description" rows="4" class="large-text" required><?php echo $cause ? esc_textarea($cause->description) : ''; ?></textarea>
                    <p class="description">Describe the cause and how donations will be used</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="goal">Fundraising Goal ($) *</label></th>
                <td>
                    <input type="number" name="goal" id="goal" step="0.01" class="regular-text" 
                           value="<?php echo $cause ? esc_attr($cause->goal) : ''; ?>" required>
                    <p class="description">Target amount to raise for this cause</p>
                </td>
            </tr>

            <?php if ($cause): ?>
            <tr>
                <th scope="row">Amount Raised</th>
                <td>
                    <strong>$<?php echo number_format($cause->raised_amount, 2); ?></strong>
                    <p class="description">This is automatically calculated from donations</p>
                </td>
            </tr>
            <?php endif; ?>

            <tr>
                <th scope="row"><label for="status">Status *</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="active" <?php echo ($cause && $cause->is_active == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($cause && $cause->is_active == 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <p class="description">Only active causes appear on the donation page</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="sort_order">Display Order</label></th>
                <td>
                    <input type="number" name="sort_order" id="sort_order" class="small-text" 
                           value="<?php echo $cause ? esc_attr($cause->display_order) : '0'; ?>">
                    <p class="description">Lower numbers appear first (0 = top)</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="tsn_save_cause" class="button button-primary" value="<?php echo $cause ? 'Update Cause' : 'Add Cause'; ?>">
            <a href="<?php echo admin_url('admin.php?page=tsn-donation-causes'); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>
