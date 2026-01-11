<?php
/**
 * Check Volunteer Table
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'tsn_event_volunteers';

echo "Checking table: $table\n";

if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
    echo "Table does not exist!\n";
    exit;
}

$columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
foreach ($columns as $col) {
    echo " - " . $col->Field . " (" . $col->Type . ")\n";
}
