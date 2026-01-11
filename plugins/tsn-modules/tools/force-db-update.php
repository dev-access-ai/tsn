<?php
/**
 * Force DB Update
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

// Check admin
// if (!current_user_can('manage_options')) {
//     die('Access denied');
// }

echo "Updating database schema...\n";

require_once TSN_MODULES_PATH . 'includes/class-tsn-database.php';
TSN_Database::create_tables();

echo "Done!\n";
