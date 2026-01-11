<?php
// Restore missing donations from orders
define('WP_USE_THEMES', false);
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;

echo "Scanning for orphaned donation orders...\n";

// Find orders with type 'donation' that do NOT have a corresponding entry in tsn_donations
$orphaned_orders = $wpdb->get_results("
    SELECT o.* 
    FROM {$wpdb->prefix}tsn_orders o
    LEFT JOIN {$wpdb->prefix}tsn_donations d ON o.id = d.order_id
    WHERE o.order_type = 'donation' 
    AND d.id IS NULL
");

if (empty($orphaned_orders)) {
    echo "No orphaned donation orders found.\n";
    exit;
}

echo "Found " . count($orphaned_orders) . " orphaned orders.\n\n";

foreach ($orphaned_orders as $o) {
    echo "Restoring Donation for Order #{$o->id} ({$o->order_number})...\n";
    
    // Attempt to reconstruct donation data from order
    $donation_data = array(
        'order_id' => $o->id,
        'donation_id' => $o->order_number, // Use order number as donation ID
        'cause_id' => null, // Cannot recover if not saved elsewhere
        'event_id' => $o->event_id,
        'amount' => $o->total,
        'donor_name' => $o->buyer_name,
        'donor_email' => $o->buyer_email,
        'donor_phone' => $o->buyer_phone,
        'anonymous' => 0, // Defaulting to 0
        'comments' => $o->comments, // Assuming notes field held the message
        'created_at' => $o->created_at // Preserve original timestamp if possible, but schema defaults to current
    );
    
    // Insert into tsn_donations
    $result = $wpdb->insert(
        $wpdb->prefix . 'tsn_donations',
        $donation_data,
        array('%d', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result) {
        $new_id = $wpdb->insert_id;
        echo "  -> SUCCESS: Created Donation #{$new_id}\n";
        
        // Update timestamp to match order (since insert defaults to NOW())
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tsn_donations SET created_at = %s WHERE id = %d",
            $o->created_at,
            $new_id
        ));
        
    } else {
        echo "  -> FAILED: " . $wpdb->last_error . "\n";
    }
}

echo "\nDone.\n";
