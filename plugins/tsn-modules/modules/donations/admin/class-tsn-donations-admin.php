<?php
/**
 * Donations Admin Class
 */

if (!defined('ABSPATH')) exit;

class TSN_Donations_Admin {
    
    public function __construct() {
        // Menu is registered in TSN_Core::add_admin_menu()
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tsn-modules',
            __('Donations', 'tsn-modules'),
            __('Donations', 'tsn-modules'),
            'manage_options',
            'tsn-donations',
            array($this, 'render_donations_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Donation Causes', 'tsn-modules'),
            __('Donation Causes', 'tsn-modules'),
            'manage_options',
            'tsn-donation-causes',
            array($this, 'render_causes_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Cause', 'tsn-modules'),
            __('Add Cause', 'tsn-modules'),
            'manage_options',
            'tsn-add-cause',
            array($this, 'render_add_cause_page')
        );
    }
    
    public function render_donations_page() {
        // Handle offline donation
        if (isset($_POST['tsn_add_offline_donation']) && check_admin_referer('tsn_offline_donation_nonce')) {
            $this->handle_offline_donation($_POST);
        }
        
        include TSN_MODULES_PATH . 'admin/views/donations/list.php';
    }
    
    public function render_causes_page() {
        // Handle delete
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['cause_id'])) {
            check_admin_referer('delete_cause_' . $_GET['cause_id']);
            $this->delete_cause($_GET['cause_id']);
            $this->delete_cause($_GET['cause_id']);
            echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-donation-causes&message=deleted') . '";</script>';
            exit;
        }
        
        include TSN_MODULES_PATH . 'admin/views/donations/causes-list.php';
    }
    
    public function render_add_cause_page() {
        // Handle save
        if (isset($_POST['tsn_save_cause']) && check_admin_referer('tsn_cause_nonce')) {
            $this->handle_save_cause($_POST);
        }
        
        include TSN_MODULES_PATH . 'admin/views/donations/add-edit-cause.php';
    }
    
    private function handle_save_cause($data) {
        global $wpdb;
        
        $cause_id = isset($data['cause_id']) ? intval($data['cause_id']) : 0;
        
        $cause_data = array(
            'title' => sanitize_text_field($data['title']),
            'slug' => sanitize_title($data['title']),
            'short_description' => sanitize_textarea_field($data['description']),
            'goal_amount' => floatval($data['goal']),
            'is_active' => ($data['status'] === 'active') ? 1 : 0,
            'display_order' => intval($data['sort_order'])
        );
        
        if ($cause_id) {
            $result = $wpdb->update(
                $wpdb->prefix . 'tsn_donation_causes',
                $cause_data,
                array('id' => $cause_id),
                array('%s', '%s', '%s', '%f', '%d', '%d'),
                array('%d')
            );
            
            if ($result === false) {
                wp_die('Cause update failed! DB Error: ' . $wpdb->last_error . '<br><a href="javascript:history.back()">Go Back</a>');
            }
            
            // Use JS redirect to avoid whitespace/header issues
            echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-donation-causes&message=updated') . '";</script>';
            exit;
        } else {
            error_log('TSN: Inserting cause with data: ' . print_r($cause_data, true));
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'tsn_donation_causes',
                $cause_data,
                array('%s', '%s', '%s', '%f', '%d', '%d')
            );
            
            error_log('TSN: Insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            error_log('TSN: Last error: ' . $wpdb->last_error);
            error_log('TSN: Last query: ' . $wpdb->last_query);
            
            if (!$result) {
                wp_die('Cause creation failed! DB Error: ' . $wpdb->last_error . '<br>Query: ' . $wpdb->last_query . '<br><a href="javascript:history.back()">Go Back</a>');
            }
            
            // Use JS redirect
            echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-donation-causes&message=added') . '";</script>';
        }
        exit;
    }
    
    private function delete_cause($cause_id) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'tsn_donation_causes',
            array('is_active' => 0),
            array('id' => $cause_id),
            array('%d'),
            array('%d')
        );
    }
    
    private function handle_offline_donation($data) {
        global $wpdb;
        
        $amount = floatval($data['amount']);
        $order_number = 'DON-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Create order
        $wpdb->insert(
            $wpdb->prefix . 'tsn_orders',
            array(
                'order_number' => $order_number,
                'order_type' => 'donation',
                'buyer_name' => sanitize_text_field($data['donor_name']),
                'buyer_email' => sanitize_email($data['donor_email']),
                'buyer_phone' => sanitize_text_field($data['donor_phone']),
                'subtotal' => $amount,
                'total' => $amount,
                'status' => 'completed',
                'payment_method' => sanitize_text_field($data['payment_method']),
                'payment_reference' => sanitize_text_field($data['payment_reference']),
                'paid_at' => current_time('mysql'),
                'notes' => sanitize_textarea_field($data['notes'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
        );
        
        $order_id = $wpdb->insert_id;
        
        // Create donation record
        $result = $wpdb->insert(
            $wpdb->prefix . 'tsn_donations',
            array(
                'order_id' => $order_id,
                'donation_id' => $order_number,
                'cause_id' => isset($data['cause_id']) && $data['cause_id'] ? intval($data['cause_id']) : null,
                'amount' => $amount,
                'donor_name' => $data['donor_name'],
                'donor_email' => $data['donor_email'],
                'donor_phone' => $data['donor_phone'],
                'anonymous' => isset($data['is_anonymous']) ? 1 : 0,
                'comments' => sanitize_textarea_field($data['notes'])
            ),
            array('%d', '%s', '%d', '%f', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            error_log('TSN Offline Donation Error: ' . $wpdb->last_error);
            wp_die('Failed to record donation. Error: ' . $wpdb->last_error);
        }
        
        // Update cause total
        if (isset($data['cause_id']) && $data['cause_id']) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}tsn_donation_causes 
                 SET raised_amount = raised_amount + %f 
                 WHERE id = %d",
                $amount,
                $data['cause_id']
            ));
        }
        
        // Send receipt
        TSN_Donation_Receipt::send_receipt($order_id);
        
        // Use JS redirect
        echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-donations&message=added') . '";</script>';
        exit;
    }
    
    public static function ajax_export_donations() {
        check_ajax_referer('tsn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $donations = $wpdb->get_results(
            "SELECT 
                d.*, 
                o.order_number, 
                o.payment_method, 
                o.payment_reference, 
                o.paid_at,
                c.title as cause_title
             FROM {$wpdb->prefix}tsn_donations d
             JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
             LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
             WHERE o.status = 'completed'
             ORDER BY d.created_at DESC",
            ARRAY_A
        );
        
        $filename = 'donations-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array(
            'Order Number', 'Donor Name', 'Email', 'Phone', 'Amount',
            'Cause', 'Payment Method', 'Reference', 'Date', 'Message'
        ));
        
        foreach ($donations as $donation) {
            fputcsv($output, array(
                $donation['order_number'],
                $donation['donor_name'],
                $donation['donor_email'],
                $donation['donor_phone'],
                '$' . number_format($donation['amount'], 2),
                $donation['cause_title'] ?: 'General Fund',
                ucfirst($donation['payment_method']),
                $donation['payment_reference'],
                date('M j, Y', strtotime($donation['paid_at'])),
                $donation['message']
            ));
        }
        
        fclose($output);
        exit;
    }
}
