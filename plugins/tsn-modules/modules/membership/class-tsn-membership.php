<?php
/**
 * Membership core class
 * 
 * Handles membership registration, management, and display
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Membership {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('tsn_membership_form', array($this, 'render_form'));
        add_shortcode('tsn_member_dashboard', array($this, 'render_dashboard'));
        add_shortcode('tsn_renew_membership', array($this, 'render_renew_form'));
        add_shortcode('tsn_edit_profile', array($this, 'render_edit_profile')); // New Shortcode
        
        // Add rewrite rules for member dashboard
        add_action('init', array($this, 'add_rewrite_rules'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_tsn_submit_membership', array(__CLASS__, 'ajax_submit_membership'));
        add_action('wp_ajax_nopriv_tsn_submit_membership', array(__CLASS__, 'ajax_submit_membership'));
        add_action('wp_ajax_tsn_update_profile', array(__CLASS__, 'ajax_update_profile'));
        add_action('wp_ajax_nopriv_tsn_update_profile', array(__CLASS__, 'ajax_update_profile'));
        add_action('wp_ajax_tsn_send_otp', array('TSN_Membership_OTP', 'send_otp'));
        add_action('wp_ajax_nopriv_tsn_send_otp', array('TSN_Membership_OTP', 'send_otp'));
        
        // Register update profile shortcode
        add_shortcode('tsn_update_profile', array($this, 'render_update_profile'));
        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Payment Cancel Listener
        add_action('template_redirect', array($this, 'handle_payment_cancellation'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script('tsn-membership', TSN_MODULES_URL . 'assets/js/tsn-membership.js', array('jquery'), '1.0', true);
        
        wp_localize_script('tsn-membership', 'tsn_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => get_rest_url(null, 'tsn/v1/'),
            'nonce' => wp_create_nonce('tsn_membership_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }

    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route('tsn/v1', '/otp/request', array(
            'methods' => 'POST',
            'callback' => array('TSN_Membership_OTP', 'rest_request_otp'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('tsn/v1', '/otp/verify', array(
            'methods' => 'POST',
            'callback' => array('TSN_Membership_OTP', 'rest_verify_otp'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Register the custom query var
        add_rewrite_tag('%tsn_member_dashboard%', '([^&]+)');
        
        // Add the rewrite rule (this works if you have a page or template to handle it)
        add_rewrite_rule('^member-dashboard/?$', 'index.php?tsn_member_dashboard=1', 'top');
        
        // Register update profile query var and rule
        add_rewrite_tag('%tsn_update_profile%', '([^&]+)');
        add_rewrite_rule('^update-profile/?$', 'index.php?tsn_update_profile=1', 'top');
    }
    
    /**
     * Render membership form
     */
    public static function render_form($atts) {
        ob_start();
        include TSN_MODULES_PATH . 'modules/membership/templates/membership-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render member dashboard
     */
    public static function render_dashboard($atts) {
        // Check if member is logged in via OTP session
        if (!TSN_Membership_OTP::is_member_logged_in()) {
            $login_url = home_url('/member-login/');
            // Use JS Redirect for better UX
            return '<script>window.location.href = "' . esc_url($login_url) . '";</script><p>Redirecting to <a href="' . esc_url($login_url) . '">Login Page</a>...</p>';
        }
        
        ob_start();
        include TSN_MODULES_PATH . 'modules/membership/templates/member-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Render update profile form
     */
    public static function render_update_profile($atts) {
        // Check if member is logged in via OTP session
        if (!TSN_Membership_OTP::is_member_logged_in()) {
            return '<p>Please <a href="' . home_url('/member-login/') . '">log in</a> to update your profile.</p>';
        }
        
        ob_start();
        include TSN_MODULES_PATH . 'modules/membership/templates/update-profile.php';
        return ob_get_clean();
    }

    /**
     * Render renewal form
     */
    public static function render_renew_form($atts) {
        ob_start();
        include TSN_MODULES_PATH . 'modules/membership/templates/renew-membership.php';
        return ob_get_clean();
    }

    /**
     * Render edit profile form
     */
    public static function render_edit_profile($atts) {
        // Check if member is logged in via OTP session
        if (!TSN_Membership_OTP::is_member_logged_in()) {
            return '<p>Please <a href="' . home_url('/member-login/') . '">log in</a> to edit your profile.</p>';
        }
        
        ob_start();
        include TSN_MODULES_PATH . 'modules/membership/templates/edit-profile.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for membership submission
     */
    public static function ajax_submit_membership() {
        // Start output buffering to catch any stray PHP warnings/notices
        ob_start();
        
        try {
            // Verify nonce
            TSN_Security::validate_ajax_request('tsn_membership_nonce');
            
            // Rate limiting check
            $ip = TSN_Security::get_client_ip();
            if (!TSN_Security::check_rate_limit('membership_' . $ip, 20, 3600)) {
                $remaining = TSN_Security::get_rate_limit_remaining_time('membership_' . $ip);
                ob_clean(); // Clean buffer before sending response
                wp_send_json_error(array(
                    'message' => 'Too many attempts. Please try again in ' . ceil($remaining / 60) . ' minutes.'
                ));
            }
            
            // Sanitize and validate input
            $data = array(
                'first_name' => TSN_Security::sanitize_input($_POST['first_name'], 'text'),
                'last_name' => TSN_Security::sanitize_input($_POST['last_name'], 'text'),
                'email' => TSN_Security::sanitize_input($_POST['email'], 'email'),
                'phone' => TSN_Security::sanitize_input($_POST['phone'], 'text'),
                'address' => TSN_Security::sanitize_input($_POST['address'], 'textarea'),
                'city' => TSN_Security::sanitize_input($_POST['city'], 'text'),
                'state' => TSN_Security::sanitize_input($_POST['state'], 'text'),
                'country' => TSN_Security::sanitize_input($_POST['country'], 'text'),
                'zip_code' => TSN_Security::sanitize_input($_POST['zip_code'], 'text'),
                'membership_type' => TSN_Security::sanitize_input($_POST['membership_type'], 'text'),
                'membership_year' => isset($_POST['membership_year']) ? intval($_POST['membership_year']) : date('Y'),
            );
            
            // Validation
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
                ob_clean();
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
            
            if (!TSN_Security::validate_email($data['email'])) {
                ob_clean();
                wp_send_json_error(array('message' => 'Invalid email address.'));
            }
            
            if (!in_array($data['membership_type'], array('annual', 'lifetime', 'student'))) {
                ob_clean();
                wp_send_json_error(array('message' => 'Invalid membership type.'));
            }
            
            // Renewal Logic Check
            $is_renewal = isset($_POST['is_renewal']) && $_POST['is_renewal'] == '1';
            $member_id = 0;
            global $wpdb;

            if ($is_renewal) {
                // Validate member ID match with email
                $existing_member = self::get_member_by_email($data['email']);
                if (!$existing_member) {
                     wp_send_json_error(array('message' => 'Member record not found for this email.'));
                }
                if ($existing_member->id != $_POST['member_id']) {
                     wp_send_json_error(array('message' => 'Security mismatch. Please refresh and try again.'));
                }
                $member_id = $existing_member->id;
            } else {
                // New Registration: Check if email already exists
                $existing_user = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tsn_members WHERE email = %s ORDER BY id DESC LIMIT 1",
                    $data['email']
                ));
                
                if ($existing_user && $existing_user->status === 'active') {
                    wp_send_json_error(array('message' => 'This email is already registered. Please log in to renew.'));
                }
                
                if ($existing_user && ($existing_user->status === 'pending' || $existing_user->status === 'inactive')) {
                    // Delete existing pending/inactive record to start fresh
                    $delete_result = $wpdb->delete(
                        $wpdb->prefix . 'tsn_members',
                        array('id' => $existing_user->id),
                        array('%d')
                    );
                    
                    if ($delete_result === false) {
                        error_log('TSN Error: Failed to delete pending member ' . $existing_user->id);
                        // Continue anyway to try creating new one?
                    } else {
                        error_log('TSN Info: Deleted stuck pending member ' . $existing_user->id . ' to allow retry.');
                    }
                    
                    // Proceed to create completely new record
                    $member_id = self::create_member($data);
                    
                    if (!$member_id) {
                        wp_send_json_error(array('message' => 'Failed to create membership record. Please try again.'));
                    }
                } else {
                    // Create new membership record
                    $member_id = self::create_member($data);
                    
                    if (!$member_id) {
                        wp_send_json_error(array('message' => 'Failed to create membership record. Please try again.'));
                    }
                }
            }
            
            // Get amount based on membership type
            $amount = self::get_membership_price($data['membership_type']);
            
            // Check if in development mode (localhost)
            $is_local = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || 
                        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
            
            if ($is_local) {
                // Development mode: Skip payment and activate immediately
                $wpdb->update(
                    $wpdb->prefix . 'tsn_members',
                    array('status' => 'active'),
                    array('id' => $member_id),
                    array('%s'),
                    array('%d')
                );
                
                // Record fake transaction
                $wpdb->insert(
                    $wpdb->prefix . 'tsn_member_transactions',
                    array(
                        'member_id' => $member_id,
                        'transaction_id' => 'DEV-' . time(),
                        'amount' => $amount,
                        'payment_method' => 'development',
                        'payment_reference' => 'Local testing - no payment required',
                        'status' => 'completed'
                    ),
                    array('%d', '%s', '%f', '%s', '%s', '%s')
                );
                
                // Get member data and send welcome email
                $member = self::get_member_by_id($member_id);
                if (class_exists('TSN_Membership_Emails')) {
                     TSN_Membership_Emails::send_welcome($member);
                }
                
                wp_send_json_success(array(
                    'message' => 'Membership activated successfully! (Development Mode - No Payment Required)',
                    'member_id' => $member_id,
                    'redirect_url' => home_url('/member-dashboard/')
                ));
            }
            
            // Production mode: Create PayPal order
            $payment = new TSN_Payment();
            $order_result = $payment->create_order(
                $amount,
                'USD',
                ucfirst($data['membership_type']) . ' Membership - Telugu Samiti of Nebraska',
                'member_' . $member_id
            );
            
            // Store session fallback (Critical for when PayPal custom_id is dropped)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['tsn_pending_member_id'] = $member_id;
            $_SESSION['tsn_pending_payment_type'] = $is_renewal ? 'renewal' : 'membership';
            
            if ($is_renewal) {
                 $_SESSION['tsn_renewal_target_type'] = $data['membership_type'];
            }
            
            if (!$order_result['success']) {
                error_log('TSN Payment Error: ' . $order_result['message']);
                ob_clean();
                wp_send_json_error(array('message' => 'Payment processing error. Please try again.'));
            }
            
            ob_clean(); // Ensure clean output
            wp_send_json_success(array(
                'message' => 'Membership application created. Redirecting to payment...',
                'member_id' => $member_id,
                'payment_url' => $order_result['approval_url']
            ));
        } catch (Exception $e) {
            error_log('TSN Critical Membership Error: ' . $e->getMessage());
            ob_clean();
            wp_send_json_error(array('message' => 'An internal error occurred. Please try again or contact support.'));
        } catch (Throwable $t) {
            error_log('TSN Critical Membership Error (Throwable): ' . $t->getMessage());
            ob_clean();
            wp_send_json_error(array('message' => 'An internal error occurred. Please try again or contact support.'));
        }
    }
    
    /**
     * AJAX handler for profile update
     */
    /**
     * AJAX handler for profile update
     */
    public static function ajax_update_profile() {
        error_log('TSN Profile Update: Started');
        
        // Verify nonce
        // Verify nonce
        // TSN_Security::validate_ajax_request('tsn_membership_nonce'); 
        if (!check_ajax_referer('tsn_membership_nonce', 'nonce', false)) {
            error_log('TSN Profile Update: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed. Please refresh.'));
        }
        
        // Get logged in member
        $member = TSN_Membership_OTP::get_logged_in_member();
        if (!$member) {
            error_log('TSN Profile Update: No logged in member found');
            wp_send_json_error(array('message' => 'Session expired. Please log in again.'));
        }
        error_log('TSN Profile Update: Member ID ' . $member->id);
        
        // Sanitize and validate input
        $data = array(
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'country' => sanitize_text_field($_POST['country']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
        );
        error_log('TSN Profile Update: Basic Data: ' . print_r($data, true));
        
        // Handle Family Details
        // Spouse
        $spouse = array();
        if (!empty($_POST['spouse_name'])) {
            $spouse['name'] = sanitize_text_field($_POST['spouse_name']);
            $spouse['email'] = sanitize_email($_POST['spouse_email']);
            $spouse['phone'] = sanitize_text_field($_POST['spouse_phone']);
        }
        // Force array structure even if empty for consistency? No, null is fine if empty.
        // But if clearing data...
        $data['spouse_details'] = !empty($spouse) ? json_encode($spouse) : '';
        error_log('TSN Profile Update: Spouse Data: ' . $data['spouse_details']);
        
        // Children
        $children = array();
        if (isset($_POST['child_name']) && is_array($_POST['child_name'])) {
            foreach ($_POST['child_name'] as $index => $name) {
                if (!empty($name)) {
                    $dob = isset($_POST['child_dob'][$index]) ? sanitize_text_field($_POST['child_dob'][$index]) : '';
                    $age = 0;
                    
                    // Calculate Age from DOB if present
                    if (!empty($dob)) {
                        try {
                            $dob_date = new DateTime($dob);
                            $now = new DateTime();
                            $age = $now->diff($dob_date)->y;
                        } catch (Exception $e) {
                            $age = 0;
                        }
                    } elseif (isset($_POST['child_age'][$index])) {
                        // Fallback to manual age if DOB not provided
                        $age = intval($_POST['child_age'][$index]);
                    }

                    $children[] = array(
                        'name' => sanitize_text_field($name),
                        'dob' => $dob,
                        'age' => $age,
                        'gender' => isset($_POST['child_gender'][$index]) ? sanitize_text_field($_POST['child_gender'][$index]) : ''
                    );
                }
            }
        }
        $data['children_details'] = !empty($children) ? json_encode($children) : '';
        error_log('TSN Profile Update: Children Data: ' . $data['children_details']);
        
        // Handle Profile Photo
        if (!empty($_FILES['profile_photo']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $uploaded = wp_handle_upload($_FILES['profile_photo'], array('test_form' => false));
            
            if (isset($uploaded['file']) && !isset($uploaded['error'])) {
                $data['profile_photo'] = $uploaded['url'];
                error_log('TSN Profile Update: Photo uploaded: ' . $uploaded['url']);
            } else {
                error_log('TSN Profile Update: Photo upload error: ' . $uploaded['error']);
                wp_send_json_error(array('message' => 'Error uploading photo: ' . $uploaded['error']));
            }
        }
        
        // Update database
        global $wpdb;
        
        // Prepare format array based on data size (all strings)
        $format = array_fill(0, count($data), '%s');
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'tsn_members',
            $data,
            array('id' => $member->id),
            $format,
            array('%d')
        );
        
        error_log('TSN Profile Update: DB Update Complete');

        if ($updated !== false) {
             wp_send_json_success(array(
                 'message' => 'Profile updated successfully!',
                 'redirect_url' => home_url('/member-dashboard/')
             ));
        } else {
             error_log('TSN Profile Update: DB Error: ' . $wpdb->last_error);
             if ($wpdb->last_error) {
                 wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
             } else {
                 // Should imply 0 rows updated
                 wp_send_json_success(array(
                     'message' => 'Profile saved (No changes detected).',
                     'redirect_url' => home_url('/member-dashboard/')
                 ));
             }
        }
        exit; // Ensure exit
    }
    
    /**
     * Create new member record
     */
    public static function create_member($data, $payment_mode = 'online') {
        global $wpdb;
        
        // Generate member ID
        $member_id_string = self::generate_member_id($data['membership_type']);
        
        // Calculate validity dates
        $valid_from = current_time('Y-m-d');
        
        // Determine Target Year for Validity
        $target_year = isset($data['membership_year']) ? intval($data['membership_year']) : date('Y');
        
        // If not lifetime, set valid_to to Dec 31 of target year
        $valid_to = $data['membership_type'] === 'lifetime' ? null : $target_year . '-12-31';
        
        // Insert member
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'tsn_members',
            array(
                'member_id' => $member_id_string,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => isset($data['phone']) ? $data['phone'] : null,
                'address' => isset($data['address']) ? $data['address'] : null,
                'city' => isset($data['city']) ? $data['city'] : null,
                'state' => isset($data['state']) ? $data['state'] : null,
                'country' => isset($data['country']) ? $data['country'] : 'USA',
                'zip_code' => isset($data['zip_code']) ? $data['zip_code'] : null,
                'membership_type' => $data['membership_type'],
                'valid_from' => $valid_from,
                'valid_to' => $valid_to,
                'status' => $payment_mode === 'offline' ? 'active' : 'pending',
                'payment_mode' => $payment_mode,
                'notes' => isset($data['notes']) ? $data['notes'] : null
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            $id = $wpdb->insert_id;
            
            // Log creation
            error_log('TSN Membership created: ' . $member_id_string . ' (' . $data['email'] . ')');
            
            return $id;
        }
        
        return false;
    }
    
    /**
     * Generate unique member ID
     */
    /**
     * Generate unique member ID
     */
    public static function generate_member_id($type = 'annual') {
        global $wpdb;
        
        $year = date('Y');
        
        // Determine pattern and prefix based on type
        if ($type === 'lifetime') {
            // Lifetime: L-<IncrementValue> (e.g., L-1)
            $prefix = 'L-';
            $pattern_sql = 'L-%';
            $regex_pattern = '/^L-(\d+)$/';
            $use_year = false;
        } elseif ($type === 'annual') {
            // Annual: A<Year>-<IncrementValue> (e.g., A2025-1)
            $prefix = 'A' . $year . '-';
            $pattern_sql = 'A' . $year . '-%';
            $regex_pattern = '/^A' . $year . '-(\d+)$/';
            $use_year = true;
        } elseif ($type === 'student') {
            // Student: S<Year>-<IncrementValue> (e.g., S2025-1)
            $prefix = 'S' . $year . '-';
            $pattern_sql = 'S' . $year . '-%';
            $regex_pattern = '/^S' . $year . '-(\d+)$/';
            $use_year = true;
        } else {
            // Default/Fallback
            $tsn_prefix = get_option('tsn_member_id_prefix', 'TSN');
            $prefix = $tsn_prefix . $year . '-';
            $pattern_sql = $tsn_prefix . $year . '-%';
            $regex_pattern = '/^' . preg_quote($tsn_prefix, '/') . $year . '-(\d+)$/';
            $use_year = true;
        }
        
        // Get the highest number used
        // We need to be careful to extract the number correctly.
        // SQL 'LIKE' finds strings starting with pattern.
        // We sort by length first then value to ensure 10 > 2.
        // Actually, just sorting by id DESC might not be enough if IDs were generated out of order or if there are gaps, but usually ID DESC is fine for creation order.
        // However, if we deleted the last member, we might want to check the max string? 
        // Standard practice: Just querying the matching IDs and extracting max number.
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT member_id FROM {$wpdb->prefix}tsn_members 
             WHERE member_id LIKE %s",
            $pattern_sql
        ));
        
        $max_number = 0;
        
        foreach ($results as $row) {
            if (preg_match($regex_pattern, $row->member_id, $matches)) {
                $num = intval($matches[1]);
                if ($num > $max_number) {
                    $max_number = $num;
                }
            }
        }
        
        $next_number = $max_number + 1;
        
        if ($type === 'lifetime') {
            // Pad to 3 digits for Lifetime (e.g. 001)
             return sprintf('%s%03d', $prefix, $next_number);
        } else {
            // No padding requested for Annual (e.g. 1)
            // But usually padding is good. The example "A2026-1" shows no padding?
            // "Use A<Year>-<IncrementValue> pattern (e.g., A2026-1)." -> Implies no padding.
            return sprintf('%s%d', $prefix, $next_number);
        }
    }
    
    /**
     * Get membership price
     */
    public static function get_membership_price($type) {
        $prices = array(
            'annual' => get_option('tsn_membership_annual_price', 35),
            'lifetime' => get_option('tsn_membership_lifetime_price', 150),
            'student' => get_option('tsn_membership_student_price', 5)
        );
        
        return isset($prices[$type]) ? floatval($prices[$type]) : 0;
    }
    
    /**
     * Check if email exists
     */
    public static function email_exists($email) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tsn_members WHERE email = %s AND status != 'inactive'",
            $email
        ));
        
        return $count > 0;
    }
    
    /**
     * Get member by email
     */
    public static function get_member_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_members WHERE email = %s AND status = 'active'",
            $email
        ));
    }
    
    /**
     * Get member by ID
     */
    public static function get_member_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_members WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Activate member after payment
     */
    /**
     * Activate member after payment (Handle both New & Renewal)
     */
    public static function activate_member($member_id, $transaction_data) {
        // ... existing implementation ...
        global $wpdb;
        
        // Update member status
        error_log("TSN Debug: Activating member ID: $member_id");
        
        $member = self::get_member_by_id($member_id);
        if (!$member) {
             error_log("TSN Error: Member not found in activate_member");
             return false;
        }

        // Determine if renewal
        $is_renewal = false;
        $target_type = $member->membership_type; 
        
        if (isset($_SESSION['tsn_pending_payment_type']) && $_SESSION['tsn_pending_payment_type'] === 'renewal') {
            $is_renewal = true;
            if (isset($_SESSION['tsn_renewal_target_type'])) {
                $target_type = $_SESSION['tsn_renewal_target_type'];
            }
        }
        
        // Calculate new dates
        $update_data = array('status' => 'active');
        $format = array('%s');
        
        if ($is_renewal) {
            error_log("TSN Debug: Processing Renewal for Member $member_id to type $target_type");
            
            // Handle Type Change (e.g. Annual -> Lifetime)
            if ($target_type !== $member->membership_type) {
                // Determine new ID if type changes? Maybe keep same ID pattern but technically pattern changes.
                // Keeping same ID is safer for history, unless client wants ID to reflect type (A vs L).
                // "Generate unique member ID" suggests ID reflects type.
                // Let's generate new ID pattern if type changed drastically (Annual to Lifetime)
                if ($target_type === 'lifetime' && $member->membership_type !== 'lifetime') {
                     $new_id = self::generate_member_id('lifetime');
                     $update_data['member_id'] = $new_id;
                     $format[] = '%s';
                     $update_data['membership_type'] = 'lifetime';
                     $format[] = '%s';
                     $update_data['valid_to'] = null; // No expiry
                     $format[] = '%s';
                }
            } elseif ($target_type === 'annual') {
                // Annual Renewal: Extend 1 Year
                $current_expiry = $member->valid_to ? strtotime($member->valid_to) : time();
                if ($current_expiry < time()) {
                    // Already expired: Start from today? Or extend from previous?
                    // Usually extend from today to avoid charging for past dead time.
                    $new_expiry = date('Y-m-d', strtotime('+1 year')); 
                } else {
                    // Active: Add 1 year to current expiry
                    $new_expiry = date('Y-m-d', strtotime('+1 year', $current_expiry));
                }
                $update_data['valid_to'] = $new_expiry;
                $format[] = '%s';
            }
            // Clear session flags
             unset($_SESSION['tsn_pending_payment_type']);
             unset($_SESSION['tsn_renewal_target_type']);
        }
        
        $updated = $wpdb->update(
            $wpdb->prefix . 'tsn_members',
            $update_data,
            array('id' => $member_id),
            $format,
            array('%d')
        );
        
        error_log("TSN Debug: DB update result: " . ($updated === false ? 'FALSE (Error)' : $updated . ' rows'));
        if ($updated === false) {
            error_log("TSN Debug: DB Error: " . $wpdb->last_error);
        }
        
        // Check if update was successful (returns false on error, number of rows on success)
        // Note: Returns 0 if data matches existing data (already active), which is still a "success" for us
        if ($updated !== false) {
            // Check if transaction already exists to avoid duplicates
            $existing_txn = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tsn_member_transactions WHERE transaction_id = %s",
                $transaction_data['transaction_id']
            ));
            
            if (!$existing_txn) {
                // Record transaction
                $wpdb->insert(
                    $wpdb->prefix . 'tsn_member_transactions',
                    array(
                        'member_id' => $member_id,
                        'transaction_id' => $transaction_data['transaction_id'],
                        'amount' => $transaction_data['amount'],
                        'payment_method' => 'paypal',
                        'payment_reference' => isset($transaction_data['reference']) ? $transaction_data['reference'] : null,
                        'status' => 'completed'
                    ),
                    array('%d', '%s', '%f', '%s', '%s', '%s')
                );
            }
            
            // Get member data
            $member = self::get_member_by_id($member_id);
            
            //Send welcome/renewal email (wrap in try-catch just in case)
            try {
                if (class_exists('TSN_Membership_Emails')) {
                    if ($is_renewal) {
                        // Ideally send_renewal_confirmation but fallback to welcome if strict
                         TSN_Membership_Emails::send_welcome($member); 
                    } else {
                        TSN_Membership_Emails::send_welcome($member);
                    }
                } else {
                    error_log('TSN Error: TSN_Membership_Emails class not found!');
                }
            } catch (Exception $e) {
                error_log('TSN Error sending email: ' . $e->getMessage());
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Handle Payment Cancellation
     * Deletes pending member if payment is cancelled
     */
    public function handle_payment_cancellation() {
        if (!session_id()) session_start();
        
        // Check if on payment cancelled page/URL
        if (strpos($_SERVER['REQUEST_URI'], '/payment-cancelled/') !== false) {
            // Check for pending member session
            if (isset($_SESSION['tsn_pending_member_id']) && !empty($_SESSION['tsn_pending_member_id'])) {
                $pending_id = intval($_SESSION['tsn_pending_member_id']);
                
                global $wpdb;
                
                // Double check it's still pending (don't delete if they managed to active somehow)
                $status = $wpdb->get_var($wpdb->prepare(
                    "SELECT status FROM {$wpdb->prefix}tsn_members WHERE id = %d",
                    $pending_id
                ));
                
                if ($status === 'pending') {
                    // DELETE
                    $wpdb->delete(
                        $wpdb->prefix . 'tsn_members',
                        array('id' => $pending_id),
                        array('%d')
                    );
                    error_log("TSN Info: Deleted pending member ID $pending_id due to payment cancellation.");
                }
                
                // Clear session
                unset($_SESSION['tsn_pending_member_id']);
                if (isset($_SESSION['tsn_pending_payment_type'])) unset($_SESSION['tsn_pending_payment_type']);
                if (isset($_SESSION['tsn_renewal_target_type'])) unset($_SESSION['tsn_renewal_target_type']);
            }
        }
    }
}
