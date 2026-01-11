<?php
// Debug script for Donation Data Integrity
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;

echo "=== Event 4 Diagnostics ===\n";
$event_id = 4;

// 1. Check Orders for Event 4
echo "\n[tsn_orders] Checking orders for Event 4:\n";
$orders = $wpdb->get_results($wpdb->prepare(
    "SELECT id, order_number, order_type, total, status, event_id, created_at 
     FROM {$wpdb->prefix}tsn_orders 
     WHERE event_id = %d", 
    $event_id
));

$donation_orders_count = 0;
if ($orders) {
    foreach ($orders as $o) {
        echo "Order #{$o->id} ({$o->order_number}): Type={$o->order_type}, Status={$o->status}, Total={$o->total}\n";
        if ($o->order_type === 'donation') {
            $donation_orders_count++;
        }
    }
} else {
    echo "No orders found for Event 4.\n";
}
echo "Total Donation Orders found in tsn_orders: $donation_orders_count\n";

// 2. Check Donations Table for Event 4
echo "\n[tsn_donations] Checking donations with event_id = 4:\n";
$donations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tsn_donations WHERE event_id = %d",
    $event_id
));

if ($donations) {
    foreach ($donations as $d) {
        echo "Donation #{$d->id}: Order ID={$d->order_id}, Amount={$d->amount}, Name={$d->donor_name}\n";
    }
} else {
    echo "No records found in tsn_donations for event_id = 4.\n";
}

// 3. Mismatch Check
echo "\n[Mismatch Check] Looking for Donation Orders without Donation Records:\n";
foreach ($orders as $o) {
    if ($o->order_type === 'donation') {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tsn_donations WHERE order_id = %d",
            $o->id
        ));
        
        if (!$exists) {
            echo "WARNING: Order #{$o->id} is type 'donation' but has NO record in tsn_donations table!\n";
            // Check if it exists but missing event_id
             $orphaned = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_donations WHERE order_id = %d",
                $o->id
            ));
            if ($orphaned) {
                echo "  -> Found in tsn_donations but event_id is " . ($orphaned->event_id ? $orphaned->event_id : 'NULL') . "\n";
            }
        } else {
            // Check event_id match
             $d = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_donations WHERE order_id = %d",
                $o->id
            ));
            if ($d->event_id != $event_id) {
                echo "WARNING: Order #{$o->id} is linked to Event $event_id, but Donation #{$d->id} is linked to Event " . ($d->event_id ? $d->event_id : 'NULL') . "\n";
            }
        }
    }
}

// 4. General Table Dump
echo "\n[Dump] All tsn_donations (Last 5):\n";
$all_donations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsn_donations ORDER BY id DESC LIMIT 5");
print_r($all_donations);
