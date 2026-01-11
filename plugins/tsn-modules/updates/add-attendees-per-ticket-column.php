<?php
/**
 * Database Migration: Add attendees_per_ticket column to ticket types table
 * 
 * This allows ticket types to specify how many people each ticket covers
 * Example: Adult=1, Family=4, Couple=2
 * 
 * Access: /wp-content/plugins/tsn-modules/updates/add-attendees-per-ticket-column.php?run_migration=1
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

if (!isset($_GET['run_migration']) || $_GET['run_migration'] != '1') {
    die('Add ?run_migration=1 to URL to run this migration');
}

global $wpdb;
$table_name = $wpdb->prefix . 'tsn_event_ticket_types';

// Check if attendees_per_ticket column already exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'attendees_per_ticket'");

if (empty($column_exists)) {
    // Add attendees_per_ticket column (default 1 person per ticket)
    $sql = "ALTER TABLE {$table_name} ADD COLUMN attendees_per_ticket INT NOT NULL DEFAULT 1 COMMENT 'Number of attendees this ticket covers' AFTER name";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "✅ SUCCESS: Added 'attendees_per_ticket' column to {$table_name}<br>";
    } else {
        echo "❌ ERROR: Failed to add column. Error: " . $wpdb->last_error . "<br>";
    }
} else {
    echo "ℹ️ INFO: Column 'attendees_per_ticket' already exists in {$table_name}<br>";
}

echo "<br>Migration complete!<br>";
echo "<a href='" . admin_url('admin.php?page=tsn-events') . "'>Go back to Events</a>";
