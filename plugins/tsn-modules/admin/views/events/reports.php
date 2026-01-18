<?php
/**
 * Event Reports Page
 * 
 * View sales reports and export attendee lists
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$view_event_id = isset($_GET['view_event_id']) ? intval($_GET['view_event_id']) : 0;
?>

<div class="wrap">
    
    <?php if ($view_event_id): 
        // -------------------------
        // DETAIL VIEW
        // -------------------------
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_events WHERE id = %d", $view_event_id));
        
        if (!$event): ?>
            <div class="notice notice-error"><p>Event not found.</p></div>
            <a href="<?php echo admin_url('admin.php?page=tsn-event-reports'); ?>" class="button">&larr; Back to Events</a>
        <?php else: ?>
            
            <h1 class="wp-heading-inline">Report: <?php echo esc_html($event->title); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=tsn-event-reports'); ?>" class="page-title-action">&larr; Back to All Events</a>
            <hr class="wp-header-end">

            <?php
            // Get stats
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    COALESCE(SUM(o.total), 0) as total_revenue,
                    (SELECT COALESCE(SUM(oi.qty), 0) 
                     FROM {$wpdb->prefix}tsn_order_items oi 
                     JOIN {$wpdb->prefix}tsn_orders o2 ON oi.order_id = o2.id 
                     WHERE o2.event_id = %d AND (o2.status = 'completed' OR o2.status = 'paid')) as tickets_sold
                 FROM {$wpdb->prefix}tsn_orders o
                 WHERE o.event_id = %d AND (o.status = 'completed' OR o.status = 'paid')",
                $event->id,
                $event->id
            ));

            if (!$stats) $stats = (object)['total_orders'=>0, 'total_revenue'=>0, 'tickets_sold'=>0];
            
            // Get simple RSVPs count (orders with no tickets)
            $simple_rsvps = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM {$wpdb->prefix}tsn_orders 
                 WHERE event_id = %d AND order_type = 'ticket' AND total = 0 AND status = 'completed'", 
                $event->id
            ));
            
            // Get total donations for this event
            $total_donations = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(d.amount), 0) 
                 FROM {$wpdb->prefix}tsn_donations d
                 JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
                 WHERE d.event_id = %d AND o.status IN ('completed', 'paid')", 
                $event->id
            ));
            
            // Recalculate revenue (Ticket Sales only)
            $ticket_revenue = $stats->total_revenue - $total_donations;
            
            $ticket_types = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tsn_event_ticket_types WHERE event_id = %d ORDER BY display_order",
                $event->id
            ));
            ?>

            <!-- KPI Cards -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Ticket Revenue</div>
                    <div class="stat-value">$<?php echo number_format($ticket_revenue, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Donations</div>
                    <div class="stat-value">$<?php echo number_format($total_donations, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Tickets Sold</div>
                    <div class="stat-value"><?php echo intval($stats->tickets_sold); ?></div>
                </div>
                <!-- Combined RSVPs + Orders count if needed, or keep simple -->
                <div class="stat-box">
                    <div class="stat-label">Simple RSVPs</div>
                    <div class="stat-value"><?php echo intval($simple_rsvps); ?></div>
                </div>
            </div>

            <div class="event-report-card">
                <h3>Ticket Breakdown</h3>
                <?php if ($ticket_types): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Ticket Type</th>
                            <th>Sold</th>
                            <th>Capacity</th>
                            <th>Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ticket_types as $ticket): ?>
                            <?php
                            $sold_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COALESCE(SUM(oi.qty), 0) 
                                 FROM {$wpdb->prefix}tsn_order_items oi 
                                 JOIN {$wpdb->prefix}tsn_orders o ON oi.order_id = o.id
                                 WHERE oi.ticket_type_id = %d 
                                 AND (o.status = 'completed' OR o.status = 'paid')",
                                $ticket->id
                            ));
                            $available = $ticket->capacity - $sold_count;
                            $percentage = ($ticket->capacity > 0) ? ($sold_count / $ticket->capacity * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html($ticket->name); ?></td>
                                <td><?php echo intval($sold_count); ?></td>
                                <td><?php echo $ticket->capacity; ?></td>
                                <td>
                                    <?php echo $available; ?> 
                                    <span style="color: <?php echo $available == 0 ? '#d63638' : '#00a32a'; ?>;">
                                        (<?php echo round($percentage); ?>%)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php
                        // Check for orphaned/legacy tickets (ID not in current list)
                        $current_ids = array();
                        foreach ($ticket_types as $t) {
                            $current_ids[] = $t->id;
                        }
                        
                        $id_list = !empty($current_ids) ? implode(',', $current_ids) : '0';
                        
                        $legacy_sold_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COALESCE(SUM(oi.qty), 0) 
                             FROM {$wpdb->prefix}tsn_order_items oi 
                             JOIN {$wpdb->prefix}tsn_orders o ON oi.order_id = o.id
                             WHERE o.event_id = %d 
                             AND (o.status = 'completed' OR o.status = 'paid')
                             AND (oi.ticket_type_id IS NULL OR oi.ticket_type_id NOT IN ($id_list))",
                            $event->id
                        ));
                        
                        if ($legacy_sold_count > 0):
                        ?>
                            <tr style="background-color: #fff1f0;">
                                <td><strong>Deleted / Legacy Tickets</strong> <span class="dashicons dashicons-warning" title="These tickets belong to types that were deleted or modified. The data is preserved here."></span></td>
                                <td><strong><?php echo intval($legacy_sold_count); ?></strong></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No ticket types configured (Simple RSVP Mode).</p>
                <?php endif; ?>

                <?php 
                // Attendees List (New Section)
                // Attendees List (New Section)
                // We prefer fetching from tsn_tickets to show individual attendees for each ticket found.
                // We fallback to tsn_order_items only if no tickets found (e.g. Simple RSVP or legacy).
                
                // Check if we have tickets (Linked via Orders)
                $has_tickets = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(t.id) 
                     FROM {$wpdb->prefix}tsn_tickets t 
                     JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
                     WHERE o.event_id = %d", 
                    $event->id
                ));
                
                $attendees = array();
                
                if ($has_tickets > 0) {
                     // Query Tickets table
                     $attendees = $wpdb->get_results($wpdb->prepare(
                        "SELECT 
                            t.id as ticket_id,
                            0 as rsvp_id,
                            t.attendee_name,
                            t.attendee_email,
                            t.attendee_phone,
                            o.buyer_name, 
                            o.buyer_email,
                            o.buyer_phone,
                            o.order_number, 
                            tt.name as ticket_name,
                            o.created_at as order_date
                         FROM {$wpdb->prefix}tsn_tickets t
                         JOIN {$wpdb->prefix}tsn_orders o ON t.order_id = o.id
                         LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON t.ticket_type_id = tt.id
                         WHERE o.event_id = %d AND t.status != 'void' AND (o.status = 'completed' OR o.status = 'paid')
                         ORDER BY o.created_at DESC",
                        $event->id
                    ));
                } else {
                    // Fallback to Order Items (Simple RSVP or legacy)
                    // Note: Simple RSVP basically = One Order per RSVP usually, so we use o.id as identifier
                    $attendees = $wpdb->get_results($wpdb->prepare(
                        "SELECT 
                            0 as ticket_id,
                            o.id as rsvp_id,
                            NULL as attendee_name,
                            NULL as attendee_email,
                            NULL as attendee_phone,
                            o.buyer_name, 
                            o.buyer_email, 
                            o.buyer_phone, 
                            o.order_number, 
                            o.status as order_status,
                            tt.name as ticket_name,
                            o.created_at as order_date
                         FROM {$wpdb->prefix}tsn_orders o
                         LEFT JOIN {$wpdb->prefix}tsn_order_items oi ON o.id = oi.order_id
                         LEFT JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON oi.ticket_type_id = tt.id
                         WHERE o.event_id = %d AND (o.status = 'completed' OR o.status = 'paid')
                         ORDER BY o.created_at DESC",
                        $event->id
                    ));
                }
                ?>
                
                <h3>Attendee List (<?php echo count($attendees); ?>)</h3>
                <div style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #ddd;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Order #</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                            <tr><td colspan="6">No attendees found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $att): 
                                $is_ticket = !empty($att->ticket_id);
                                $item_id = $is_ticket ? $att->ticket_id : $att->rsvp_id;
                                $type = $is_ticket ? 'ticket' : 'rsvp';
                                $current_name = $att->attendee_name ?? $att->buyer_name;
                                $current_email = $att->attendee_email ?? $att->buyer_email;
                            ?>
                            <tr id="row-<?php echo $type . '-' . $item_id; ?>">
                                <td class="col-name">
                                    <?php echo esc_html($current_name); ?>
                                </td>
                                <td class="col-email"><?php echo esc_html($current_email); ?></td>
                                <td><?php echo esc_html($att->buyer_phone); ?></td>
                                <td>
                                    <?php echo esc_html($att->ticket_name ?? 'Simple RSVP'); ?>
                                </td>
                                <td><?php echo esc_html($att->order_number); ?></td>
                                <td>
                                    <button type="button" class="button button-small edit-ticket-btn" 
                                            data-id="<?php echo $item_id; ?>" 
                                            data-type="<?php echo $type; ?>"
                                            data-name="<?php echo esc_attr($current_name); ?>"
                                            data-email="<?php echo esc_attr($current_email); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </button>
                                    <button type="button" class="button button-small delete-ticket-btn"
                                            data-id="<?php echo $item_id; ?>"
                                            data-type="<?php echo $type; ?>"
                                            style="color: #a00;">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <div class="report-actions">
                     <a href="<?php echo admin_url('admin-ajax.php?action=tsn_export_attendees&event_id=' . $event->id . '&nonce=' . wp_create_nonce('tsn_export_' . $event->id)); ?>" class="button button-primary">Export Attendees CSV</a>
                     <a href="<?php echo admin_url('admin.php?page=tsn-offline-tickets&event_id=' . $event->id); ?>" class="button">Add Offline Tickets</a>
                     <a href="<?php echo admin_url('admin-ajax.php?action=tsn_export_donations&event_id=' . $event->id . '&nonce=' . wp_create_nonce('tsn_export_donations_' . $event->id)); ?>" class="button">Export Donations CSV</a>
                </div>
            </div>

            <!-- Volunteers Section -->
            <?php
            $volunteers = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tsn_event_volunteers WHERE event_id = %d ORDER BY created_at DESC", $event->id));
            if (!empty($volunteers)): ?>
                <div class="event-report-card">
                    <h3>Volunteers (<?php echo count($volunteers); ?>)</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($volunteers as $vol): ?>
                                <tr>
                                    <td><?php echo esc_html($vol->name); ?></td>
                                    <td><?php echo esc_html($vol->email); ?></td>
                                    <td><?php echo esc_html($vol->phone); ?></td>
                                    <td>
                                        <select class="volunteer-status-select" data-id="<?php echo $vol->id; ?>">
                                            <option value="pending" <?php selected($vol->status, 'pending'); ?>>Pending</option>
                                            <option value="approved" <?php selected($vol->status, 'approved'); ?>>Approved</option>
                                            <option value="rejected" <?php selected($vol->status, 'rejected'); ?>>Rejected</option>
                                        </select>
                                        <span class="spinner" style="float: none; margin: 0;"></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($vol->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <!-- Edit Ticket Modal -->
            <div id="edit-ticket-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
                <div style="background:white; width:400px; margin:100px auto; padding:20px; border-radius:5px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
                    <h3 style="margin-top:0;">Edit Attendee</h3>
                    <form id="edit-ticket-form">
                        <input type="hidden" id="edit-id">
                        <input type="hidden" id="edit-type">
                        <p>
                            <label>Name</label><br>
                            <input type="text" id="edit-name" class="widefat">
                        </p>
                        <p>
                            <label>Email</label><br>
                            <input type="email" id="edit-email" class="widefat">
                        </p>
                        <p style="text-align:right; margin-top:20px;">
                            <button type="button" class="button" onclick="document.getElementById('edit-ticket-modal').style.display='none'">Cancel</button>
                            <button type="submit" class="button button-primary">Save Changes</button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Volunteer AJAX Script + Ticket Actions -->
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var nonce = '<?php echo wp_create_nonce("tsn_ticket_action_nonce"); ?>';
                    
                    // Delete Ticket
                    $('.delete-ticket-btn').on('click', function() {
                        if(!confirm('Are you sure you want to remove this attendee? This action cannot be undone.')) return;
                        
                        var btn = $(this);
                        var id = btn.data('id');
                        var type = btn.data('type'); // ticket or rsvp
                        
                        var data = { action: 'tsn_delete_ticket', nonce: nonce };
                        if(type === 'ticket') data.ticket_id = id;
                        else data.order_id = id;
                        
                        $.ajax({
                            url: ajaxurl, type: 'POST', data: data,
                            success: function(resp) {
                                if(resp.success) {
                                    $('#row-' + type + '-' + id).fadeOut();
                                } else {
                                    alert(resp.data.message);
                                }
                            }
                        });
                    });
                    
                    // Edit Ticket
                    $('.edit-ticket-btn').on('click', function() {
                        var btn = $(this);
                        $('#edit-id').val(btn.data('id'));
                        $('#edit-type').val(btn.data('type'));
                        $('#edit-name').val(btn.data('name'));
                        $('#edit-email').val(btn.data('email'));
                        $('#edit-ticket-modal').fadeIn();
                    });
                    
                    // Submit Edit
                    $('#edit-ticket-form').on('submit', function(e) {
                        e.preventDefault();
                        var id = $('#edit-id').val();
                        var type = $('#edit-type').val();
                        var name = $('#edit-name').val();
                        var email = $('#edit-email').val();
                        
                        var data = { 
                            action: 'tsn_update_ticket', 
                            nonce: nonce,
                            attendee_name: name,
                            attendee_email: email
                        };
                        
                        if(type === 'ticket') data.ticket_id = id;
                        else data.order_id = id;
                        
                        $.ajax({
                            url: ajaxurl, type: 'POST', data: data,
                            success: function(resp) {
                                if(resp.success) {
                                    $('#edit-ticket-modal').fadeOut();
                                    // Update UI
                                    var row = $('#row-' + type + '-' + id);
                                    row.find('.col-name').text(name);
                                    row.find('.col-email').text(email);
                                    
                                    // Update Button Data
                                    var btn = row.find('.edit-ticket-btn');
                                    btn.data('name', name);
                                    btn.data('email', email);
                                } else {
                                    alert(resp.data.message);
                                }
                            }
                        });
                    });

                    // Existing Volunteer Logic
                    var previous;
                    $('.volunteer-status-select').on('focus', function () { previous = this.value; }).change(function() {
                        var select = $(this);
                        var volunteer_id = select.data('id');
                        var status = select.val();
                        var spinner = select.siblings('.spinner');
                        
                        if (!confirm('Change status to "' + status + '"? This will notify the volunteer.')) {
                            select.val(previous); return;
                        }
                        
                        spinner.addClass('is-active');
                        select.prop('disabled', true);
                        
                        $.ajax({
                            url: ajaxurl, type: 'POST',
                            data: { action: 'tsn_update_volunteer_status', volunteer_id: volunteer_id, status: status, nonce: '<?php echo wp_create_nonce("tsn_volunteer_status_nonce"); ?>' },
                            success: function(response) {
                                spinner.removeClass('is-active'); select.prop('disabled', false);
                                if (response.success) {
                                    select.after('<span class="update-msg" style="color:#46b450; margin-left:5px;">Updated!</span>');
                                    setTimeout(function() { select.siblings('.update-msg').fadeOut(function(){ $(this).remove(); }); }, 3000);
                                    previous = status;
                                } else { alert(response.data.message || 'Error'); select.val(previous); }
                            },
                            error: function() { spinner.removeClass('is-active'); select.prop('disabled', false); alert('Connection error'); select.val(previous); }
                        });
                    });
                });
                </script>
            <?php endif; ?>

            <!-- Donations Section -->
            <?php
            $donations = $wpdb->get_results($wpdb->prepare(
                "SELECT d.*, o.order_number, d.comments as message, d.anonymous as is_anonymous
                 FROM {$wpdb->prefix}tsn_donations d
                 JOIN {$wpdb->prefix}tsn_orders o ON d.order_id = o.id
                 WHERE d.event_id = %d ORDER BY d.created_at DESC", $event->id
            ));
            ?>
            <?php if (!empty($donations)): ?>
                <div class="event-report-card">
                    <h3>Donations ($<?php echo number_format(array_sum(array_column($donations, 'amount')), 2); ?>)</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>Donor</th><th>Amount</th><th>Date</th><th>Message</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($donations as $donation): ?>
                                <tr>
                                    <td><?php echo esc_html($donation->donor_name); ?><?php if ($donation->is_anonymous) echo ' <small>(Anon)</small>'; ?></td>
                                    <td>$<?php echo number_format($donation->amount, 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($donation->created_at)); ?></td>
                                    <td><?php echo esc_html($donation->message); ?></td>
                                    <td><a href="<?php echo admin_url('admin-ajax.php?action=tsn_download_receipt&order_id=' . $donation->order_id . '&nonce=' . wp_create_nonce('tsn_receipt_' . $donation->order_id)); ?>" class="button button-small" target="_blank">Download Receipt</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    <?php else: 
        // -------------------------
        // DASHBOARD VIEW
        // -------------------------
        $events = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tsn_events WHERE status != 'archived' ORDER BY start_datetime DESC");
        ?>
        
        <h1>Event Reports Dashboard</h1>
        
        <!-- Global Stats (Optional, fetched for list) -->
        
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Tickets Sold</th>
                    <th>Revenue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="6">No events found.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): 
                        // Quick stats for overview
                        $stats = $wpdb->get_row($wpdb->prepare(
                            "SELECT 
                                (SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}tsn_orders WHERE event_id = %d AND (status = 'completed' OR status = 'paid')) as total_revenue,
                                (SELECT COUNT(id) FROM {$wpdb->prefix}tsn_tickets WHERE event_id = %d AND status != 'void') as tickets_sold",
                            $event->id,
                            $event->id
                        ));
                        
                        // Check for simple RSVPs
                        $reg_mode = isset($event->registration_mode) ? $event->registration_mode : 'ticket';
                        
                        if ($reg_mode === 'simple_rsvp') {
                             $rsvps = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(id) FROM {$wpdb->prefix}tsn_orders 
                                 WHERE event_id = %d AND order_type = 'ticket' AND total = 0 AND status = 'completed'", 
                                $event->id
                            ));
                            $display_sold = $rsvps . ' (RSVPs)';
                        } else {
                            $display_sold = intval($stats->tickets_sold);
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo admin_url('admin.php?page=tsn-event-reports&view_event_id=' . $event->id); ?>">
                                <?php echo esc_html($event->title); ?>
                            </a></strong>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($event->start_datetime)); ?></td>
                        <td>
                            <?php echo $reg_mode === 'simple_rsvp' ? '<span class="status-badge">Simple RSVP</span>' : 'Ticketed'; ?>
                        </td>
                        <td><?php echo $display_sold; ?></td>
                        <td>$<?php echo number_format($stats->total_revenue, 2); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tsn-event-reports&view_event_id=' . $event->id); ?>" class="button button-primary">View Report</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    <?php endif; ?>
</div>

<style>
.event-report-card { background: white; border: 1px solid #ddd; border-radius: 4px; padding: 25px; margin-bottom: 30px; }
.event-report-card h3 { margin-top: 0; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
.stat-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; padding: 15px; text-align: center; }
.stat-label { font-size: 13px; color: #666; margin-bottom: 8px; }
.stat-value { font-size: 28px; font-weight: bold; color: #0066cc; }
.report-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
.report-actions .button { margin-right: 10px; }
.status-badge { background: #e5e5e5; color: #333; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
</style>
