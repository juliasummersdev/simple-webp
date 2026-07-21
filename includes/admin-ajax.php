<?php
/**
 * AJAX handlers for Simple WebP Converter
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for converting a single image
 */
add_action('wp_ajax_jsdev_webp_convert_single', 'jsdev_simple_webp_ajax_convert_single_image');
function jsdev_simple_webp_ajax_convert_single_image() {
    // Check nonce
    check_ajax_referer('jsdev_webp_nonce', 'nonce');

    // Check capabilities
    if (!current_user_can('upload_files')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    // Get attachment ID
    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

    if (!$attachment_id) {
        wp_send_json_error(array('message' => 'Invalid attachment ID.'));
    }

    // Check if attachment exists
    if (!wp_attachment_is_image($attachment_id)) {
        wp_send_json_error(array('message' => 'Attachment is not an image.'));
    }

    // Generate WebP versions
    $results = jsdev_simple_webp_generate_all_sizes_webp($attachment_id);

    if (empty($results)) {
        wp_send_json_error(array('message' => 'Failed to convert image.'));
    }

    // Count successes
    $successful = 0;
    $failed = 0;
    foreach ($results as $result) {
        if ($result['success']) {
            $successful++;
        } else {
            $failed++;
        }
    }

    wp_send_json_success(array(
        'message' => sprintf('Converted %d image size(s) successfully. %d failed.', $successful, $failed),
        'results' => $results
    ));
}

/**
 * AJAX handler for regenerating all images
 */
add_action('wp_ajax_jsdev_webp_regenerate_all', 'jsdev_simple_webp_ajax_regenerate_all');
function jsdev_simple_webp_ajax_regenerate_all() {
    // Check nonce
    check_ajax_referer('jsdev_webp_nonce', 'nonce');

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    // Get batch parameters
    $batch_size = 5; // Process 5 images at a time
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    // Get all image attachments
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'post_status' => 'inherit',
        'posts_per_page' => $batch_size,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids'
    );

    $attachments = get_posts($args);

    // Get total count on first request
    if ($offset === 0) {
        $count_args = $args;
        $count_args['posts_per_page'] = -1;
        $count_args['offset'] = 0;
        $all_attachments = get_posts($count_args);
        $total = count($all_attachments);
    } else {
        $total = isset($_POST['total']) ? intval($_POST['total']) : 0;
    }

    // Process batch
    $batch_results = array();
    foreach ($attachments as $attachment_id) {
        $results = jsdev_simple_webp_generate_all_sizes_webp($attachment_id);
        $batch_results[$attachment_id] = $results;
    }

    $processed = $offset + count($attachments);
    $has_more = $processed < $total;

    wp_send_json_success(array(
        'processed' => $processed,
        'total' => $total,
        'has_more' => $has_more,
        'batch_results' => $batch_results
    ));
}

/**
 * AJAX handler for deleting all WebP images
 */
add_action('wp_ajax_jsdev_webp_delete_all_webp', 'jsdev_simple_webp_ajax_delete_all_webp');
function jsdev_simple_webp_ajax_delete_all_webp() {
    // Check nonce
    check_ajax_referer('jsdev_webp_nonce', 'nonce');

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $deleted_count = jsdev_simple_webp_delete_all_webp_images();

    wp_send_json_success(array(
        'message' => sprintf('Deleted %d WebP file(s).', $deleted_count),
        'count' => $deleted_count
    ));
}

/**
 * Delete all WebP images from uploads directory
 *
 * @return int Number of files deleted
 */
function jsdev_simple_webp_delete_all_webp_images() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    $deleted_count = 0;

    // Use RecursiveIteratorIterator to find all .webp files
    if (is_dir($base_dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                if (@unlink($file->getPathname())) {
                    $deleted_count++;
                }
            }
        }
    }

    return $deleted_count;
}

/**
 * Regenerate all images (non-AJAX version for WP-CLI or manual triggering)
 */
function jsdev_simple_webp_regenerate_all_images() {
    $args = array(
        'post_type' => 'attachment',
        'post_mime_type' => array('image/jpeg', 'image/png'),
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );

    $attachments = get_posts($args);
    $results = array();

    foreach ($attachments as $attachment_id) {
        $results[$attachment_id] = jsdev_simple_webp_generate_all_sizes_webp($attachment_id);
    }

    return $results;
}

/**
 * AJAX handler for loading conversion log page
 */
add_action('wp_ajax_jsdev_webp_load_log_page', 'jsdev_simple_webp_ajax_load_log_page');
function jsdev_simple_webp_ajax_load_log_page() {
    // Check nonce
    check_ajax_referer('jsdev_webp_nonce', 'nonce');

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'jsdev_simple_webp_log';

    // Get page number
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);

    // Get log entries
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d
    ", $per_page, $offset));

    // Build HTML
    ob_start();
    if (empty($logs)) {
        echo '<p>No conversion logs yet.</p>';
    } else {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Attachment ID</th>
                    <th>Filename</th>
                    <th>Size</th>
                    <th>Original</th>
                    <th>WebP</th>
                    <th>Saved</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('post.php?post=' . $log->attachment_id . '&action=edit'); ?>">
                                #<?php echo esc_html($log->attachment_id); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($log->filename); ?></td>
                        <td><?php echo esc_html($log->size_name); ?></td>
                        <td><?php echo size_format($log->original_size); ?></td>
                        <td><?php echo size_format($log->webp_size); ?></td>
                        <td>
                            <?php
                            $saved = $log->original_size - $log->webp_size;
                            $saved_percent = $log->original_size > 0 ? round(($saved / $log->original_size) * 100, 1) : 0;
                            echo size_format($saved) . ' (' . $saved_percent . '%)';
                            ?>
                        </td>
                        <td>
                            <?php if ($log->success): ?>
                                <span class="jsdev-webp-status-success">
                                    <span class="dashicons dashicons-yes"></span> Success
                                </span>
                            <?php else: ?>
                                <span class="jsdev-webp-status-error" title="<?php echo esc_attr($log->error_message); ?>">
                                    <span class="dashicons dashicons-no"></span> Failed
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => '%_%',
                        'format' => '',
                        'prev_text' => '&laquo; Prev',
                        'next_text' => 'Next &raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'current_page' => $page,
        'total_pages' => $total_pages
    ));
}
