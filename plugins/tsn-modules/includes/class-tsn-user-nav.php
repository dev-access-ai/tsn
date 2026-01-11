<?php
/**
 * User Navigation class
 * 
 * Handles dynamic user navigation based on authentication state
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_User_Nav {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode
        add_shortcode('tsn_user_nav', array($this, 'render_navigation'));
        
        // Add filter hook for theme integration
        add_filter('tsn_user_navigation_html', array($this, 'render_navigation'));
    }
    
    /**
     * Render user navigation (AJAX Placeholder)
     */
    public function render_navigation($atts = array()) {
        $nonce = wp_create_nonce('tsn_nav_nonce');
        $ajax_url = admin_url('admin-ajax.php');
        
        // Return a placeholder and a script to fetch the actual nav
        ob_start();
        ?>
        <div id="tsn-user-nav-container" class="tsn-user-nav-loading">
            <!-- Content loaded via AJAX to bypass cache -->
            <div class="tsn-nav-placeholder" style="min-width: 100px; height: 40px;"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Load Nav
            $.ajax({
                url: '<?php echo $ajax_url; ?>',
                type: 'POST',
                data: {
                    action: 'tsn_get_user_nav',
                    nonce: '<?php echo $nonce; ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#tsn-user-nav-container').html(response.data.html).removeClass('tsn-user-nav-loading');
                    }
                }
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.tsn-user-nav').length) {
                    $('.tsn-user-nav').removeClass('active');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Get the actual HTML for the navigation (Internal)
     */
    public function get_navigation_html() {
        // Embed styles directly in the AJAX response to ensure they exist even if page is cached
        ob_start();
        ?>
        <style>
            .tsn-user-nav { position: relative; }
            .tsn-user-nav .user-dropdown-menu { 
                display: none; 
                position: absolute; 
                right: 0; 
                top: 100%; 
                background: white; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                z-index: 99999; 
                min-width: 250px; 
                margin-top: 5px;
            }
            .tsn-user-nav.active .user-dropdown-menu { 
                display: block !important; 
            }
            .user-menu-trigger { cursor: pointer; display: flex; align-items: center; gap: 10px; user-select: none; }
        </style>
        <?php
        $html = ob_get_clean();

        // Check if member is logged in
        $is_logged_in = TSN_Membership_OTP::is_member_logged_in();
        
        if ($is_logged_in) {
            $html .= $this->render_logged_in_nav();
        } elseif (is_user_logged_in()) {
            $html .= $this->render_wp_user_nav();
        } else {
            $html .= $this->render_logged_out_nav();
        }
        
        return $html;
    }
    
    /**
     * AJAX Handler to get user nav
     */
    public static function ajax_get_user_nav() {
        // We don't strictly enforce nonce here because it's just a read operation for public UI state, 
        // and caching might make the nonce stale on the page. 
        // But checking it if present is good practice.
        
        $instance = new self();
        $html = $instance->get_navigation_html();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Render navigation for WP Logged-in Users (Admins/Editors etc who are not via member OTP)
     */
    private function render_wp_user_nav() {
        $current_user = wp_get_current_user();
        $name = $current_user->display_name;
        $email = $current_user->user_email;
        $profile_image = get_avatar_url($current_user->ID);
        
        $dashboard_url = admin_url(); // Go to WP Admin
        
        ob_start();
        ?>
        <div class="tsn-user-nav logged-in wp-user">
            <div class="user-menu-trigger" onclick="this.parentElement.classList.toggle('active')">
                <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($name); ?>" class="user-avatar">
                <span class="user-name"><?php echo esc_html($name); ?></span>
                <span class="dropdown-arrow">▼</span>
            </div>
            <div class="user-dropdown-menu">
                <div class="user-dropdown-header">
                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($name); ?>" class="user-avatar-large">
                    <div class="user-info">
                        <div class="user-full-name"><?php echo esc_html($name); ?></div>
                        <div class="user-email"><?php echo esc_html($email); ?></div>
                        <div class="user-member-id">WP User</div>
                    </div>
                </div>
                <div class="user-dropdown-links">
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="dropdown-link dashboard-link">
                        <span class="icon">⚙️</span>
                        <span class="text">WP Dashboard</span>
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="dropdown-link logout-link">
                        <span class="icon">🚪</span>
                        <span class="text">Logout</span>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render navigation for logged-out users
     */
    private function render_logged_out_nav() {
        $login_url = home_url('/member-login/');
        $register_url = home_url('/membership/');
        
        ob_start();
        ?>
        <div class="tsn-user-nav logged-out">
            <a href="<?php echo esc_url($login_url); ?>" class="tsn-nav-link login-link">
                <span class="text">Login</span>
            </a>
            <a href="<?php echo esc_url($register_url); ?>" class="tsn-nav-link register-link">
                <span class="text">Register</span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render navigation for logged-in users
     */
    private function render_logged_in_nav() {
        $member = TSN_Membership_OTP::get_logged_in_member();
        
        if (!$member) {
            return $this->render_logged_out_nav();
        }
        
        $dashboard_url = home_url('/member-dashboard/');
        
        // Use custom profile photo if available, otherwise fallback to Gravatar
        if (!empty($member->profile_photo)) {
            $profile_image = $member->profile_photo;
        } else {
            $profile_image = $this->get_profile_image($member->email, $member->first_name);
        }
        
        $display_name = $member->first_name . ' ' . substr($member->last_name, 0, 1) . '.';
        
        ob_start();
        ?>
        <div class="tsn-user-nav logged-in">
            <div class="user-menu-trigger" onclick="this.parentElement.classList.toggle('active')">
                <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($display_name); ?>" class="user-avatar">
                <span class="user-name"><?php echo esc_html($display_name); ?></span>
                <span class="dropdown-arrow">▼</span>
            </div>
            <div class="user-dropdown-menu">
                <div class="user-dropdown-header">
                    <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($display_name); ?>" class="user-avatar-large">
                    <div class="user-info">
                        <div class="user-full-name"><?php echo esc_html($member->first_name . ' ' . $member->last_name); ?></div>
                        <div class="user-email"><?php echo esc_html($member->email); ?></div>
                        <div class="user-member-id"><?php echo esc_html($member->member_id); ?></div>
                    </div>
                </div>
                <div class="user-dropdown-links">
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="dropdown-link dashboard-link">
                        <span class="icon">📊</span>
                        <span class="text">Member Dashboard</span>
                    </a>
                    <a href="#" class="dropdown-link logout-link" id="tsn-logout-btn">
                        <span class="icon">🚪</span>
                        <span class="text">Logout</span>
                    </a>
                </div>
            </div>
        </div>
        <script>
        // Re-bind logout button since it's loaded via AJAX
        jQuery('#tsn-logout-btn').on('click', function(e) {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'tsn_member_logout',
                        nonce: '<?php echo wp_create_nonce('tsn_membership_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get profile image URL (using Gravatar)
     */
    private function get_profile_image($email, $name = '') {
        $hash = md5(strtolower(trim($email)));
        $default = urlencode('identicon'); // Default avatar style
        $size = 80;
        
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
    }
    
    /**
     * AJAX handler for logout
     */
    public static function ajax_logout() {
        // Immediate debug output to verify this is being called
        header('Content-Type: application/json');
        
        error_log('TSN: Logout AJAX handler called');
        error_log('TSN: POST data: ' . print_r($_POST, true));
        
        // Manual nonce verification (don't use check_ajax_referer as it dies on failure)
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        error_log('TSN: Nonce received: ' . $nonce);
        
        if (!wp_verify_nonce($nonce, 'tsn_membership_nonce')) {
            error_log('TSN: Nonce validation failed');
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        error_log('TSN: Nonce validation passed');
        
        // Logout member
        TSN_Membership_OTP::logout_member();
        error_log('TSN: Member logged out successfully');
        
        // Log security event
        TSN_Security::log_security_event('member_logout', 'Member logged out');
        
        wp_send_json_success(array(
            'message' => 'Logged out successfully',
            'redirect_url' => home_url('/')
        ));
        exit; // Make sure we exit
    }
}
