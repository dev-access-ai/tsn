<?php
/**
 * QR Scanner Admin Page
 * 
 * Browser-based QR code scanner for event check-in
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get event ID from URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$event = null;

global $wpdb;

// Get all published events
$events = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}tsn_events 
     WHERE status = 'published' 
     ORDER BY start_datetime DESC"
);

if ($event_id) {
    $event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tsn_events WHERE id = %d",
        $event_id
    ));
}
?>

<div class="wrap qr-scanner-page">
    <h1>QR Code Scanner - Event Check-in</h1>

    <?php if ($event): ?>
        <div class="event-info">
            <h2><?php echo esc_html($event->title); ?></h2>
            <p>
                <strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($event->start_datetime)); ?><br>
                <strong>Venue:</strong> <?php echo esc_html($event->venue_name); ?>
            </p>
        </div>

        <div class="scanner-container">
            <div class="scanner-tabs">
                <button class="tab-btn active" data-tab="camera">Camera Scanner</button>
                <button class="tab-btn" data-tab="manual">Manual Entry</button>
                <button class="tab-btn" data-tab="history">Scan History</button>
            </div>

            <!-- Camera Scanner Tab -->
            <div class="tab-content active" id="camera-tab">
                <div class="camera-section">
                    <div id="qr-reader" style="width: 100%; max-width: 600px; margin: 0 auto;"></div>
                    <div class="camera-controls">
                        <button id="start-scan" class="button button-primary button-large">Start Camera</button>
                        <button id="stop-scan" class="button button-secondary button-large" style="display: none;">Stop Camera</button>
                    </div>
                </div>
            </div>

            <!-- Manual Entry Tab -->
            <div class="tab-content" id="manual-tab">
                <div class="manual-section">
                    <h3>Enter Ticket Number</h3>
                    <form id="manual-validate-form">
                        <input type="text" id="ticket-number-input" placeholder="TKT-XXXXXXXX" class="regular-text" autofocus>
                        <button type="submit" class="button button-primary button-large">Validate Ticket</button>
                    </form>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-content" id="history-tab">
                <div class="history-section">
                    <h3>Recent Scans</h3>
                    <div id="scan-history">
                        <p class="description">No scans yet. Start scanning to see history.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Display -->
        <div id="scan-result" class="scan-result" style="display: none;">
            <div class="result-content">
                <div class="result-icon"></div>
                <div class="result-message"></div>
                <div class="result-details"></div>
                <button class="button button-large" onclick="document.getElementById('scan-result').style.display='none'">Close</button>
            </div>
        </div>

        <!-- Stats -->
        <div class="scan-stats">
            <div class="stat-box">
                <div class="stat-label">Total Scans</div>
                <div class="stat-value" id="total-scans">0</div>
            </div>
            <div class="stat-box success">
                <div class="stat-label">Valid</div>
                <div class="stat-value" id="valid-scans">0</div>
            </div>
            <div class="stat-box error">
                <div class="stat-label">Invalid</div>
                <div class="stat-value" id="invalid-scans">0</div>
            </div>
        </div>

    <?php else: ?>
        <div class="event-selector">
            <h2>Select Event to Scan</h2>
            <?php if ($events): ?>
                <div class="events-list">
                    <?php foreach ($events as $ev): ?>
                        <div class="event-card">
                            <h3><?php echo esc_html($ev->title); ?></h3>
                            <p>
                                <strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($ev->start_datetime)); ?><br>
                                <strong>Venue:</strong> <?php echo esc_html($ev->venue_name); ?>
                            </p>
                            <a href="?page=tsn-qr-scanner&event_id=<?php echo $ev->id; ?>" class="button button-primary button-large">
                                Start Scanning
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

<!-- Include QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
jQuery(document).ready(function($) {
    let html5QrCode = null;
    let scanHistory = [];
    let stats = { total: 0, valid: 0, invalid: 0 };
    
    // Load scan history from database on page load
    function loadScanHistory() {
        <?php if ($event_id): ?>
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'tsn_get_scan_history',
                nonce: '<?php echo wp_create_nonce('tsn_scanner_nonce'); ?>',
                event_id: <?php echo $event_id; ?>
            },
            success: function(response) {
                if (response.success && response.data.scans) {
                    // Process database scans
                    response.data.scans.forEach(scan => {
                        const time = new Date(scan.scanned_at).toLocaleTimeString();
                        const isValid = scan.result === 'valid';
                        
                        scanHistory.push({
                            time: time,
                            type: isValid ? 'success' : 'error',
                            data: scan.ticket_number || scan.reason || 'Invalid',
                            detail: scan.ticket_number ? ('Ticket: ' + scan.ticket_number) : (scan.reason || 'Unknown')
                        });
                        
                        if (isValid) {
                            stats.valid++;
                        } else {
                            stats.invalid++;
                        }
                        stats.total++;
                    });
                    
                    updateHistoryDisplay();
                    updateStats();
                }
            }
        });
        <?php endif; ?>
    }
    
    // Load history on page load
    loadScanHistory();

    // Tab switching
    $('.tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');

        // Stop camera when switching away from camera tab
        if (tab !== 'camera' && html5QrCode) {
            stopScanning();
        }
    });

    // Start camera scanning
    $('#start-scan').on('click', function() {
        startScanning();
    });

    $('#stop-scan').on('click', function() {
        stopScanning();
    });

    let isScanning = false;

    function startScanning() {
        if (isScanning) return;
        
        // Ensure clean state - if instance exists, try to clear it first
        if (html5QrCode) {
            try {
                html5QrCode.clear();
            } catch (e) {
                console.log("Error clearing previous instance", e);
            }
            html5QrCode = null;
        }

        html5QrCode = new Html5Qrcode("qr-reader");
        
        isScanning = true;
        
        // Show stop, hide start immediately to prevent double clicks
        $('#start-scan').hide();
        $('#stop-scan').show();
        
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            onScanError
        ).then(() => {
            // Started successfully
        }).catch(err => {
            isScanning = false;
            html5QrCode = null;
            $('#start-scan').show();
            $('#stop-scan').hide();
            alert('Camera access denied or error: ' + err);
        });
    }

    function stopScanning() {
        if (!html5QrCode) {
            isScanning = false;
            return Promise.resolve();
        }

        return html5QrCode.stop().then(() => {
            return html5QrCode.clear();
        }).catch(err => {
            console.warn("Stop/Clear scanning failed", err);
        }).finally(() => {
            isScanning = false;
            html5QrCode = null;
            $('#start-scan').show();
            $('#stop-scan').hide();
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning temporarily
        stopScanning();
        
        // Validate the ticket
        validateTicket(decodedText);
    }

    function onScanError(errorMessage) {
        // Ignore scan errors (happens continuously while scanning)
    }

    // Manual validation
    $('#manual-validate-form').on('submit', function(e) {
        e.preventDefault();
        const ticketNumber = $('#ticket-number-input').val().trim();
        if (ticketNumber) {
            validateTicket(ticketNumber);
        }
    });

    function validateTicket(ticketData) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'tsn_validate_qr',
                nonce: '<?php echo wp_create_nonce('tsn_scanner_nonce'); ?>',
                qr_data: ticketData,
                event_id: <?php echo $event_id ? $event_id : 0; ?>
            },
            success: function(response) {
                stats.total++;
                
                if (response.success) {
                    stats.valid++;
                    showResult('success', 'Valid Ticket - Checked In!', response.data);
                    addToHistory('success', ticketData, response.data.ticket_number);
                } else {
                    stats.invalid++;
                    showResult('error', response.data.message, {});
                    addToHistory('error', ticketData, response.data.message);
                }
                
                updateStats();
                $('#ticket-number-input').val('');
            },
            error: function() {
                stats.total++;
                stats.invalid++;
                showResult('error', 'Connection error. Please try again.', {});
                updateStats();
            }
        });
    }

    function showResult(type, message, data) {
        const resultDiv = $('#scan-result');
        const icon = type === 'success' ? '✓' : '✗';
        const className = type === 'success' ? 'success' : 'error';
        
        resultDiv.removeClass('success error').addClass(className);
        resultDiv.find('.result-icon').text(icon);
        resultDiv.find('.result-message').html('<h2>' + message + '</h2>');
        
        let details = '';
        if (type === 'success' && data.ticket_number) {
            details = '<p><strong>Ticket:</strong> ' + data.ticket_number + '<br>';
            details += '<strong>Email:</strong> ' + data.attendee_email + '</p>';
        }
        resultDiv.find('.result-details').html(details);
        
        resultDiv.show();
        
        // Auto-hide after 3 seconds for success
        if (type === 'success') {
            setTimeout(() => {
                resultDiv.fadeOut();
                // Restart camera if on camera tab
                if ($('#camera-tab').hasClass('active')) {
                    startScanning();
                }
            }, 3000);
        }
    }

    function addToHistory(type, data, detail) {
        const time = new Date().toLocaleTimeString();
        const icon = type === 'success' ? '✓' : '✗';
        const className = type === 'success' ? 'success' : 'error';
        
        scanHistory.unshift({
            time: time,
            type: type,
            data: data,
            detail: detail
        });
        
        // Keep only last 50 scans in memory
        if (scanHistory.length > 50) {
            scanHistory.pop();
        }
        
        updateHistoryDisplay();
    }

    function updateHistoryDisplay() {
        if (scanHistory.length === 0) {
            $('#scan-history').html('<p class="description">No scans yet. Start scanning to see history.</p>');
            return;
        }
        
        let html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Time</th><th>Status</th><th>Details</th></tr></thead><tbody>';
        
        scanHistory.forEach(item => {
            const className = item.type === 'success' ? 'success' : 'error';
            const icon = item.type === 'success' ? '✓' : '✗';
            html += '<tr class="' + className + '">';
            html += '<td>' + item.time + '</td>';
            html += '<td><span class="status-icon">' + icon + '</span> ' + (item.type === 'success' ? 'Valid' : 'Invalid') + '</td>';
            html += '<td>' + item.detail + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $('#scan-history').html(html);
    }

    function updateStats() {
        $('#total-scans').text(stats.total);
        $('#valid-scans').text(stats.valid);
        $('#invalid-scans').text(stats.invalid);
    }
});
</script>

<style>
.qr-scanner-page {
    max-width: 1200px;
}

.event-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.event-info h2 {
    margin: 0 0 10px 0;
}

.scanner-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.scanner-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
}

.tab-btn {
    flex: 1;
    padding: 15px;
    background: #f8f9fa;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
}

.tab-btn.active {
    background: white;
    border-bottom: 3px solid #0066cc;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.camera-section, .manual-section, .history-section {
    text-align: center;
}

.camera-controls {
    margin-top: 20px;
}

#qr-reader {
    border: 2px dashed #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.manual-section input {
    font-size: 18px;
    padding: 12px;
    width: 300px;
    text-align: center;
    margin-right: 10px;
}

.scan-result {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.result-content {
    background: white;
    padding: 40px;
    border-radius: 8px;
    text-align: center;
    max-width: 500px;
}

.result-icon {
    font-size: 80px;
    margin-bottom: 20px;
}

.scan-result.success .result-icon {
    color: #28a745;
}

.scan-result.error .result-icon {
    color: #dc3545;
}

.result-message h2 {
    margin: 0 0 10px 0;
}

.result-details {
    margin: 20px 0;
    font-size: 16px;
}

.scan-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.stat-box {
    background: white;
    border: 2px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-box.success {
    border-color: #28a745;
}

.stat-box.error {
    border-color: #dc3545;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 36px;
    font-weight: bold;
    color: #0066cc;
}

.stat-box.success .stat-value {
    color: #28a745;
}

.stat-box.error .stat-value {
    color: #dc3545;
}

#scan-history table tr.success {
    background: #d4edda !important;
}

#scan-history table tr.error {
    background: #f8d7da !important;
}

.status-icon {
    font-weight: bold;
    font-size: 18px;
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
