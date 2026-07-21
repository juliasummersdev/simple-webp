<?php
/**
 * Media Library Integration for Simple WebP Converter
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add WebP Status column to Media Library list view
 */
add_filter('manage_media_columns', 'jsdev_simple_webp_add_media_column');
function jsdev_simple_webp_add_media_column($columns) {
    $columns['webp_status'] = 'WebP Status';
    return $columns;
}

/**
 * Display WebP Status in Media Library list view
 */
add_action('manage_media_custom_column', 'jsdev_simple_webp_display_media_column', 10, 2);
function jsdev_simple_webp_display_media_column($column_name, $post_id) {
    if ($column_name !== 'webp_status') {
        return;
    }

    // Only show for images
    if (!wp_attachment_is_image($post_id)) {
        echo '<span style="color: #999;">N/A</span>';
        return;
    }

    // Check mime type
    $mime_type = get_post_mime_type($post_id);
    if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
        echo '<span style="color: #999;">N/A</span>';
        return;
    }

    // Check if WebP version exists
    $metadata = wp_get_attachment_metadata($post_id);
    if (!$metadata || !isset($metadata['file'])) {
        echo '<span style="color: #999;">No metadata</span>';
        return;
    }

    $upload_dir = wp_upload_dir();
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    $webp_file = pathinfo($original_file, PATHINFO_DIRNAME) . '/' . pathinfo($original_file, PATHINFO_FILENAME) . '.webp';

    if (file_exists($webp_file)) {
        // Calculate savings
        $original_size = filesize($original_file);
        $webp_size = filesize($webp_file);
        $saved = $original_size - $webp_size;
        $saved_percent = $original_size > 0 ? round(($saved / $original_size) * 100, 1) : 0;

        echo '<span style="color: #28a745; font-weight: bold;">✓ Converted</span><br>';
        echo '<small style="color: #666;">Saved: ' . size_format($saved) . ' (' . $saved_percent . '%)</small>';
    } else {
        echo '<span style="color: #dc3545;">✗ Not converted</span><br>';
        echo '<button type="button" class="button button-small jsdev-webp-convert-single" data-attachment-id="' . esc_attr($post_id) . '" style="margin-top: 5px;">Convert Now</button>';
    }
}

/**
 * Add "Convert to WebP" button to attachment details modal
 */
add_filter('attachment_fields_to_edit', 'jsdev_simple_webp_add_attachment_field', 10, 2);
function jsdev_simple_webp_add_attachment_field($form_fields, $post) {
    // Only show for images
    if (!wp_attachment_is_image($post->ID)) {
        return $form_fields;
    }

    // Check mime type
    $mime_type = get_post_mime_type($post->ID);
    if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
        return $form_fields;
    }

    // Check WebP support
    $support = jsdev_simple_webp_check_webp_support();
    if (!$support['supported']) {
        $form_fields['webp_conversion'] = array(
            'label' => 'WebP Conversion',
            'input' => 'html',
            'html' => '<p style="color: #dc3545;">WebP not supported on this server.</p>'
        );
        return $form_fields;
    }

    // Check if WebP exists
    $metadata = wp_get_attachment_metadata($post->ID);
    if (!$metadata || !isset($metadata['file'])) {
        return $form_fields;
    }

    $upload_dir = wp_upload_dir();
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    $webp_file = pathinfo($original_file, PATHINFO_DIRNAME) . '/' . pathinfo($original_file, PATHINFO_FILENAME) . '.webp';

    $html = '<div class="jsdev-webp-attachment-field">';

    if (file_exists($webp_file)) {
        // Show conversion status - ONLY for full/original size
        $original_size = filesize($original_file);
        $webp_size = filesize($webp_file);
        $saved = $original_size - $webp_size;
        $saved_percent = $original_size > 0 ? round(($saved / $original_size) * 100, 1) : 0;

        // Custom size formatting with 2 decimal places
        $format_size = function($bytes) {
            if ($bytes < 1024) {
                return $bytes . ' B';
            } elseif ($bytes < 1048576) {
                return round($bytes / 1024, 2) . ' KB';
            } elseif ($bytes < 1073741824) {
                return round($bytes / 1048576, 2) . ' MB';
            } else {
                return round($bytes / 1073741824, 2) . ' GB';
            }
        };

        $html .= '<p style="color: #28a745; font-weight: bold; margin: 0 0 10px 0;">✓ WebP version exists</p>';
        $html .= '<p style="margin: 0 0 10px 0;"><strong>Original:</strong> ' . $format_size($original_size) . '<br>';
        $html .= '<strong>WebP:</strong> ' . $format_size($webp_size) . '<br>';
        $html .= '<strong>Saved:</strong> ' . $format_size($saved) . ' (' . $saved_percent . '%)</p>';
        $html .= '<p style="margin: 0 0 10px 0; font-size: 11px; color: #666;"><em>Showing full-size image only</em></p>';
        $html .= '<button type="button" class="button jsdev-webp-reconvert-attachment" data-attachment-id="' . esc_attr($post->ID) . '">Regenerate WebP</button>';
    } else {
        $html .= '<p style="color: #dc3545; margin: 0 0 10px 0;">✗ No WebP version found</p>';
        $html .= '<button type="button" class="button button-primary jsdev-webp-convert-attachment" data-attachment-id="' . esc_attr($post->ID) . '">Convert to WebP</button>';
    }

    $html .= '<div class="jsdev-webp-conversion-result" style="margin-top: 10px;"></div>';
    $html .= '</div>';

    $form_fields['webp_conversion'] = array(
        'label' => 'WebP Conversion',
        'input' => 'html',
        'html' => $html
    );

    return $form_fields;
}

/**
 * Enqueue media library scripts
 */
add_action('admin_enqueue_scripts', 'jsdev_simple_webp_enqueue_media_scripts');
function jsdev_simple_webp_enqueue_media_scripts($hook) {
    // Only load on media library pages
    if (!in_array($hook, array('upload.php', 'post.php', 'post-new.php'))) {
        return;
    }

    wp_enqueue_script(
        'jsdev-webp-media-library',
        JSDEV_WEBP_PLUGIN_URL . 'assets/js/media-library.js',
        array('jquery'),
        JSDEV_WEBP_VERSION,
        true
    );

    wp_localize_script('jsdev-webp-media-library', 'jsdevWebpMedia', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jsdev_webp_nonce')
    ));
}

/**
 * Make WebP status column sortable (optional)
 */
add_filter('manage_upload_sortable_columns', 'jsdev_simple_webp_sortable_columns');
function jsdev_simple_webp_sortable_columns($columns) {
    $columns['webp_status'] = 'webp_status';
    return $columns;
}

/**
 * Add WebP conversion meta box to post edit screen
 */
add_action('add_meta_boxes', 'jsdev_simple_webp_add_post_meta_box');
function jsdev_simple_webp_add_post_meta_box() {
    $post_types = get_post_types(array('public' => true), 'names');

    foreach ($post_types as $post_type) {
        add_meta_box(
            'jsdev_webp_conversion',
            'WebP Conversion',
            'jsdev_simple_webp_render_post_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}

/**
 * Render WebP conversion meta box content
 */
function jsdev_simple_webp_render_post_meta_box($post) {
    // Check WebP support
    $support = jsdev_simple_webp_check_webp_support();

    if (!$support['supported']) {
        echo '<p style="color: #dc3545;">WebP conversion not supported on this server.</p>';
        echo '<p><small>' . esc_html($support['message']) . '</small></p>';
        return;
    }

    $all_image_ids = array();

    // Check if we're editing an attachment itself
    if ($post->post_type === 'attachment') {
        // Check if this attachment is an image
        if (wp_attachment_is_image($post->ID)) {
            $mime_type = get_post_mime_type($post->ID);
            if (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                $all_image_ids[] = $post->ID;
            }
        }
    } else {
        // Get featured image
        $thumbnail_id = get_post_thumbnail_id($post->ID);

        // Get all images attached to this post
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('image/jpeg', 'image/png'),
            'post_parent' => $post->ID,
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        $attached_images = get_posts($args);

        // Also check content for images
        $content_images = array();
        if (isset($post->post_content) && preg_match_all('/wp-image-(\d+)/i', $post->post_content, $matches)) {
            $content_image_ids = array_unique($matches[1]);
            foreach ($content_image_ids as $img_id) {
                $img_id = intval($img_id);
                if (wp_attachment_is_image($img_id)) {
                    $mime_type = get_post_mime_type($img_id);
                    if (in_array($mime_type, array('image/jpeg', 'image/png'))) {
                        $content_images[] = $img_id;
                    }
                }
            }
        }

        // Merge and deduplicate
        if ($thumbnail_id) {
            $all_image_ids[] = $thumbnail_id;
        }

        foreach ($attached_images as $img) {
            $all_image_ids[] = $img->ID;
        }

        $all_image_ids = array_merge($all_image_ids, $content_images);
        $all_image_ids = array_unique($all_image_ids);
    }

    if (empty($all_image_ids)) {
        if ($post->post_type === 'attachment') {
            echo '<p>This attachment is not a JPG or PNG image.</p>';
        } else {
            echo '<p>No JPG or PNG images found in this post.</p>';
            echo '<p><small>Add a featured image or insert images into the content to convert them to WebP.</small></p>';
        }
        return;
    }

    // Display images with conversion status
    echo '<div class="jsdev-webp-post-images">';
    echo '<p><strong>' . count($all_image_ids) . ' image(s) found:</strong></p>';

    $converted_count = 0;
    $not_converted_count = 0;

    foreach ($all_image_ids as $image_id) {
        $metadata = wp_get_attachment_metadata($image_id);
        if (!$metadata || !isset($metadata['file'])) {
            continue;
        }

        $upload_dir = wp_upload_dir();
        $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
        $webp_file = pathinfo($original_file, PATHINFO_DIRNAME) . '/' . pathinfo($original_file, PATHINFO_FILENAME) . '.webp';

        $image_title = get_the_title($image_id);
        if (empty($image_title)) {
            $image_title = basename($metadata['file']);
        }

        $is_featured = ($image_id == $thumbnail_id);
        $badge = $is_featured ? '<span style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 5px;">Featured</span>' : '';

        echo '<div class="jsdev-webp-image-item" style="padding: 10px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #ddd;" data-attachment-id="' . esc_attr($image_id) . '">';
        echo '<div style="margin-bottom: 5px;"><strong>' . esc_html($image_title) . '</strong>' . $badge . '</div>';

        if (file_exists($webp_file)) {
            $converted_count++;
            echo '<div style="color: #28a745; font-size: 12px;">✓ WebP exists</div>';
        } else {
            $not_converted_count++;
            echo '<div style="color: #dc3545; font-size: 12px;">✗ No WebP version</div>';
        }

        echo '</div>';
    }

    echo '</div>';

    echo '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
    echo '<p><strong>Summary:</strong><br>';
    echo '<span style="color: #28a745;">✓ ' . $converted_count . ' converted</span><br>';
    echo '<span style="color: #dc3545;">✗ ' . $not_converted_count . ' not converted</span></p>';

    if ($not_converted_count > 0) {
        echo '<button type="button" class="button button-primary button-large jsdev-webp-convert-post-images" data-post-id="' . esc_attr($post->ID) . '" style="width: 100%;">Convert All to WebP</button>';
    } else {
        echo '<button type="button" class="button button-large jsdev-webp-convert-post-images" data-post-id="' . esc_attr($post->ID) . '" style="width: 100%;">Regenerate All WebP</button>';
    }

    echo '<div class="jsdev-webp-post-conversion-result" style="margin-top: 10px;"></div>';
    echo '</div>';

    wp_nonce_field('jsdev_webp_post_meta_box', 'jsdev_webp_post_meta_box_nonce');
}
