<?php
/**
 * Donations List Admin View
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Get all donations
$donations = $wpdb->get_results(
    "SELECT d.*, o.order_number, o.payment_method, o.paid_at, o.status as order_status, c.title as cause_title
     FROM {$wpdb->prefix}tsn_donations d
     JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
     LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
     ORDER BY d.created_at DESC"
);

// Get total statistics
$stats = $wpdb->get_row(
    "SELECT 
        COUNT(*) as total_donations,
        COALESCE(SUM(d.amount), 0) as total_amount
     FROM {$wpdb->prefix}tsn_donations d
     JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
     WHERE o.status = 'completed' OR o.status = 'paid'"
);

// Fallback if no stats
if (!$stats) {
    $stats = (object) array(
        'total_donations' => 0,
        'total_amount' => 0
    );
}

$causes = TSN_Donations::get_active_causes();
?>

<div class="wrap">
    <h1>Donations</h1>

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success">
            <p>
                <?php 
                if ($_GET['message'] === 'added') {
                    echo 'Donation recorded successfully!';
                } elseif ($_GET['message'] === 'deleted') {
                    echo 'Donation deleted successfully!';
                } else {
                    echo 'Donation updated!';
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="stats-boxes" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0;">
        <div style="background: white; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
            <div style="font-size: 14px; color: #666;">Total Donations</div>
            <div style="font-size: 32px; font-weight: bold; color: #0066cc;"><?php echo intval($stats->total_donations); ?></div>
        </div>
        <div style="background: white; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
            <div style="font-size: 14px; color: #666;">Total Amount</div>
            <div style="font-size: 32px; font-weight: bold; color: #28a745;">$<?php echo number_format($stats->total_amount, 2); ?></div>
        </div>
    </div>

    <div class="actions-bar" style="margin: 20px 0; display: flex; gap: 10px;">
        <button class="button button-primary" onclick="document.getElementById('offline-donation-modal').style.display='block'">
            Add Offline Donation
        </button>
        <a href="<?php echo admin_url('admin-ajax.php?action=tsn_export_donations&nonce=' . wp_create_nonce('tsn_admin_nonce')); ?>" class="button">
            Export CSV
        </a>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Status</th>
                <th>Donor</th>
                <th>Amount</th>
                <th>Cause</th>
                <th>Payment Method</th>
                <th>Date</th>
                <th>Message</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($donations): ?>
                <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td><?php echo esc_html($donation->order_number); ?></td>
                        <td>
                            <?php 
                            $status_colors = array(
                                'completed' => '#00a32a',
                                'paid' => '#00a32a',
                                'pending' => '#ffeb3b',
                                'failed' => '#d63638',
                                'cancelled' => '#999'
                            );
                            $color = isset($status_colors[$donation->order_status]) ? $status_colors[$donation->order_status] : '#666';
                            $bg_color = $donation->order_status == 'pending' ? '#fff8e5' : 'transparent';
                            ?>
                            <span class="tsn-status-pill" style="color: <?php echo $color; ?>; font-weight: bold;">
                                <?php echo ucfirst($donation->order_status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($donation->anonymous): ?>
                                <em>Anonymous</em>
                            <?php else: ?>
                                <?php echo esc_html($donation->donor_name); ?><br>
                                <small><?php echo esc_html($donation->donor_email); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong>$<?php echo number_format($donation->amount, 2); ?></strong></td>
                        <td><?php echo $donation->cause_title ? esc_html($donation->cause_title) : 'General Fund'; ?></td>
                        <td><?php echo $donation->payment_method ? ucfirst($donation->payment_method) : '-'; ?></td>
                        <td><?php echo !empty($donation->paid_at) ? date('M j, Y', strtotime($donation->paid_at)) : '-'; ?></td>
                        <td><?php echo $donation->comments ? substr(esc_html($donation->comments), 0, 50) . '...' : '-'; ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button type="button" class="button button-small" onclick="downloadReceipt(<?php echo $donation->order_id; ?>)" title="Download Receipt">
                                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                                </button>
                                <button type="button" class="button button-small" onclick="emailReceipt(<?php echo $donation->order_id; ?>)" title="Email Receipt">
                                    <span class="dashicons dashicons-email" style="margin-top: 3px;"></span>
                                </button>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tsn-donations&action=delete_donation&donation_id=' . $donation->id), 'delete_donation_' . $donation->id); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Are you sure you want to delete this donation? This will remove the record and update the totals. This action cannot be undone.');" 
                                   title="Delete Donation" 
                                   style="color: #a00; border-color: #a00;">
                                    <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No donations found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Offline Donation Modal -->
<div id="offline-donation-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="background: white; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 8px;">
        <h2>Add Offline Donation</h2>
        <form method="post">
            <?php wp_nonce_field('tsn_offline_donation_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label>Donor Name *</label></th>
                    <td><input type="text" name="donor_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label>Email *</label></th>
                    <td><input type="email" name="donor_email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label>Phone</label></th>
                    <td><input type="tel" name="donor_phone" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Amount *</label></th>
                    <td><input type="number" name="amount" step="0.01" min="1" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label>Cause</label></th>
                    <td>
                        <select name="cause_id">
                            <option value="">General Fund</option>
                            <?php foreach ($causes as $cause): ?>
                                <option value="<?php echo $cause->id; ?>"><?php echo esc_html($cause->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Payment Method *</label></th>
                    <td>
                        <select name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Reference</label></th>
                    <td><input type="text" name="payment_reference" class="regular-text" placeholder="Check #, etc"></td>
                </tr>
                <tr>
                    <th><label>Notes</label></th>
                    <td><textarea name="notes" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th></th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_anonymous" value="1">
                            Anonymous donation
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="tsn_add_offline_donation" class="button button-primary" value="Record Donation">
                <button type="button" class="button" onclick="document.getElementById('offline-donation-modal').style.display='none'">Cancel</button>
            </p>
        </form>
    </div>
</div>

<script>
function downloadReceipt(orderId) {
    var button = jQuery(event.currentTarget);
    button.prop('disabled', true);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'tsn_download_receipt',
            order_id: orderId,
            nonce: '<?php echo wp_create_nonce("tsn_admin_nonce"); ?>',
            is_admin: 'true'
        },
        success: function(response) {
            button.prop('disabled', false);
            if (response.success) {
                window.open(response.data.url, '_blank');
            } else {
                alert(response.data.message || 'Error occurred');
            }
        },
        error: function() {
            button.prop('disabled', false);
            alert('System error occurred');
        }
    });
}

function emailReceipt(orderId) {
    if (!confirm('Are you sure you want to resend the receipt email?')) {
        return;
    }
    
    var button = jQuery(event.currentTarget);
    button.prop('disabled', true);
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'tsn_resend_receipt',
            order_id: orderId,
            nonce: '<?php echo wp_create_nonce("tsn_admin_nonce"); ?>',
            is_admin: 'true'
        },
        success: function(response) {
            button.prop('disabled', false);
            if (response.success) {
                alert('Receipt email sent successfully!');
            } else {
                alert(response.data.message || 'Error occurred');
            }
        },
        error: function() {
            button.prop('disabled', false);
            alert('System error occurred');
        }
    });
}
</script>
