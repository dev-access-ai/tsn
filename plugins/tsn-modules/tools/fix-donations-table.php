<?php
/**
 * Fix Donations Table
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'tsn_donations';

echo "Attempting to fix table: $table\n";

// Check if column exists first
$col_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'order_id'");
if (empty($col_exists)) {
    echo "Adding order_id column...\n";
    $sql = "ALTER TABLE $table ADD COLUMN order_id bigint(20) UNSIGNED NOT NULL DEFAULT 0 AFTER id";
    $result = $wpdb->query($sql);
    if ($result === false) {
        echo "Error adding column: " . $wpdb->last_error . "\n";
    } else {
        echo "Column added successfully.\n";
        
        // Add index
        $wpdb->query("ALTER TABLE $table ADD INDEX order_id (order_id)");
        echo "Index added.\n";
    }
} else {
    echo "Column order_id already exists.\n";
}

echo "Done.\n";
