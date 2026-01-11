<?php
/**
 * Events Admin Class
 * 
 * Admin interface for event management
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Events_Admin {
    
    public function __construct() {
        // Menu is registered in TSN_Core::add_admin_menu()
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load media scripts on add/edit event pages
        if ($hook === 'tsn-modules_page_tsn-add-event') {
            wp_enqueue_media();
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tsn-modules',
            __('Events', 'tsn-modules'),
            __('Events', 'tsn-modules'),
            'manage_options',
            'tsn-events',
            array($this, 'render_events_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Event', 'tsn-modules'),
            __('Add Event', 'tsn-modules'),
            'manage_options',
            'tsn-add-event',
            array($this, 'render_add_event_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('QR Scanner', 'tsn-modules'),
            __('QR Scanner', 'tsn-modules'),
            'manage_options',
            'tsn-qr-scanner',
            array($this, 'render_scanner_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Offline Tickets', 'tsn-modules'),
            __('Offline Tickets', 'tsn-modules'),
            'manage_options',
            'tsn-offline-tickets',
            array($this, 'render_offline_tickets_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Event Reports', 'tsn-modules'),
            __('Event Reports', 'tsn-modules'),
            'manage_options',
            'tsn-event-reports',
            array($this, 'render_reports_page')
        );
    }
    
    /**
     * Render events list page
     */
    public function render_events_page() {
        // Handle event save/update
        if (isset($_POST['tsn_save_event']) && check_admin_referer('tsn_event_nonce')) {
            $this->handle_save_event();
        }
        
        // Handle event deletion
        if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['event_id'])) {
            $this->handle_delete_event($_GET['event_id']);
        }
        
        // Get status filter - exclude archived by default
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'not_archived';
        $events = TSN_Events::get_all_events($status_filter);
        include TSN_MODULES_PATH . 'admin/views/events/list.php';
    }
    
    /**
     * Render add/edit event page
     */
    public function render_add_event_page() {
        $event = null;
        $ticket_types = array();
        
        if (isset($_GET['event_id'])) {
            $event = TSN_Events::get_event_by_id($_GET['event_id']);
            $ticket_types = TSN_Events::get_event_ticket_types($_GET['event_id']);
        }
        
        include TSN_MODULES_PATH . 'admin/views/events/add-edit.php';
    }
    
    /**
     * Render QR scanner page
     */
    public function render_scanner_page() {
        include TSN_MODULES_PATH . 'admin/views/events/scanner.php';
    }
    
    /**
     * Render offline tickets page
     */
    public function render_offline_tickets_page() {
        include TSN_MODULES_PATH . 'admin/views/events/offline-tickets.php';
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        include TSN_MODULES_PATH . 'admin/views/events/reports.php';
    }
    
    /**
     * Handle save event
     */
    private function handle_save_event() {
        $event_id = TSN_Events::save_event($_POST);
        
        // Check if save failed
        if (!$event_id) {
            global $wpdb;
            wp_die('Event save failed! Database error: ' . $wpdb->last_error . '<br><br><a href="javascript:history.back()">Go Back</a>');
        }
        
        // Save ticket types
        if (isset($_POST['ticket_types']) && is_array($_POST['ticket_types'])) {
            $this->save_ticket_types($event_id, $_POST['ticket_types']);
        }
        
        // Use JS redirect to avoid whitespace/header issues
        echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-events&message=saved') . '";</script>';
        exit;
    }
    
    /**
     * Save ticket types
     */
    private function save_ticket_types($event_id, $ticket_types) {
        global $wpdb;
        
        // Only process if ticket types data is provided
        if (empty($ticket_types)) {
            return; 
        }

        // Collect IDs of submitted tickets to identify what to keep
        $submitted_ids = array();
        foreach ($ticket_types as $ticket) {
            if (!empty($ticket['id'])) {
                $submitted_ids[] = intval($ticket['id']);
            }
        }
        
        // Delete ticket types that are NOT in the submitted list for this event
        if (!empty($submitted_ids)) {
            $id_placeholders = implode(',', array_fill(0, count($submitted_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tsn_event_ticket_types 
                 WHERE event_id = %d AND id NOT IN ($id_placeholders)",
                array_merge(array($event_id), $submitted_ids)
            ));
        } else {
             // If no existing IDs submitted, but we have NEW tickets, we might want to clear old ones? 
             // Or maybe this is a fresh event. 
             // Logic: If user removed all rows in UI, submitted_ids is empty. 
             // But the loop below will insert new ones.
             // So safe to delete all for this event if submitted_ids is empty?
             // YES, if we are overwriting.
             $wpdb->delete(
                $wpdb->prefix . 'tsn_event_ticket_types',
                array('event_id' => $event_id),
                array('%d')
            );
        }
        
        // Process each ticket type
        $display_order = 0;
        foreach ($ticket_types as $ticket) {
            if (empty($ticket['type_name'])) continue;
            
            $data = array(
                'event_id' => $event_id,
                'name' => sanitize_text_field($ticket['type_name']),
                'attendees_per_ticket' => isset($ticket['attendees_per_ticket']) ? intval($ticket['attendees_per_ticket']) : 1,
                'description' => isset($ticket['description']) ? sanitize_text_field($ticket['description']) : '',
                'member_price' => floatval($ticket['member_price']),
                'non_member_price' => floatval($ticket['non_member_price']),
                'capacity' => intval($ticket['available_quantity']),
                // Do NOT reset 'sold' on update, specific handling below
                'display_order' => $display_order++
            );
            
            $formats = array('%d', '%s', '%d', '%s', '%f', '%f', '%d', '%d'); // 'sold' is handled separately for insert

            if (!empty($ticket['id'])) {
                // Update existing
                $wpdb->update(
                    $wpdb->prefix . 'tsn_event_ticket_types',
                    $data,
                    array('id' => intval($ticket['id']), 'event_id' => $event_id),
                    $formats,
                    array('%d', '%d')
                );
            } else {
                // Insert new
                $data['sold'] = 0; // New tickets have 0 sold
                $formats[] = '%d'; // Add format for 'sold'
                
                $wpdb->insert(
                    $wpdb->prefix . 'tsn_event_ticket_types',
                    $data,
                    $formats
                );
            }
        }
    }
    
    /**
     * Handle delete event
     */
    private function handle_delete_event($event_id) {
        global $wpdb;
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_event_' . $event_id)) {
            wp_die('Invalid nonce');
        }
        
        $wpdb->update(
            $wpdb->prefix . 'tsn_events',
            array('status' => 'archived'),
            array('id' => $event_id),
            array('%s'),
            array('%d')
        );
        
        // Use JS redirect
        echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-events&message=deleted') . '";</script>';
        exit;
    }
    
    // AJAX handlers
    public static function ajax_add_offline_ticket() {
        wp_send_json_error(array('message' => 'Not yet implemented'));
    }
    
    /**
     * Handle offline ticket sale
     */
    public static function handle_offline_ticket_sale($data) {
        global $wpdb;
        
        $event_id = intval($data['event_id']);
        $tickets = isset($data['tickets']) ? $data['tickets'] : array();
        
        // Validate tickets
        $order_items = array();
        $subtotal = 0;
        
        foreach ($tickets as $ticket_type_id => $ticket_data) {
            $qty = intval($ticket_data['qty']);
            if ($qty <= 0) continue;
            
            $is_member = isset($ticket_data['is_member']);
            
            // Get ticket type
            $ticket_type = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types WHERE id = %d",
                $ticket_type_id
            ));
            
            if (!$ticket_type) continue;
            
            $price = $is_member ? $ticket_type->member_price : $ticket_type->non_member_price;
            
            $order_items[] = array(
                'ticket_type_id' => $ticket_type_id,
                'name' => $ticket_type->name,
                'quantity' => $qty,
                'price' => $price,
                'is_member_price' => $is_member ? 1 : 0,
                'line_total' => $price * $qty
            );
            
            $subtotal += $price * $qty;
        }
        
        if (empty($order_items)) {
            return array('success' => false, 'message' => 'No tickets selected');
        }
        
        // Create order
        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $order_data = array(
            'order_number' => $order_number,
            'event_id' => $event_id,
            'buyer_name' => sanitize_text_field($data['buyer_name']),
            'buyer_email' => sanitize_email($data['buyer_email']),
            'buyer_phone' => isset($data['buyer_phone']) ? sanitize_text_field($data['buyer_phone']) : '',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'status' => 'completed',
            'payment_method' => sanitize_text_field($data['payment_method']),
            'payment_reference' => isset($data['payment_reference']) ? sanitize_textarea_field($data['payment_reference']) : ''
        );
        
        error_log('TSN Offline Ticket - Order Data: ' . print_r($order_data, true));
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'tsn_orders',
            $order_data,
            array('%s', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s')
        );
        
        error_log('TSN Offline Ticket - Insert Result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        error_log('TSN Offline Ticket - Last Error: ' . $wpdb->last_error);
        error_log('TSN Offline Ticket - Last Query: ' . $wpdb->last_query);
        
        $order_id = $wpdb->insert_id;
        
        if (!$order_id) {
            return array('success' => false, 'message' => 'Failed to create order. DB Error: ' . $wpdb->last_error);
        }
        
        // Create order items and tickets
        foreach ($order_items as $item) {
            // Create order item
            $wpdb->insert(
                $wpdb->prefix . 'tsn_order_items',
                array(
                    'order_id' => $order_id,
                    'ticket_type_id' => $item['ticket_type_id'],
                    'qty' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'is_member_price' => $item['is_member_price'],
                    'line_total' => $item['price'] * $item['quantity']
                ),
                array('%d', '%d', '%d', '%f', '%d', '%f')
            );
            
            // Generate tickets
            for ($i = 0; $i < $item['quantity']; $i++) {
                TSN_Ticket_QR::generate_ticket($order_id, $item['ticket_type_id'], $event_id, $data['buyer_email']);
            }
            
            // Update sold count
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}tsn_event_ticket_types 
                 SET sold = sold + %d 
                 WHERE id = %d",
                $item['quantity'],
                $item['ticket_type_id']
            ));
        }
        
        // Send email
        TSN_Event_Emails::send_ticket_confirmation($order_id);
        
        return array(
            'success' => true, 
            'message' => 'Tickets generated successfully! Confirmation email sent to ' . $data['buyer_email'],
            'order_id' => $order_id
        );
    }
    
    public static function ajax_export_attendees() {
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        if (!$event_id || !wp_verify_nonce($_GET['nonce'], 'tsn_export_' . $event_id)) {
            wp_die('Invalid request');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        // Get event
        $event = TSN_Events::get_event_by_id($event_id);
        if (!$event) {
            wp_die('Event not found');
        }
        
        // Get all tickets for this event
        // Get all attendees (from order items)
        $attendees = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                COALESCE(t.ticket_number, 'N/A') as ticket_number,
                COALESCE(t.status, o.status) as status,
                t.scanned_at,
                COALESCE(tt.name, 'Simple RSVP') as ticket_type,
                o.order_number,
                o.buyer_name,
                o.buyer_email,
                o.buyer_phone,
                o.payment_method,
                o.created_at,
                oi.attendee_name,
                oi.attendee_email
             FROM {$wpdb->prefix}tsn_order_items oi
             JOIN {$wpdb->prefix}tsn_orders o ON oi.order_id = o.id
             LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON oi.ticket_type_id = tt.id
             LEFT JOIN {$wpdb->prefix}tsn_tickets t ON (t.order_id = o.id AND (t.ticket_type_id = oi.ticket_type_id OR (oi.ticket_type_id IS NULL AND t.ticket_type_id IS NULL))) 
             WHERE o.event_id = %d
             GROUP BY oi.id
             ORDER BY o.created_at DESC",
            $event_id
        ), ARRAY_A);
        
        if (empty($attendees)) {
            wp_die('No attendees found for this event. <a href="' . admin_url('admin.php?page=tsn-event-reports') . '">Go Back</a>');
        }
        
        // Generate CSV
        $filename = sanitize_title($event->title) . '-attendees-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'Ticket Number', 'Ticket Type', 'Status', 'Checked In',
            'Buyer Name', 'Email', 'Phone', 'Order Number',
            'Payment Method', 'Purchase Date'
        ));
        
        // Data
        foreach ($attendees as $attendee) {
            fputcsv($output, array(
                $attendee['ticket_number'],
                $attendee['ticket_type'],
                ucfirst($attendee['status']),
                $attendee['scanned_at'] ? date('M j, Y g:i A', strtotime($attendee['scanned_at'])) : 'No',
                $attendee['buyer_name'],
                $attendee['buyer_email'],
                $attendee['buyer_phone'],
                $attendee['order_number'],
                ucfirst($attendee['payment_method']),
                date('M j, Y', strtotime($attendee['created_at']))
            ));
        }
        
        fclose($output);
        exit;
    }
    
    public static function ajax_mark_sold_out() {
        wp_send_json_error(array('message' => 'Not yet implemented'));
    }

    /**
     * AJAX: Export donations CSV
     */
    public static function ajax_export_donations() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        
        if (!$event_id) {
            wp_die('Event ID required');
        }
        
        check_admin_referer('tsn_export_donations_' . $event_id, 'nonce');
        
        global $wpdb;
        
        $donations = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, o.order_number, d.comments as message, d.anonymous as is_anonymous
             FROM {$wpdb->prefix}tsn_donations d
             JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
             WHERE d.event_id = %d
             ORDER BY d.created_at DESC",
            $event_id
        ));
        
        // Generate CSV
        $filename = 'donations-event-' . $event_id . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'Date',
            'Order Number',
            'Donor Name',
            'Email',
            'Phone',
            'Amount',
            'Message',
            'Is Anonymous',
            'Status'
        ));
        
        foreach ($donations as $d) {
            fputcsv($output, array(
                date('Y-m-d H:i:s', strtotime($d->created_at)),
                $d->order_number,
                $d->donor_name,
                $d->donor_email,
                $d->donor_phone,
                $d->amount,
                $d->message,
                $d->is_anonymous ? 'Yes' : 'No',
                'Completed' // Since we only query linked completed orders usually, or display status if joined properly
            ));
        }
        
        fclose($output);
        exit;
    }


    /**
     * AJAX: Download Receipt
     */
    public static function ajax_download_receipt() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if (!$order_id) {
            wp_die('Order ID required');
        }
        
        check_admin_referer('tsn_receipt_' . $order_id, 'nonce');
        
        global $wpdb;
        
        // Get order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        if (!$order) wp_die('Order not found');
        
        // Get donation
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, c.title as cause_title, d.comments as message, d.anonymous as is_anonymous
             FROM {$wpdb->prefix}tsn_donations d
             LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
             WHERE d.order_id = %d",
            $order_id
        ));
        
        // Generate HTML
        $html = TSN_Donation_Receipt::get_receipt_html($order, $donation);
        
        // Serve as download
        $filename = 'receipt-' . $order->order_number . '.html';
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $html;
        exit;
    }

    /**
     * AJAX: Update volunteer status
     */
    public static function ajax_update_volunteer_status() {
        // Verify nonce
        if (!check_ajax_referer('tsn_volunteer_status_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $volunteer_id = isset($_POST['volunteer_id']) ? intval($_POST['volunteer_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$volunteer_id || !in_array($status, array('pending', 'approved', 'rejected'))) {
            wp_send_json_error(array('message' => 'Invalid data'));
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'tsn_event_volunteers',
            array('status' => $status),
            array('id' => $volunteer_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error'));
        }
        
        // Send email notification
        TSN_Event_Emails::send_volunteer_status_update($volunteer_id, $status);
        
        wp_send_json_success(array('message' => 'Status updated'));
    }
    // Ticket Management Handlers
    public static function ajax_delete_ticket() {
        if (!check_ajax_referer('tsn_ticket_action_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        global $wpdb;
        
        if ($ticket_id) {
            // Ticket Mode: Void the ticket
            $updated = $wpdb->update(
                $wpdb->prefix . 'tsn_tickets',
                array('status' => 'void'),
                array('id' => $ticket_id),
                array('%s'),
                array('%d')
            );
            
            if ($updated !== false) {
                wp_send_json_success(array('message' => 'Ticket deleted (voided)'));
            }
        } elseif ($order_id) {
            // Simple RSVP Mode: Cancel the Order
            $updated = $wpdb->update(
                $wpdb->prefix . 'tsn_orders',
                array('status' => 'cancelled'),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            if ($updated !== false) {
                wp_send_json_success(array('message' => 'RSVP cancelled'));
            }
        }
        
        wp_send_json_error(array('message' => 'Failed to delete item'));
    }
    
    public static function ajax_update_ticket() {
        if (!check_ajax_referer('tsn_ticket_action_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        $name = sanitize_text_field($_POST['attendee_name']);
        $email = sanitize_email($_POST['attendee_email']);
        
        global $wpdb;
        
        if ($ticket_id) {
            // Update Ticket
            $updated = $wpdb->update(
                $wpdb->prefix . 'tsn_tickets',
                array(
                    'attendee_name' => $name,
                    'attendee_email' => $email
                ),
                array('id' => $ticket_id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            // Update Order (Simple RSVP Buyer)
            $updated = $wpdb->update(
                $wpdb->prefix . 'tsn_orders',
                array(
                    'buyer_name' => $name,
                    'buyer_email' => $email
                ),
                array('id' => $order_id),
                array('%s', '%s'),
                array('%d')
            );
        }
        
        if ($updated !== false) {
            wp_send_json_success(array('message' => 'Details updated'));
        }
        
        wp_send_json_error(array('message' => 'Failed to update'));
    }
}
