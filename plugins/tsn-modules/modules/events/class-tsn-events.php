<?php
/**
 * Events Module
 * 
 * Main events management class
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Events {
    
    public function __construct() {
        // Register shortcodes
        add_shortcode('tsn_events_list', array($this, 'render_list'));
        add_shortcode('tsn_event_detail', array($this, 'render_detail'));
        
        // Register custom post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add rewrite rules for SEO-friendly URLs
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // AJAX Handlers
        add_action('wp_ajax_tsn_download_tickets', array('TSN_Event_Emails', 'ajax_download_tickets'));
        add_action('wp_ajax_nopriv_tsn_download_tickets', array('TSN_Event_Emails', 'ajax_download_tickets'));
        add_action('wp_ajax_tsn_resend_tickets', array('TSN_Event_Emails', 'ajax_resend_tickets'));
        add_action('wp_ajax_nopriv_tsn_resend_tickets', array('TSN_Event_Emails', 'ajax_resend_tickets'));


    }
    
    /**
     * Add rewrite rules for event slugs
     */
    public function add_rewrite_rules() {
        // These rules work WITH the existing WordPress pages
        // If you have a page called "events" or "upcoming-events", the event_slug will be added
        add_rewrite_rule(
            '^events/([^/]+)/?$',
            'index.php?pagename=events&event_slug=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^upcoming-events/([^/]+)/?$',
            'index.php?pagename=upcoming-events&event_slug=$matches[1]',
            'top'
        );
        
        // Also handle event_id parameter for backward compatibility
        add_rewrite_tag('%event_id%', '([0-9]+)');
        add_rewrite_tag('%event_slug%', '([^&]+)');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'event_slug';
        $vars[] = 'event_id';
        return $vars;
    }
    
    /**
     * Register events custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Events',
            'singular_name' => 'Event',
            'add_new' => 'Add New Event',
            'add_new_item' => 'Add New Event',
            'edit_item' => 'Edit Event',
            'new_item' => 'New Event',
            'view_item' => 'View Event',
            'search_items' => 'Search Events',
            'not_found' => 'No events found',
            'not_found_in_trash' => 'No events found in trash'
        );
        
        register_post_type('tsn_event', array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-calendar-alt',
            'show_in_menu' => false // We'll add it to TSN menu
        ));
    }
    
    /**
     * Render events list
     */
    public static function render_list($atts) {
        // Check if viewing a specific event (via ID or slug)
        $event_slug = get_query_var('event_slug');
        if (isset($_GET['event_id']) || !empty($event_slug)) {
            return self::render_detail($atts);
        }
        
        ob_start();
        include TSN_MODULES_PATH . 'modules/events/templates/events-list.php';
        return ob_get_clean();
    }
    
    /**
     * Render event detail
     */
    public static function render_detail($atts) {
        ob_start();
        include TSN_MODULES_PATH . 'modules/events/templates/event-detail.php';
        return ob_get_clean();
    }
    
    /**
     * Get all events from database
     */
    /**
     * Get all events from database
     */
    public static function get_all_events($status = 'not_archived', $orderby = 'default') {
        global $wpdb;
        
        $where = "WHERE 1=1";
        $params = array();
        
        if ($status === 'not_archived') {
            $where .= " AND status != 'archived'";
        } elseif ($status !== 'all') {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        // Base Sort: Active events first, Past events last
        // We consider an event "Past" if end_datetime is before NOW.
        // If end_datetime is null, use start_datetime.
        $now = current_time('mysql');
        $params[] = $now;
        
        // CASE WHEN condition returns 1 for Past, 0 for Active. Sorting ASC puts 0 (Active) before 1 (Past).
        $base_order = "CASE WHEN COALESCE(end_datetime, start_datetime) < %s THEN 1 ELSE 0 END ASC";

        // Secondary Sort
        switch ($orderby) {
            case 'reg_asc':
                $secondary_order = "reg_open_datetime ASC";
                break;
            case 'reg_desc':
                $secondary_order = "reg_open_datetime DESC";
                break;
            case 'date_asc':
                $secondary_order = "start_datetime ASC";
                break;
            case 'date_desc':
                $secondary_order = "start_datetime DESC";
                break;
            case 'title_asc':
                $secondary_order = "title ASC";
                break;
            case 'updated_desc':
            default:
                $secondary_order = "updated_at DESC";
                break;
        }
        
        $query = "SELECT * FROM {$wpdb->prefix}tsn_events 
                  {$where}
                  ORDER BY {$base_order}, {$secondary_order}";
                 
        if (!empty($params)) {
             return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
             return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get event by ID
     */
    public static function get_event_by_id($event_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_events WHERE id = %d",
            $event_id
        ));
    }
    
    /**
     * Get ticket types for event
     */
    public static function get_event_ticket_types($event_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types 
             WHERE event_id = %d
             ORDER BY display_order ASC",
            $event_id
        ));
    }
    
    /**
     * Create or update event
     */
    /**
     * Create or update event
     */
    public static function save_event($event_data) {
        global $wpdb;
        
        // Convert datetime-local format to MySQL datetime
        $start_datetime = str_replace('T', ' ', $event_data['event_start_date']) . ':00';
        $end_datetime = !empty($event_data['event_end_date']) ? str_replace('T', ' ', $event_data['event_end_date']) . ':00' : null;
        $reg_open = !empty($event_data['registration_open_date']) ? str_replace('T', ' ', $event_data['registration_open_date']) . ':00' : current_time('mysql');
        $reg_close = !empty($event_data['registration_close_date']) ? str_replace('T', ' ', $event_data['registration_close_date']) . ':00' : $start_datetime;
        
        // Helper to check standard checkboxes
        $enable_ticketing = isset($event_data['enable_ticketing']) ? 1 : 0;
        $enable_volunteering = isset($event_data['enable_volunteering']) ? 1 : 0;
        $enable_donations = isset($event_data['enable_donations']) ? 1 : 0;
        
        // Generate unique slug from title
        $base_slug = sanitize_title($event_data['event_name']);
        $slug = $base_slug;
        $counter = 1;
        
        // Check for existing slug and make it unique
        while (true) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}tsn_events WHERE slug = %s AND id != %d",
                $slug,
                isset($event_data['id']) ? intval($event_data['id']) : 0
            ));
            
            if (!$existing) {
                break; // Slug is unique
            }
            
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        $data = array(
            'title' => sanitize_text_field($event_data['event_name']),
            'slug' => $slug,
            'description' => isset($event_data['event_description']) ? wp_kses_post($event_data['event_description']) : '',
            'excerpt' => isset($event_data['event_excerpt']) ? wp_kses_post($event_data['event_excerpt']) : '',
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'venue_name' => isset($event_data['venue_name']) ? sanitize_text_field($event_data['venue_name']) : '',
            'address_line1' => isset($event_data['venue_address']) ? sanitize_textarea_field($event_data['venue_address']) : '',
            'banner_url' => isset($event_data['featured_image_url']) ? esc_url_raw($event_data['featured_image_url']) : '',
            'reg_open_datetime' => $reg_open,
            'reg_close_datetime' => $reg_close,
            'status' => isset($event_data['status']) ? sanitize_text_field($event_data['status']) : 'published',
            'registration_mode' => isset($event_data['registration_mode']) ? sanitize_text_field($event_data['registration_mode']) : 'ticket',
            'enable_ticketing' => $enable_ticketing,
            'enable_volunteering' => $enable_volunteering,
            'enable_donations' => $enable_donations,
            'updated_at' => current_time('mysql')
        );
        
        if (isset($event_data['id']) && $event_data['id']) {
            // Update existing
            $result = $wpdb->update(
                $wpdb->prefix . 'tsn_events',
                $data,
                array('id' => intval($event_data['id'])),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'),
                array('%d')
            );
            error_log('TSN Event updated: ' . $event_data['id'] . ' Result: ' . $result);
            return intval($event_data['id']);
        } else {
            // Insert new
            error_log('TSN Event - Attempting to insert with data: ' . print_r($data, true));
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'tsn_events',
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
            );
            
            error_log('TSN Event - Insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            error_log('TSN Event - Insert ID: ' . $wpdb->insert_id);
            error_log('TSN Event - Last error: ' . $wpdb->last_error);
            error_log('TSN Event - Last query: ' . $wpdb->last_query);
            
            if ($result) {
                $id = $wpdb->insert_id;
                error_log('TSN Event created: ' . $id . ' - ' . $event_data['event_name']);
                return $id;
            } else {
                error_log('TSN Event creation failed: ' . $wpdb->last_error);
                return false;
            }
        }
    }
    
    /**
     * Check if member has active membership
     */
    public static function is_member($email) {
        $member = TSN_Membership::get_member_by_email($email);
        return $member && $member->status === 'active';
    }

    /**
     * AJAX: Submit volunteer registration
     */
    public static function ajax_submit_volunteer() {
        // Verify nonce
        check_ajax_referer('tsn_events_nonce', 'tsn_events_nonce');
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $age = isset($_POST['age']) ? intval($_POST['age']) : null;
        $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if (!$event_id || empty($name) || empty($email)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }
        
        global $wpdb;
        
        // Check for duplicate
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}tsn_event_volunteers WHERE event_id = %d AND email = %s",
            $event_id,
            $email
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'You have already registered as a volunteer for this event.'));
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'tsn_event_volunteers',
            array(
                'event_id' => $event_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'age' => $age,
                'gender' => $gender,
                'address' => $address,
                'notes' => $notes,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $volunteer_id = $wpdb->insert_id;
                
            // Send emails
            TSN_Event_Emails::send_volunteer_confirmation($volunteer_id);
            TSN_Event_Emails::send_volunteer_admin_notification($volunteer_id);

            wp_send_json_success(array('message' => 'Thank you! Your volunteer registration has been received.'));
        } else {
            wp_send_json_error(array('message' => 'Database error. Please try again.'));
        }
    }


}
