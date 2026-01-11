<?php
/**
 * Debug Event 3 IDs ONLY
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');
global $wpdb;

$type_ids = $wpdb->get_results($wpdb->prepare(
    "SELECT t.ticket_type_id, COUNT(*) as count 
     FROM {$wpdb->prefix}tsn_tickets t
     JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
     WHERE o.event_id = 3
     GROUP BY t.ticket_type_id"
));

echo "FOUND TYPE IDs:\n";
foreach ($type_ids as $tid) {
    echo "ID: " . $tid->ticket_type_id . " (Count: " . $tid->count . ")\n";
}

