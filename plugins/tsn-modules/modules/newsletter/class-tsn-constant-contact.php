<?php
/**
 * Constant Contact Integration Class
 * 
 * Handles API communication with Constant Contact
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Constant_Contact {
    
    private $api_key;
    private $access_token;
    private $api_url = 'https://api.cc.email/v3';
    private $list_id;
    
    public function __construct() {
        $this->api_key = get_option('tsn_cc_api_key', '');
        $this->access_token = get_option('tsn_cc_access_token', '');
        $this->list_id = get_option('tsn_cc_list_id', '');
    }
    
    /**
     * Sync Contact
     * Create or Update a contact in Constant Contact
     */
    public function sync_contact($subscriber) {
        if (!$this->access_token) {
            return new WP_Error('no_token', 'Constant Contact access token is missing.');
        }

        // 1. Check if contact exists by email
        $contact_id = $this->get_contact_id_by_email($subscriber->email);
        
        if ($contact_id) {
            return $this->update_contact($contact_id, $subscriber);
        } else {
            return $this->create_contact($subscriber);
        }
    }
    
    /**
     * Get Contact ID by Email
     */
    private function get_contact_id_by_email($email) {
        $url = $this->api_url . '/contacts?email=' . urlencode($email) . '&status=all';
        $body = $this->make_request($url, 'GET');
        
        if (is_wp_error($body)) {
            return false;
        }
        
        if (isset($body['contacts']) && !empty($body['contacts'])) {
            return $body['contacts'][0]['contact_id'];
        }
        
        return false;
    }
    
    /**
     * Create Contact
     */
    private function create_contact($subscriber) {
        $url = $this->api_url . '/contacts';
        
        $data = array(
            'email_address' => array(
                'address' => $subscriber->email,
                'permission_to_send' => 'implicit'
            ),
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'create_source' => 'Contact',
        );
        
        // Add to List if ID is set
        if (!empty($this->list_id)) {
            $data['list_memberships'] = array($this->list_id);
        }
        
        return $this->make_request($url, 'POST', $data);
    }
    
    /**
     * Update Contact (simplified)
     */
    private function update_contact($contact_id, $subscriber) {
        // Needs full object to update PUT, simpler to skip if exists for basic sync
        // Or specific PATCH if supported. V3 uses PUT with full resource usually.
        // For MVP, we might just return the ID if already exists
        return array('contact_id' => $contact_id); 
    }
    
    /**
     * Make API Request
     */
    private function make_request($url, $method = 'GET', $data = null) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 15
        );
        
        if ($data) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code >= 400) {
            $msg = isset($body['error_message']) ? $body['error_message'] : wp_remote_retrieve_body($response);
            return new WP_Error('api_error', 'Constant Contact API Error: ' . $code . ' ' . $msg);
        }
        
        return $body;
    }
    
    /**
     * Get Contact Lists
     */
    public function get_lists() {
        if (!$this->access_token) return new WP_Error('no_token', 'No access token');
        
        $url = $this->api_url . '/contact_lists?limit=50&status=active';
        $body = $this->make_request($url, 'GET');
        
        if (is_wp_error($body)) {
            return $body;
        }
        
        return isset($body['lists']) ? $body['lists'] : array();
    }

    /**
     * Test Connection
     */
    public function test_connection() {
         if (!$this->access_token) return false;
         // Just try fetching lists as a test
         $lists = $this->get_lists();
         return !is_wp_error($lists);
    }
}
