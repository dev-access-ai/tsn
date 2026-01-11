<?php
// Add registration_mode to tsn_events
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;
$table_name = $wpdb->prefix . 'tsn_events';

// Check if column exists
$row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '$table_name' AND COLUMN_NAME = 'registration_mode'");

if (empty($row)) {
    echo "Adding registration_mode column to $table_name...\n";
    $sql = "ALTER TABLE $table_name ADD COLUMN registration_mode VARCHAR(20) DEFAULT 'ticket' AFTER status";
    $wpdb->query($sql);
    echo "Column added successfully.\n";
} else {
    echo "Column registration_mode already exists.\n";
}
