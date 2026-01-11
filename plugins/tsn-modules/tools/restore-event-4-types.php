<?php
/**
 * Restore Missing Ticket Types for Event 4
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;
$event_id = 4;

echo "Restoring ticket types for Event $event_id...\n";

// Check if types already exist
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}tsn_event_ticket_types WHERE event_id = %d",
    $event_id
));

if ($exists > 0) {
    echo "Ticket types already exist. No action taken.\n";
    exit;
}

// Check for orphans to see which ID they need
$orphan_type_id = $wpdb->get_var($wpdb->prepare(
    "SELECT t.ticket_type_id 
     FROM {$wpdb->prefix}tsn_tickets t
     JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
     WHERE o.event_id = %d 
     LIMIT 1",
    $event_id
));

if ($orphan_type_id) {
    echo "Found orphaned tickets pointing to Type ID: $orphan_type_id\n";
    echo "Restoring this Type ID...\n";
    
    // Insert with specific ID
    $wpdb->insert(
        $wpdb->prefix . 'tsn_event_ticket_types',
        array(
            'id' => $orphan_type_id,
            'event_id' => $event_id,
            'name' => 'Standard Ticket (Restored)',
            'attendees_per_ticket' => 1,
            'description' => 'Restored ticket type',
            'member_price' => 20.00,
            'non_member_price' => 25.00,
            'capacity' => 100,
            'sold' => 0, // Will be updated by recalc script
            'display_order' => 0
        ),
        array('%d', '%d', '%s', '%d', '%s', '%f', '%f', '%d', '%d', '%d')
    );
     echo "Ticket Type Restored.\n";
} else {
    echo "No orphans found. Creating default ticket type.\n";
    $wpdb->insert(
        $wpdb->prefix . 'tsn_event_ticket_types',
        array(
            'event_id' => $event_id,
            'name' => 'General Admission',
            'attendees_per_ticket' => 1,
            'description' => '',
            'member_price' => 0.00,
            'non_member_price' => 0.00,
            'capacity' => 100,
            'sold' => 0,
            'display_order' => 0
        ),
        array('%d', '%s', '%d', '%s', '%f', '%f', '%d', '%d', '%d')
    );
    echo "Default Ticket Type Created.\n";
}
