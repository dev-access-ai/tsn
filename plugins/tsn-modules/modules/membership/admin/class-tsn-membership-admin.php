<?php
/**
 * Membership Admin class
 * 
 * Admin interface for member management
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Membership_Admin {
    
    public function __construct() {
        // Menu is registered in TSN_Core::add_admin_menu()
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tsn-modules',
            __('Memberships', 'tsn-modules'),
            __('Memberships', 'tsn-modules'),
            'manage_options',
            'tsn-memberships',
            array($this, 'render_members_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Member', 'tsn-modules'),
            __('Add Member', 'tsn-modules'),
            'manage_options',
            'tsn-add-member',
            array($this, 'render_add_member_page')
        );
    }
    
    /**
     * Render members list page
     */
    public function render_members_page() {
        global $wpdb;
        
        // Debug: Check if constant is defined
        if (!defined('TSN_MODULES_PATH')) {
            wp_die('TSN_MODULES_PATH constant is not defined. The plugin may not be loaded correctly.');
        }
        
        // Handle member deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['member_id'])) {
            $this->handle_delete_member($_GET['member_id']);
        }

        // Handle Resend Email
        if (isset($_GET['action']) && $_GET['action'] === 'resend_email' && isset($_GET['member_id'])) {
            check_admin_referer('resend_email_' . $_GET['member_id']);
            $member_id = intval($_GET['member_id']);
            $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_members WHERE id = %d", $member_id));
            
            if ($member) {
                if (class_exists('TSN_Membership_Emails')) {
                    // This call now includes the Admin CC by default helper logic we added earlier
                    $sent = TSN_Membership_Emails::send_welcome($member);
                    if ($sent) {
                        $redirect_url = remove_query_arg(array('action', 'member_id', '_wpnonce'), $_SERVER['REQUEST_URI']);
                        $redirect_url = add_query_arg('message', 'email_sent', $redirect_url);
                        echo '<script>window.location.href = "' . $redirect_url . '";</script>';
                        exit;
                    } else {
                        // Handle error (optional: add error param)
                        $redirect_url = remove_query_arg(array('action', 'member_id', '_wpnonce'), $_SERVER['REQUEST_URI']);
                        $redirect_url = add_query_arg('message', 'email_error', $redirect_url);
                        echo '<script>window.location.href = "' . $redirect_url . '";</script>';
                        exit;
                    }
                }
            }
        }
        
        // Get filter parameters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Build query
        $where = array("1=1");
        
        if ($status_filter !== 'all') {
            $where[] = $wpdb->prepare("status = %s", $status_filter);
        }
        
        if ($type_filter !== 'all') {
            $where[] = $wpdb->prepare("membership_type = %s", $type_filter);
        }
        
        if (!empty($search)) {
            $where[] = $wpdb->prepare(
                "(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR member_id LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        
        $allowed_sort_cols = array('member_id', 'first_name', 'email', 'membership_type', 'status', 'valid_to', 'created_at');
        if (!in_array($orderby, $allowed_sort_cols)) {
            $orderby = 'created_at';
        }
        
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
        
        $where_clause = implode(' AND ', $where);
        
        // Get members
        $members = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}tsn_members 
             WHERE {$where_clause} 
             ORDER BY {$orderby} {$order}"
        );
        
        // Get counts for filters
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members");
        $active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'active'");
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE status = 'pending'");
        
        // Check if view file exists
        $view_file = TSN_MODULES_PATH . 'admin/views/membership/list.php';
        if (!file_exists($view_file)) {
            wp_die('Members list view file not found at: ' . $view_file);
        }
        
        include $view_file;
    }
    
    /**
     * Render add/edit member page
     */
    public function render_add_member_page() {
        // Handle form submission
        if (isset($_POST['tsn_save_member']) && check_admin_referer('tsn_member_nonce')) {
            $this->handle_save_member();
        }
        
        $member = null;
        if (isset($_GET['member_id'])) {
            global $wpdb;
            $member = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_members WHERE id = %d",
                intval($_GET['member_id'])
            ));
        }
        
        include TSN_MODULES_PATH . 'admin/views/membership/add-edit.php';
    }
    
    /**
     * Handle save member
     */
    private function handle_save_member() {
        global $wpdb;
        
        $member_id_primary = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'country' => sanitize_text_field($_POST['country']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'membership_type' => sanitize_text_field($_POST['membership_type']),
            'status' => sanitize_text_field($_POST['status']),
            'payment_mode' => 'offline',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
        );

        // Handle Spouse Details
        $spouse = array();
        if (!empty($_POST['spouse_name'])) {
            $spouse['name'] = sanitize_text_field($_POST['spouse_name']);
            $spouse['email'] = sanitize_email($_POST['spouse_email']);
            $spouse['phone'] = sanitize_text_field($_POST['spouse_phone']);
        }
        $data['spouse_details'] = !empty($spouse) ? json_encode($spouse) : null;

        // Handle Children Details
        $children = array();
        if (isset($_POST['child_name']) && is_array($_POST['child_name'])) {
            foreach ($_POST['child_name'] as $index => $name) {
                if (!empty($name)) {
                    $children[] = array(
                        'name' => sanitize_text_field($name),
                        'age' => isset($_POST['child_age'][$index]) ? intval($_POST['child_age'][$index]) : 0,
                        'gender' => isset($_POST['child_gender'][$index]) ? sanitize_text_field($_POST['child_gender'][$index]) : ''
                    );
                }
            }
        }
        $data['children_details'] = !empty($children) ? json_encode($children) : null;
        
        if ($member_id_primary) {
            // Update existing member
            error_log('TSN: Updating member ID ' . $member_id_primary);
            
            $result = $wpdb->update(
                $wpdb->prefix . 'tsn_members',
                $data,
                array('id' => $member_id_primary),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result === false) {
                wp_die('Member update failed! Error: ' . $wpdb->last_error . '<br><a href="javascript:history.back()">Go Back</a>');
            }
            
            $redirect_url = admin_url('admin.php?page=tsn-memberships&message=updated');
        } else {
            // Generate member ID
            $member_id = TSN_Membership::generate_member_id($data['membership_type']);
            $data['member_id'] = $member_id;
            
            // Set validity dates
            if ($data['membership_type'] === 'lifetime') {
                $data['valid_from'] = current_time('mysql', false);
                $data['valid_to'] = null;
            } else {
                $data['valid_from'] = current_time('mysql', false);
                $target_year = isset($_POST['membership_year']) ? intval($_POST['membership_year']) : date('Y');
                $data['valid_to'] = $target_year . '-12-31';
            }
            
            error_log('TSN: Inserting member with data: ' . print_r($data, true));
            
            // Insert new member
            $result = $wpdb->insert(
                $wpdb->prefix . 'tsn_members',
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            error_log('TSN: Insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            error_log('TSN: Insert ID: ' . $wpdb->insert_id);
            error_log('TSN: Last error: ' . $wpdb->last_error);
            error_log('TSN: Last query: ' . $wpdb->last_query);
            
            if (!$result) {
                wp_die('Member creation failed! Database error: ' . $wpdb->last_error . '<br>Query: ' . $wpdb->last_query . '<br><a href="javascript:history.back()">Go Back</a>');
            }
            
            $redirect_url = admin_url('admin.php?page=tsn-memberships&message=added');
        }
        
        // Use JS redirect to avoid whitespace/header issues
        echo '<script>window.location.href = "' . $redirect_url . '";</script>';
        exit;
    }
    
    /**
     * Handle delete member
     */
    private function handle_delete_member($member_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_member_' . $member_id)) {
            wp_die('Invalid nonce');
        }
        
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'tsn_members',
            array('id' => $member_id),
            array('%d')
        );
        
        // Use JS redirect to avoid whitespace/header issues
        echo '<script>window.location.href = "' . admin_url('admin.php?page=tsn-memberships&message=deleted') . '";</script>';
        exit;
    }
    
    // AJAX handlers for admin
    public static function ajax_add_offline_member() {
        check_ajax_referer('tsn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        // This is handled through the regular form now
        wp_send_json_success(array('message' => 'Use the Add Member page'));
    }
    
    public static function ajax_export_members() {
        check_ajax_referer('tsn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        global $wpdb;
        
        $members = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}tsn_members ORDER BY created_at DESC",
            ARRAY_A
        );
        
        if (empty($members)) {
            wp_send_json_error(array('message' => 'No members to export'));
        }
        
        // Generate CSV
        $filename = 'tsn-members-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array(
            'Member ID', 'First Name', 'Last Name', 'Email', 'Phone',
            'Address', 'City', 'State', 'Country', 'Zip Code',
            'Membership Type', 'Valid From', 'Valid To', 'Status',
            'Payment Mode', 'Created At'
        ));
        
        // Data
        foreach ($members as $member) {
            fputcsv($output, array(
                $member['member_id'],
                $member['first_name'],
                $member['last_name'],
                $member['email'],
                $member['phone'],
                $member['address'],
                $member['city'],
                $member['state'],
                $member['country'],
                $member['zip_code'],
                $member['membership_type'],
                $member['valid_from'],
                $member['valid_to'],
                $member['status'],
                $member['payment_mode'],
                $member['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }
}
