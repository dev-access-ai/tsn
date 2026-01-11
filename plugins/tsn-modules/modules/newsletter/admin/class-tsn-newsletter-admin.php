<?php
/**
 * Newsletter Admin Class
 * 
 * Handles Admin UI for Subscribers and Import
 *
 * @package TSN_Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

class TSN_Newsletter_Admin {
    
    /**
     * Render Subscribers Page
     */
    public function render_subscribers_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Handle Actions
        if (isset($_POST['import_subscribers']) && check_admin_referer('tsn_import_nonce')) {
            $this->handle_import();
        }
        
        if (isset($_POST['sync_members']) && check_admin_referer('tsn_import_nonce')) {
            $count = TSN_Newsletter::sync_members();
            echo '<div class="notice notice-success"><p>Synced ' . $count . ' new members to subscribers list.</p></div>';
        }
        
        if (isset($_POST['sync_members']) && check_admin_referer('tsn_import_nonce')) {
            $count = TSN_Newsletter::sync_members();
            echo '<div class="notice notice-success"><p>Synced ' . $count . ' new members to subscribers list.</p></div>';
        }
        
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $subscribers = TSN_Newsletter::get_subscribers($search);
        
        include TSN_MODULES_PATH . 'modules/newsletter/admin/views/subscribers-list.php';
    }
    
    /**
     * Handle CSV Import
     */
    private function handle_import() {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="notice notice-error"><p>Please upload a valid CSV file.</p></div>';
            return;
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            echo '<div class="notice notice-error"><p>Could not open file.</p></div>';
            return;
        }
        
        // Skip header row
        fgetcsv($handle);
        
        $count = 0;
        $duplicates = 0;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Expected format: Email, First Name, Last Name
            $email = isset($data[0]) ? trim($data[0]) : '';
            $first = isset($data[1]) ? trim($data[1]) : '';
            $last = isset($data[2]) ? trim($data[2]) : '';
            
            if (is_email($email)) {
                $result = TSN_Newsletter::add_subscriber($email, $first, $last, 'import');
                if ($result) {
                    $count++;
                } else {
                    $duplicates++;
                }
            }
        }
        
        fclose($handle);
        
        echo '<div class="notice notice-success"><p>Import Complete: ' . $count . ' added, ' . $duplicates . ' skipped (duplicates).</p></div>';
    }
    
    /**
     * Render Import Page (can be part of list page for simplicity)
     */
    public function render_import_page() {
         // Not used separate page for now, modal/inline in list
    }
}
