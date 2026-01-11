<?php
/**
 * TSN Database Schema Export Tool
 * 
 * Generates a clean SQL file with CREATE TABLE statements for all TSN tables.
 * Use this output to create the tables on your production site.
 * 
 * Access: http://localhost/tsn/wp-content/plugins/tsn-modules/tools/export-schema.php
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Check permission
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

global $wpdb;

// Define tables to export
$tables = array(
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

$sql_output = "-- TSN Modules Database Schema Export\n";
$sql_output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql_output .= "-- --------------------------------------------------------\n\n";

foreach ($tables as $table) {
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        $sql_output .= "-- Table $table does not exist in local database. Skipping.\n\n";
        continue;
    }

    // Get Create Info
    $row = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_N);
    $create_sql = $row[1];
    
    // Replace current prefix with placeholder if desired, or keep as is.
    // Usually easier to just keep local prefix and let user find/replace if needed, 
    // or typically production uses same 'wp_' prefix.
    
    // Ensure formatting
    $sql_output .= "-- Table structure for table `$table`\n";
    $sql_output .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql_output .= $create_sql . ";\n\n";
}

// Offer as download
$filename = 'tsn-production-schema-' . date('Y-m-d') . '.sql';

if (isset($_GET['download'])) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $sql_output;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>TSN Schema Export</title>
    <style>
        body { font-family: sans-serif; padding: 30px; line-height: 1.6; background: #f0f0f1; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 5px; }
        h1 { margin-top: 0; color: #0073aa; }
        textarea { width: 100%; height: 400px; font-family: monospace; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; }
        .btn { background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
        .btn:hover { background: #005177; }
        .note { background: #fff3cd; padding: 10px; border-left: 4px solid #ffba00; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Database Schema Export</h1>
        
        <div class="note">
            <p><strong>Safe for your Local Data:</strong> This tool only reads the structure. Your local data remains untouched.</p>
            <p><strong>Ready for Production:</strong> Run the SQL below on your production database (via phpMyAdmin > SQL tab) to create clean, empty tables.</p>
        </div>

        <p><strong>Preview SQL:</strong></p>
        <textarea readonly><?php echo esc_textarea($sql_output); ?></textarea>
        
        <a href="?download=1" class="btn">⬇️ Download .sql File</a>
    </div>
</body>
</html>
