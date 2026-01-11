<?php
/**
 * Events List Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}



$total_events = count($events);
$published_count = count(array_filter($events, function($e) { return $e->status === 'published'; }));
$draft_count = count(array_filter($events, function($e) { return $e->status === 'draft'; }));
?>


<div class="wrap">
    <h1 class="wp-heading-inline">Events</h1>
    <a href="<?php echo admin_url('admin.php?page=tsn-add-event'); ?>" class="page-title-action">Add New Event</a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                if ($_GET['message'] === 'saved') echo 'Event saved successfully!';
                if ($_GET['message'] === 'deleted') echo 'Event deleted successfully!';
                ?>
            </p>
        </div>
    <?php endif; ?>

    <ul class="subsubsub">
        <li><a href="?page=tsn-events" class="current">All (<?php echo $total_events; ?>)</a> |</li>
        <li><a href="?page=tsn-events&status=published">Published (<?php echo $published_count; ?>)</a> |</li>
        <li><a href="?page=tsn-events&status=draft">Draft (<?php echo $draft_count; ?>)</a></li>
    </ul>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Event Name</th>
                <th>Date & Time</th>
                <th>Venue</th>
                <th>Registration</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($events): ?>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo $event->id; ?></td>
                        <td>
                            <strong><a href="<?php echo admin_url('admin.php?page=tsn-add-event&event_id=' . $event->id); ?>"><?php echo esc_html($event->title); ?></a></strong>
                        </td>
                        <td>
                            <?php echo date('M j, Y g:i A', strtotime($event->start_datetime)); ?>
                            <?php if ($event->end_datetime): ?>
                                <br><small>to <?php echo date('M j, Y g:i A', strtotime($event->end_datetime)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($event->venue_name); ?></td>
                        <td>
                            <small>Opens: <?php echo date('M j, Y', strtotime($event->reg_open_datetime)); ?></small><br>
                            <small>Closes: <?php echo date('M j, Y', strtotime($event->reg_close_datetime)); ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($event->status); ?>">
                                <?php echo esc_html(ucfirst($event->status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tsn-add-event&event_id=' . $event->id); ?>">Edit</a> |
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=tsn-events&action=delete&event_id=' . $event->id), 'delete_event_' . $event->id); ?>" 
                               onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No events found. <a href="<?php echo admin_url('admin.php?page=tsn-add-event'); ?>">Create your first event!</a></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.status-published {
    background: #d4edda;
    color: #155724;
}
.status-draft {
    background: #fff3cd;
    color: #856404;
}
.status-archived {
    background: #f8d7da;
    color: #721c24;
}
.status-sold_out {
    background: #e2e3e5;
    color: #383d41;
}
</style>
