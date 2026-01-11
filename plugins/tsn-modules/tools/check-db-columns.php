<?php
/**
 * Check DB Columns
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'tsn_events';

echo "Checking columns for table: $table\n";

$columns = $wpdb->get_results("SHOW COLUMNS FROM $table");

$found_ticketing = false;
$found_volunteering = false;
$found_donations = false;

foreach ($columns as $col) {
    echo " - " . $col->Field . " (" . $col->Type . ")\n";
    if ($col->Field === 'enable_ticketing') $found_ticketing = true;
    if ($col->Field === 'enable_volunteering') $found_volunteering = true;
    if ($col->Field === 'enable_donations') $found_donations = true;
}

echo "\nResults:\n";
echo "enable_ticketing: " . ($found_ticketing ? "FOUND" : "MISSING") . "\n";
echo "enable_volunteering: " . ($found_volunteering ? "FOUND" : "MISSING") . "\n";
echo "enable_donations: " . ($found_donations ? "FOUND" : "MISSING") . "\n";
