<?php
/**
 * Donations Frontend Template
 */

if (!defined('ABSPATH')) exit;

$causes = TSN_Donations::get_active_causes();
?>

<div class="tsn-donations-page">
    <div class="donations-header">
        <h1>Support Our Mission</h1>
        <p>Your generous donation helps us serve the Telugu community. Every contribution makes a difference.</p>
    </div>

    <?php if ($causes): ?>
        <div class="donation-causes">
            <h2>Our Causes</h2>
            <div class="causes-grid">
                <?php foreach ($causes as $cause): ?>
                    <?php
                    $percentage = ($cause->goal > 0) ? ($cause->total_raised / $cause->goal * 100) : 0;
                    ?>
                    <div class="cause-card">
                        <h3><?php echo esc_html($cause->title); ?></h3>
                        <p><?php echo esc_html($cause->description); ?></p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                        </div>
                        <div class="progress-text">
                            $<?php echo number_format($cause->total_raised, 0); ?> raised of $<?php echo number_format($cause->goal, 0); ?> goal
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="donation-form-container">
        <h2>Make a Donation</h2>
        
        <form id="donation-form">
            <?php if ($causes): ?>
                <div class="form-group">
                    <label>Select Cause <span class="optional">(Optional)</span></label>
                    <select name="cause_id">
                        <option value="">General Fund</option>
                        <?php foreach ($causes as $cause): ?>
                            <option value="<?php echo $cause->id; ?>"><?php echo esc_html($cause->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Donation Amount *</label>
                <div class="amount-buttons">
                    <button type="button" class="amount-btn" data-amount="25">$25</button>
                    <button type="button" class="amount-btn" data-amount="50">$50</button>
                    <button type="button" class="amount-btn" data-amount="100">$100</button>
                    <button type="button" class="amount-btn" data-amount="250">$250</button>
                    <button type="button" class="amount-btn active" data-amount="custom">Custom</button>
                </div>
                <input type="number" name="amount" id="donation-amount" placeholder="Enter amount" min="5" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="donor_name">Full Name *</label>
                <input type="text" name="donor_name" id="donor_name" required>
            </div>

            <div class="form-group">
                <label for="donor_email">Email Address *</label>
                <input type="email" name="donor_email" id="donor_email" required>
            </div>

            <div class="form-group">
                <label for="donor_phone">Phone Number</label>
                <input type="tel" name="donor_phone" id="donor_phone">
            </div>

            <div class="form-group">
                <label for="message">Message <span class="optional">(Optional)</span></label>
                <textarea name="message" id="message" rows="3" placeholder="Your message or dedication"></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_anonymous" value="1">
                    Make my donation anonymous
                </label>
            </div>

            <div class="donation-message"></div>

            <button type="submit" class="btn-donate">Donate Now</button>
        </form>
    </div>
</div>

<style>
.tsn-donations-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.donations-header {
    text-align: center;
    margin-bottom: 50px;
}

.donations-header h1 {
    font-size: 36px;
    margin-bottom: 15px;
}

.donation-causes {
    margin-bottom: 50px;
}

.causes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 25px;
}

.cause-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
}

.cause-card h3 {
    margin: 0 0 15px 0;
    color: #0066cc;
}

.progress-bar {
    width: 100%;
    height: 12px;
    background: #f0f0f0;
    border-radius: 6px;
    overflow: hidden;
    margin: 15px 0 10px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0066cc, #0099ff);
    transition: width 0.3s;
}

.progress-text {
    font-size: 14px;
    color: #666;
}

.donation-form-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 35px;
}

.donation-form-container h2 {
    margin: 0 0 25px 0;
    text-align: center;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.amount-buttons {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.amount-btn {
    padding: 12px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.amount-btn:hover {
    border-color: #0066cc;
}

.amount-btn.active {
    background: #0066cc;
    color: white;
    border-color: #0066cc;
}

.optional {
    font-weight: normal;
    color: #999;
    font-size: 14px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    font-weight: normal;
}

.checkbox-label input {
    width: auto;
    margin-right: 8px;
}

.btn-donate {
    width: 100%;
    padding: 15px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-donate:hover {
    background: #218838;
}

.btn-donate:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.donation-message {
    margin: 15px 0;
    padding: 12px;
    border-radius: 4px;
    display: none;
}

.donation-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    display: block;
}

.donation-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    display: block;
}

@media (max-width: 768px) {
    .amount-buttons {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Amount button selection
    $('.amount-btn').on('click', function(e) {
        e.preventDefault();
        $('.amount-btn').removeClass('active');
        $(this).addClass('active');
        
        const amount = $(this).data('amount');
        if (amount !== 'custom') {
            $('#donation-amount').val(amount);
        } else {
            $('#donation-amount').val('').focus();
        }
    });
    
    // Form submission
    $('#donation-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('.btn-donate');
        const messageDiv = $('.donation-message');
        
        messageDiv.removeClass('success error').hide();
        submitBtn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            method: 'POST',
            data: form.serialize() + '&action=tsn_submit_donation&nonce=<?php echo wp_create_nonce("tsn_donation_nonce"); ?>',
            success: function(response) {
                if (response.success) {
                    messageDiv.addClass('success').text(response.data.message).show();
                    
                    if (response.data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                    } else if (response.data.payment_url) {
                        window.location.href = response.data.payment_url;
                    }
                } else {
                    messageDiv.addClass('error').text(response.data.message || 'Donation failed').show();
                    submitBtn.prop('disabled', false).text('Donate Now');
                }
            },
            error: function() {
                messageDiv.addClass('error').text('Connection error. Please try again.').show();
                submitBtn.prop('disabled', false).text('Donate Now');
            }
        });
    });
});
</script>
