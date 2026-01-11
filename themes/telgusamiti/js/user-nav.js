/**
 * User Navigation JavaScript
 * Handles dropdown menu interactions and logout functionality
 */

(function ($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function () {
        console.log('User navigation script loaded');
        console.log('jQuery version:', $.fn.jquery);
        console.log('tsnUserNav exists:', typeof tsnUserNav !== 'undefined');
        if (typeof tsnUserNav !== 'undefined') {
            console.log('tsnUserNav:', tsnUserNav);
        }
        initUserNavigation();
    });

    /**
     * Initialize user navigation
     */
    function initUserNavigation() {
        const $userNav = $('.tsn-user-nav.logged-in');

        if (!$userNav.length) {
            return;
        }

        // Toggle dropdown on trigger click
        $('.user-menu-trigger').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $userNav.toggleClass('menu-open');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.tsn-user-nav.logged-in').length) {
                $userNav.removeClass('menu-open');
            }
        });

        // Handle logout button click
        $('#tsn-logout-btn').on('click', function (e) {
            e.preventDefault();
            handleLogout($(this));
        });

        // Close dropdown on escape key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $userNav.removeClass('menu-open');
            }
        });

        // Prevent dropdown from closing when clicking inside
        $('.user-dropdown-menu').on('click', function (e) {
            e.stopPropagation();
        });
    }

    /**
     * Handle member logout
     */
    function handleLogout($button) {
        console.log('Logout button clicked');
        console.log('tsnUserNav object:', typeof tsnUserNav !== 'undefined' ? tsnUserNav : 'UNDEFINED');

        // Check if tsnUserNav is defined
        if (typeof tsnUserNav === 'undefined') {
            console.error('ERROR: tsnUserNav is not defined! Script localization failed.');
            alert('Configuration error. Please refresh the page and try again.');
            return;
        }

        // Prevent double-clicks
        if ($button.hasClass('loading')) {
            console.log('Already loading, preventing double-click');
            return;
        }

        // Add loading state
        $button.addClass('loading');
        $button.find('.text').text('Logging out...');

        console.log('Making AJAX request to:', tsnUserNav.ajaxUrl);
        console.log('Action:', 'tsn_member_logout');
        console.log('Nonce:', tsnUserNav.nonce);

        // Make AJAX request
        $.ajax({
            url: tsnUserNav.ajaxUrl,
            type: 'POST',
            data: {
                action: 'tsn_member_logout',
                nonce: tsnUserNav.nonce
            },
            success: function (response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    // Show success message (optional)
                    showMessage('Logged out successfully!', 'success');

                    // Redirect after short delay
                    setTimeout(function () {
                        console.log('Redirecting to:', response.data.redirect_url);
                        window.location.href = response.data.redirect_url;
                    }, 500);
                } else {
                    // Show error message
                    console.error('Logout failed:', response.data.message);
                    showMessage(response.data.message || 'Logout failed. Please try again.', 'error');
                    $button.removeClass('loading');
                    $button.find('.text').text('Logout');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', { xhr: xhr, status: status, error: error });
                console.error('Response text:', xhr.responseText);
                console.error('Response status:', xhr.status);
                showMessage('Network error. Please try again.', 'error');
                $button.removeClass('loading');
                $button.find('.text').text('Logout');
            }
        });
    }

    /**
     * Show notification message
     */
    function showMessage(message, type) {
        // Check if a notification container exists
        let $notification = $('#tsn-notification');

        if (!$notification.length) {
            // Create notification element
            $notification = $('<div id="tsn-notification"></div>');
            $('body').append($notification);
        }

        // Set message and type
        $notification
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn(300);

        // Auto-hide after 3 seconds
        setTimeout(function () {
            $notification.fadeOut(300);
        }, 3000);
    }

})(jQuery);
