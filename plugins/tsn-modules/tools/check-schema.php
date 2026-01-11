<?php
// Check schema of tsn_donations
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;

echo "Database Prefix: " . $wpdb->prefix . "\n";
echo "Table: " . $wpdb->prefix . "tsn_donations\n";
echo "Columns:\n";


echo "\nTable: " . $wpdb->prefix . "tsn_orders\n";
echo "Columns:\n";
$columns = $wpdb->get_results("DESCRIBE " . $wpdb->prefix . "tsn_orders");
foreach ($columns as $col) {
    echo $col->Field . " (" . $col->Type . ")\n";
}


echo "\nTable: " . $wpdb->prefix . "tsn_order_items\n";
$create = $wpdb->get_row("SHOW CREATE TABLE " . $wpdb->prefix . "tsn_order_items", ARRAY_N);
echo $create[1] . "\n";

echo "\nChecking pages...\n";
$pages = array('ticket-confirmation', 'payment-success');
foreach ($pages as $slug) {
    $exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}posts WHERE post_name = %s AND post_type = 'page'", $slug));
    echo "Page '/$slug': " . ($exists ? "EXISTS (ID: $exists)" : "MISSING") . "\n";
}
