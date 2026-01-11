<?php
/**
 * Template Name: Ticket Confirmation
 * 
 * Displays order confirmation after ticket purchase
 */

get_header();

$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

if (!$order_id) {
    echo '<div class="container"><p>Invalid order.</p></div>';
    get_footer();
    exit;
}

global $wpdb;

// Get order
$order = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
    $order_id
));

if (!$order) {
    echo '<div class="container"><p>Order not found.</p></div>';
    get_footer();
    exit;
}

// Get event
$event = TSN_Events::get_event_by_id($order->event_id);

// Get tickets
$tickets = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, tt.name as ticket_type_name 
     FROM {$wpdb->prefix}tsn_tickets t
     JOIN {$wpdb->prefix}tsn_event_ticket_types tt ON t.ticket_type_id = tt.id
     WHERE t.order_id = %d",
    $order_id
));
?>

<div class="container confirmation-page">
    <div class="confirmation-header">
        <div class="success-icon">✓</div>
        <h1>Booking Confirmed!</h1>
        <p>Your tickets for <strong><?php echo esc_html($event->title); ?></strong> have been confirmed.</p>
    </div>

    <div class="confirmation-details">
        <div class="details-section">
            <h2>Order Details</h2>
            <table class="details-table">
                <tr>
                    <td><strong>Order Number:</strong></td>
                    <td><?php echo esc_html($order->order_number); ?></td>
                </tr>
                <tr>
                    <td><strong>Event:</strong></td>
                    <td><?php echo esc_html($event->title); ?></td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td><?php echo date('l, F j, Y', strtotime($event->start_datetime)); ?></td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td><?php echo date('g:i A', strtotime($event->start_datetime)); ?></td>
                </tr>
                <?php if ($event->venue_name): ?>
                <tr>
                    <td><strong>Venue:</strong></td>
                    <td><?php echo esc_html($event->venue_name); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Total Amount:</strong></td>
                    <td><strong>$<?php echo number_format($order->total, 2); ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="tickets-section">
            <h2>Your Tickets (<?php echo count($tickets); ?>)</h2>
            <p class="email-notice">📧 Your tickets with QR codes have been sent to <strong><?php echo esc_html($order->buyer_email); ?></strong></p>
            
            <div class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card">
                    <h3><?php echo esc_html($ticket->ticket_type_name); ?></h3>
                    <p><strong>Ticket #:</strong> <?php echo esc_html($ticket->ticket_number); ?></p>
                    <p class="qr-instruction">Show the QR code from your email at the event entrance</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="next-steps">
            <h2>What's Next?</h2>
            <ol>
                <li>Check your email for tickets with QR codes</li>
                <li>Save the email or take a screenshot</li>
                <li>Show your QR code at the event entrance</li>
            </ol>
            <p><strong>Need help?</strong> Contact us at events@telugusamiti.org</p>
        </div>
    </div>

    <div class="confirmation-actions">
        <a href="<?php echo home_url('/events/'); ?>" class="btn-secondary">View All Events</a>
        <a href="<?php echo home_url(); ?>" class="btn-primary">Back to Home</a>
    </div>
</div>

<style>
.confirmation-page {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
}

.confirmation-header {
    text-align: center;
    margin-bottom: 40px;
}

.success-icon {
    width: 80px;
    height: 80px;
    line-height: 80px;
    margin: 0 auto 20px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    font-size: 48px;
    font-weight: bold;
}

.confirmation-header h1 {
    color: #28a745;
    margin-bottom: 10px;
}

.confirmation-details {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.details-section,
.tickets-section,
.next-steps {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.next-steps {
    border-bottom: none;
}

.details-table {
    width: 100%;
    margin-top: 15px;
}

.details-table td {
    padding: 10px 0;
}

.details-table td:first-child {
    width: 200px;
}

.email-notice {
    background: #d4edda;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
}

.tickets-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ticket-card {
    border: 2px solid #0066cc;
    border-radius: 8px;
    padding: 20px;
    background: #f8f9fa;
}

.ticket-card h3 {
    margin: 0 0 10px 0;
    color: #0066cc;
}

.ticket-card p {
    margin: 5px 0;
}

.qr-instruction {
    font-size: 14px;
    color: #666;
    margin-top: 10px;
}

.next-steps ol {
    margin: 15px 0;
    padding-left: 25px;
}

.next-steps li {
    margin: 10px 0;
}

.confirmation-actions {
    text-align: center;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-primary,
.btn-secondary {
    padding: 12px 30px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary {
    background: #0066cc;
    color: white;
}

.btn-primary:hover {
    background: #0052a3;
}

.btn-secondary {
    background: white;
    color: #0066cc;
    border: 2px solid #0066cc;
}

.btn-secondary:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .tickets-list {
        grid-template-columns: 1fr;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>
