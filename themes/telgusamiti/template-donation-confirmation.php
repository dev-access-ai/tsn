<?php
/**
 * Template Name: Donation Confirmation
 */

get_header();

$order_id = isset($_GET['order']) ? intval($_GET['order']) : 0;

if ($order_id) {
    global $wpdb;
    
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tsn_orders WHERE id = %d",
        $order_id
    ));
    
    $donation = $wpdb->get_row($wpdb->prepare(
        "SELECT d.*, c.title as cause_title 
         FROM {$wpdb->prefix}tsn_donations d
         LEFT JOIN {$wpdb->prefix}tsn_donation_causes c ON d.cause_id = c.id
         WHERE d.order_id = %d",
        $order_id
    ));
}
?>

<div class="donation-confirmation-page" style="max-width: 800px; margin: 50px auto; padding: 20px;">
    <?php if ($order && $donation): ?>
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="font-size: 80px; color: #28a745;">✓</div>
            <h1 style="color: #28a745; margin: 20px 0;">Thank You for Your Donation!</h1>
            <p style="font-size: 18px; color: #666;">Your generosity makes a difference</p>
        </div>

        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 30px; margin-bottom: 30px;">
            <h2 style="margin-top: 0;">Donation Receipt</h2>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 0; font-weight: bold;">Order Number:</td>
                    <td style="padding: 12px 0;"><?php echo esc_html($order->order_number); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 0; font-weight: bold;">Date:</td>
                    <td style="padding: 12px 0;"><?php echo date('F j, Y g:i A', strtotime($order->paid_at)); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 0; font-weight: bold;">Amount:</td>
                    <td style="padding: 12px 0; font-size: 24px; color: #0066cc; font-weight: bold;">
                        $<?php echo number_format($order->total, 2); ?>
                    </td>
                </tr>
                <?php if ($donation->cause_title): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 0; font-weight: bold;">Cause:</td>
                    <td style="padding: 12px 0;"><?php echo esc_html($donation->cause_title); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 0; font-weight: bold;">Payment Method:</td>
                    <td style="padding: 12px 0;"><?php echo ucfirst($order->payment_method); ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; font-weight: bold;">Donor:</td>
                    <td style="padding: 12px 0;"><?php echo esc_html($order->buyer_name); ?></td>
                </tr>
            </table>
        </div>

        <?php if ($donation->message): ?>
        <div style="background: #f8f9fa; border-left: 4px solid #0066cc; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0;">Your Message</h3>
            <p><?php echo nl2br(esc_html($donation->message)); ?></p>
        </div>
        <?php endif; ?>

        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin-top: 0;">📧 Receipt Sent</h3>
            <p>A detailed receipt has been sent to <strong><?php echo esc_html($order->buyer_email); ?></strong></p>
            <p style="margin-bottom: 0;">Please check your email (and spam folder) for the receipt.</p>
        </div>

        <div style="text-align: center;">
            <a href="<?php echo home_url(); ?>" style="display: inline-block; padding: 12px 30px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                Return to Home
            </a>
        </div>

    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <h2>Donation Not Found</h2>
            <p>We couldn't find this donation. Please contact us if you need assistance.</p>
            <a href="<?php echo home_url(); ?>" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px;">
                Return to Home
            </a>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
