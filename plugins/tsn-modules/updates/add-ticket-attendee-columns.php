<?php
/**
 * Database Migration: Add Individual Ticket Attendee Details
 * 
 * Adds columns to tsn_tickets table to store individual attendee information
 * Run this once to update the schema
 *
 * @package TSN_Modules
 */

// Basic WP Load (Standalone Script)
$wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    // Fallback for different directory structures
    $wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('Error: Could not find wp-load.php. Please place this file in wp-content/plugins/tsn-modules/updates/');
    }
}

function tsn_add_ticket_attendee_columns() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'tsn_tickets';
    
    // Check if columns already exist
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_column($columns, 'Field');
    
    $sql_statements = array();
    
    // Add attendee_name if not exists
    if (!in_array('attendee_name', $column_names)) {
        $sql_statements[] = "ALTER TABLE `$table_name` ADD COLUMN `attendee_name` VARCHAR(255) NULL AFTER `attendee_email`";
    }
    
    // Add attendee_age if not exists
    if (!in_array('attendee_age', $column_names)) {
        $sql_statements[] = "ALTER TABLE `$table_name` ADD COLUMN `attendee_age` INT NULL AFTER `attendee_name`";
    }
    
    // Add attendee_gender if not exists
    if (!in_array('attendee_gender', $column_names)) {
        $sql_statements[] = "ALTER TABLE `$table_name` ADD COLUMN `attendee_gender` ENUM('Male', 'Female', 'Other') NULL AFTER `attendee_age`";
    }
    
    // Execute SQL statements
    $results = array();
    foreach ($sql_statements as $sql) {
        $result = $wpdb->query($sql);
        $results[] = array(
            'sql' => $sql,
            'success' => $result !== false,
            'error' => $wpdb->last_error
        );
    }
    
    return $results;
}

// Auto-run migration if this file is accessed directly by admin
// Run immediately
$results = tsn_add_ticket_attendee_columns();

echo '<div style="padding: 20px; font-family: sans-serif; max-width: 800px; margin: 20px auto; border: 1px solid #ccc; border-radius: 5px;">';
echo '<h2 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">TSN Database Update: Ticket Attendee Columns</h2>';

if (empty($results)) {
    echo '<div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 4px; margin-bottom: 15px;">✓ All columns already exist. Your database is up to date!</div>';
} else {
    foreach ($results as $result) {
        if ($result['success']) {
            echo '<div style="background: #e8f5e9; color: #2e7d32; padding: 10px; margin-bottom: 10px; border-radius: 4px;">✓ Executed: <code>' . esc_html($result['sql']) . '</code></div>';
        } else {
             echo '<div style="background: #ffebee; color: #c62828; padding: 10px; margin-bottom: 10px; border-radius: 4px;">✗ Failed: <code>' . esc_html($result['sql']) . '</code><br><small>Error: ' . esc_html($result['error']) . '</small></div>';
        }
    }
}

echo '<p>You can now delete this file from your server.</p>';
echo '</div>';
