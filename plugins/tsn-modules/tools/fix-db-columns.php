<?php
/**
 * Fix DB Columns
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'tsn_events';

echo "Attempting to add missing columns to: $table\n";

$queries = array(
    "ALTER TABLE $table ADD COLUMN enable_ticketing tinyint(1) NOT NULL DEFAULT 1",
    "ALTER TABLE $table ADD COLUMN enable_volunteering tinyint(1) NOT NULL DEFAULT 0",
    "ALTER TABLE $table ADD COLUMN enable_donations tinyint(1) NOT NULL DEFAULT 0"
);

foreach ($queries as $sql) {
    echo "Running: $sql\n";
    $result = $wpdb->query($sql);
    if ($result === false) {
        echo "Error: " . $wpdb->last_error . "\n";
    } else {
        echo "Success.\n";
    }
}

echo "Done.\n";
