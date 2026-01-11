<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;

$total_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'active'");
$annual_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'active' AND membership_type = 'annual'");
$lifetime_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'active' AND membership_type = 'lifetime'");
$student_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'active' AND membership_type = 'student'");
$pending_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'pending'");

// Get recent members
$recent_members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsn_members ORDER BY created_at DESC LIMIT 10");

// Get total revenue
$total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}tsn_member_transactions WHERE status = 'completed'");

?>

<div class="wrap">
    <h1 class="wp-heading-inline">TSN Modules Dashboard</h1>
    <hr class="wp-header-end">

    <div class="tsn-dashboard-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_members); ?></h3>
                    <p>Total Active Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo number_format($annual_members); ?></h3>
                    <p>Annual Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <h3><?php echo number_format($lifetime_members); ?></h3>
                    <p>Lifetime Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-content">
                    <h3><?php echo number_format($student_members); ?></h3>
                    <p>Student Members</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo number_format($pending_members); ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <h2>Recent Members</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Type</th>
                <th>Status</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($recent_members): ?>
                <?php foreach ($recent_members as $member): ?>
                    <tr>
                        <td><strong><?php echo esc_html($member->member_id); ?></strong></td>
                        <td><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></td>
                        <td><?php echo esc_html($member->email); ?></td>
                        <td><?php echo esc_html(ucfirst($member->membership_type)); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($member->status); ?>">
                                <?php echo esc_html(ucfirst($member->status)); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($member->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No members yet. Create your first member!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p><a href="<?php echo admin_url('admin.php?page=tsn-memberships'); ?>" class="button button-primary">View All Members</a></p>
</div>

<style>
.tsn-dashboard-stats {
    margin: 20px 0;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.stat-icon {
    font-size: 36px;
}
.stat-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
}
.stat-content p {
    margin: 5px 0 0 0;
    color: #757575;
    font-size: 14px;
}
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
</style>
