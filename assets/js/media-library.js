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
                            // Get ONLY the full-size image file sizes (not all sizes combined)
                            let fullOriginalSize = 0;
                            let fullWebPSize = 0;

                            if (response.data.results && response.data.results.full) {
                                const fullResult = response.data.results.full;
                                if (fullResult.success) {
                                    fullOriginalSize = parseInt(fullResult.original_size) || 0;
                                    fullWebPSize = parseInt(fullResult.webp_size) || 0;
                                }
                            }

                            // Format file sizes
                            const formatSize = function(bytes) {
                                if (bytes === 0) return '0 B';
                                const k = 1024;
                                const sizes = ['B', 'KB', 'MB', 'GB'];
                                const i = Math.floor(Math.log(bytes) / Math.log(k));
                                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                            };

                            // Calculate savings
                            const saved = fullOriginalSize - fullWebPSize;
                            const savedPercent = fullOriginalSize > 0 ? ((saved / fullOriginalSize) * 100).toFixed(1) : 0;

                            // Show simple success message
                            let successHtml = '<div class="notice notice-success inline"><p><strong>Success!</strong> ' + response.data.message + '</p></div>';

                            resultContainer.html(successHtml);

                            // Update button state
                            button.prop('disabled', false).text('Regenerate WebP');

                            // Update the static display above the button with new file sizes (full-size only)
                            if (fullOriginalSize > 0) {
                                // Find the paragraph with file sizes (it's the second <p> in the parent div)
                                const parentDiv = button.closest('.jsdev-webp-attachment-field');
                                const sizeParagraph = parentDiv.find('p').eq(1);

                                if (sizeParagraph.length) {
                                    sizeParagraph.html(
                                        '<strong>Original:</strong> ' + formatSize(fullOriginalSize) + '<br>' +
                                        '<strong>WebP:</strong> ' + formatSize(fullWebPSize) + '<br>' +
                                        '<strong>Saved:</strong> ' + formatSize(saved) + ' (' + savedPercent + '%)'
                                    );
                                }
                            }
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

                    // Update the meta box to show conversion status for each image
                    postImagesContainer.find('.jsdev-webp-image-item').each(function() {
                        const item = $(this);
                        const statusSpan = item.find('.jsdev-webp-image-status');
                        statusSpan.html('<span style="color: #28a745; font-weight: bold;">✓ Converted</span>');
                    });

                    resultContainer.html(
                        '<div class="notice notice-success inline"><p>' +
                        'Completed! ' + successful + ' converted successfully' +
                        (failed > 0 ? ', ' + failed + ' failed' : '') + '.' +
                        '</p></div>'
                    );
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

        // Add WebP conversion to WordPress Media Library Modal
        if (typeof wp !== 'undefined' && wp.media) {
            wp.media.view.Attachment.Details.TwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend({
                template: function(view) {
                    const template = wp.media.template('attachment-details-two-column');
                    const output = template(view);

                    // Only add button for JPG/PNG images
                    if (view.type === 'image' && view.subtype && ['jpeg', 'png'].includes(view.subtype)) {
                        const $output = $(output);
                        const $detailsDiv = $output.find('.attachment-info');

                        if ($detailsDiv.length) {
                            const convertButton = $('<button type="button" class="button button-secondary jsdev-webp-modal-convert" data-attachment-id="' + view.id + '" style="margin-top: 10px; width: 100%;">Convert to WebP</button>');
                            const resultDiv = $('<div class="jsdev-webp-modal-result" style="margin-top: 10px;"></div>');

                            $detailsDiv.append(convertButton);
                            $detailsDiv.append(resultDiv);

                            return $output.prop('outerHTML');
                        }
                    }

                    return output;
                }
            });

            // Handle conversion in media modal
            $(document).on('click', '.jsdev-webp-modal-convert', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const button = $(this);
                const attachmentId = button.data('attachment-id');
                const resultContainer = button.siblings('.jsdev-webp-modal-result');
                const originalText = button.text();

                // Disable button and show loading
                button.prop('disabled', true).text('Converting...');
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
                            // Show simple success message
                            resultContainer.html('<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724; margin-top: 10px;"><strong>Success!</strong> ' + response.data.message + '</div>');
                            button.prop('disabled', false).text('Reconvert to WebP');
                        } else {
                            // Show error
                            resultContainer.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin-top: 10px;">Error: ' + (response.data.message || 'Conversion failed.') + '</div>');
                            button.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function() {
                        resultContainer.html('<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; margin-top: 10px;">An error occurred. Please try again.</div>');
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
        }
    });

})(jQuery);
