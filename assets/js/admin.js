/**
 * Admin JavaScript for Simple WebP Converter
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if we just completed a conversion
        const conversionComplete = sessionStorage.getItem('jsdev_webp_conversion_complete');
        if (conversionComplete) {
            // Remove the flag
            sessionStorage.removeItem('jsdev_webp_conversion_complete');

            // Show success notice at top of page
            const successNotice = $('<div class="notice notice-success is-dismissible" style="margin: 20px 0;">' +
                '<p><strong>WebP Conversion Complete!</strong> Successfully regenerated WebP versions for <strong>' + conversionComplete + '</strong> image(s). Statistics have been updated.</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                '</div>');

            $('.jsdev-webp-admin h1').after(successNotice);

            // Scroll to top to show the notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);

            // Make dismiss button work
            successNotice.on('click', '.notice-dismiss', function(e) {
                e.preventDefault();
                successNotice.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        }

        // Check if we just completed a deletion
        const deletionComplete = sessionStorage.getItem('jsdev_webp_deletion_complete');
        if (deletionComplete) {
            // Remove the flag
            sessionStorage.removeItem('jsdev_webp_deletion_complete');

            // Show success notice at top of page
            const deletionNotice = $('<div class="notice notice-success is-dismissible" style="margin: 20px 0;">' +
                '<p><strong>WebP Deletion Complete!</strong> Successfully deleted <strong>' + deletionComplete + '</strong> WebP file(s) from your uploads directory. Statistics have been updated.</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                '</div>');

            $('.jsdev-webp-admin h1').after(deletionNotice);

            // Scroll to top to show the notice
            $('html, body').animate({
                scrollTop: 0
            }, 300);

            // Make dismiss button work
            deletionNotice.on('click', '.notice-dismiss', function(e) {
                e.preventDefault();
                deletionNotice.fadeOut(200, function() {
                    $(this).remove();
                });
            });
        }

        // Sync quality input and slider
        const qualityInput = $('#jsdev_simple_webp_webp_quality');
        const qualitySlider = $('#jsdev_simple_webp_webp_quality_slider');

        qualityInput.on('input', function() {
            qualitySlider.val($(this).val());
        });

        qualitySlider.on('input', function() {
            qualityInput.val($(this).val());
        });

        // Handle quality change regeneration prompt
        $('#jsdev-webp-quality-regenerate-yes').on('click', function() {
            // Confirm action first
            if (!confirm('This will regenerate WebP versions for all images in your media library with the new quality setting. This may take a while. Continue?')) {
                return;
            }

            // Hide the notice
            $('#jsdev-webp-quality-change-notice').fadeOut();

            // Scroll to bulk actions section
            $('html, body').animate({
                scrollTop: $('#jsdev-webp-regenerate-all').closest('.jsdev-webp-card').offset().top - 50
            }, 500, function() {
                // Trigger the regenerate all button after scroll (skip confirmation since we already confirmed)
                $('#jsdev-webp-regenerate-all').trigger('click', [true]);
            });
        });

        $('#jsdev-webp-quality-regenerate-no').on('click', function() {
            // Just dismiss the notice
            $('#jsdev-webp-quality-change-notice').fadeOut();
        });

        // Regenerate all images
        $('#jsdev-webp-regenerate-all').on('click', function(e, skipConfirm) {
            const button = $(this);
            const progressContainer = $('#jsdev-webp-regenerate-progress');
            const progressFill = $('.jsdev-webp-progress-fill');
            const progressText = $('.jsdev-webp-progress-text');
            const resultContainer = $('#jsdev-webp-regenerate-result');

            // Confirm action (skip if triggered programmatically)
            if (!skipConfirm && !confirm('This will generate WebP versions for all images in your media library. This may take a while. Continue?')) {
                return;
            }

            // Disable button and show progress
            button.prop('disabled', true);
            progressContainer.show();
            resultContainer.empty();

            // Show initial loading state
            progressText.find('.current').text('0');
            progressText.find('.total').text('...');

            // Start processing
            processNextBatch(0, 0);

            function processNextBatch(offset, total) {
                $.ajax({
                    url: jsdevWebp.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'jsdev_webp_regenerate_all',
                        nonce: jsdevWebp.nonce,
                        offset: offset,
                        total: total
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            const processed = data.processed;
                            const totalCount = data.total;
                            const hasMore = data.has_more;

                            // Update progress
                            const percentage = totalCount > 0 ? (processed / totalCount) * 100 : 0;
                            progressFill.css('width', percentage + '%');
                            progressText.find('.current').text(processed);
                            progressText.find('.total').text(totalCount);

                            if (hasMore) {
                                // Process next batch
                                processNextBatch(processed, totalCount);
                            } else {
                                // Complete
                                button.prop('disabled', false);
                                progressContainer.hide();

                                // Show success message with better styling
                                resultContainer.html(
                                    '<div class="notice notice-success is-dismissible" style="padding: 15px; margin-top: 15px;">' +
                                    '<p style="margin: 0; font-size: 14px;"><strong>Conversion Complete!</strong></p>' +
                                    '<p style="margin: 10px 0 0 0;">Successfully regenerated WebP versions for <strong>' + totalCount + '</strong> image(s).</p>' +
                                    '<p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">The page will reload shortly to show updated statistics...</p>' +
                                    '</div>'
                                );

                                // Set flag for completion notice after reload
                                sessionStorage.setItem('jsdev_webp_conversion_complete', totalCount);

                                // Reload page after 2 seconds to show updated stats
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        } else {
                            // Error
                            button.prop('disabled', false);
                            progressContainer.hide();
                            resultContainer.html('<div class="notice notice-error"><p>Error: ' + (response.data.message || 'Unknown error') + '</p></div>');
                        }
                    },
                    error: function() {
                        button.prop('disabled', false);
                        progressContainer.hide();
                        resultContainer.html('<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>');
                    }
                });
            }
        });

        // Delete all WebP images
        $('#jsdev-webp-delete-all').on('click', function() {
            const button = $(this);
            const resultContainer = $('#jsdev-webp-delete-result');

            // Confirm action with strong warning
            if (!confirm('WARNING: This will permanently delete ALL WebP files from your uploads directory.\n\nThis action CANNOT be undone!\n\nAre you sure you want to continue?')) {
                return;
            }

            // Double confirmation
            if (!confirm('Are you absolutely sure? This will delete all WebP files permanently.')) {
                return;
            }

            // Disable button and show loading
            button.prop('disabled', true).text('Deleting...');
            resultContainer.empty();

            // Send AJAX request
            $.ajax({
                url: jsdevWebp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jsdev_webp_delete_all_webp',
                    nonce: jsdevWebp.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Success
                        button.prop('disabled', false).text('Delete All WebP Images');
                        resultContainer.html('<div class="notice notice-success"><p>' + response.data.message + ' The page will reload shortly...</p></div>');

                        // Set flag for deletion completion notice after reload
                        sessionStorage.setItem('jsdev_webp_deletion_complete', response.data.count);

                        // Reload page after 2 seconds to show updated stats
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        // Error
                        button.prop('disabled', false).text('Delete All WebP Images');
                        resultContainer.html('<div class="notice notice-error"><p>Error: ' + (response.data.message || 'Unknown error') + '</p></div>');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Delete All WebP Images');
                    resultContainer.html('<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>');
                }
            });
        });

        // Handle conversion log pagination with AJAX
        $(document).on('click', '#jsdev-webp-log-container .tablenav-pages a', function(e) {
            e.preventDefault();

            const link = $(this);

            // Extract page number - WordPress paginate_links uses text content for page numbers
            let page = 1;

            // Check if it's a numbered link (text is a number)
            const linkText = link.text().trim();
            const pageNum = parseInt(linkText);
            if (!isNaN(pageNum)) {
                page = pageNum;
            } else if (link.hasClass('prev') || linkText.includes('Prev')) {
                // Previous page
                const currentSpan = $('#jsdev-webp-log-container .tablenav-pages .current');
                if (currentSpan.length) {
                    const currentText = currentSpan.text().trim();
                    page = Math.max(1, parseInt(currentText) - 1);
                }
            } else if (link.hasClass('next') || linkText.includes('Next')) {
                // Next page
                const currentSpan = $('#jsdev-webp-log-container .tablenav-pages .current');
                if (currentSpan.length) {
                    const currentText = currentSpan.text().trim();
                    page = parseInt(currentText) + 1;
                }
            }

            // Load the page via AJAX
            loadLogPage(page);
        });

        function loadLogPage(page) {
            const container = $('#jsdev-webp-log-container');
            const logCard = $('#jsdev-webp-log-card');

            // Show loading state
            container.css('opacity', '0.5');

            $.ajax({
                url: jsdevWebp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'jsdev_webp_load_log_page',
                    nonce: jsdevWebp.nonce,
                    page: page
                },
                success: function(response) {
                    if (response.success) {
                        // Update the container with new HTML
                        container.html(response.data.html);
                        container.css('opacity', '1');

                        // Scroll to the log card
                        $('html, body').animate({
                            scrollTop: logCard.offset().top - 50
                        }, 300);
                    } else {
                        container.css('opacity', '1');
                        alert('Error loading log page: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    container.css('opacity', '1');
                    alert('An error occurred while loading the log page.');
                }
            });
        }
    });

})(jQuery);
