var tsnMembership = {
    printTicket: function(orderId) {
        this.downloadFile('tsn_download_tickets', orderId);
    },
    
    emailTicket: function(orderId) {
        this.sendEmail('tsn_resend_tickets', orderId, 'Tickets sent successfully!');
    },
    
    printReceipt: function(orderId) {
        this.downloadFile('tsn_download_receipt', orderId);
    },
    
    emailReceipt: function(orderId) {
        this.sendEmail('tsn_resend_receipt', orderId, 'Receipt sent successfully!');
    },
    
    downloadFile: function(action, orderId) {
        var button = event.target;
        var originalText = button.innerHTML;
        button.innerHTML = '⏳';
        button.disabled = true;
        
        jQuery.ajax({
            url: tsn_obj.ajax_url,
            type: 'POST',
            data: {
                action: action,
                order_id: orderId,
                nonce: tsn_obj.nonce
            },
            success: function(response) {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (response.success) {
                    window.open(response.data.url, '_blank');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Connection failed. Please try again.');
            }
        });
    },
    
    sendEmail: function(action, orderId, successMessage) {
        var button = event.target;
        var originalText = button.innerHTML;
        button.innerHTML = '⏳';
        button.disabled = true;
        
        jQuery.ajax({
            url: tsn_obj.ajax_url,
            type: 'POST',
            data: {
                action: action,
                order_id: orderId,
                nonce: tsn_obj.nonce
            },
            success: function(response) {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (response.success) {
                    alert(successMessage);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Connection failed. Please try again.');
            }
        });
    }
};
