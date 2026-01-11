<?php
/**
 * repair_schema_full.php
 * 
 * Runs dbDelta on ALL table definitions to ensure the production database matches 
 * the required schema. This will create missing tables and add missing columns.
 */

// Load WordPress
$wp_load_path = __DIR__ . '/../../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    $wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
}

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die("Could not find wp-load.php");
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

echo "Starting Full Schema Repair...\n";
echo "------------------------------\n";

$tables = array();

// 1. Members
$tables['tsn_members'] = "CREATE TABLE " . $wpdb->prefix . "tsn_members (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id varchar(50) NOT NULL,
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    email varchar(100) NOT NULL,
    phone varchar(20) DEFAULT NULL,
    address text DEFAULT NULL,
    city varchar(100) DEFAULT NULL,
    state varchar(100) DEFAULT NULL,
    country varchar(100) DEFAULT 'USA',
    zip_code varchar(20) DEFAULT NULL,
    membership_type varchar(20) NOT NULL COMMENT 'annual, lifetime, student',
    valid_from date NOT NULL,
    valid_to date DEFAULT NULL COMMENT 'NULL for lifetime',
    status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, inactive, suspended',
    payment_mode varchar(50) DEFAULT NULL COMMENT 'online, offline',
    notes text DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY member_id (member_id),
    KEY email (email),
    KEY membership_type (membership_type),
    KEY status (status),
    KEY valid_to (valid_to)
) $charset_collate;";

// 2. Member Transactions
$tables['tsn_member_transactions'] = "CREATE TABLE " . $wpdb->prefix . "tsn_member_transactions (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id bigint(20) UNSIGNED NOT NULL,
    transaction_id varchar(100) DEFAULT NULL,
    amount decimal(10,2) NOT NULL,
    payment_method varchar(50) DEFAULT NULL COMMENT 'paypal, stripe, cash, cheque, other',
    payment_reference varchar(255) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, completed, failed, refunded',
    transaction_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes text DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY member_id (member_id),
    KEY transaction_id (transaction_id),
    KEY status (status)
) $charset_collate;";

// 3. Events
$tables['tsn_events'] = "CREATE TABLE " . $wpdb->prefix . "tsn_events (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Link to WordPress post',
    title varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    description longtext DEFAULT NULL,
    excerpt text DEFAULT NULL COMMENT 'Short event description for listings',
    category varchar(100) DEFAULT NULL,
    venue_name varchar(255) DEFAULT NULL,
    address_line1 varchar(255) DEFAULT NULL,
    address_line2 varchar(255) DEFAULT NULL,
    city varchar(100) DEFAULT NULL,
    state varchar(100) DEFAULT NULL,
    zip varchar(20) DEFAULT NULL,
    country varchar(100) DEFAULT 'USA',
    map_url text DEFAULT NULL,
    start_datetime datetime NOT NULL,
    end_datetime datetime DEFAULT NULL,
    timezone varchar(50) DEFAULT 'America/Chicago',
    banner_url text DEFAULT NULL COMMENT 'Featured image URL',
    status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, archived',
    registration_mode varchar(50) DEFAULT 'ticket' COMMENT 'ticket, simple_rsvp, external',
    reg_open_datetime datetime DEFAULT NULL,
    reg_close_datetime datetime DEFAULT NULL,
    max_capacity int DEFAULT NULL,
    created_by bigint(20) UNSIGNED DEFAULT NULL,
    enable_ticketing tinyint(1) NOT NULL DEFAULT 1,
    enable_volunteering tinyint(1) NOT NULL DEFAULT 0,
    enable_donations tinyint(1) NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY slug (slug),
    KEY post_id (post_id),
    KEY status (status),
    KEY start_datetime (start_datetime)
) $charset_collate;";

// 4. Ticket Types
$tables['tsn_event_ticket_types'] = "CREATE TABLE " . $wpdb->prefix . "tsn_event_ticket_types (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id bigint(20) UNSIGNED NOT NULL,
    name varchar(100) NOT NULL COMMENT 'e.g., Adult, Child, Family',
    member_price decimal(10,2) NOT NULL,
    non_member_price decimal(10,2) NOT NULL,
    attendees_per_ticket int NOT NULL DEFAULT 1,
    description text DEFAULT NULL,
    capacity int NOT NULL,
    sold int NOT NULL DEFAULT 0,
    sales_start_datetime datetime DEFAULT NULL,
    sales_end_datetime datetime DEFAULT NULL,
    per_user_limit int DEFAULT NULL,
    min_qty int DEFAULT 1,
    max_qty int DEFAULT NULL,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    display_order int NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY event_id (event_id),
    KEY is_active (is_active)
) $charset_collate;";

// 5. Orders
$tables['tsn_orders'] = "CREATE TABLE " . $wpdb->prefix . "tsn_orders (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number varchar(50) NOT NULL,
    event_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL for donations',
    buyer_user_id bigint(20) UNSIGNED DEFAULT NULL COMMENT 'WP user ID if logged in',
    buyer_name varchar(255) NOT NULL,
    buyer_email varchar(100) NOT NULL,
    buyer_phone varchar(20) DEFAULT NULL,
    source varchar(20) NOT NULL DEFAULT 'online' COMMENT 'online, offline',
    order_type varchar(20) NOT NULL DEFAULT 'ticket' COMMENT 'ticket, donation',
    status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, cancelled, refunded',
    subtotal decimal(10,2) NOT NULL,
    tax decimal(10,2) NOT NULL DEFAULT 0,
    fees decimal(10,2) NOT NULL DEFAULT 0,
    total decimal(10,2) NOT NULL,
    payment_method varchar(50) DEFAULT NULL COMMENT 'paypal, stripe, cash, cheque, other',
    payment_reference varchar(255) DEFAULT NULL,
    paid_at datetime DEFAULT NULL,
    notes text DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY order_number (order_number),
    KEY event_id (event_id),
    KEY buyer_email (buyer_email),
    KEY status (status),
    KEY order_type (order_type)
) $charset_collate;";

// 6. Order Items
$tables['tsn_order_items'] = "CREATE TABLE " . $wpdb->prefix . "tsn_order_items (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id bigint(20) UNSIGNED NOT NULL,
    ticket_type_id bigint(20) UNSIGNED DEFAULT NULL,
    qty int NOT NULL DEFAULT 1,
    unit_price decimal(10,2) NOT NULL,
    is_member_price tinyint(1) NOT NULL DEFAULT 0,
    line_total decimal(10,2) NOT NULL,
    attendee_name varchar(255) DEFAULT NULL,
    attendee_email varchar(100) DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY order_id (order_id),
    KEY ticket_type_id (ticket_type_id)
) $charset_collate;";

// 7. Tickets
$tables['tsn_tickets'] = "CREATE TABLE " . $wpdb->prefix . "tsn_tickets (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_number varchar(50) NOT NULL,
    order_id bigint(20) UNSIGNED NOT NULL,
    ticket_type_id bigint(20) UNSIGNED NOT NULL,
    event_id bigint(20) UNSIGNED NOT NULL,
    attendee_name varchar(255) DEFAULT NULL,
    attendee_email varchar(100) DEFAULT NULL,
    attendee_phone varchar(20) DEFAULT NULL,
    member_user_id bigint(20) UNSIGNED DEFAULT NULL,
    qr_token_hash varchar(255) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, void, refunded',
    scanned_at datetime DEFAULT NULL,
    scan_count int NOT NULL DEFAULT 0,
    issued_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY ticket_number (ticket_number),
    UNIQUE KEY qr_token_hash (qr_token_hash),
    KEY order_id (order_id),
    KEY event_id (event_id),
    KEY status (status)
) $charset_collate;";

// 8. Scans Audit
$tables['tsn_scans_audit'] = "CREATE TABLE " . $wpdb->prefix . "tsn_scans_audit (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id bigint(20) UNSIGNED NOT NULL,
    ticket_id bigint(20) UNSIGNED DEFAULT NULL,
    scanner_user_id bigint(20) UNSIGNED DEFAULT NULL,
    result varchar(20) NOT NULL COMMENT 'valid, duplicate, invalid',
    reason text DEFAULT NULL,
    device_info text DEFAULT NULL,
    ip_address varchar(50) DEFAULT NULL,
    scanned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY event_id (event_id),
    KEY ticket_id (ticket_id),
    KEY scanner_user_id (scanner_user_id),
    KEY result (result)
) $charset_collate;";

// 9. Donations
$tables['tsn_donations'] = "CREATE TABLE " . $wpdb->prefix . "tsn_donations (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id bigint(20) UNSIGNED NOT NULL,
    donation_id varchar(50) NOT NULL,
    donor_name varchar(255) DEFAULT NULL,
    donor_email varchar(100) DEFAULT NULL,
    donor_phone varchar(20) DEFAULT NULL,
    address text DEFAULT NULL,
    city varchar(100) DEFAULT NULL,
    state varchar(100) DEFAULT NULL,
    country varchar(100) DEFAULT 'USA',
    zip varchar(20) DEFAULT NULL,
    donation_type varchar(20) NOT NULL DEFAULT 'general' COMMENT 'general, cause, event',
    cause_id bigint(20) UNSIGNED DEFAULT NULL,
    event_id bigint(20) UNSIGNED DEFAULT NULL,
    amount decimal(10,2) NOT NULL,
    payment_method varchar(50) DEFAULT NULL COMMENT 'paypal, stripe, cash, cheque, other',
    payment_reference varchar(255) DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, refunded',
    anonymous tinyint(1) NOT NULL DEFAULT 0,
    comments text DEFAULT NULL,
    receipt_pdf_url text DEFAULT NULL,
    paid_at datetime DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY donation_id (donation_id),
    KEY order_id (order_id),
    KEY donor_email (donor_email),
    KEY cause_id (cause_id),
    KEY status (status)
) $charset_collate;";

// 10. Donation Causes
$tables['tsn_donation_causes'] = "CREATE TABLE " . $wpdb->prefix . "tsn_donation_causes (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    slug varchar(255) NOT NULL,
    short_description text DEFAULT NULL,
    long_description longtext DEFAULT NULL,
    goal_amount decimal(10,2) DEFAULT NULL,
    raised_amount decimal(10,2) NOT NULL DEFAULT 0,
    hero_image text DEFAULT NULL,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    display_order int NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY slug (slug),
    KEY is_active (is_active)
) $charset_collate;";

// 11. Volunteers
$tables['tsn_event_volunteers'] = "CREATE TABLE " . $wpdb->prefix . "tsn_event_volunteers (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id bigint(20) UNSIGNED NOT NULL,
    name varchar(255) NOT NULL,
    email varchar(100) NOT NULL,
    phone varchar(20) DEFAULT NULL,
    age int DEFAULT NULL,
    gender varchar(20) DEFAULT NULL,
    address text DEFAULT NULL,
    notes text DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY event_id (event_id),
    KEY email (email),
    KEY status (status)
) $charset_collate;";

foreach ($tables as $name => $sql) {
    echo "Checking schema for {$name}...\n";
    dbDelta($sql);
}

echo "------------------------------\n";
echo "Full Schema Repair Complete.\n";
