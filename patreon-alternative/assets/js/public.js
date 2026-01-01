/**
 * Public JavaScript for Patreon Alternative
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Join Tier Button
        $('.pa-join-tier').on('click', function(e) {
            e.preventDefault();

            if (!paPublic.is_logged_in) {
                window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(window.location.href);
                return;
            }

            var tierId = $(this).data('tier-id');
            var $button = $(this);

            $button.prop('disabled', true).text('Processing...');

            $.ajax({
                url: paPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'pa_create_checkout_session',
                    nonce: paPublic.checkout_nonce,
                    tier_id: tierId
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to Stripe checkout or success page
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Join This Tier');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Join This Tier');
                }
            });
        });

        // Cancel Membership
        $('.pa-cancel-membership').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to cancel your membership? You will lose access to patron-only content.')) {
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Cancelling...');

            $.ajax({
                url: paPublic.ajax_url,
                type: 'POST',
                data: {
                    action: 'pa_cancel_subscription',
                    nonce: paPublic.cancel_nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Membership cancelled successfully.');
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Cancel Membership');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false).text('Cancel Membership');
                }
            });
        });

        // Smooth scroll for internal links
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });

    });

})(jQuery);
