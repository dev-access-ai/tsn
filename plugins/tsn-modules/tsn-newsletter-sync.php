<?php
/**
 * TSN Newsletter Sync Script
 * 
 * Usage: 
 * Place in `wp-content/plugins/tsn-modules/`
 * Access via browser.
 */

// Better error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load WordPress
$possible_paths = [
    dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/tsn/wp-load.php',
    'C:/xampp/htdocs/tsn/wp-load.php'
];

$wp_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('<div style="font-family:sans-serif; padding:20px; color:red;">Critical Error: Could not load WordPress.</div>');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this script.');
}

// Load Dependencies
if (!class_exists('TSN_Newsletter')) {
     require_once TSN_MODULES_PATH . 'modules/newsletter/class-tsn-newsletter.php';
}
if (!class_exists('TSN_Constant_Contact')) {
    require_once TSN_MODULES_PATH . 'modules/newsletter/class-tsn-constant-contact.php';
}

// Handle Cleanup Action
if (isset($_POST['clean_emails'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tsn_newsletter_subscribers';
    
    // Delete invalid emails
    $result = $wpdb->query("DELETE FROM $table_name WHERE email LIKE 'noemail%' OR email NOT LIKE '%@%' OR email = '' OR email IS NULL");
    
    if ($result !== false) {
        $message = "<div class='msg success'><strong>Cleanup Successful:</strong> Removed $result invalid subscribers.</div>";
    } else {
        $message = "<div class='msg error'><strong>Error:</strong> Database cleanup failed. " . $wpdb->last_error . "</div>";
    }
}

$message = isset($message) ? $message : '';

// Handle Sync Action
$is_running = isset($_GET['step']) && $_GET['step'] === 'run';
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$batch_size = 20; // Process 20 at a time to be safe

if (isset($_POST['start_sync']) || $is_running) {
    
    // Check credentials
    $list_id = get_option('tsn_cc_list_id', '');
    $token = get_option('tsn_cc_access_token', '');
    
    if (empty($list_id) || empty($token)) {
        $message = "<div class='msg error'><strong>Error:</strong> Please configure Constant Contact API Token and List ID in TSN Settings first.</div>";
    } else {
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tsn_newsletter_subscribers';
        
        // Count total
        $total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Fetch batch
        $subscribers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name LIMIT %d OFFSET %d", $batch_size, $offset));
        
        if (empty($subscribers)) {
            // Done!
             $message = "<div class='msg success'><strong>Sync Completed!</strong><br>All $total_subscribers subscribers have been processed.</div>";
             $message .= '<a href="tsn-newsletter-sync.php" class="btn">Back</a>';
        } else {
            // Process Batch
            $success_count = 0;
            $fail_count = 0;
            $logs = [];
            
            // Initialize CC Class
            $cc = new TSN_Constant_Contact();
            
            foreach ($subscribers as $sub) {
                // Prepare object
                $sub_obj = (object) [
                    'email' => $sub->email,
                    'first_name' => $sub->first_name,
                    'last_name' => $sub->last_name
                ];
                
                $result = $cc->sync_contact($sub_obj);
                
                if (is_wp_error($result)) {
                    $fail_count++;
                    // Debug
                    // echo "Fail: " . $result->get_error_message() . "<br>";
                } else {
                    $success_count++;
                    
                    // Update local DB
                    if (isset($result['contact_id'])) {
                        $updated = $wpdb->update(
                            $table_name,
                            array('cc_contact_id' => $result['contact_id']),
                            array('id' => $sub->id)
                        );
                        
                        // Debug first one
                        if ($success_count == 1) {
                             // echo "Debug: Found ID " . $result['contact_id'] . ". DB Update Result: " . var_export($updated, true) . "<br>";
                        }
                    } else {
                        // Debug missing ID
                        // echo "Debug: No contact_id in result: " . print_r($result, true) . "<br>";
                    }
                }
                
                // Small pause
                usleep(100000); // 0.1s
            }
            
            // Calculate progress
            $next_offset = $offset + $batch_size;
            $percentage = min(100, round(($next_offset / $total_subscribers) * 100));
            
            // Render Progress and Redirect
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Syncing...</title>
                <style>
                    body { font-family: sans-serif; padding: 50px; text-align: center; background: #f3f4f6; }
                    .progress-box { background: white; padding: 40px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .bar { width: 100%; height: 20px; background: #e5e7eb; border-radius: 10px; overflow: hidden; margin: 20px 0; }
                    .fill { height: 100%; background: #0ea5e9; width: <?php echo $percentage; ?>%; transition: width 0.3s; }
                    .debug { text-align: left; font-size: 11px; color: #999; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                </style>
                <meta http-equiv="refresh" content="2;url=tsn-newsletter-sync.php?step=run&offset=<?php echo $next_offset; ?>">
            </head>
            <body>
                <div class="progress-box">
                    <h2>Syncing...</h2>
                    <p>Processed <?php echo min($next_offset, $total_subscribers); ?> of <?php echo $total_subscribers; ?> subscribers.</p>
                    <div class="bar"><div class="fill"></div></div>
                    <p style="color: #666; font-size: 0.9em;">Please wait, do not close this window.</p>
                    
                    <div class="debug">
                        <strong>Last API Result Debug:</strong><br>
                        <?php 
                        if (isset($result)) {
                            $debug_data = is_wp_error($result) ? $result->get_error_message() : (isset($result['contact_id']) ? 'ID: ' . substr($result['contact_id'], 0, 8) . '...' : 'No ID found');
                            echo $debug_data;
                            if (isset($updated)) echo " | DB Update: " . ($updated === false ? 'FALSE' : $updated);
                        }
                        ?>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSN Newsletter Sync</title>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-hover: #0284c7;
            --bg: #F3F4F6;
            --card-bg: #FFFFFF;
            --text: #1F2937;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --success-bg: #D1FAE5;
            --success-text: #065F46;
            --error-bg: #FEE2E2;
            --error-text: #B91C1C;
        }
        body { font-family: 'Inter', system-ui, sans-serif; background-color: var(--bg); color: var(--text); padding: 40px 20px; }
        .container { max-width: 800px; margin: 0 auto; background: var(--card-bg); padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #111827; }
        .btn { background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; display: inline-block; }
        .btn:hover { background: var(--primary-hover); }
        .msg { padding: 16px; border-radius: 6px; margin-bottom: 20px; }
        .msg.success { background: var(--success-bg); color: var(--success-text); }
        .msg.error { background: var(--error-bg); color: var(--error-text); }
    </style>
</head>
<body>
    <div class="container">
        <h1>Constant Contact Sync</h1>
        <p style="color: var(--text-muted); margin-bottom: 30px;">
            Sync all locally stored newsletter subscribers to your configured Constant Contact List.
        </p>

        <?php echo $message; ?>

        <?php 
        $list_id = get_option('tsn_cc_list_id', '');
        
        // Try to fetch lists to help the user
        if (class_exists('TSN_Constant_Contact')) {
            $cc = new TSN_Constant_Contact();
            $lists = $cc->get_lists();
            
            if (!is_wp_error($lists) && !empty($lists)) {
                echo '<div style="margin-bottom: 30px; padding: 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;">';
                echo '<h3 style="margin-top:0;">Available Contact Lists</h3>';
                echo '<p style="font-size:0.9em; color:#666;">Copy the ID below and paste it into <a href="'.admin_url('admin.php?page=tsn-modules-settings').'">Settings</a>.</p>';
                echo '<table style="width:100%; border-collapse:collapse; text-align:left;">';
                echo '<tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;"><th style="padding:8px;">List Name</th><th style="padding:8px;">List ID (UUID)</th></tr>';
                foreach ($lists as $list) {
                     $is_current = ($list_id === $list['list_id']);
                     $style = $is_current ? 'background:#ecfdf5; color:#065f46; font-weight:bold;' : '';
                     echo "<tr style='border-bottom:1px solid #f3f4f6; $style'>";
                     echo "<td style='padding:8px;'>{$list['name']}</td>";
                     echo "<td style='padding:8px; font-family:monospace;'>{$list['list_id']} " . ($is_current ? '(Active)' : '') . "</td>";
                     echo "</tr>";
                }
                echo '</table>';
                echo '</div>';
            } elseif (is_wp_error($lists)) {
                 echo '<div class="msg error">Could not fetch lists from Constant Contact. Check your API Key/Token.<br><small>Error: ' . $lists->get_error_message() . '</small></div>';
            }
        }
        
        if (empty($list_id)) {
            echo '<div class="msg error">⚠️ <strong>Configuration Missing:</strong> Please set the Contact List ID in TSN Settings before syncing.</div>';
        } else {
            ?>
            <div style="background: #F0F9FF; border: 1px solid #BAE6FD; padding: 20px; border-radius: 8px;">
                <h3 style="margin-top:0; color: #0369A1;">Ready to Sync</h3>
                <p>Target List ID: <code><?php echo esc_html($list_id); ?></code></p>
                
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <form method="post">
                        <input type="hidden" name="start_sync" value="1">
                        <button type="submit" class="btn">Start Sync Process</button>
                    </form>
                    
                    <form method="post" onsubmit="return confirm('Are you sure? This will permanently delete subscribers with \'noemail\' or invalid formats.');">
                        <button type="submit" name="clean_emails" class="btn" style="background:#ef4444;">Clean Invalid Emails</button>
                    </form>
                </div>
                
                <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                    <strong>Sync:</strong> Processes 20 subscribers at a time.<br>
                    <strong>Clean:</strong> Removes 'noemail...' and invalid formats from database.
                </p>
            </div>
            <?php
        }
        ?>

        <br>
        <a href="<?php echo admin_url('admin.php?page=tsn-modules-settings'); ?>" style="color: var(--primary); text-decoration: none;">&larr; Go to Settings</a>
    </div>
</body>
</html>
