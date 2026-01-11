<?php
/**
 * Plugin deactivation class
 * 
 * Handles cleanup on deactivation (preserves data)
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Deactivator {
    
    /**
     * Deactivation function
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('tsn_membership_renewal_check');
        wp_clear_scheduled_hook('tsn_event_reminder_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('TSN Modules plugin deactivated');
        
        // Note: We don't delete data on deactivation
        // Data is only removed on uninstall
    }
}
