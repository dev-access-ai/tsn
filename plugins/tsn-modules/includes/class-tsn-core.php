<?php
/**
 * Core plugin class
 * 
 * Loads all modules and coordinates plugin functionality
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Core {
    
    /**
     * Plugin loader
     */
    protected $loader;
    
    /**
     * Plugin version
     */
    protected $version;
    
    /**
     * Admin class instances (public so WordPress can access callbacks)
     */
    public $membership_admin;
    public $events_admin;
    public $donations_admin;
    public $newsletter_admin;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = TSN_MODULES_VERSION;
        
        $this->load_dependencies();
        $this->load_modules();          // Load modules FIRST so classes exist
        $this->define_admin_hooks();
        $this->define_public_hooks();   // Then register AJAX hooks
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once TSN_MODULES_PATH . 'includes/class-tsn-database.php';
        require_once TSN_MODULES_PATH . 'includes/class-tsn-security.php';
        require_once TSN_MODULES_PATH . 'includes/class-tsn-ajax.php';
        require_once TSN_MODULES_PATH . 'includes/class-tsn-user-nav.php';
        
        // Temporary test file
        if (file_exists(TSN_MODULES_PATH . 'test-ajax.php')) {
            require_once TSN_MODULES_PATH . 'test-ajax.php';
        }
        
        // Utility classes
        require_once TSN_MODULES_PATH . 'includes/class-tsn-email.php';
        require_once TSN_MODULES_PATH . 'includes/class-tsn-payment.php';
    }
    
    /**
     * Load all modules
     */
    private function load_modules() {
        // Membership module
        require_once TSN_MODULES_PATH . 'modules/membership/class-tsn-membership.php';
        require_once TSN_MODULES_PATH . 'modules/membership/class-tsn-membership-payment.php';
        require_once TSN_MODULES_PATH . 'modules/membership/class-tsn-membership-otp.php';
        require_once TSN_MODULES_PATH . 'modules/membership/class-tsn-membership-emails.php';
        require_once TSN_MODULES_PATH . 'modules/membership/admin/class-tsn-membership-admin.php';
        
        // Events module
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-events.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-types.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-checkout.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-qr.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-scanner.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-event-emails.php';
        require_once TSN_MODULES_PATH . 'modules/events/admin/class-tsn-events-admin.php';
        
        // Donations module
        require_once TSN_MODULES_PATH . 'modules/donations/class-tsn-donations.php';
        require_once TSN_MODULES_PATH . 'modules/donations/class-tsn-donation-causes.php';
        require_once TSN_MODULES_PATH . 'modules/donations/class-tsn-donation-payment.php';
        require_once TSN_MODULES_PATH . 'modules/donations/class-tsn-donation-receipt.php';
        require_once TSN_MODULES_PATH . 'modules/donations/class-tsn-donation-receipt.php';
        require_once TSN_MODULES_PATH . 'modules/donations/admin/class-tsn-donations-admin.php';
        
        // Newsletter module
        require_once TSN_MODULES_PATH . 'modules/newsletter/class-tsn-newsletter.php';
        require_once TSN_MODULES_PATH . 'modules/newsletter/class-tsn-constant-contact.php';
        require_once TSN_MODULES_PATH . 'modules/newsletter/admin/class-tsn-newsletter-admin.php';
        
        // Event ticket management
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-checkout.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-ticket-qr.php';
        require_once TSN_MODULES_PATH . 'modules/events/class-tsn-event-emails.php';
        
        // Initialize modules
        new TSN_Membership();
        new TSN_Events();
        new TSN_Donations();
        new TSN_Newsletter(); // Init
        new TSN_Ticket_Checkout();
        new TSN_User_Nav();
        
        // Initialize admin interfaces and store instances to prevent garbage collection
        if (is_admin()) {
            $this->membership_admin = new TSN_Membership_Admin();
            $this->events_admin = new TSN_Events_Admin();
            $this->donations_admin = new TSN_Donations_Admin();
            $this->newsletter_admin = new TSN_Newsletter_Admin();
        }
    }
    
    /**
     * Define admin hooks
     */
    private function define_admin_hooks() {
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle schema repair
        add_action('admin_post_tsn_repair_schema', array($this, 'handle_schema_repair'));
    }
    
    /**
     * Handle schema repair action
     */
    public function handle_schema_repair() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_admin_referer('tsn_repair_schema_nonce');
        
        // Run schema repair
        TSN_Database::create_tables();
        
        // Redirect back
        wp_redirect(add_query_arg(array(
            'page' => 'tsn-tools',
            'status' => 'success',
            'message' => 'schema_repaired'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Handle template redirects for custom pages
        add_action('template_redirect', array($this, 'handle_template_redirect'));
        
        // AJAX handlers
        TSN_Ajax::init();
        
        // Email SMTP Init
        TSN_Email::init();
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on TSN plugin pages
        if (strpos($hook, 'tsn-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'tsn-admin-css',
            TSN_MODULES_URL . 'assets/css/tsn-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'tsn-admin-js',
            TSN_MODULES_URL . 'assets/js/tsn-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('tsn-admin-js', 'tsnAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tsn_admin_nonce')
        ));
    }
    
    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        // CSS
        wp_enqueue_style(
            'tsn-frontend-css',
            TSN_MODULES_URL . 'assets/css/tsn-frontend.css',
            array(),
            $this->version
        );
        
        // Scripts
        wp_enqueue_script(
            'tsn-membership-js',
            TSN_MODULES_URL . 'assets/js/tsn-membership.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'tsn-events-js',
            TSN_MODULES_URL . 'assets/js/tsn-events.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'tsn-donations-js',
            TSN_MODULES_URL . 'assets/js/tsn-donations.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localize scripts for AJAX
        wp_localize_script('tsn-membership-js', 'tsnMembership', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tsn_membership_nonce')
        ));
        
        wp_localize_script('tsn-events-js', 'tsnEvents', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tsn_events_nonce')
        ));
        
        wp_localize_script('tsn-donations-js', 'tsnDonations', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tsn_donations_nonce')
        ));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('tsn_membership_form', array('TSN_Membership', 'render_form'));
        add_shortcode('tsn_member_dashboard', array('TSN_Membership', 'render_dashboard'));
        add_shortcode('tsn_events_list', array('TSN_Events', 'render_list'));
        add_shortcode('tsn_event_detail', array('TSN_Events', 'render_detail'));
        add_shortcode('tsn_donations_form', array('TSN_Donations', 'render_form'));
        add_shortcode('tsn_scanner_app', array('TSN_Ticket_Scanner', 'render_scanner'));
        add_shortcode('tsn_payment_success', array('TSN_Ticket_Checkout', 'render_payment_success'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('TSN Modules', 'tsn-modules'),
            __('TSN Modules', 'tsn-modules'),
            'manage_options',
            'tsn-modules',
            array($this, 'render_dashboard'),
            'dashicons-groups',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'tsn-modules',
            __('Dashboard', 'tsn-modules'),
            __('Dashboard', 'tsn-modules'),
            'manage_options',
            'tsn-modules',
            array($this, 'render_dashboard')
        );
        
        // Membership submenus
        add_submenu_page(
            'tsn-modules',
            __('Memberships', 'tsn-modules'),
            __('Memberships', 'tsn-modules'),
            'manage_options',
            'tsn-memberships',
            array($this->membership_admin, 'render_members_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Member', 'tsn-modules'),
            __('Add Member', 'tsn-modules'),
            'manage_options',
            'tsn-add-member',
            array($this->membership_admin, 'render_add_member_page')
        );
        
        // Events submenus
        add_submenu_page(
            'tsn-modules',
            __('Events', 'tsn-modules'),
            __('Events', 'tsn-modules'),
            'manage_options',
            'tsn-events',
            array($this->events_admin, 'render_events_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Event', 'tsn-modules'),
            __('Add Event', 'tsn-modules'),
            'manage_options',
            'tsn-add-event',
            array($this->events_admin, 'render_add_event_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('QR Scanner', 'tsn-modules'),
            __('QR Scanner', 'tsn-modules'),
            'manage_options',
            'tsn-qr-scanner',
            array($this->events_admin, 'render_scanner_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Offline Tickets', 'tsn-modules'),
            __('Offline Tickets', 'tsn-modules'),
            'manage_options',
            'tsn-offline-tickets',
            array($this->events_admin, 'render_offline_tickets_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Event Reports', 'tsn-modules'),
            __('Event Reports', 'tsn-modules'),
            'manage_options',
            'tsn-event-reports',
            array($this->events_admin, 'render_reports_page')
        );
        
        // Donations submenus
        add_submenu_page(
            'tsn-modules',
            __('Donations', 'tsn-modules'),
            __('Donations', 'tsn-modules'),
            'manage_options',
            'tsn-donations',
            array($this->donations_admin, 'render_donations_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Donation Causes', 'tsn-modules'),
            __('Donation Causes', 'tsn-modules'),
            'manage_options',
            'tsn-donation-causes',
            array($this->donations_admin, 'render_causes_page')
        );
        
        add_submenu_page(
            'tsn-modules',
            __('Add Cause', 'tsn-modules'),
            __('Add Cause', 'tsn-modules'),
            'manage_options',
            'tsn-add-cause',
            array($this->donations_admin, 'render_add_cause_page')
        );
        
        // Newsletter
        add_submenu_page(
            'tsn-modules',
            __('Newsletter', 'tsn-modules'),
            __('Newsletter', 'tsn-modules'),
            'manage_options',
            'tsn-newsletter',
            array($this->newsletter_admin, 'render_subscribers_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'tsn-modules',
            __('Settings', 'tsn-modules'),
            __('Settings','tsn-modules'),
            'manage_options',
            'tsn-settings',
            array($this, 'render_settings')
        );
        
        // System Tools (Hidden or for admins)
        add_submenu_page(
            'tsn-modules',
            __('System Tools', 'tsn-modules'),
            __('System Tools', 'tsn-modules'),
            'manage_options',
            'tsn-tools',
            array($this, 'render_tools_page')
        );
    }
    
    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render_dashboard() {
        include TSN_MODULES_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings() {
        include TSN_MODULES_PATH . 'admin/views/settings.php';
    }

    /**
     * Render tools page
     * 
     * @return void
     */
    public function render_tools_page() {
        include TSN_MODULES_PATH . 'admin/views/tools.php';
    }
    
    /**
     * Handle template redirects for custom TSN pages
     * This prevents 404 errors when accessing module URLs
     */
    public function handle_template_redirect() {
        global $wp_query;
        
        // Check if this is a 404 and we're on a TSN-related URL
        if (is_404()) {
            $request_uri = $_SERVER['REQUEST_URI'];
            $base_uri = str_replace(home_url(), '', $request_uri);
            $base_uri = trim($base_uri, '/');
            
            // Check for TSN module paths
            if (preg_match('#^tsn/events/?#', $base_uri) ||
                preg_match('#^tsn/upcoming-events/?#', $base_uri) ||
                preg_match('#^tsn/member-dashboard/?#', $base_uri) ||
                preg_match('#^tsn/donations/?#', $base_uri) ||
                preg_match('#^tsn/membership/?#', $base_uri)) {
                
                // This is a TSN module URL - set status to 200
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                // Try to find the corresponding page
                error_log('TSN: Intercepted 404 for: ' . $request_uri);
            }
            
            // Handle RSVP Confirmation (Dedicated Virtual Page)
            if (preg_match('#^tsn/rsvp-confirmation/?#', $base_uri)) {
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                // Render success page directly
                get_header(); 
                echo '<div class="wrap tsn-container" style="padding: 40px 20px;">';
                echo TSN_Ticket_Checkout::render_payment_success(array());
                echo '</div>';
                get_footer();
                exit;
            }
            
            // Handle Payment Confirmation (Dedicated Virtual Page for Donations/Paid Tickets)
            if (preg_match('#^tsn/payment-confirmation/?#', $base_uri)) {
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                // Render success page directly
                get_header(); 
                echo '<div class="wrap tsn-container" style="padding: 40px 20px;">';
                echo TSN_Ticket_Checkout::render_payment_success(array());
                echo '</div>';
                get_footer();
                exit;
            }
            
            // Handle Payment Cancelled
            if (preg_match('#^payment-cancelled/?#', $base_uri) || preg_match('#^tsn/payment-cancelled/?#', $base_uri)) {
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                get_header(); 
                echo '<div class="wrap tsn-container" style="padding: 40px 20px;">';
                echo TSN_Ticket_Checkout::render_payment_cancelled();
                echo '</div>';
                get_footer();
                exit;
            }
        }
        
        // Handle member dashboard custom query var
        if (get_query_var('tsn_member_dashboard')) {
            // Check if there's a page with the dashboard shortcode
            $dashboard_page = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                's' => '[tsn_member_dashboard]',
                'posts_per_page' => 1
            ));
            
            if (empty($dashboard_page)) {
                // No page found, serve content directly
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                // Load template
                include TSN_MODULES_PATH . 'modules/membership/templates/member-dashboard.php';
                exit;
            }
        }
        
        // Handle update profile custom query var
        if (get_query_var('tsn_update_profile')) {
            // Check if there's a page with the shortcode
            $profile_page = get_posts(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                's' => '[tsn_update_profile]',
                'posts_per_page' => 1
            ));
            
            if (empty($profile_page)) {
                // No page found, serve content directly
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
                
                // Load header
                get_header();
                
                // Load template
                include TSN_MODULES_PATH . 'modules/membership/templates/update-profile.php';
                
                // Load footer
                get_footer();
                exit;
            }
        }
        
        // Handle event detail pages
        $event_slug = get_query_var('event_slug');
        if ($event_slug) {
            // Verify this is a real event
            global $wpdb;
            $event = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_events WHERE slug = %s",
                $event_slug
            ));
            
            if ($event) {
                // Event exists, make sure WordPress doesn't 404
                status_header(200);
                $wp_query->is_404 = false;
                $wp_query->is_page = true;
            }
        }
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        // Plugin is initialized
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
}
