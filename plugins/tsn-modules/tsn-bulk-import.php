<?php
/**
 * TSN Bulk Member Import Script
 * 
 * Usage: 
 * 1. Place this file in `wp-content/plugins/tsn-modules/`.
 * 2. Access via browser.
 */

// Better error reporting for debugging
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
    die('<div style="font-family:sans-serif; padding:20px; color:red;">Critical Error: Could not load WordPress. Checked paths:<br>' . implode('<br>', $possible_paths) . '</div>');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this script.');
}

$message = '';

// Handle Delete Imported
if (isset($_POST['delete_imported'])) {
    global $wpdb;
    $deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}tsn_members WHERE payment_mode = 'import'");
    if ($deleted !== false) {
        $message = "<div class='msg success'><strong>Deletion Completed!</strong><br>Removed $deleted members that were previously imported.</div>";
    } else {
        $message = "<div class='msg error'><strong>Error:</strong> Could not delete members. DB Error: " . $wpdb->last_error . "</div>";
    }
}

// Handle Delete ALL (Reset)
if (isset($_POST['delete_all'])) {
    global $wpdb;
    // Using TRUNCATE to reset auto-increment IDs as well for a true "fresh start"
    $deleted = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}tsn_members");
    if ($deleted !== false) {
        $message = "<div class='msg success'><strong>Database Reset!</strong><br>All members have been removed. The table is now empty.</div>";
    } else {
        $message = "<div class='msg error'><strong>Error:</strong> Could not reset table. DB Error: " . $wpdb->last_error . "</div>";
    }
}

// Handle Upload
if (isset($_POST['submit_import']) && isset($_FILES['csv_file'])) {
    
    if (empty($_FILES['csv_file']['tmp_name'])) {
         $message = '<div class="msg error">No file selected.</div>';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        if ($handle) {
            $headers = fgetcsv($handle);
            if ($headers) {
                // Normalize headers
                $headers_map = array();
                foreach ($headers as $i => $h) {
                    // Remove UTF-8 BOM if present
                    $h = preg_replace('/\x{FEFF}/u', '', $h);
                    $headers_map[strtolower(trim($h))] = $i;
                }
                
                global $wpdb;
                $success_count = 0;
                $error_count = 0;
                $errors_list = array();
                $row_num = 1;
                $last_imported_id = null;
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row_num++;
                    
                    // Helper
                     $get_val = function($key) use ($data, $headers_map) {
                        $key = strtolower($key);
                        // Try exact match
                        if (isset($headers_map[$key]) && isset($data[$headers_map[$key]])) {
                            return trim($data[$headers_map[$key]]);
                        }
                        return '';
                    };
                    
                    // extract data based on User's NEW Column List
                    $first_name = sanitize_text_field($get_val('first name'));
                    $last_name = sanitize_text_field($get_val('last name'));
                    $email = sanitize_email($get_val('email 1')); 
                    
                    // Defaults
                    if (empty($first_name)) $first_name = 'Member';
                    if (empty($last_name)) $last_name = 'Unknown';
                    // Generate Unique Dummy Email if empty
                    if (empty($email)) $email = 'nomail-' . uniqid() . '@telugusamiti.org';
                    
                    // Duplicate check
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tsn_members WHERE email = %s", $email));
                    if ($exists) {
                        $errors_list[] = "Row $row_num: Email '$email' already exists. Skipped.";
                        $error_count++;
                        continue;
                    }
                    
                    // ID Generation Logic (Smart Increment)
                    $provided_id = sanitize_text_field($get_val('membership#'));
                     
                    if (!empty($provided_id)) {
                        // Case A: ID Provided in CSV
                        $member_id = $provided_id;
                        
                        // Check if exists (Strict Skip if duplicate, per user implication of uniqueness)
                        $id_check = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tsn_members WHERE member_id = %s", $member_id));
                        if ($id_check) {
                             $errors_list[] = "Row $row_num: Member ID '$member_id' already exists. Skipped.";
                             $error_count++;
                             continue;
                        }
                        
                    } else {
                        // Case B: No ID Provided - Generate one
                        
                        // 1. Try to increment based on the last ID seen in this import session
                        if (!empty($last_imported_id)) {
                            // Extract numeric part from end of string
                             if (preg_match('/^(.*?)(\d+)$/', $last_imported_id, $matches)) {
                                 $prefix = $matches[1];
                                 $number = intval($matches[2]);
                                 $member_id = $prefix . ($number + 1);
                             } else {
                                 // Last ID had no number? Fallback.
                                 $member_id = null;
                             }
                        }
                        
                        // 2. Fallback to System Generator if step 1 failed or no last_id
                        if (empty($member_id)) {
                            if (class_exists('TSN_Membership')) {
                                $gen_type = (strpos(strtolower($get_val('membership type')), 'life') !== false) ? 'lifetime' : 'annual'; 
                                $member_id = TSN_Membership::generate_member_id($gen_type);
                            } else {
                                $member_id = 'TSN-' . strtoupper(uniqid()); // Ultimate fallback
                            }
                        }
                        
                        // Final Safety Check for Uniqueness
                        // (In rare case generated ID exists, loop to find next)
                        $loop_safety = 0;
                        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tsn_members WHERE member_id = %s", $member_id)) && $loop_safety < 10) {
                             if (preg_match('/^(.*?)(\d+)$/', $member_id, $matches)) {
                                 $member_id = $matches[1] . (intval($matches[2]) + 1);
                             } else {
                                 $member_id .= '-' . rand(1,9);
                             }
                             $loop_safety++;
                        }
                    }
                    
                    // Track this ID for the next iteration
                    $last_imported_id = $member_id;
                    
                    // Type & Dates
                    $raw_type = strtolower($get_val('membership type'));
                    $type = 'annual'; // Default
                    
                    $year = intval(date('Y')); // Default to current year if no year col
                    
                    $date_val = $get_val('date');
                    $valid_from = (!empty($date_val) && strtotime($date_val)) ? date('Y-m-d', strtotime($date_val)) : "$year-01-01";
                    
                    $valid_to = "$year-12-31";
                    
                    // OVERRIDE: ID-based Date Logic (e.g. A2026 -> 2026-01-01 to 2026-12-31)
                    if (!empty($member_id) && preg_match('/^([ASas])(\d{4})/', $member_id, $matches)) {
                        $id_year = intval($matches[2]);
                        $valid_from = "$id_year-01-01";
                        $valid_to = "$id_year-12-31";
                        // Note: If type is lifetime, valid_to will be overwritten to null below, which is correct.
                    }
                    
                    if (strpos($raw_type, 'life') !== false) {
                        $type = 'lifetime';
                        $valid_to = null;
                    } elseif (strpos($raw_type, 'student') !== false) {
                         $type = 'student';
                    }
                    
                    // Family - Spouse
                     $spouse = null;
                    $spouse_fn = $get_val('spouse first name');
                    if (!empty($spouse_fn)) {
                        $spouse = array(
                            'first_name' => sanitize_text_field($spouse_fn),
                            'last_name' => sanitize_text_field($get_val('spouse last name')) ?: $last_name
                        );
                    }
                    
                    // Notes 
                    $email2 = $get_val('email 2');
                    $notes = $email2 ? "Email 2: $email2" : '';
                    
                    // JSON Fields need to be null if empty logic
                    $spouse_json = $spouse ? json_encode($spouse) : null;
                    $children_json = null; // No children columns in this version
                    
                    // Insert
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'tsn_members',
                        array(
                            'member_id' => $member_id,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            // 'phone' => '', // Removing unmapped fields
                            // 'address' => '', 
                            'membership_type' => $type,
                            'valid_from' => $valid_from,
                            'valid_to' => $valid_to,
                            'status' => 'active',
                            'payment_mode' => 'import', 
                            'spouse_details' => $spouse_json,
                            'children_details' => $children_json,
                            'notes' => $notes,
                            'created_at' => current_time('mysql')
                        )
                    );
                    
                    if ($result) {
                        $success_count++;
                    } else {
                        $errors_list[] = "Row $row_num: DB Error - " . $wpdb->last_error;
                        $error_count++;
                    }
                }
                
                $message = "<div class='msg success'><strong>Import Completed!</strong><br>Successfully Imported: $success_count members.<br>Failed/Skipped: $error_count.</div>";
                if (!empty($errors_list)) {
                    $message .= "<div class='msg error'><strong>Detailed Log:</strong><ul><li>" . implode('</li><li>', array_slice($errors_list, 0, 50)) . "</li></ul></div>";
                }
                
            } else {
                 $message = '<div class="msg error">The CSV file appears to be empty or unreadable.</div>';
            }
            fclose($handle);
        } else {
             $message = '<div class="msg error">Could not open the uploaded file.</div>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSN Bulk Member Import</title>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338ca;
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
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 40px 20px;
            line-height: 1.5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-top: 0;
            margin-bottom: 0.5em;
        }
        p {
            color: var(--text-muted);
            margin-bottom: 1.5em;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .btn-download {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: #fff;
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-download:hover {
            background-color: #EEF2FF;
        }
        .upload-area {
            background: #F9FAFB;
            border: 2px dashed #D1D5DB;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
        }
        input[type=file] {
            font-size: 1rem;
        }
        button[type=submit] {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button[type=submit]:hover {
            background-color: var(--primary-hover);
        }
        .msg {
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .msg.success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        .msg.error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }
        .columns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .column-tag {
            background: #EFF6FF;
            color: #1E40AF;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-family: monospace;
            border: 1px solid #BFDBFE;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: var(--text-muted);
            text-decoration: none;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <div>
                <h1>Bulk Member Import</h1>
                <div style="color: var(--text-muted);">Securely import members into the database.</div>
            </div>
            <a href="sample-members-import.csv" class="btn-download" download>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Sample CSV
            </a>
        </div>

        <?php echo $message; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="upload-area">
                <label style="display:block; margin-bottom:15px; font-weight:600;">Select CSV File</label>
                <input type="file" name="csv_file" accept=".csv" required>
                <br><br>
                <button type="submit" name="submit_import">Start Import Process</button>
            </div>
        </form>

        <div style="margin-top: 30px; padding: 20px; border: 1px solid #FECACA; background: #FEF2F2; border-radius: 8px;">
            <h3 style="color: #991B1B; margin-top:0;">Danger Zone</h3>
            
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <p style="color: #7F1D1D; margin-bottom: 10px; font-weight: 600;">Option 1: Delete Imported Only</p>
                    <p style="color: #7F1D1D; margin-bottom: 15px; font-size: 0.9em;">Removes only members with 'payment_mode' = 'import'. Keeps manually added members.</p>
                    <form method="post" onsubmit="return confirm('Are you sure? This will delete only members previously imported via this script.');">
                         <button type="submit" name="delete_imported" style="background-color: #DC2626; color: white; border:none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Delete Imported Members</button>
                    </form>
                </div>
                
                <div style="flex: 1; min-width: 250px; border-left: 1px solid #FECACA; padding-left: 20px;">
                    <p style="color: #7F1D1D; margin-bottom: 10px; font-weight: 600;">Option 2: Delete EVERYTHING (Reset)</p>
                    <p style="color: #7F1D1D; margin-bottom: 15px; font-size: 0.9em;">Truncates the table. Deletes ALL members regardless of source. Resets IDs.</p>
                    <form method="post" onsubmit="return confirm('WARNING: THIS WILL WIPE THE ENTIRE MEMBER DATABASE. \n\nAre you absolutely sure you want to delete ALL members?');">
                         <button type="submit" name="delete_all" style="background-color: #7F1D1D; color: white; border:none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Delete ALL Members</button>
                    </form>
                </div>
            </div>
        </div>

        <div style="margin-top: 40px;">
            <h3 style="border-bottom: 2px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">Required Column Headers</h3>
            <p>Your CSV <strong>must</strong> contain the following headers (order does not matter):</p>
            <div class="columns-grid">
                <span class="column-tag">Date</span>
                <span class="column-tag">First name</span>
                <span class="column-tag">Last Name</span>
                <span class="column-tag">Spouse First Name</span>
                <span class="column-tag">Spouse Last Name</span>
                <span class="column-tag">Membership type</span>
                <span class="column-tag">Membership#</span>
                <span class="column-tag">Email 1</span>
                <span class="column-tag">Email 2</span>
            </div>
        </div>
        
        <a href="<?php echo admin_url(); ?>" class="back-link">&larr; Return to WordPress Dashboard</a>
    </div>
</body>
</html>
