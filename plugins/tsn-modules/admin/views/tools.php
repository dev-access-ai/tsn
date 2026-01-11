<div class="wrap">
    <h1>TSN System Tools</h1>
    
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Success:</strong> <?php 
                if ($_GET['message'] === 'schema_repaired') {
                    echo 'Database schema has been repaired successfully. All tables and columns are now up to date.';
                }
            ?></p>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Database Maintenance</h2>
        <p>Use this tool to verify and repair the plugin's database tables. This is safe to run on production as it uses <code>dbDelta</code> to only add missing tables or columns without affecting existing data.</p>
        
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="tsn_repair_schema">
            <?php wp_nonce_field('tsn_repair_schema_nonce'); ?>
            <p>
                <button type="submit" class="button button-primary">Repair Database Schema</button>
            </p>
        </form>
    </div>

    <div class="card">
        <h2>Debug Information</h2>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>Plugin Version:</strong> <?php echo TSN_MODULES_VERSION; ?></p>
    </div>
</div>
