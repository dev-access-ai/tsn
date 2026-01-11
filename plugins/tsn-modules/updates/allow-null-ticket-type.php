<?php
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

global $wpdb;

echo "Updating tsn_order_items schema...\n";

// Modify ticket_type_id to be nullable
$table_name = $wpdb->prefix . 'tsn_order_items';
$sql = "ALTER TABLE $table_name MODIFY ticket_type_id BIGINT(20) UNSIGNED NULL";

if ($wpdb->query($sql) === false) {
    echo "Error modifying table: " . $wpdb->last_error . "\n";
} else {
    echo "Success: ticket_type_id is now nullable.\n";
}

// Verify
$cols = $wpdb->get_results("DESCRIBE $table_name");
foreach ($cols as $col) {
    if ($col->Field === 'ticket_type_id') {
        echo "Verification: ticket_type_id Null=" . $col->Null . "\n";
    }
}
