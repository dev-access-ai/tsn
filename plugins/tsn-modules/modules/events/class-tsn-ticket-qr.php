<?php
/**
 * Ticket QR Code Class
 * 
 * Generates and validates QR codes for tickets
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Ticket_QR {
    
    /**
     * Generate a ticket with QR code
     */
    public static function generate_ticket($order_id, $ticket_type_id, $event_id, $attendee_email, $attendee_name = null, $attendee_age = null, $attendee_gender = null) {
        global $wpdb;
        
        // Generate unique ticket number
        $ticket_number = 'TKT-' . date('Ymd') . '-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        
        // Generate QR token (unique hash)
        $qr_token = bin2hex(random_bytes(32));
        $qr_token_hash = hash('sha256', $qr_token);
        
        // Insert ticket
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'tsn_tickets',
            array(
                'ticket_number' => $ticket_number,
                'order_id' => $order_id,
                'ticket_type_id' => $ticket_type_id,
                'event_id' => $event_id,
                'attendee_email' => $attendee_email,
                'attendee_name' => $attendee_name,
                'attendee_age' => $attendee_age,
                'attendee_gender' => $attendee_gender,
                'qr_token_hash' => $qr_token_hash,
                'status' => 'active'
            ),
            array('%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($inserted === false) {
             error_log("TSN CRITICAL ERROR: Failed to insert ticket: " . $wpdb->last_error);
             return false;
        }
        
        error_log("TSN Debug: Ticket generated successfully. ID: " . $wpdb->insert_id . " Name: " . $attendee_name);
        
        $ticket_id = $wpdb->insert_id;
        
        // Store QR token temporarily for email (will be sent once)
        set_transient('tsn_ticket_qr_' . $ticket_id, $qr_token, 3600);
        
        return $ticket_id;
    }
    
    /**
     * Get QR code data URL for ticket
     */
    public static function get_qr_code_url($ticket_id) {
        global $wpdb;
        
        // Get ticket info
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_tickets WHERE id = %d",
            $ticket_id
        ));
        
        if (!$ticket) {
            return false;
        }
        
        // Create simple QR data with ticket number (can be validated against database)
        $qr_data = $ticket->ticket_number;
        
        // Use QR server API (more reliable than Google Charts)
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
        
        return $qr_url;
    }
    
    /**
     * Validate QR code
     */
    public static function validate_qr($qr_data) {
        global $wpdb;
        
        // QR data is simply the ticket number
        $ticket_number = sanitize_text_field($qr_data);
        
        // Get ticket by ticket number
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_tickets WHERE ticket_number = %s",
            $ticket_number
        ));
        
        if (!$ticket) {
            return array('valid' => false, 'message' => 'Ticket not found');
        }
        
        // Check ticket status
        if ($ticket->status !== 'active') {
            return array('valid' => false, 'message' => 'Ticket is ' . $ticket->status);
        }
        
        // Check if already scanned
        if ($ticket->scanned_at) {
            try {
                // Treat stored time as UTC
                $date = new DateTime($ticket->scanned_at, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('America/Chicago'));
                $formatted_date = $date->format('M j, Y g:i A T');
            } catch (Exception $e) {
                $formatted_date = date('M j, Y g:i A T', strtotime($ticket->scanned_at));
            }

            return array(
                'valid' => false, 
                'message' => 'Ticket already scanned at ' . $formatted_date,
                'duplicate' => true
            );
        }
        
        return array(
            'valid' => true,
            'ticket' => $ticket,
            'message' => 'Valid ticket'
        );
    }
    
    /**
     * Record scan attempt in audit log
     */
    public static function record_scan_attempt($event_id, $ticket_id, $result, $reason = '', $scanner_user_id = null) {
        global $wpdb;

        return $wpdb->insert(
            $wpdb->prefix . 'tsn_scans_audit',
            array(
                'event_id' => $event_id,
                'ticket_id' => $ticket_id, // Can be NULL for invalid tickets
                'scanner_user_id' => $scanner_user_id,
                'result' => $result, // valid, duplicate, invalid
                'reason' => $reason,
                'ip_address' => TSN_Security::get_client_ip()
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Mark ticket as scanned
     */
    public static function mark_scanned($ticket_id, $scanner_user_id = null) {
        global $wpdb;
        
        // Update ticket
        $wpdb->update(
            $wpdb->prefix . 'tsn_tickets',
            array(
                'scanned_at' => current_time('mysql', 1), // Store as GMT
            ),
            array('id' => $ticket_id),
            array('%s'),
            array('%d')
        );
        
        // Increment scan count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}tsn_tickets SET scan_count = scan_count + 1 WHERE id = %d",
            $ticket_id
        ));
        
        // Log in audit table
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_tickets WHERE id = %d",
            $ticket_id
        ));
        
        if ($ticket) {
            self::record_scan_attempt($ticket->event_id, $ticket_id, 'valid', '', $scanner_user_id);
        }
        
        return true;
    }
    
    /**
     * AJAX: Validate QR code (for scanner app)
     */
    public static function ajax_validate_qr() {
        check_ajax_referer('tsn_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $qr_data = isset($_POST['qr_data']) ? $_POST['qr_data'] : '';
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        $result = self::validate_qr($qr_data);
        
        if ($result['valid']) {
            // Check if ticket belongs to the current event (strict check)
            if ($event_id && $result['ticket']->event_id != $event_id) {
                // Determine if it's for another valid event
                self::record_scan_attempt($event_id, $result['ticket']->id, 'invalid', 'Ticket for different event', get_current_user_id());
                wp_send_json_error(array('message' => 'Ticket is for a different event'));
                return;
            }

            // Mark as scanned
            self::mark_scanned($result['ticket']->id, get_current_user_id());
            
            wp_send_json_success(array(
                'message' => 'Ticket validated and checked in!',
                'ticket_number' => $result['ticket']->ticket_number,
                'attendee_email' => $result['ticket']->attendee_email
            ));
        } else {
            // Log invalid attempt
            // If we have a ticket object (e.g. duplicate or wrong status), log it linked to ticket
            // If ticket not found, log with NULL ticket_id
            
            $ticket_id = null;
            $audit_result = 'invalid';
            $reason = $result['message'];
            
            if (isset($result['ticket']) && $result['ticket']) {
                $ticket_id = $result['ticket']->id;
                // If it was valid structurally but invalid logic (e.g. duplicate or void)
                if (isset($result['duplicate']) && $result['duplicate']) {
                    $audit_result = 'duplicate';
                }
            }
            
            // If we have a ticket, use its event ID, otherwise use passed event ID
            // If ticket is from different event, we might want to log against the SCANNED event (passed event_id)
            $log_event_id = $event_id; 
            if (!$log_event_id && $ticket_id && isset($result['ticket'])) {
                 $log_event_id = $result['ticket']->event_id;
            }
            
            if ($log_event_id) {
                self::record_scan_attempt($log_event_id, $ticket_id, $audit_result, $reason, get_current_user_id());
            }
            
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Manual validation (backup method)
     */
    public static function ajax_validate_manual() {
        check_ajax_referer('tsn_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        
        $ticket_number = sanitize_text_field($_POST['ticket_number']);
        
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_tickets WHERE ticket_number = %s",
            $ticket_number
        ));
        
        if (!$ticket) {
            wp_send_json_error(array('message' => 'Ticket not found'));
        }
        
        if ($ticket->status !== 'active') {
            wp_send_json_error(array('message' => 'Ticket is ' . $ticket->status));
        }
        
        if ($ticket->scanned_at) {
            try {
                // Treat stored time as UTC
                $date = new DateTime($ticket->scanned_at, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('America/Chicago'));
                $formatted_date = $date->format('M j, Y g:i A T');
            } catch (Exception $e) {
                $formatted_date = date('M j, Y g:i A T', strtotime($ticket->scanned_at));
            }

            wp_send_json_error(array(
                'message' => 'Already scanned at ' . $formatted_date
            ));
        }
        
        // Mark as scanned
        self::mark_scanned($ticket->id, get_current_user_id());
        
        wp_send_json_success(array(
            'message' => 'Ticket validated!',
            'attendee_email' => $ticket->attendee_email
        ));
    }
    
    /**
     * AJAX: Get scan history for an event
     */
    public static function ajax_get_scan_history() {
        check_ajax_referer('tsn_scanner_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Event ID required'));
        }
        
        // Get recent scans for this event (last 50)
        $scans = $wpdb->get_results($wpdb->prepare(
            "SELECT sa.*, t.ticket_number 
             FROM {$wpdb->prefix}tsn_scans_audit sa
             LEFT JOIN {$wpdb->prefix}tsn_tickets t ON sa.ticket_id = t.id
             WHERE sa.event_id = %d
             ORDER BY sa.scanned_at DESC",
            $event_id
        ));
        
        // Format dates for display (CST)
        foreach ($scans as $scan) {
            try {
                // Treat stored time as UTC
                $date = new DateTime($scan->scanned_at, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('America/Chicago'));
                $scan->formatted_time = $date->format('M j, Y g:i:s A T');
            } catch (Exception $e) {
                // Fallback
                $scan->formatted_time = date('M j, Y g:i:s A', strtotime($scan->scanned_at));
            }
        }
        
        wp_send_json_success(array(
            'scans' => $scans
        ));
    }
}
