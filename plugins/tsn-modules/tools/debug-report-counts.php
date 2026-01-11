<?php
/**
 * Debug Ticket Reports
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;

echo "DEBUGGING TICKET REPORTS\n";
echo "========================\n";

// Get all events
$events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsn_events WHERE status != 'archived'");

foreach ($events as $event) {
    echo "\nEVENT: " . $event->title . " (ID: " . $event->id . ")\n";
    echo "------------------------------------------------\n";
    
    // Get ticket types
    $ticket_types = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types WHERE event_id = %d",
        $event->id
    ));
    
    if (empty($ticket_types)) {
        echo "No ticket types found for this event.\n";
        continue;
    }
    
    foreach ($ticket_types as $tt) {
        echo "Ticket Type: " . $tt->name . " (ID: " . $tt->id . ")\n";
        
        // 1. Check raw tickets count
        $raw_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tsn_tickets WHERE ticket_type_id = %d",
            $tt->id
        ));
        echo "  - Total tickets in DB: $raw_count\n";
        
        // 2. Run the specific query from reports.php
        $report_count = $wpdb->get_var($wpdb->prepare(
             "SELECT COUNT(t.id) 
              FROM {$wpdb->prefix}tsn_tickets t 
              JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
              WHERE t.ticket_type_id = %d AND (o.status = 'completed' OR o.status = 'paid')",
             $tt->id
        ));
        echo "  - Report Query Count (completed/paid): $report_count\n";
        
        // 3. Check order statuses for these tickets
        $statuses = $wpdb->get_results($wpdb->prepare(
            "SELECT o.status, COUNT(*) as count
             FROM {$wpdb->prefix}tsn_tickets t 
             JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
             WHERE t.ticket_type_id = %d
             GROUP BY o.status",
            $tt->id
        ));
        
        if (!empty($statuses)) {
            echo "  - Breakdown by Order Status:\n";
            foreach ($statuses as $s) {
                echo "    - " . $s->status . ": " . $s->count . "\n";
            }
        } else {
            echo "  - No associated orders found.\n";
        }
    }
}
