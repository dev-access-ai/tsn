<?php
/**
 * Combined Database Migration: Add description AND attendees_per_ticket columns
 * 
 * Run this once to add both missing fields to ticket types table
 * Access: /wp-content/plugins/tsn-modules/updates/add-ticket-fields.php?run_migration=1
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

if (!isset($_GET['run_migration']) || $_GET['run_migration'] != '1') {
    die('Add ?run_migration=1 to URL to run this migration');
}

global $wpdb;
$table_name = $wpdb->prefix . 'tsn_event_ticket_types';

echo "<h2>Ticket Types Table Migration</h2>";

// Check and add description column
$description_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'description'");

if (empty($description_exists)) {
    $sql = "ALTER TABLE {$table_name} ADD COLUMN description TEXT NULL AFTER name";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "✅ SUCCESS: Added 'description' column<br>";
    } else {
        echo "❌ ERROR: Failed to add description column. Error: " . $wpdb->last_error . "<br>";
    }
} else {
    echo "ℹ️ INFO: Column 'description' already exists<br>";
}

// Check and add attendees_per_ticket column
$attendees_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'attendees_per_ticket'");

if (empty($attendees_exists)) {
    $sql = "ALTER TABLE {$table_name} ADD COLUMN attendees_per_ticket INT NOT NULL DEFAULT 1 COMMENT 'Number of attendees this ticket covers' AFTER name";
    $result = $wpdb->query($sql);
    
    if ($result !== false) {
        echo "✅ SUCCESS: Added 'attendees_per_ticket' column<br>";
    } else {
        echo "❌ ERROR: Failed to add attendees_per_ticket column. Error: " . $wpdb->last_error . "<br>";
    }
} else {
    echo "ℹ️ INFO: Column 'attendees_per_ticket' already exists<br>";
}

echo "<br><strong>Migration complete!</strong><br><br>";
echo "<a href='" . admin_url('admin.php?page=tsn-events') . "' class='button button-primary'>Go to Events</a>";
echo " ";
echo "<a href='" . admin_url('admin.php?page=tsn-add-event') . "' class='button'>Create New Event</a>";
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        padding: 40px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
    }
    .button {
        display: inline-block;
        padding: 10px 20px;
        background: #0073aa;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        margin-top: 20px;
    }
    .button-primary {
        background: #00a0d2;
    }
</style>
