<?php
/**
 * Donation Receipt Email
 */

if (!defined('ABSPATH')) exit;

class TSN_Donation_Receipt {
    
    public static function send_receipt($order_id) {
        global $wpdb;
        
        // Get order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        if (!$order) return false;
        
        // Get donation
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, c.title as cause_title 
             FROM {$wpdb->prefix}tsn_donations d
             LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
             WHERE d.order_id = %d",
            $order_id
        ));
        
        $to = $order->buyer_email;
        $subject = 'Donation Receipt - Telugu Samiti of Nebraska';
        
        $message = self::get_receipt_html($order, $donation);
        
        // Generate Receipt File (PDF)
        $upload_dir = wp_upload_dir();
        $receipt_dir = $upload_dir['basedir'] . '/tsn-receipts';
        if (!file_exists($receipt_dir)) {
            wp_mkdir_p($receipt_dir);
        }
        
        $file_name = 'Receipt-' . $order->order_number . '.pdf';
        $file_path = $receipt_dir . '/' . $file_name;
        
        // Generate PDF
        self::generate_pdf_receipt($order, $donation, $file_path);
        
        $attachments = array($file_path);
        
        // Add download link to message
        $receipt_url = $upload_dir['baseurl'] . '/tsn-receipts/' . $file_name;
        $message .= '<p style="text-align: center; margin-top: 20px;"><a href="' . $receipt_url . '" style="background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Download PDF Receipt</a></p>';
        
        // Set Headers with explicit From name/email
        $admin_email = get_option('admin_email');
        $from_name = 'Telugu Samiti of Nebraska';
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $from_name . ' <' . $admin_email . '>';
        $headers[] = 'Cc: finance@telugusamiti.org';
        
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
    
    /**
     * Generate PDF Receipt using FPDF
     */
    public static function generate_pdf_receipt($order, $donation, $file_path) {
        if (!class_exists('FPDF')) {
            require_once plugin_dir_path(__FILE__) . '../../includes/libraries/fpdf.php';
        }
        
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(0, 102, 204); // #0066cc
        $pdf->Cell(0, 10, 'Telugu Samiti of Nebraska', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(0, 10, 'Donation Receipt', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Donor Details
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Date: ' . date('F j, Y', strtotime($order->paid_at)), 0, 1);
        $pdf->Cell(0, 7, 'Order #: ' . $order->order_number, 0, 1);
        
        $safe_name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order->buyer_name);
        $pdf->Cell(0, 7, 'Donor: ' . $safe_name, 0, 1);
        $pdf->Ln(10);
        
        // Amount Box
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Rect($pdf->GetX(), $pdf->GetY(), 190, 40, 'F');
        $pdf->SetXY($pdf->GetX() + 5, $pdf->GetY() + 5);
        
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(180, 10, 'Amount Received', 0, 1);
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(0, 102, 204);
        $pdf->Cell(180, 15, '$' . number_format($order->total, 2), 0, 1);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Ln(15);
        
        // Cause Details
        if ($donation && $donation->cause_title) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(40, 7, 'Cause:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $safe_cause = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $donation->cause_title);
            $pdf->Cell(0, 7, $safe_cause, 0, 1);
        }
        
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(40, 7, 'Payment Method:', 0, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, ucfirst($order->payment_method), 0, 1);
        
        if ($order->payment_reference) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(40, 7, 'Transaction ID:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 7, $order->payment_reference, 0, 1);
        }
        
        $pdf->Ln(20);
        
        // Footer / Tax Info
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 6, "Telugu Samiti is a registered non-profit organization. This receipt may be used for tax deduction purposes. Please consult your tax advisor for details.\n\nThank you for your generous support!", 0, 'C');
        
        $pdf->Output('F', $file_path);
    }
    
    public static function get_receipt_html($order, $donation) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0066cc; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .receipt-box { background: white; border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
                .amount { font-size: 32px; color: #0066cc; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Thank You!</h1>
                    <p>Your donation has been received</p>
                </div>
                
                <div class="content">
                    <p>Dear <?php echo esc_html($order->buyer_name); ?>,</p>
                    
                    <p>Thank you for your generous donation to Telugu Samiti. Your support helps us continue our mission of serving the Telugu community.</p>
                    
                    <div class="receipt-box">
                        <h2>Donation Receipt</h2>
                        <p><strong>Order Number:</strong> <?php echo esc_html($order->order_number); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order->paid_at)); ?></p>
                        <p><strong>Amount:</strong> <span class="amount">$<?php echo number_format($order->total, 2); ?></span></p>
                        <?php if ($donation && $donation->cause_title): ?>
                            <p><strong>Cause:</strong> <?php echo esc_html($donation->cause_title); ?></p>
                        <?php endif; ?>
                        <p><strong>Payment Method:</strong> <?php echo ucfirst($order->payment_method); ?></p>
                        <?php if ($order->payment_reference): ?>
                            <p><strong>Transaction ID:</strong> <?php echo esc_html($order->payment_reference); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($donation && !empty($donation->comments)): ?>
                        <p><strong>Your Message:</strong><br><?php echo nl2br(esc_html($donation->comments)); ?></p>
                    <?php endif; ?>
                    
                    <p><strong>Tax Information:</strong><br>
                    Telugu Samiti is a registered non-profit organization. This receipt may be used for tax deduction purposes. Please consult your tax advisor for details.</p>
                    
                    <p>If you have any questions about your donation, please contact us.</p>
                    
                    <p>With gratitude,<br>
                    <strong>Telugu Samiti</strong></p>
                </div>
                
                <div class="footer">
                    <p>This is an automated receipt. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send admin notification
     */
    public static function send_admin_notification($order_id) {
        global $wpdb;
        
        // Get order
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
            $order_id
        ));
        
        if (!$order) return false;
        
        // Get donation
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, c.title as cause_title 
             FROM {$wpdb->prefix}tsn_donations d
             LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
             WHERE d.order_id = %d",
            $order_id
        ));
        
        // Get event if applicable
        $event_title = 'N/A';
        if ($donation->event_id) {
            $event = TSN_Events::get_event_by_id($donation->event_id);
            if ($event) {
                $event_title = $event->title;
            }
        }
        
        $admin_email = get_option('admin_email');
        $subject = 'New Donation Received - $' . number_format($order->total, 2);
        
        // Ensure From header is correct here too (although TSN_Email handles logic, explicit override is safer if needed)
        // But TSN_Email::send handles it, so we rely on that for admin emails. 
        // Admin emails go TO admin, FROM system.
        
        $message = '<h2>New Donation Received</h2>';
        $message .= '<p>A new donation has been received.</p>';
        
        $message .= '<h3>Details:</h3>';
        $message .= '<ul>';
        $message .= '<li><strong>Donor:</strong> ' . esc_html($order->buyer_name) . ' (' . esc_html($order->buyer_email) . ')</li>';
        $message .= '<li><strong>Amount:</strong> $' . number_format($order->total, 2) . '</li>';
        if ($donation->cause_title) {
            $message .= '<li><strong>Cause:</strong> ' . esc_html($donation->cause_title) . '</li>';
        }
        if ($donation->event_id) {
            $message .= '<li><strong>Event:</strong> ' . esc_html($event_title) . '</li>';
        }
        $message .= '<li><strong>Date:</strong> ' . date('F j, Y g:i A', strtotime($order->paid_at)) . '</li>';
        $message .= '</ul>';
        
        if (!empty($donation->comments)) {
            $message .= '<h3>Message from Donor:</h3>';
            $message .= '<p><em>' . nl2br(esc_html($donation->comments)) . '</em></p>';
        }
        
        $message .= '<p><a href="' . admin_url('admin.php?page=tsn-donations') . '">View in Admin Panel</a></p>';
        
        // Generate PDF Receipt for Admin
        $upload_dir = wp_upload_dir();
        $receipt_dir = $upload_dir['basedir'] . '/tsn-receipts';
        $file_name = 'Receipt-' . $order->order_number . '.pdf';
        $file_path = $receipt_dir . '/' . $file_name;
        
        if (!file_exists($file_path)) {
            self::generate_pdf_receipt($order, $donation, $file_path);
        }
        
        $attachments = array($file_path);
        
        return TSN_Email::send($admin_email, $subject, $message, $attachments);
    }

    /**
     * Get or Generate PDF URL
     */
    public static function get_pdf_url($order_id) {
        global $wpdb;
        
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d", $order_id));
        if (!$order) return false;
        
        $donation = $wpdb->get_row($wpdb->prepare("SELECT d.*, c.title as cause_title FROM {$wpdb->prefix}tsn_donations d LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id WHERE d.order_id = %d", $order_id));
        
        $upload_dir = wp_upload_dir();
        $receipt_dir = $upload_dir['basedir'] . '/tsn-receipts';
        if (!file_exists($receipt_dir)) wp_mkdir_p($receipt_dir);
        
        $file_name = 'Receipt-' . $order->order_number . '.pdf';
        $file_path = $receipt_dir . '/' . $file_name;
        
        // Generate if not exists
        if (!file_exists($file_path)) {
            self::generate_pdf_receipt($order, $donation, $file_path);
        }
        
        return $upload_dir['baseurl'] . '/tsn-receipts/' . $file_name;
    }

    /**
     * AJAX: Download Receipt
     */
    public static function ajax_download_receipt() {
        // Check for admin nonce or membership nonce
        $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';
        $nonce_action = $is_admin ? 'tsn_admin_nonce' : 'tsn_membership_nonce';
        
        check_ajax_referer($nonce_action, 'nonce');
        
        // Allow if user is admin OR member
        if ($is_admin) {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized Admin'));
            }
        } else {
            if (!is_user_logged_in() && !TSN_Membership_OTP::is_member_logged_in()) {
                wp_send_json_error(array('message' => 'Unauthorized Member'));
            }
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $url = self::get_pdf_url($order_id);
        
        if ($url) {
            wp_send_json_success(array('url' => $url));
        } else {
            wp_send_json_error(array('message' => 'Could not generate receipt'));
        }
    }

    /**
     * AJAX: Resend Receipt
     */
    public static function ajax_resend_receipt() {
        // Check for admin nonce or membership nonce
        $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';
        $nonce_action = $is_admin ? 'tsn_admin_nonce' : 'tsn_membership_nonce';
        
        check_ajax_referer($nonce_action, 'nonce');
        
        // Allow if user is admin OR member
        if ($is_admin) {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Unauthorized Admin'));
            }
        } else {
            if (!is_user_logged_in() && !TSN_Membership_OTP::is_member_logged_in()) {
                wp_send_json_error(array('message' => 'Unauthorized Member'));
            }
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (self::send_receipt($order_id)) {
            wp_send_json_success(array('message' => 'Receipt sent successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to send receipt'));
        }
    }
}
