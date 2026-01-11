<?php
// Debug script to check tsn_donations table
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;

echo "Checking tsn_donations table...\n";
$donations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsn_donations ORDER BY id DESC LIMIT 10");

if (empty($donations)) {
    echo "No donations found.\n";
} else {
    foreach ($donations as $d) {
        echo "ID: " . $d->id . " | Order: " . $d->order_id . " | Event ID: " . ($d->event_id ? $d->event_id : 'NULL') . " | Amount: " . $d->amount . " | Name: " . $d->donor_name . "\n";
    }
}

echo "\nChecking event IDs...\n";
$events = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}tsn_events");
foreach ($events as $e) {
    echo "Event ID: " . $e->id . " - " . $e->title . "\n";
}
