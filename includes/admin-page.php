<?php
/**
 * Admin settings page for Simple WebP Converter
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu
 */
add_action('admin_menu', 'jsdev_simple_webp_add_admin_menu');
function jsdev_simple_webp_add_admin_menu() {
    add_options_page(
        'Simple WebP Converter',
        'WebP Converter',
        'manage_options',
        'jsdev-simple-webp-converter',
        'jsdev_simple_webp_render_admin_page'
    );
}

/**
 * Register settings
 */
add_action('admin_init', 'jsdev_simple_webp_register_settings');
function jsdev_simple_webp_register_settings() {
    register_setting('jsdev_webp_settings', 'jsdev_simple_webp_webp_quality', array(
        'type' => 'integer',
        'default' => 80,
        'sanitize_callback' => 'jsdev_simple_webp_sanitize_quality'
    ));
}

/**
 * Sanitize quality value
 */
function jsdev_simple_webp_sanitize_quality($value) {
    $old_value = get_option('jsdev_simple_webp_webp_quality', 80);

    $value = intval($value);
    if ($value < 1) {
        $value = 1;
    }
    if ($value > 100) {
        $value = 100;
    }

    // Store flag if quality changed
    if ($old_value != $value) {
        set_transient('jsdev_simple_webp_quality_changed', array(
            'old' => $old_value,
            'new' => $value
        ), 60); // Store for 60 seconds
    }

    return $value;
}

/**
 * Render admin page
 */
function jsdev_simple_webp_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get current support status
    $support = jsdev_simple_webp_check_webp_support();
    $quality = get_option('jsdev_simple_webp_webp_quality', 80);

    // Check if quality was just changed
    $quality_changed = get_transient('jsdev_simple_webp_quality_changed');
    if ($quality_changed) {
        delete_transient('jsdev_simple_webp_quality_changed');
    }

    // Get statistics
    $stats = jsdev_simple_webp_get_statistics();
    ?>
    <div class="wrap jsdev-webp-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php if ($quality_changed): ?>
            <div id="jsdev-webp-quality-change-notice" class="notice notice-warning is-dismissible" style="position: relative;">
                <p>
                    <strong>WebP quality changed from <?php echo esc_html($quality_changed['old']); ?> to <?php echo esc_html($quality_changed['new']); ?>.</strong>
                    <br>
                    Would you like to regenerate all existing WebP images with the new quality setting?
                </p>
                <p>
                    <button type="button" class="button button-primary" id="jsdev-webp-quality-regenerate-yes">
                        Yes, Regenerate All Images
                    </button>
                    <button type="button" class="button" id="jsdev-webp-quality-regenerate-no">
                        No, Keep Existing Images
                    </button>
                </p>
            </div>
        <?php endif; ?>

        <div class="jsdev-webp-dashboard">
            <!-- Server Support Status -->
            <div class="jsdev-webp-card">
                <h2>Server Support Status</h2>
                <div class="jsdev-webp-support-status <?php echo $support['supported'] ? 'supported' : 'not-supported'; ?>">
                    <div class="status-indicator">
                        <?php if ($support['supported']): ?>
                            <span class="dashicons dashicons-yes-alt"></span>
                            <strong>WebP Supported</strong>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span>
                            <strong>WebP Not Supported</strong>
                        <?php endif; ?>
                    </div>
                    <div class="status-details">
                        <p><strong>Library:</strong> <?php echo esc_html($support['library']); ?></p>
                        <?php if ($support['version']): ?>
                            <p><strong>Version:</strong> <?php echo esc_html($support['version']); ?></p>
                        <?php endif; ?>
                        <p><?php echo esc_html($support['message']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Storage Summary -->
            <div class="jsdev-webp-card">
                <h2>Storage Summary</h2>
                <div class="jsdev-webp-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats['total_conversions']); ?></div>
                        <div class="stat-label">Total Conversions</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($stats['successful_conversions']); ?></div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo size_format($stats['original_size']); ?></div>
                        <div class="stat-label">Total Original Size</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo size_format($stats['webp_size']); ?></div>
                        <div class="stat-label">Total WebP Size</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value <?php echo $stats['space_saved'] > 0 ? 'positive' : ''; ?>">
                            <?php echo size_format($stats['space_saved']); ?>
                        </div>
                        <div class="stat-label">Total Web Size Saved</div>
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <div class="jsdev-webp-card">
                <h2>Settings</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('jsdev_webp_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="jsdev_simple_webp_webp_quality">WebP Quality</label>
                            </th>
                            <td>
                                <input type="number"
                                       id="jsdev_simple_webp_webp_quality"
                                       name="jsdev_simple_webp_webp_quality"
                                       value="<?php echo esc_attr($quality); ?>"
                                       min="1"
                                       max="100"
                                       step="1">
                                <input type="range"
                                       id="jsdev_simple_webp_webp_quality_slider"
                                       min="1"
                                       max="100"
                                       value="<?php echo esc_attr($quality); ?>"
                                       step="1">
                                <p class="description">Quality level for WebP conversion (1-100). Higher values produce better quality but larger files.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Save Settings'); ?>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="jsdev-webp-card">
                <h2>Bulk Actions</h2>

                <div class="jsdev-webp-bulk-actions" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #e5e5e5;">
                    <h3 style="margin-top: 0;">Regenerate All Images</h3>
                    <p>Generate WebP versions for all existing images in your media library.</p>
                    <button type="button" class="button button-primary" id="jsdev-webp-regenerate-all">
                        Regenerate All Images
                    </button>
                    <div id="jsdev-webp-regenerate-progress" style="display: none;">
                        <div class="jsdev-webp-progress-bar">
                            <div class="jsdev-webp-progress-fill" style="width: 0%;"></div>
                        </div>
                        <p class="jsdev-webp-progress-text">Processing: <span class="current">0</span> of <span class="total">0</span></p>
                    </div>
                    <div id="jsdev-webp-regenerate-result"></div>
                </div>

                <div class="jsdev-webp-bulk-actions">
                    <h3 style="margin-top: 0;">Delete All WebP Images</h3>
                    <p style="color: #dc3545;">
                        <strong>Warning:</strong> This will permanently delete all WebP files from your uploads directory. This action cannot be undone.
                    </p>
                    <button type="button" class="button button-secondary" id="jsdev-webp-delete-all">
                        Delete All WebP Images
                    </button>
                    <div id="jsdev-webp-delete-result" style="margin-top: 10px;"></div>
                </div>
            </div>

            <!-- Conversion Log -->
            <div class="jsdev-webp-card" id="jsdev-webp-log-card">
                <h2>Conversion Log</h2>
                <div id="jsdev-webp-log-container">
                    <?php jsdev_simple_webp_render_conversion_log(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get statistics by scanning actual files in uploads directory
 */
function jsdev_simple_webp_get_statistics() {
    $stats = array(
        'total_conversions' => 0,
        'successful_conversions' => 0,
        'original_size' => 0,
        'webp_size' => 0,
        'space_saved' => 0
    );

    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    if (!is_dir($base_dir)) {
        return $stats;
    }

    // Use RecursiveIteratorIterator to find all .webp files
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $webp_count = 0;
        $total_original_size = 0;
        $total_webp_size = 0;

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'webp') {
                $webp_count++;
                $webp_path = $file->getPathname();
                $webp_size = $file->getSize();

                // Try to find the original file (jpg, jpeg, or png)
                $path_without_ext = preg_replace('/\.webp$/i', '', $webp_path);
                $original_path = null;

                // Check for jpg, jpeg, png
                foreach (array('jpg', 'jpeg', 'png') as $ext) {
                    $test_path = $path_without_ext . '.' . $ext;
                    if (file_exists($test_path)) {
                        $original_path = $test_path;
                        break;
                    }
                }

                if ($original_path && file_exists($original_path)) {
                    $original_size = filesize($original_path);
                    $total_original_size += $original_size;
                    $total_webp_size += $webp_size;
                }
            }
        }

        $stats['total_conversions'] = $webp_count;
        $stats['successful_conversions'] = $webp_count;
        $stats['original_size'] = $total_original_size;
        $stats['webp_size'] = $total_webp_size;
        $stats['space_saved'] = $total_original_size - $total_webp_size;

    } catch (Exception $e) {
        // If there's an error, return empty stats
        return $stats;
    }

    return $stats;
}

/**
 * Render conversion log table (initial load)
 */
function jsdev_simple_webp_render_conversion_log() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jsdev_simple_webp_log';

    $per_page = 20;
    $offset = 0;

    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);

    // Get log entries
    $logs = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d
    ", $per_page, $offset));

    ?>
    <div class="jsdev-webp-log-table">
        <?php if (empty($logs)): ?>
            <p>No conversion logs yet.</p>
        <?php else: ?>
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
                        echo paginate_links(array(
                            'base' => '%_%',
                            'format' => '',
                            'prev_text' => '&laquo; Prev',
                            'next_text' => 'Next &raquo;',
                            'total' => $total_pages,
                            'current' => 1
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
