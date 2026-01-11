<?php
/**
 * TSN Database Cleanup Tool
 * 
 * WARNING: This tool will DELETE ALL DATA from TSN tables!
 * Use this to prepare for fresh production deployment.
 * 
 * Access: http://localhost/tsn/wp-content/plugins/tsn-modules/tools/cleanup-database.php
 * 
 * @package TSN_Modules
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator.');
}

// Prevent accidental execution on production
$is_production = !in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) && 
                 strpos($_SERVER['HTTP_HOST'], 'localhost') === false &&
                 !WP_DEBUG;

if ($is_production && !isset($_GET['allow_production'])) {
    wp_die('
        <h1>⚠️ Production Environment Detected</h1>
        <p>This tool is designed for local development cleanup.</p>
        <p>If you really want to run this on production, add <code>?allow_production=1</code> to the URL.</p>
        <p><strong>WARNING: This will delete ALL TSN data!</strong></p>
        <p><a href="' . admin_url() . '">← Go to Dashboard</a></p>
    ');
}

global $wpdb;

// Define tables to clean
$tables = array(
    $wpdb->prefix . 'tsn_members' => 'Members',
    $wpdb->prefix . 'tsn_member_transactions' => 'Member Transactions',
    $wpdb->prefix . 'tsn_events' => 'Events',
    $wpdb->prefix . 'tsn_event_ticket_types' => 'Event Ticket Types',
    $wpdb->prefix . 'tsn_orders' => 'Orders',
    $wpdb->prefix . 'tsn_order_items' => 'Order Items',
    $wpdb->prefix . 'tsn_tickets' => 'Tickets',
    $wpdb->prefix . 'tsn_scans_audit' => 'Scan Audit',
    $wpdb->prefix . 'tsn_donations' => 'Donations',
    $wpdb->prefix . 'tsn_donation_causes' => 'Donation Causes'
);

// Get current counts
$counts = array();
foreach ($tables as $table => $label) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $counts[$table] = $count ? $count : 0;
}

// Handle cleanup action
$cleaned = false;
$results = array();

if (isset($_POST['confirm_cleanup']) && check_admin_referer('tsn_cleanup_nonce')) {
    foreach ($tables as $table => $label) {
        $deleted = $wpdb->query("TRUNCATE TABLE $table");
        $results[$label] = $deleted !== false ? 'success' : 'error';
    }
    
    // Clear transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tsn_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tsn_%'");
    
    // Clear any uploaded media tags (optional)
    // You can add more cleanup here if needed
    
    $cleaned = true;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TSN Database Cleanup Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: #f0f0f1;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning-box ul {
            margin-left: 20px;
            color: #856404;
        }
        .warning-box li {
            margin: 8px 0;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .count {
            font-weight: bold;
            color: #667eea;
        }
        .count.zero {
            color: #28a745;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .checkbox-wrapper label {
            font-weight: 600;
            color: #333;
            cursor: pointer;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .info-box {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            border-radius: 6px;
            padding: 20px;
            margin-top: 30px;
        }
        .info-box h3 {
            color: #0c5460;
            margin-bottom: 10px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧹 TSN Database Cleanup Tool</h1>
            <p>Remove all test data and prepare for fresh production deployment</p>
        </div>
        
        <div class="content">
            <?php if ($cleaned): ?>
                <div class="success-box">
                    <h3>✅ Cleanup Complete!</h3>
                    <p>All TSN data has been successfully removed from the database.</p>
                </div>
                
                <h2>Cleanup Results</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $label => $status): ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td>
                                    <?php if ($status === 'success'): ?>
                                        <span class="status status-success">✓ Cleaned</span>
                                    <?php else: ?>
                                        <span class="status status-error">✗ Error</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td>Transients</td>
                            <td><span class="status status-success">✓ Cleared</span></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="info-box">
                    <h3>📋 Next Steps for Production Deployment</h3>
                    <ul>
                        <li><strong>Export the database:</strong> Use phpMyAdmin or command line to export your clean database</li>
                        <li><strong>Package your files:</strong> Zip your WordPress installation</li>
                        <li><strong>Upload to server:</strong> Transfer files via FTP/SFTP</li>
                        <li><strong>Import database:</strong> Create new database on server and import</li>
                        <li><strong>Update wp-config.php:</strong> Update database credentials for production</li>
                        <li><strong>Update URLs:</strong> Use Search & Replace to update URLs from localhost to production domain</li>
                        <li><strong>Set WP_DEBUG to false:</strong> Disable debug mode in production</li>
                        <li><strong>Test everything:</strong> Verify all functionality works on production</li>
                    </ul>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">← Back to Dashboard</a>
                    <a href="?" class="btn btn-secondary">Run Again</a>
                </div>
                
            <?php else: ?>
                <div class="warning-box">
                    <h3>⚠️ Warning: This Action Cannot Be Undone!</h3>
                    <p><strong>This tool will permanently delete:</strong></p>
                    <ul>
                        <li>All member records and transactions</li>
                        <li>All events and ticket types</li>
                        <li>All orders, tickets, and registrations</li>
                        <li>All donations and causes</li>
                        <li>All scan audit logs</li>
                        <li>All TSN-related transients and cache</li>
                    </ul>
                    <p style="margin-top: 15px;"><strong>Database tables will remain (structure preserved), but all content will be deleted.</strong></p>
                </div>
                
                <h2>Current Database Status</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Current Record Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_records = 0;
                        foreach ($counts as $table => $count): 
                            $total_records += $count;
                        ?>
                            <tr>
                                <td><?php echo esc_html($tables[$table]); ?></td>
                                <td class="count <?php echo $count == 0 ? 'zero' : ''; ?>">
                                    <?php echo number_format($count); ?> records
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td>TOTAL</td>
                            <td class="count"><?php echo number_format($total_records); ?> records</td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if ($total_records > 0): ?>
                    <form method="post" id="cleanup-form">
                        <?php wp_nonce_field('tsn_cleanup_nonce'); ?>
                        
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="confirm-checkbox" required>
                            <label for="confirm-checkbox">
                                I understand this will permanently delete <?php echo number_format($total_records); ?> records and cannot be undone
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">← Cancel</a>
                            <button type="submit" name="confirm_cleanup" class="btn btn-danger" onclick="return confirm('Are you absolutely sure? This cannot be undone!');">
                                🗑️ Delete All Data
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="success-box">
                        <h3>✅ Database Already Clean!</h3>
                        <p>No TSN data found. Your database is ready for production deployment.</p>
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">← Back to Dashboard</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Disable submit button until checkbox is checked
    document.addEventListener('DOMContentLoaded', function() {
        var checkbox = document.getElementById('confirm-checkbox');
        var form = document.getElementById('cleanup-form');
        
        if (form && checkbox) {
            var submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
            
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';
                }
            });
        }
    });
    </script>
</body>
</html>
