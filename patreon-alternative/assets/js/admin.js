/**
 * Admin JavaScript for Patreon Alternative
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Add Tier
        $('.pa-add-tier').on('click', function(e) {
            e.preventDefault();
            // Open modal or redirect to add tier page
            alert('Add tier functionality - implement modal or redirect');
        });

        // Edit Tier
        $('.pa-edit-tier').on('click', function(e) {
            e.preventDefault();
            var tierId = $(this).data('tier-id');

            // AJAX get tier data
            $.ajax({
                url: paAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pa_get_tier',
                    nonce: paAdmin.nonce,
                    tier_id: tierId
                },
                success: function(response) {
                    if (response.success) {
                        // Show edit modal with tier data
                        console.log('Tier data:', response.data.tier);
                        alert('Edit tier: ' + response.data.tier.name);
                    }
                }
            });
        });

        // Delete Tier
        $('.pa-delete-tier').on('click', function(e) {
            e.preventDefault();
            var tierId = $(this).data('tier-id');

            if (!confirm('Are you sure you want to delete this tier?')) {
                return;
            }

            $.ajax({
                url: paAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pa_delete_tier',
                    nonce: paAdmin.nonce,
                    tier_id: tierId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });

        // Upload Video Button
        $('#pa_upload_video').on('click', function(e) {
            e.preventDefault();

            var mediaUploader;

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Select Video',
                button: {
                    text: 'Use this video'
                },
                library: {
                    type: 'video'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#pa_video_url').val(attachment.url);
            });

            mediaUploader.open();
        });

    });

})(jQuery);
