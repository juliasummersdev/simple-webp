/**
 * Gutenberg Block Controls for Simple WebP Converter
 */

(function() {
    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const { BlockControls } = wp.blockEditor;
    const { ToolbarGroup, ToolbarButton } = wp.components;
    const { __ } = wp.i18n;

    /**
     * Add WebP conversion button to Image block toolbar
     */
    const withWebPConversionControl = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            // Only add to Image blocks
            if (props.name !== 'core/image') {
                return <BlockEdit {...props} />;
            }

            const { attributes, setAttributes } = props;
            const { id: attachmentId, url } = attributes;

            // Only show button if image has been selected
            if (!attachmentId) {
                return <BlockEdit {...props} />;
            }

            /**
             * Handle WebP conversion
             */
            const handleConvertToWebP = () => {
                // Show loading state
                wp.data.dispatch('core/notices').createNotice(
                    'info',
                    __('Converting image to WebP...', 'jsdev-simple-webp-converter'),
                    {
                        isDismissible: true,
                        type: 'snackbar'
                    }
                );

                // Send AJAX request
                jQuery.ajax({
                    url: jsdevWebpBlock.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'jsdev_webp_convert_single',
                        nonce: jsdevWebpBlock.nonce,
                        attachment_id: attachmentId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Success - update image URL to WebP
                            const urlParts = url.split('.');
                            const extension = urlParts.pop();

                            // Only replace if it's jpg/jpeg/png
                            if (['jpg', 'jpeg', 'png'].includes(extension.toLowerCase())) {
                                const webpUrl = urlParts.join('.') + '.webp';

                                setAttributes({
                                    url: webpUrl
                                });

                                // Show success notice
                                wp.data.dispatch('core/notices').createNotice(
                                    'success',
                                    response.data.message || __('Image converted to WebP successfully!', 'jsdev-simple-webp-converter'),
                                    {
                                        isDismissible: true,
                                        type: 'snackbar'
                                    }
                                );
                            }
                        } else {
                            // Error
                            wp.data.dispatch('core/notices').createNotice(
                                'error',
                                response.data.message || __('Failed to convert image to WebP.', 'jsdev-simple-webp-converter'),
                                {
                                    isDismissible: true,
                                    type: 'snackbar'
                                }
                            );
                        }
                    },
                    error: function() {
                        // AJAX error
                        wp.data.dispatch('core/notices').createNotice(
                            'error',
                            __('An error occurred while converting the image.', 'jsdev-simple-webp-converter'),
                            {
                                isDismissible: true,
                                type: 'snackbar'
                            }
                        );
                    }
                });
            };

            return (
                <Fragment>
                    <BlockEdit {...props} />
                    <BlockControls>
                        <ToolbarGroup>
                            <ToolbarButton
                                icon="images-alt2"
                                label={__('Convert to WebP', 'jsdev-simple-webp-converter')}
                                onClick={handleConvertToWebP}
                            />
                        </ToolbarGroup>
                    </BlockControls>
                </Fragment>
            );
        };
    }, 'withWebPConversionControl');

    addFilter(
        'editor.BlockEdit',
        'jsdev-simple-webp-converter/with-webp-conversion-control',
        withWebPConversionControl
    );

})();
