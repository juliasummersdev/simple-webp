/**
 * Media Library JavaScript for Simple WebP Converter
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle conversion in list view
        $(document).on('click', '.jsdev-webp-convert-single', function(e) {
            e.preventDefault();
            const button = $(this);
            const attachmentId = button.data('attachment-id');
            const statusCell = button.closest('td');

            convertImage(attachmentId, button, statusCell);
        });

        // Handle conversion in attachment details modal
        $(document).on('click', '.jsdev-webp-convert-attachment, .jsdev-webp-reconvert-attachment', function(e) {
            e.preventDefault();
            const button = $(this);
            const attachmentId = button.data('attachment-id');
            const resultContainer = button.siblings('.jsdev-webp-conversion-result');

            convertImage(attachmentId, button, resultContainer, true);
        });

        /**
         * Convert image to WebP
         */
        function convertImage(attachmentId, button, resultContainer, isModal = false) {
            // Disable button and show loading
            const originalText = button.text();
            button.prop('disabled', true).text('Converting...');

            // Clear previous results
            resultContainer.empty();

            // Send AJAX request
            $.ajax({
                url: jsdevWebpMedia.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jsdev_webp_convert_single',
                    nonce: jsdevWebpMedia.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        if (isModal) {
                            // Show success message in modal
                            resultContainer.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');

                            // Reload the attachment details to show updated stats
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Update the status cell in list view
                            resultContainer.html('<span style="color: #28a745; font-weight: bold;">✓ Converted</span><br><small style="color: #666;">' + response.data.message + '</small>');
                        }
                    } else {
                        // Show error
                        const errorHtml = isModal
                            ? '<div class="notice notice-error inline"><p>' + (response.data.message || 'Conversion failed.') + '</p></div>'
                            : '<span style="color: #dc3545;">Error: ' + (response.data.message || 'Conversion failed.') + '</span>';

                        resultContainer.html(errorHtml);
                        button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    const errorHtml = isModal
                        ? '<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>'
                        : '<span style="color: #dc3545;">An error occurred. Please try again.</span>';

                    resultContainer.html(errorHtml);
                    button.prop('disabled', false).text(originalText);
                }
            });
        }

        // Handle conversion of all images in a post (post edit screen)
        $(document).on('click', '.jsdev-webp-convert-post-images', function(e) {
            e.preventDefault();
            const button = $(this);
            const postId = button.data('post-id');
            const resultContainer = $('.jsdev-webp-post-conversion-result');
            const postImagesContainer = $('.jsdev-webp-post-images');

            // Get all image IDs from the meta box
            const imageIds = [];
            postImagesContainer.find('.jsdev-webp-image-item').each(function() {
                imageIds.push($(this).data('attachment-id'));
            });

            if (imageIds.length === 0) {
                resultContainer.html('<div class="notice notice-error inline"><p>No images found to convert.</p></div>');
                return;
            }

            // Confirm action
            if (!confirm('Convert ' + imageIds.length + ' image(s) to WebP?')) {
                return;
            }

            // Disable button and show loading
            const originalText = button.text();
            button.prop('disabled', true).text('Converting...');
            resultContainer.empty();

            // Process images one by one
            let processed = 0;
            let successful = 0;
            let failed = 0;

            function processNextImage() {
                if (processed >= imageIds.length) {
                    // All done
                    button.prop('disabled', false).text(originalText);
                    resultContainer.html(
                        '<div class="notice notice-success inline"><p>' +
                        'Completed! ' + successful + ' converted successfully, ' + failed + ' failed.' +
                        '</p></div>'
                    );

                    // Reload page after 2 seconds to show updated status
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                    return;
                }

                const currentId = imageIds[processed];
                processed++;

                // Show progress
                button.text('Converting... (' + processed + '/' + imageIds.length + ')');

                $.ajax({
                    url: jsdevWebpMedia.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'jsdev_webp_convert_single',
                        nonce: jsdevWebpMedia.nonce,
                        attachment_id: currentId
                    },
                    success: function(response) {
                        if (response.success) {
                            successful++;
                        } else {
                            failed++;
                        }
                        processNextImage();
                    },
                    error: function() {
                        failed++;
                        processNextImage();
                    }
                });
            }

            processNextImage();
        });

        // Refresh page after bulk actions complete (if WebP regeneration was part of it)
        $(document).on('click', '#doaction, #doaction2', function() {
            const action = $(this).siblings('select').val();
            // You can add custom bulk actions here if needed
        });
    });

})(jQuery);
