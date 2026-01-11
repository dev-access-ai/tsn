<?php
/**
 * Database Migration: Add description column to ticket types table
 * 
 * Run this once to add the description field to ticket types
 * Access: /wp-content/plugins/tsn-modules/updates/add-ticket-description-column.php?run_migration=1
 */

// Load WordPress - adjust path for subdirectory installation
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

if (!isset($_GET['run_migration']) || $_GET['run_migration'] != '1') {
    die('Add ?run_migration=1 to URL to run this migration');
}

global $wpdb;
$table_name = $wpdb->prefix . 'tsn_event_ticket_types';

// Check if description column already exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'description'");

if (empty($column_exists)) {
    // Add description column
    $sql = "ALTER TABLE {$table_name} ADD COLUMN description TEXT NULL AFTER name";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "✅ SUCCESS: Added 'description' column to {$table_name}<br>";
    } else {
        echo "❌ ERROR: Failed to add column. Error: " . $wpdb->last_error . "<br>";
    }
} else {
    echo "ℹ️ INFO: Column 'description' already exists in {$table_name}<br>";
}

echo "<br>Migration complete!<br>";
echo "<a href='" . admin_url('admin.php?page=tsn-events') . "'>Go back to Events</a>";
