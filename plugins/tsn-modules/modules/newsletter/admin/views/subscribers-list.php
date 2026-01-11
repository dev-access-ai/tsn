<div class="wrap">
    <h1>Newsletter Subscribers</h1>
    
    <!-- Import Form -->
    <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 20px; box-sizing: border-box;">
        <h2 style="margin-top:0;">Manage Subscribers</h2>
        
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px;">
            <!-- Import Section -->
            <div style="flex:1; min-width:300px; border-right:1px solid #eee; padding-right:20px;">
                <h3>Import from CSV</h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('tsn_import_nonce'); ?>
                    <p>
                        <label><strong>Step 1:</strong> Prepare your CSV file.</label><br>
                        <span class="description">Format: Email, First Name, Last Name (No headers required, or skip first row if headers exist)</span><br>
                        <a href="data:text/csv;charset=utf-8,Email,FirstName,LastName%0Aexample@test.com,John,Doe" download="sample_subscribers.csv" class="button button-small" style="margin-top:5px;"><span class="dashicons dashicons-download" style="vertical-align:text-top;"></span> Download Sample CSV</a>
                    </p>
                    <p>
                        <label><strong>Step 2:</strong> Upload File</label><br>
                        <input type="file" name="csv_file" acccept=".csv" required>
                    </p>
                    <p>
                        <input type="submit" name="import_subscribers" class="button button-primary" value="Import CSV">
                    </p>
                </form>
            </div>
            
            <!-- Sync Section -->
            <div style="flex:1; min-width:300px;">
                <h3>Sync Existing Members</h3>
                <p>Pull all currently <strong>Active</strong> members from the Membership system into this list.</p>
                <form method="post">
                    <?php wp_nonce_field('tsn_import_nonce'); ?>
                    <input type="submit" name="sync_members" class="button button-secondary" value="Sync from Members Database">
                </form>
            </div>
            
            <!-- Constant Contact Sync Section -->
            <div style="flex:1; min-width:300px; border-left: 1px solid #eee; padding-left: 20px;">
                <h3>Constant Contact Sync</h3>
                <p>Push all subscribers to your configured <strong>Constant Contact</strong> list.</p>
                <p>
                    <a href="<?php echo plugins_url('tsn-modules/tsn-newsletter-sync.php'); ?>" target="_blank" class="button button-primary">
                        <span class="dashicons dashicons-cloud-upload" style="vertical-align: text-top; margin-right: 5px;"></span>
                        Open Sync Tool
                    </a>
                </p>
                <p class="description">Opens in a new window to prevent timeouts.</p>
            </div>
        </div>
    </div>
    
    <!-- Search Box -->
    <form method="get" style="text-align: right; margin-bottom: 10px;">
        <input type="hidden" name="page" value="tsn-newsletter">
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search Subscribers:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo isset($_REQUEST['s']) ? esc_attr($_REQUEST['s']) : ''; ?>">
            <input type="submit" id="search-submit" class="button" value="Search">
        </p>
    </form>
    
    <!-- List -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Email</th>
                <th>Name</th>
                <th>Source</th>
                <th>Status</th>
                <th>CC Sync</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($subscribers)): ?>
                <?php 
                $i = 1;
                foreach ($subscribers as $sub): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo esc_html($sub->email); ?></td>
                        <td><?php echo esc_html($sub->first_name . ' ' . $sub->last_name); ?></td>
                        <td><?php echo esc_html(ucfirst($sub->source)); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $sub->status == 'subscribed' ? 'success' : 'secondary'; ?>">
                                <?php echo esc_html(ucfirst($sub->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sub->cc_contact_id): ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span> Synced
                            <?php else: ?>
                                <span class="dashicons dashicons-minus" style="color: #ccc;"></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($sub->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px;">
                        <span class="dashicons dashicons-warning" style="color:orange; font-size:24px; vertical-align:middle;"></span> 
                        <strong>No subscribers found!</strong><br><br>
                        Tip: Click "Sync from Members Database" above to import all your active members automatically.
                    </td>
                </tr>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-success { background: #d4edda; color: #155724; }
.badge-secondary { background: #e2e3e5; color: #383d41; }
</style>
