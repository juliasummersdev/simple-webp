/**
 * Deactivation modal for Simple WebP Converter
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Get the deactivate link for our plugin
        const deactivateLink = $('tr[data-plugin="' + jsdevWebpDeactivation.pluginBasename + '"] .deactivate a');

        if (deactivateLink.length === 0) {
            return;
        }

        const originalDeactivateUrl = deactivateLink.attr('href');

        // Intercept deactivation click
        deactivateLink.on('click', function(e) {
            e.preventDefault();

            // Show modal
            showDeactivationModal(originalDeactivateUrl);
        });

        function showDeactivationModal(deactivateUrl) {
            // Create modal HTML
            const modalHtml = `
                <div id="jsdev-webp-deactivation-modal" class="jsdev-webp-modal">
                    <div class="jsdev-webp-modal-content">
                        <div class="jsdev-webp-modal-header">
                            <h2>Deactivate Simple WebP Converter</h2>
                            <span class="jsdev-webp-modal-close">&times;</span>
                        </div>
                        <div class="jsdev-webp-modal-body">
                            <p><strong>Do you want to delete all generated WebP images from your uploads folder?</strong></p>
                            <p>This action cannot be undone. If you choose "No", the WebP files will remain and can be used if you reactivate the plugin later.</p>
                            <div class="jsdev-webp-modal-actions">
                                <button type="button" class="button button-primary" id="jsdev-webp-delete-and-deactivate">
                                    Yes, Delete WebP Files
                                </button>
                                <button type="button" class="button" id="jsdev-webp-keep-and-deactivate">
                                    No, Keep WebP Files
                                </button>
                                <button type="button" class="button" id="jsdev-webp-cancel-deactivate">
                                    Cancel
                                </button>
                            </div>
                            <div id="jsdev-webp-deactivation-status" style="display: none; margin-top: 15px;">
                                <p class="jsdev-webp-status-message"></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            $('body').append(modalHtml);

            const modal = $('#jsdev-webp-deactivation-modal');
            const statusContainer = $('#jsdev-webp-deactivation-status');
            const statusMessage = $('.jsdev-webp-status-message');

            // Close modal
            $('.jsdev-webp-modal-close, #jsdev-webp-cancel-deactivate').on('click', function() {
                modal.remove();
            });

            // Close on outside click
            modal.on('click', function(e) {
                if ($(e.target).is(modal)) {
                    modal.remove();
                }
            });

            // Delete and deactivate
            $('#jsdev-webp-delete-and-deactivate').on('click', function() {
                const button = $(this);
                button.prop('disabled', true);
                $('#jsdev-webp-keep-and-deactivate').prop('disabled', true);

                statusContainer.show();
                statusMessage.text('Deleting WebP files... Please wait.');

                $.ajax({
                    url: jsdevWebpDeactivation.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'jsdev_webp_delete_all_webp',
                        nonce: jsdevWebpDeactivation.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            statusMessage.text(response.data.message + ' Deactivating plugin...');

                            // Wait a moment, then proceed to deactivate
                            setTimeout(function() {
                                window.location.href = deactivateUrl;
                            }, 1000);
                        } else {
                            statusMessage.text('Error: ' + (response.data.message || 'Failed to delete WebP files.'));
                            button.prop('disabled', false);
                            $('#jsdev-webp-keep-and-deactivate').prop('disabled', false);
                        }
                    },
                    error: function() {
                        statusMessage.text('An error occurred. Proceeding with deactivation without deleting files.');

                        setTimeout(function() {
                            window.location.href = deactivateUrl;
                        }, 1500);
                    }
                });
            });

            // Keep and deactivate
            $('#jsdev-webp-keep-and-deactivate').on('click', function() {
                // Just deactivate without deleting
                window.location.href = deactivateUrl;
            });
        }
    });

})(jQuery);
