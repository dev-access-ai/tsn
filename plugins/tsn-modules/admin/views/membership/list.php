<?php
/**
 * Members List Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Memberships</h1>
    <a href="<?php echo admin_url('admin.php?page=tsn-add-member'); ?>" class="page-title-action">Add New Member</a>
    <a href="<?php echo admin_url('admin-ajax.php?action=tsn_export_members&nonce=' . wp_create_nonce('tsn_admin_nonce')); ?>" class="page-title-action">Export CSV</a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                if ($_GET['message'] === 'added') echo 'Member added successfully!';
                if ($_GET['message'] === 'updated') echo 'Member updated successfully!';
                if ($_GET['message'] === 'deleted') echo 'Member deactivated successfully!';
                if ($_GET['message'] === 'email_sent') echo 'Registration email resent successfully (with CC to admin)!';
                if ($_GET['message'] === 'email_error') echo 'Error: Could not send email.';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="?page=tsn-memberships&status=all" <?php echo $status_filter === 'all' ? 'class="current"' : ''; ?>>All (<?php echo $total_count; ?>)</a> |</li>
        <li><a href="?page=tsn-memberships&status=active" <?php echo $status_filter === 'active' ? 'class="current"' : ''; ?>>Active (<?php echo $active_count; ?>)</a> |</li>
        <li><a href="?page=tsn-memberships&status=pending" <?php echo $status_filter === 'pending' ? 'class="current"' : ''; ?>>Pending (<?php echo $pending_count; ?>)</a></li>
    </ul>

    <form method="get" class="search-form">
        <input type="hidden" name="page" value="tsn-memberships">
        <p class="search-box">
            <label class="screen-reader-text" for="member-search-input">Search Members:</label>
            <input type="search" id="member-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Search Members">
        </p>
    </form>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="type">
                <option value="all">All Types</option>
                <option value="annual" <?php selected($type_filter, 'annual'); ?>>Annual</option>
                <option value="lifetime" <?php selected($type_filter, 'lifetime'); ?>>Lifetime</option>
                <option value="student" <?php selected($type_filter, 'student'); ?>>Student</option>
            </select>
            <button class="button" onclick="window.location.href='?page=tsn-memberships&type=' + this.previousElementSibling.value; return false;">Filter</button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 120px;">
                    <a href="?page=tsn-memberships&orderby=member_id&order=<?php echo ($orderby == 'member_id' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Member ID <?php if($orderby=='member_id') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>
                    <a href="?page=tsn-memberships&orderby=first_name&order=<?php echo ($orderby == 'first_name' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Name <?php if($orderby=='first_name') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>
                    <a href="?page=tsn-memberships&orderby=email&order=<?php echo ($orderby == 'email' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Email <?php if($orderby=='email') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>Phone</th>
                <th>
                     <a href="?page=tsn-memberships&orderby=membership_type&order=<?php echo ($orderby == 'membership_type' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Type <?php if($orderby=='membership_type') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>
                    <a href="?page=tsn-memberships&orderby=status&order=<?php echo ($orderby == 'status' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Status <?php if($orderby=='status') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>
                     <a href="?page=tsn-memberships&orderby=valid_to&order=<?php echo ($orderby == 'valid_to' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Valid Until <?php if($orderby=='valid_to') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>
                     <a href="?page=tsn-memberships&orderby=created_at&order=<?php echo ($orderby == 'created_at' && $order == 'asc') ? 'desc' : 'asc'; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&s=<?php echo $search; ?>">
                        Joined <?php if($orderby=='created_at') echo ($order=='asc' ? '▲' : '▼'); ?>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($members): ?>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><strong><?php echo esc_html($member->member_id); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></strong>
                        </td>
                        <td><?php echo esc_html($member->email); ?></td>
                        <td><?php echo esc_html($member->phone); ?></td>
                        <td><?php echo esc_html(ucfirst($member->membership_type)); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($member->status); ?>">
                                <?php echo esc_html(ucfirst($member->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($member->membership_type === 'lifetime') {
                                echo 'Lifetime';
                            } elseif ($member->valid_to) {
                                echo date('M j, Y', strtotime($member->valid_to));
                                $days_left = ceil((strtotime($member->valid_to) - time()) / (60*60*24));
                                if ($days_left < 30 && $days_left > 0) {
                                    echo ' <span style="color: #d63638;">(' . $days_left . ' days)</span>';
                                } elseif ($days_left <= 0) {
                                    echo ' <span style="color: #d63638;">(Expired)</span>';
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                        <td>
                            <div class="row-actions visible header-actions-row">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=tsn-add-member&member_id=' . $member->id); ?>" class="tsn-action-btn" data-tip="Edit Member">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                </span> 
                                <span class="delete">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tsn-memberships&action=delete&member_id=' . $member->id), 'delete_member_' . $member->id); ?>" class="tsn-action-btn delete-btn" onclick="return confirm('Are you sure you want to deactivate this member?')" data-tip="Delete Member">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </span> 
                                <span class="email">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tsn-memberships&action=resend_email&member_id=' . $member->id), 'resend_email_' . $member->id); ?>" class="tsn-action-btn" onclick="return confirm('Send registration email to user and CC admin?')" data-tip="Resend Email">
                                        <span class="dashicons dashicons-email-alt"></span>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No members found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-active {
    background: #d4edda;
    color: #155724;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}
.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* Tooltip & Action Styles */
.tsn-action-btn {
    position: relative;
    text-decoration: none;
    margin: 0 4px;
    color: #555;
}
.tsn-action-btn:hover {
    color: #2271b1;
}
.tsn-action-btn.delete-btn:hover {
    color: #d63638;
}
.tsn-action-btn:hover::after {
    content: attr(data-tip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: #fff;
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 99;
    margin-bottom: 6px;
}
.tsn-action-btn:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #333;
    margin-bottom: -2px;
}
</style>
