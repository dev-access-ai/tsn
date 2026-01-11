<?php
/**
 * TSN Production Export Generator
 * 
 * Creates a clean database export for production deployment
 * - Exports WordPress database
 * - Empties TSN tables (keeps structure)
 * - Updates URLs from localhost to production
 * - Downloads clean SQL file
 * - DOES NOT modify local database
 * 
 * Access: http://localhost/tsn/wp-content/plugins/tsn-modules/tools/production-export.php
 * 
 * @package TSN_Modules
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator.');
}

global $wpdb;

// Get configuration
$production_url = isset($_POST['production_url']) ? trim($_POST['production_url']) : '';
$local_url = home_url();

// TSN tables to empty (keep structure, remove data)
$tsn_tables = array(
    $wpdb->prefix . 'tsn_members',
    $wpdb->prefix . 'tsn_member_transactions',
    $wpdb->prefix . 'tsn_events',
    $wpdb->prefix . 'tsn_event_ticket_types',
    $wpdb->prefix . 'tsn_orders',
    $wpdb->prefix . 'tsn_order_items',
    $wpdb->prefix . 'tsn_tickets',
    $wpdb->prefix . 'tsn_scans_audit',
    $wpdb->prefix . 'tsn_donations',
    $wpdb->prefix . 'tsn_donation_causes'
);

// Handle export generation
if (isset($_POST['generate_export']) && check_admin_referer('tsn_export_nonce')) {
    
    if (empty($production_url)) {
        $error = "Please enter your production domain URL.";
    } else {
        // Sanitize URL
        $production_url = rtrim($production_url, '/');
        
        // Get all tables
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        
        // Start building SQL
        $sql_output = "";
        $sql_output .= "-- TSN Production Database Export\n";
        $sql_output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql_output .= "-- Local URL: $local_url\n";
        $sql_output .= "-- Production URL: $production_url\n";
        $sql_output .= "-- Note: TSN tables are empty (structure only)\n\n";
        $sql_output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_output .= "SET time_zone = \"+00:00\";\n\n";
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            $sql_output .= "\n-- Table structure for `$table_name`\n";
            $sql_output .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $sql_output .= $create_table[1] . ";\n\n";
            
            // Check if this is a TSN table that should be empty
            if (in_array($table_name, $tsn_tables)) {
                $sql_output .= "-- TSN Table: Keeping structure only, no data exported\n\n";
                continue; // Skip data export for TSN tables
            }
            
            // Export data for non-TSN tables
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            
            if ($rows) {
                $sql_output .= "-- Data for table `$table_name`\n";
                
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array();
                    
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            // Replace local URL with production URL
                            $value = str_replace($local_url, $production_url, $value);
                            $values[] = "'" . $wpdb->_real_escape($value) . "'";
                        }
                    }
                    
                    $sql_output .= "INSERT INTO `$table_name` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                
                $sql_output .= "\n";
            }
        }
        
        // Set headers for download
        $filename = 'tsn-production-' . date('Y-m-d-His') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql_output));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo $sql_output;
        exit;
    }
}

// Get current table counts
$counts = array();
$total_records = 0;
foreach ($tsn_tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $counts[$table] = $count ? $count : 0;
    $total_records += $counts[$table];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Database Export</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            color: #0c5460;
            margin-bottom: 15px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #0c5460;
        }
        .info-box li {
            margin: 8px 0;
        }
        .form-group {
            margin-bottom: 25px;
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
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #11998e;
            box-shadow: 0 0 0 3px rgba(17,153,142,0.1);
        }
        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
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
        }
        .count {
            font-weight: bold;
            color: #11998e;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #11998e;
            color: white;
        }
        .btn-primary:hover {
            background: #0d7a6f;
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
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .error-box {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            color: #721c24;
        }
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Production Database Export</h1>
            <p>Create clean database export for production deployment</p>
        </div>
        
        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error-box">
                    <strong>❌ Error:</strong> <?php echo esc_html($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>✨ What This Tool Does</h3>
                <ul>
                    <li><strong>Exports your entire WordPress database</strong></li>
                    <li><strong>Empties TSN tables</strong> (members, events, donations) - keeps structure only</li>
                    <li><strong>Updates URLs</strong> from localhost to your production domain</li>
                    <li><strong>Downloads clean SQL file</strong> ready for production import</li>
                    <li><strong>Does NOT modify your local database</strong> - you can keep working!</li>
                </ul>
            </div>
            
            <div class="highlight">
                <strong>📊 Current Local Data:</strong> Your local database has <strong><?php echo number_format($total_records); ?> test records</strong> in TSN tables. These will NOT be included in the export (structure only).
            </div>
            
            <h2>Configuration</h2>
            
            <form method="post">
                <?php wp_nonce_field('tsn_export_nonce'); ?>
                
                <div class="form-group">
                    <label for="production_url">Production Domain URL *</label>
                    <input 
                        type="url" 
                        id="production_url" 
                        name="production_url" 
                        placeholder="https://telugusamiti.org" 
                        required
                        value="<?php echo esc_attr($production_url); ?>">
                    <p class="help-text">
                        Enter your production website URL (without trailing slash).<br>
                        Current local URL: <code><?php echo esc_html($local_url); ?></code>
                    </p>
                </div>
                
                <h3>TSN Tables Status</h3>
                <p>These tables will be exported with structure only (no data):</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Local Records</th>
                            <th>Export Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tsn_tables as $table): ?>
                            <tr>
                                <td><code><?php echo esc_html($table); ?></code></td>
                                <td class="count"><?php echo number_format($counts[$table]); ?></td>
                                <td>✓ Structure only</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="success-box">
                    <strong>✅ Safe Operation:</strong> Your local database remains unchanged. This tool only creates an export file.
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo admin_url(); ?>" class="btn btn-secondary">← Back to Dashboard</a>
                    <button type="submit" name="generate_export" class="btn btn-primary">
                        📥 Generate & Download Export
                    </button>
                </div>
            </form>
            
            <div class="info-box" style="margin-top: 30px;">
                <h3>📋 After Export</h3>
                <ul>
                    <li>Upload the SQL file to your production server</li>
                    <li>Import using phpMyAdmin or command line</li>
                    <li>Update <code>wp-config.php</code> with production database credentials</li>
                    <li>Set <code>WP_DEBUG</code> to <code>false</code></li>
                    <li>Test the site thoroughly</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
