<?php
/**
 * Fix Event 3 Tickets
 * Map old/orphaned ticket type IDs to new valid ones.
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

global $wpdb;

echo "FIXING EVENT 3 TICKETS\n";
echo "======================\n";

// Mappings: Old ID => New ID
// Based on debug output:
// Orphan ID 3 (Adult?) -> New ID 34 (Adult)
// Orphan ID 1 (Kid?) -> New ID 35 (Kid)
// Orphan ID 2 (Parent?) -> New ID 36 (Parent)

// Mappings: Old ID => New ID
// 7 -> 34 (Adult)
// 8 -> 35 (Kid)
// 9 -> 36 (Parent) (Guessing mapping, better than missing)

$mapping = array(
    7 => 34,
    8 => 35,
    9 => 36
);

foreach ($mapping as $old_id => $new_id) {
    echo "Updating tickets with Type ID $old_id to $new_id...\n";
    
    // Check count first
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tsn_tickets WHERE ticket_type_id = %d",
        $old_id
    ));
    
    if ($count > 0) {
        $result = $wpdb->update(
            $wpdb->prefix . 'tsn_tickets',
            array('ticket_type_id' => $new_id),
            array('ticket_type_id' => $old_id),
            array('%d'),
            array('%d')
        );
        echo "Updated $result tickets.\n";
    } else {
        echo "No tickets found for Old ID $old_id.\n";
    }
}

echo "\nDone. Re-checking Event 3...\n";

// Helper check
$ticket_types = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}tsn_event_ticket_types WHERE event_id = 3");
foreach ($ticket_types as $tt) {
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(t.id) 
         FROM {$wpdb->prefix}tsn_tickets t 
         JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
         WHERE t.ticket_type_id = %d AND (o.status = 'completed' OR o.status = 'paid')",
        $tt->id
    ));
    echo "- " . $tt->name . " (ID " . $tt->id . "): Report Count = $count\n";
}
