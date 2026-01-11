<?php
/**
 * Donation Causes List Admin View
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$causes = $wpdb->get_results(
    "SELECT *, 
     short_description as description, 
     goal_amount as goal, 
     raised_amount as total_raised,
     display_order as sort_order,
     is_active as status
     FROM {$wpdb->prefix}tsn_donation_causes 
     ORDER BY display_order ASC, created_at DESC"
);
?>

<div class="wrap">
    <h1>
        Donation Causes
        <a href="<?php echo admin_url('admin.php?page=tsn-add-cause'); ?>" class="page-title-action">Add New</a>
    </h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success">
            <p>
                <?php 
                if ($_GET['message'] === 'added') echo 'Cause added successfully!';
                elseif ($_GET['message'] === 'updated') echo 'Cause updated successfully!';
                elseif ($_GET['message'] === 'deleted') echo 'Cause deleted successfully!';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">Order</th>
                <th>Title</th>
                <th>Description</th>
                <th>Goal</th>
                <th>Raised</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($causes): ?>
                <?php foreach ($causes as $cause): ?>
                    <?php $percentage = ($cause->goal > 0) ? ($cause->total_raised / $cause->goal * 100) : 0; ?>
                    <tr>
                        <td><?php echo $cause->sort_order; ?></td>
                        <td><strong><?php echo esc_html($cause->title); ?></strong></td>
                        <td><?php echo esc_html(substr($cause->description, 0, 80)) . '...'; ?></td>
                        <td>$<?php echo number_format($cause->goal, 0); ?></td>
                        <td><strong>$<?php echo number_format($cause->total_raised, 0); ?></strong></td>
                        <td>
                            <div style="background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                                <div style="background: #0066cc; height: 100%; width: <?php echo min($percentage, 100); ?>%;"></div>
                            </div>
                            <?php echo round($percentage); ?>%
                        </td>
                        <td>
                            <span class="<?php echo $cause->is_active == 1 ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $cause->is_active == 1 ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tsn-add-cause&cause_id=' . $cause->id); ?>">Edit</a> |
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tsn-donation-causes&action=delete&cause_id=' . $cause->id), 'delete_cause_' . $cause->id); ?>" 
                               onclick="return confirm('Are you sure you want to delete this cause?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">No causes found. <a href="<?php echo admin_url('admin.php?page=tsn-add-cause'); ?>">Add your first cause</a></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-active {
    color: #28a745;
    font-weight: bold;
}
.status-inactive {
    color: #dc3545;
}
</style>
