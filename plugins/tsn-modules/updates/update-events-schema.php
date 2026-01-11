<?php
/**
 * Database Schema Update - Add Excerpt to Events
 * Run this file once to add the excerpt column to existing tsn_events table
 * 
 * Access: http://localhost/tsn/wp-content/plugins/tsn-modules/updates/update-events-schema.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'tsn_events';

// Check if excerpt column already exists
$column_exists = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'excerpt'",
        DB_NAME,
        $table_name
    )
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>TSN Events Schema Update</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2271b1; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        code { background: #f6f7f7; padding: 2px 6px; border-radius: 3px; }
        pre { background: #f6f7f7; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 TSN Events Schema Update</h1>
    
    <?php if (!empty($column_exists)): ?>
        <div class="warning">
            <strong>⚠️ Column Already Exists</strong>
            <p>The <code>excerpt</code> column already exists in the <code><?php echo $table_name; ?></code> table.</p>
            <p>No database changes needed.</p>
        </div>
    <?php else: ?>
        <div class="info">
            <strong>ℹ️ Schema Update Required</strong>
            <p>The <code>excerpt</code> column does not exist in the <code><?php echo $table_name; ?></code> table.</p>
            <p>Click the button below to add it.</p>
        </div>
        
        <?php if (isset($_POST['run_update']) && check_admin_referer('tsn_schema_update')): ?>
            <?php
            // Run the ALTER TABLE command
            $sql = "ALTER TABLE $table_name ADD COLUMN excerpt TEXT DEFAULT NULL COMMENT 'Short event description for listings' AFTER description";
            
            $result = $wpdb->query($sql);
            
            if ($result !== false) {
                echo '<div class="success">';
                echo '<strong>✅ Success!</strong>';
                echo '<p>The <code>excerpt</code> column has been added successfully.</p>';
                echo '<p><strong>SQL Executed:</strong></p>';
                echo '<pre>' . htmlspecialchars($sql) . '</pre>';
                echo '</div>';
                
                // Verify the column was added
                $verify = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_COMMENT 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'excerpt'",
                        DB_NAME,
                        $table_name
                    )
                );
                
                if (!empty($verify)) {
                    echo '<div class="success">';
                    echo '<strong>✅ Verification Successful</strong>';
                    echo '<p>Column details:</p>';
                    echo '<pre>' . print_r($verify[0], true) . '</pre>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Error</strong>';
                echo '<p>Failed to add the column. Database error:</p>';
                echo '<pre>' . htmlspecialchars($wpdb->last_error) . '</pre>';
                echo '<p><strong>Attempted SQL:</strong></p>';
                echo '<pre>' . htmlspecialchars($sql) . '</pre>';
                echo '</div>';
            }
            ?>
        <?php else: ?>
            <form method="post">
                <?php wp_nonce_field('tsn_schema_update'); ?>
                <p>
                    <button type="submit" name="run_update" class="button button-primary" style="padding: 10px 20px; font-size: 16px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        ▶️ Run Schema Update
                    </button>
                </p>
            </form>
            
            <div class="info">
                <p><strong>What will this do?</strong></p>
                <p>This will execute the following SQL command:</p>
                <pre>ALTER TABLE <?php echo $table_name; ?> 
ADD COLUMN excerpt TEXT DEFAULT NULL 
COMMENT 'Short event description for listings' 
AFTER description</pre>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <h2>📊 Current Table Schema</h2>
    <?php
    $columns = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s 
             ORDER BY ORDINAL_POSITION",
            DB_NAME,
            $table_name
        )
    );
    
    if ($columns) {
        echo '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        echo '<thead><tr style="background: #2271b1; color: white;">';
        echo '<th style="padding: 10px; text-align: left;">Column Name</th>';
        echo '<th style="padding: 10px; text-align: left;">Type</th>';
        echo '<th style="padding: 10px; text-align: left;">Nullable</th>';
        echo '<th style="padding: 10px; text-align: left;">Comment</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($columns as $col) {
            $is_new = $col->COLUMN_NAME === 'excerpt';
            echo '<tr style="' . ($is_new ? 'background: #d4edda;' : '') . ' border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 10px;"><code>' . htmlspecialchars($col->COLUMN_NAME) . '</code>' . ($is_new ? ' <strong style="color: #155724;">NEW</strong>' : '') . '</td>';
            echo '<td style="padding: 10px;">' . htmlspecialchars($col->DATA_TYPE) . '</td>';
            echo '<td style="padding: 10px;">' . htmlspecialchars($col->IS_NULLABLE) . '</td>';
            echo '<td style="padding: 10px;">' . htmlspecialchars($col->COLUMN_COMMENT ?: '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    ?>
    
    <p style="margin-top: 30px;">
        <a href="<?php echo admin_url('admin.php?page=tsn-events'); ?>" style="color: #2271b1;">← Back to Events</a>
    </p>
</body>
</html>
