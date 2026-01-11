<?php
/**
 * Event Emails Class
 * 
 * Handles event-related email notifications
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Event_Emails {
    
    /**
     * Send ticket confirmation email with QR codes
     */
    public static function send_ticket_confirmation($order_id) {
        global $wpdb;
        
        // Get order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            return false;
        }
        
        // Get event
        $event = TSN_Events::get_event_by_id($order->event_id);
        
        // Get tickets for this order
        $tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, tt.name as ticket_type_name 
             FROM {$wpdb->prefix}tsn_tickets t
             JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON t.ticket_type_id = tt.id
             WHERE t.order_id = %d",
            $order_id
        ));
        
        // Build email content
        $subject = 'Your Tickets for ' . $event->title;
        
        $message = '<h2>Ticket Confirmation</h2>';
        $message .= '<p>Dear ' . esc_html($order->buyer_name) . ',</p>';
        $message .= '<p>Your tickets for <strong>' . esc_html($event->title) . '</strong> have been confirmed!</p>';
        
        $message .= '<h3>Event Details:</h3>';
        $message .= '<p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($event->start_datetime)) . '<br>';
        $message .= '<strong>Time:</strong> ' . date('g:i A', strtotime($event->start_datetime)) . '<br>';
        if ($event->venue_name) {
            $message .= '<strong>Venue:</strong> ' . esc_html($event->venue_name) . '</p>';
        }
        
        $message .= '<h3>Your Tickets:</h3>';
        $message .= '<p><strong>Order Number:</strong> ' . esc_html($order->order_number) . '</p>';
        
        foreach ($tickets as $ticket) {
            $message .= '<div style="border: 2px solid #0066cc; padding: 20px; margin: 20px 0; border-radius: 8px;">';
            $message .= '<h4>' . esc_html($ticket->ticket_type_name) . '</h4>';
            $message .= '<p><strong>Ticket #:</strong> ' . esc_html($ticket->ticket_number) . '</p>';
            
            // Add QR code
            $qr_url = TSN_Ticket_QR::get_qr_code_url($ticket->id);
            if ($qr_url) {
                $message .= '<p><img src="' . esc_url($qr_url) . '" alt="QR Code" style="max-width: 200px;"></p>';
                $message .= '<p><small>Show this QR code at the event entrance</small></p>';
            }
            
            $message .= '</div>';
        }
        
        $message .= '<p><strong>Important:</strong> Please bring these tickets (printed or on your phone) to the event.</p>';
        $message .= '<p>Need help? Contact us at events@telugusamiti.org</p>';
        
        // Send email
        error_log("TSN Debug: Sending ticket email to " . $order->buyer_email);
        $extra_headers = array('Cc: finance@telugusamiti.org');
        $sent = TSN_Email::send($order->buyer_email, $subject, $message, array(), $extra_headers);
        error_log("TSN Debug: Email sent result: " . ($sent ? 'TRUE' : 'FALSE'));
        
        return $sent;
    }

    /**
     * Send volunteer confirmation email
     */
    public static function send_volunteer_confirmation($volunteer_id) {
        global $wpdb;
        
        $volunteer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_event_volunteers WHERE id = %d",
            $volunteer_id
        ));
        
        if (!$volunteer) return false;
        
        $event = TSN_Events::get_event_by_id($volunteer->event_id);
        
        $subject = 'Volunteer Registration Confirmation - ' . $event->title;
        
        $message = '<h2>Thank you for Volunteering!</h2>';
        $message .= '<p>Dear ' . esc_html($volunteer->name) . ',</p>';
        $message .= '<p>Thank you for registering as a volunteer for <strong>' . esc_html($event->title) . '</strong>.</p>';
        $message .= '<p>We have received your application and will review it shortly. Our team will contact you with further details.</p>';
        
        $message .= '<h3>Your Details:</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Phone:</strong> ' . esc_html($volunteer->phone) . '</li>';
        $message .= '<li><strong>Email:</strong> ' . esc_html($volunteer->email) . '</li>';
        if ($volunteer->notes) {
            $message .= '<li><strong>Notes:</strong> ' . esc_html($volunteer->notes) . '</li>';
        }
        $message .= '</ul>';
        
        $message .= '<p>If you have any questions, please reply to this email.</p>';
        
        return TSN_Email::send($volunteer->email, $subject, $message);
    }
    
    /**
     * Send volunteer admin notification
     */
    public static function send_volunteer_admin_notification($volunteer_id) {
        global $wpdb;
        
        $volunteer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_event_volunteers WHERE id = %d",
            $volunteer_id
        ));
        
        if (!$volunteer) return false;
        
        $event = TSN_Events::get_event_by_id($volunteer->event_id);
        $admin_email = get_option('admin_email');
        
        $subject = 'New Volunteer Registration - ' . $event->title;
        
        $message = '<h2>New Volunteer Registration</h2>';
        $message .= '<p>A new volunteer has registered for <strong>' . esc_html($event->title) . '</strong>.</p>';
        
        $message .= '<h3>Volunteer Details:</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Name:</strong> ' . esc_html($volunteer->name) . '</li>';
        $message .= '<li><strong>Email:</strong> ' . esc_html($volunteer->email) . '</li>';
        $message .= '<li><strong>Phone:</strong> ' . esc_html($volunteer->phone) . '</li>';
        $message .= '<li><strong>Age:</strong> ' . esc_html($volunteer->age) . '</li>';
        $message .= '<li><strong>Gender:</strong> ' . esc_html($volunteer->gender) . '</li>';
        if ($volunteer->address) {
            $message .= '<li><strong>Address:</strong> ' . esc_html($volunteer->address) . '</li>';
        }
        if ($volunteer->notes) {
            $message .= '<li><strong>Notes:</strong> ' . esc_html($volunteer->notes) . '</li>';
        }
        $message .= '</ul>';
        
        $message .= '<p><a href="' . admin_url('admin.php?page=tsn-events') . '">View in Admin Panel</a></p>';
        
        return TSN_Email::send($admin_email, $subject, $message);
    }
    /**
     * Send volunteer status update email
     */
    public static function send_volunteer_status_update($volunteer_id, $status) {
        global $wpdb;

        $volunteer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_event_volunteers WHERE id = %d",
            $volunteer_id
        ));

        if (!$volunteer) return false;

        $event = TSN_Events::get_event_by_id($volunteer->event_id);
        
        $subject = 'Volunteer Application Update - ' . $event->title;
        
        $message = '<h2>Application Update</h2>';
        $message .= '<p>Dear ' . esc_html($volunteer->name) . ',</p>';
        
        if ($status === 'approved') {
            $message .= '<p>Congratulations! Your application to volunteer for <strong>' . esc_html($event->title) . '</strong> has been <strong>approved</strong>.</p>';
            $message .= '<p>Our team will be in touch with you shortly regarding the schedule and responsibilities.</p>';
        } elseif ($status === 'rejected') {
            $message .= '<p>Thank you for your interest in volunteering for <strong>' . esc_html($event->title) . '</strong>.</p>';
            $message .= '<p>Unfortunately, we are unable to accept your application at this time. We appreciate your support and hope you will join us for future events.</p>';
        } else {
            // Pending or other
            $message .= '<p>Your application status for <strong>' . esc_html($event->title) . '</strong> has been updated to <strong>' . ucfirst($status) . '</strong>.</p>';
        }
        
        $message .= '<p>If you have any questions, please reply to this email.</p>';
        
        $message .= '<p>Best regards,<br><strong>Telugu Samiti</strong></p>';

        return TSN_Email::send($volunteer->email, $subject, $message);
    }

    /**
     * Send simple RSVP confirmation email
     */
    public static function send_simple_rsvp_confirmation($order_id) {
        global $wpdb;
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        if (!$order) return false;
        
        $event = TSN_Events::get_event_by_id($order->event_id);
        $event_link = home_url('/event/' . ($event->slug ? $event->slug : $event->id));
        
        $subject = 'Registration Confirmed: ' . $event->title;
        
        $message = '<h2>Event Registration Confirmation</h2>';
        $message .= '<p>Dear ' . esc_html($order->buyer_name) . ',</p>';
        $message .= '<p>You have successfully registered for <strong><a href="' . esc_url($event_link) . '">' . esc_html($event->title) . '</a></strong>.</p>';
        
        $message .= '<h3>Event Details:</h3>';
        $message .= '<p><strong>Date:</strong> ' . date('l, F j, Y', strtotime($event->start_datetime)) . '<br>';
        $message .= '<strong>Time:</strong> ' . date('g:i A', strtotime($event->start_datetime)) . '<br>';
        
        if ($event->venue_name) {
            $message .= '<strong>Venue:</strong> ' . esc_html($event->venue_name);
            if ($event->address_line1) {
                $address = esc_html($event->address_line1);
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
                $message .= '<br>' . nl2br($address);
                $message .= '<br><a href="' . esc_url($map_url) . '" target="_blank">View on Map</a>';
            }
            $message .= '</p>';
        }
        
        $message .= '<h3>Registration Details:</h3>';
        $message .= '<p><strong>RSVP ID:</strong> ' . esc_html($order->order_number) . '</p>';
        
        if ($order->notes) {
            $message .= '<div style="background:#f9f9f9; padding:15px; border-radius:4px; margin-top:10px;">';
            $message .= nl2br(esc_html($order->notes));
            $message .= '</div>';
        }
        
        $message .= '<p style="margin-top:20px;">We look forward to seeing you there!</p>';
        $message .= '<p>Best regards,<br><strong>Telugu Samiti</strong></p>';
        
        $extra_headers = array('Cc: finance@telugusamiti.org');
        return TSN_Email::send($order->buyer_email, $subject, $message, array(), $extra_headers);
    }
    
    /**
     * Generate Ticket PDF
     */
    public static function generate_ticket_pdf($order_id, $file_path) {
        global $wpdb;

        if (!class_exists('FPDF')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/libraries/fpdf.php';
        }

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d", $order_id));
        $event = TSN_Events::get_event_by_id($order->event_id);
        $tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, tt.name as ticket_type_name FROM {$wpdb->prefix}tsn_tickets t JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON t.ticket_type_id = tt.id WHERE t.order_id = %d",
            $order_id
        ));

        $pdf = new FPDF();
        
        foreach ($tickets as $ticket) {
            $pdf->AddPage();
            
            // Header
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(0, 102, 204);
            $pdf->Cell(0, 10, 'Telugu Samiti of Nebraska', 0, 1, 'C');
            
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetTextColor(51, 51, 51);
            $pdf->Cell(0, 10, 'Ticket Type', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Event Title Box
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Rect(10, 40, 190, 20, 'F');
            $pdf->SetXY(10, 45);
            $pdf->SetFont('Arial', 'B', 14);
            
            // Clean Title (Convert UTF-8 to ISO-8859-1 for FPDF)
            $safe_title = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $event->title);
            $pdf->Cell(190, 10, $safe_title, 0, 1, 'C');
            $pdf->Ln(15);
            
            // Details
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 8, 'Attendee:', 0, 0);
            $pdf->SetFont('Arial', 'B', 12);
            $safe_name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order->buyer_name);
            $pdf->Cell(0, 8, $safe_name, 0, 1);
            
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 8, 'Ticket Type:', 0, 0);
            $pdf->SetFont('Arial', 'B', 12);
            $safe_type = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $ticket->ticket_type_name);
            $pdf->Cell(0, 8, $safe_type, 0, 1);
            
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 8, 'Ticket #:', 0, 0);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, $ticket->ticket_number, 0, 1);
            
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 8, 'Date:', 0, 0);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, date('F j, Y g:i A', strtotime($event->start_datetime)), 0, 1);
            
            if ($event->venue_name) {
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 8, 'Venue:', 0, 0);
                $pdf->SetFont('Arial', 'B', 12);
                $safe_venue = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $event->venue_name);
                $pdf->Cell(0, 8, $safe_venue, 0, 1);
            }
            
            // QR Code
            $qr_url = TSN_Ticket_QR::get_qr_code_url($ticket->id);
            if ($qr_url) {
                // FPDF needs a local file or accesible URL. 
                // Since api.qrserver.com is external, we can use it directly in Image() if allow_url_fopen is on.
                // However, standard FPDF might not support HTTPS or redirection well without extensions.
                // Safest bet: Download to temp file.
                
                $temp_qr_file = get_temp_dir() . 'qr_' . $ticket->id . '.png';
                $qr_content = wp_remote_get($qr_url);
                
                if (!is_wp_error($qr_content) && wp_remote_retrieve_response_code($qr_content) == 200) {
                    file_put_contents($temp_qr_file, wp_remote_retrieve_body($qr_content));
                    
                    // Position: Right side, aligned with Title/Venue
                    // X=140, Y=45, W=50
                    $pdf->Image($temp_qr_file, 140, 60, 50, 50, 'PNG');
                    
                    // Cleanup
                    @unlink($temp_qr_file);
                } else {
                     // Fallback box
                     $pdf->Rect(140, 60, 50, 50);
                     $pdf->SetXY(140, 80);
                     $pdf->SetFont('Arial', '', 8);
                     $pdf->Cell(50, 5, 'QR Unavailable', 0, 0, 'C');
                }
            } else {
                 $pdf->Rect(140, 60, 50, 50);
                 $pdf->SetXY(140, 80);
                 $pdf->SetFont('Arial', '', 8);
                 $pdf->Cell(50, 5, 'QR Error', 0, 0, 'C');
            }
            
            $pdf->Ln(55); // Move down past QR code area
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->MultiCell(0, 5, "Please present this ticket at the entrance.\nOrder #: " . $order->order_number, 0, 'C');
        }
        
        $pdf->Output('F', $file_path);
    }

    /**
     * Get or Generate Ticket PDF URL
     */
    public static function get_pdf_url($order_id) {
        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d", $order_id));
        if (!$order) return false;

        $upload_dir = wp_upload_dir();
        $ticket_dir = $upload_dir['basedir'] . '/tsn-tickets';
        if (!file_exists($ticket_dir)) wp_mkdir_p($ticket_dir);
        
        $file_name = 'Tickets-' . $order->order_number . '.pdf';
        $file_path = $ticket_dir . '/' . $file_name;
        
        if (!file_exists($file_path)) {
            self::generate_ticket_pdf($order_id, $file_path);
        }
        
        return $upload_dir['baseurl'] . '/tsn-tickets/' . $file_name;
    }

    /**
     * AJAX: Download Tickets
     */
    public static function ajax_download_tickets() {
        check_ajax_referer('tsn_membership_nonce', 'nonce');
        
        if (!is_user_logged_in() && !TSN_Membership_OTP::is_member_logged_in()) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $url = self::get_pdf_url($order_id);
        
        if ($url) {
            wp_send_json_success(array('url' => $url));
        } else {
            wp_send_json_error(array('message' => 'Could not generate tickets'));
        }
    }

    /**
     * AJAX: Resend Tickets
     */
    public static function ajax_resend_tickets() {
        check_ajax_referer('tsn_membership_nonce', 'nonce');
        
        if (!is_user_logged_in() && !TSN_Membership_OTP::is_member_logged_in()) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (self::send_ticket_confirmation($order_id)) {
            wp_send_json_success(array('message' => 'Tickets sent successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to send tickets'));
        }
    }
}
