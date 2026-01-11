<?php
/**
 * Offline Ticketing Admin Page
 * 
 * Add tickets manually for cash/offline sales
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$event = null;
$ticket_types = array();

// Get all published events
$events = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}tsn_events 
     WHERE status = 'published' 
     ORDER BY start_datetime DESC"
);

if ($event_id) {
    $event = TSN_Events::get_event_by_id($event_id);
    $ticket_types = TSN_Events::get_event_ticket_types($event_id);
}

// Handle form submission
if (isset($_POST['tsn_add_offline_tickets']) && check_admin_referer('tsn_offline_ticket_nonce')) {
    $result = TSN_Events_Admin::handle_offline_ticket_sale($_POST);
    
    if ($result['success']) {
        echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . $result['message'] . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1>Add Offline Tickets</h1>

    <?php if ($event): ?>
        <div class="event-info">
            <h2><?php echo esc_html($event->title); ?></h2>
            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($event->start_datetime)); ?></p>
        </div>

        <form method="post" class="offline-ticket-form">
            <?php wp_nonce_field('tsn_offline_ticket_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo $event->id; ?>">

            <table class="form-table">
                <tr>
                    <th colspan="2"><h2>Buyer Information</h2></th>
                </tr>
                <tr>
                    <th><label for="buyer_name">Name *</label></th>
                    <td><input type="text" name="buyer_name" id="buyer_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="buyer_email">Email *</label></th>
                    <td><input type="email" name="buyer_email" id="buyer_email" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="buyer_phone">Phone</label></th>
                    <td><input type="tel" name="buyer_phone" id="buyer_phone" class="regular-text"></td>
                </tr>

                <tr>
                    <th colspan="2"><h2>Ticket Selection</h2></th>
                </tr>
                <?php foreach ($ticket_types as $ticket): ?>
                    <?php 
                    $available = $ticket->capacity - $ticket->sold;
                    ?>
                    <tr>
                        <th><?php echo esc_html($ticket->name); ?></th>
                        <td>
                            <label>
                                <input type="number" name="tickets[<?php echo $ticket->id; ?>][qty]" min="0" max="<?php echo $available; ?>" value="0" class="small-text">
                                tickets
                            </label>
                            <p class="description">
                                Member: $<?php echo number_format($ticket->member_price, 2); ?> | 
                                Non-Member: $<?php echo number_format($ticket->non_member_price, 2); ?> | 
                                Available: <?php echo $available; ?>
                            </p>
                            <label>
                                <input type="checkbox" name="tickets[<?php echo $ticket->id; ?>][is_member]" value="1">
                                Use member pricing
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <th colspan="2"><h2>Payment</h2></th>
                </tr>
                <tr>
                    <th><label for="payment_method">Payment Method *</label></th>
                    <td>
                        <select name="payment_method" id="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="payment_reference">Reference/Notes</label></th>
                    <td>
                        <textarea name="payment_reference" id="payment_reference" rows="3" class="large-text"></textarea>
                        <p class="description">Check number, transaction ID, or other notes</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="tsn_add_offline_tickets" class="button button-primary button-large" value="Generate Tickets">
                <a href="<?php echo admin_url('admin.php?page=tsn-events'); ?>" class="button">Cancel</a>
            </p>
        </form>

    <?php else: ?>
        <div class="event-selector">
            <h2>Select Event for Offline Ticket Sale</h2>
            <?php if ($events): ?>
                <div class="events-list">
                    <?php foreach ($events as $ev): ?>
                        <div class="event-card">
                            <h3><?php echo esc_html($ev->title); ?></h3>
                            <p>
                                <strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($ev->start_datetime)); ?><br>
                                <strong>Venue:</strong> <?php echo esc_html($ev->venue_name); ?>
                            </p>
                            <a href="?page=tsn-offline-tickets&event_id=<?php echo $ev->id; ?>" class="button button-primary button-large">
                                Sell Tickets
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p>No published events found. Please create an event first.</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=tsn-add-event'); ?>" class="button button-primary">Create Event</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.event-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.form-table h2 {
    margin: 20px 0 10px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.form-table h2:first-child {
    margin-top: 0;
}

.event-selector {
    max-width: 800px;
    margin: 40px auto;
}

.event-selector h2 {
    text-align: center;
    margin-bottom: 30px;
}

.events-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.event-card {
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.event-card h3 {
    margin: 0 0 15px 0;
    color: #0066cc;
}

.event-card p {
    text-align: left;
    margin: 15px 0;
    color: #666;
}

.event-card .button {
    margin-top: 15px;
}
</style>
