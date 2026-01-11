<?php
/**
 * Check Payment Success Page
 */
define('WP_USE_THEMES', false);
require_once('c:/xampp/htdocs/tsn/wp-load.php');

$page = get_page_by_path('payment-success');

if ($page) {
    echo "Page 'payment-success' EXISTS (ID: " . $page->ID . ")\n";
    echo "Status: " . $page->post_status . "\n";
} else {
    echo "Page 'payment-success' DOES NOT EXIST.\n";
    
    // Create it?
    $new_page_id = wp_insert_post(array(
        'post_title'    => 'Payment Success',
        'post_name'     => 'payment-success',
        'post_content'  => '<!-- wp:shortcode -->[tsn_payment_success]<!-- /wp:shortcode -->',
        'post_status'   => 'publish',
        'post_type'     => 'page'
    ));
    
    if ($new_page_id && !is_wp_error($new_page_id)) {
        echo "Created 'payment-success' page (ID: $new_page_id)\n";
    } else {
        echo "Failed to create page.\n";
    }
}
