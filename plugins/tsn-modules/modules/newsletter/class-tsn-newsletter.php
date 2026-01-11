<?php
/**
 * Newsletter Module Class
 * 
 * Handles subscribers and newsletter integration
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Newsletter {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_tsn_subscribe', array($this, 'handle_ajax_subscribe'));
        add_action('wp_ajax_nopriv_tsn_subscribe', array($this, 'handle_ajax_subscribe'));
    }
    
    /**
     * Handle AJAX Subscription
     */
    public function handle_ajax_subscribe() {
        check_ajax_referer('tsn_newsletter_nonce', 'nonce');
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address.'));
        }
        
        // 1. Save to Local DB
        $subscriber_id = self::add_subscriber($email, '', '', 'footer_form');
        
        if ($subscriber_id === false) {
             // Check if already exists?
             global $wpdb;
             $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tsn_newsletter_subscribers WHERE email = %s", $email));
             if ($exists) {
                  wp_send_json_success(array('message' => 'You are already subscribed!'));
             } else {
                  wp_send_json_error(array('message' => 'Could not save subscription.'));
             }
        }
        
        // 2. Sync to Constant Contact
        $this->trigger_cc_sync($email, '', '');
        
        wp_send_json_success(array('message' => 'Successfully subscribed!'));
    }

    /**
     * Trigger CC Sync
     */
    private function trigger_cc_sync($email, $first_name, $last_name) {
        if (!class_exists('TSN_Constant_Contact')) {
            require_once TSN_MODULES_PATH . 'modules/newsletter/class-tsn-constant-contact.php';
        }
        
        $cc = new TSN_Constant_Contact();
        // Construct minimal subscriber object/array
        $subscriber = (object) array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        );
        
        $result = $cc->sync_contact($subscriber);
        
        // Optionally update local DB with CC ID if successful
        if (!is_wp_error($result) && isset($result['contact_id'])) {
            global $wpdb;
             $wpdb->update(
                $wpdb->prefix . 'tsn_newsletter_subscribers',
                array('cc_contact_id' => $result['contact_id']),
                array('email' => $email)
            );
        }
        
        return $result;
    }
    
    /**
     * Add a subscriber
     * 
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @param string $source
     */
    public static function add_subscriber($email, $first_name = '', $last_name = '', $source = 'manual') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tsn_newsletter_subscribers';
        
        // Check if exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));
        
        if ($exists) {
            return false; // Already exists
        }
        
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'email' => sanitize_email($email),
                'first_name' => sanitize_text_field($first_name),
                'last_name' => sanitize_text_field($last_name),
                'source' => sanitize_text_field($source),
                'status' => 'subscribed'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        return $inserted !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Sync Members to Subscribers
     * 
     * @return int Number of new members added
     */
    public static function sync_members() {
        global $wpdb;
        $members_table = $wpdb->prefix . 'tsn_members';
        
        // Get all active members
        $members = $wpdb->get_results("SELECT email, first_name, last_name FROM $members_table WHERE status = 'active'");
        
        $count = 0;
        foreach ($members as $member) {
            $added = self::add_subscriber($member->email, $member->first_name, $member->last_name, 'member');
            if ($added) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get all subscribers
     */
    public static function get_subscribers($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tsn_newsletter_subscribers';
        
        $sql = "SELECT * FROM $table_name WHERE 1=1";
        
        if (isset($filters['status']) && $filters['status']) {
            $sql .= $wpdb->prepare(" AND status = %s", $filters['status']);
        }
        
        if (isset($filters['source']) && $filters['source']) {
            $sql .= $wpdb->prepare(" AND source = %s", $filters['source']);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($sql);
    }
}
