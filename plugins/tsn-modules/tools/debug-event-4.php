<?php
/**
 * Debug Event 4
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;

echo "DEBUGGING EVENT ID 4\n";
echo "====================\n";

$event = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}tsn_events WHERE id = 4");

if (!$event) {
    echo "Event ID 4 NOT FOUND.\n";
    // List all events
    echo "Listing all events:\n";
    $all = $wpdb->get_results("SELECT id, title, status FROM {$wpdb->prefix}tsn_events");
    print_r($all);
} else {
    echo "Event Found: " . $event->title . " (Status: " . $event->status . ")\n";
    
    // Ticket Types
    $ticket_types = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types WHERE event_id = 4"
    ));
    
    echo "Ticket Types Count: " . count($ticket_types) . "\n";
    foreach ($ticket_types as $tt) {
        echo "- " . $tt->name . " (ID: " . $tt->id . ", Capacity: " . $tt->capacity . ")\n";
        
        // Orders
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.id, o.status, COUNT(t.id) as tickets 
             FROM {$wpdb->prefix}tsn_orders o
             JOIN {$wpdb->prefix}tsn_tickets t ON t.order_id = o.id
             WHERE t.ticket_type_id = %d
             GROUP BY o.id",
            $tt->id
        ));
        
        echo "  - Orders: " . count($orders) . "\n";
        foreach ($orders as $o) {
            echo "    - Order " . $o->id . ": Status = " . $o->status . ", Tickets = " . $o->tickets . "\n";
        }
    }
    
    // Check for orphaned tickets
    echo "\nOrphaned Tickets Check:\n";
    $orphans = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.ticket_type_id, o.id as order_id 
         FROM {$wpdb->prefix}tsn_tickets t
         JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
         WHERE o.event_id = 4 
         AND t.ticket_type_id NOT IN (SELECT id FROM {$wpdb->prefix}tsn_event_ticket_types)"
    ));
    
    if (!empty($orphans)) {
        echo "FOUND " . count($orphans) . " ORPHANED TICKETS!\n";
        foreach ($orphans as $orphan) {
            echo "- Ticket " . $orphan->id . " (Type ID: " . $orphan->ticket_type_id . ") in Order " . $orphan->order_id . "\n";
        }
    } else {
        echo "No orphaned tickets found.\n";
    }
}
