<?php
/**
 * Plugin Name: Simple WebP
 * Plugin URI: https://juliasummers.dev
 * Description: Automatically converts uploaded images (JPG/PNG) to WebP and serves WebP in place of JPG/PNG throughout your site.
 * Version: 0.1
 * Author: Julia Summers
 * Author URI: https://juliasummers.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jsdev-simple-webp
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JSDEV_WEBP_VERSION', '1.0.5');
define('JSDEV_WEBP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JSDEV_WEBP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JSDEV_WEBP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if the server supports WebP conversion
 *
 * @return array Support information including status, library, and version
 */
function jsdev_simple_webp_check_webp_support() {
    $support = array(
        'supported' => false,
        'library' => 'none',
        'version' => '',
        'message' => ''
    );

    // Check GD support
    if (extension_loaded('gd') && function_exists('imagewebp')) {
        $gd_info = gd_info();
        if (isset($gd_info['WebP Support']) && $gd_info['WebP Support']) {
            $support['supported'] = true;
            $support['library'] = 'GD';
            $support['version'] = $gd_info['GD Version'];
            $support['message'] = sprintf('GD library version %s with WebP support detected.', $gd_info['GD Version']);
            return $support;
        }
    }

    // Check Imagick support
    if (extension_loaded('imagick') && class_exists('Imagick')) {
        $imagick = new Imagick();
        $formats = $imagick->queryFormats('WEBP');
        if (!empty($formats)) {
            $imagick_version = $imagick->getVersion();
            $support['supported'] = true;
            $support['library'] = 'Imagick';
            $support['version'] = $imagick_version['versionString'];
            $support['message'] = sprintf('Imagick version %s with WebP support detected.', $imagick_version['versionString']);
            return $support;
        }
    }

    $support['message'] = 'No WebP support detected. Please contact your hosting provider to enable GD with WebP support or install the Imagick extension with WebP support.';
    return $support;
}

/**
 * Convert a single image file to WebP format
 *
 * @param string $file_path The full path to the source image file
 * @param int $quality WebP quality (1-100)
 * @return array Result array with success status, webp_path, and error message if any
 */
function jsdev_simple_webp_convert_image_to_webp($file_path, $quality = 80) {
    $result = array(
        'success' => false,
        'webp_path' => '',
        'error' => '',
        'original_size' => 0,
        'webp_size' => 0
    );

    // Check if file exists
    if (!file_exists($file_path)) {
        $result['error'] = 'Source file does not exist.';
        return $result;
    }

    // Get file info
    $file_info = pathinfo($file_path);
    $extension = strtolower($file_info['extension']);

    // Check if file is JPG or PNG
    $allowed_types = apply_filters('jsdev_simple_webp_allowed_mime_types', array('jpg', 'jpeg', 'png'));
    if (!in_array($extension, $allowed_types)) {
        $result['error'] = 'File type not supported for conversion.';
        return $result;
    }

    // Set output WebP path
    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

    // Fire before conversion action
    do_action('jsdev_simple_webp_before_webp_conversion', $file_path, $webp_path, $quality);

    // Check WebP support
    $support = jsdev_simple_webp_check_webp_support();
    if (!$support['supported']) {
        $result['error'] = 'WebP conversion not supported on this server.';
        return $result;
    }

    // Get original file size
    $result['original_size'] = filesize($file_path);

    // Convert using available library
    if ($support['library'] === 'GD') {
        $conversion_result = jsdev_simple_webp_convert_with_gd($file_path, $webp_path, $quality, $extension);
    } else {
        $conversion_result = jsdev_simple_webp_convert_with_imagick($file_path, $webp_path, $quality);
    }

    if ($conversion_result) {
        $result['success'] = true;
        $result['webp_path'] = $webp_path;
        $result['webp_size'] = file_exists($webp_path) ? filesize($webp_path) : 0;
    } else {
        $result['error'] = 'Failed to convert image to WebP.';
    }

    // Fire after conversion action
    do_action('jsdev_simple_webp_after_webp_conversion', $file_path, $webp_path, $result['success'], $result);

    return $result;
}

/**
 * Convert image using GD library
 */
function jsdev_simple_webp_convert_with_gd($file_path, $webp_path, $quality, $extension) {
    $image = null;

    switch ($extension) {
        case 'jpeg':
        case 'jpg':
            $image = @imagecreatefromjpeg($file_path);
            break;
        case 'png':
            $image = @imagecreatefrompng($file_path);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
    }

    if (!$image) {
        return false;
    }

    $result = imagewebp($image, $webp_path, $quality);
    imagedestroy($image);

    return $result;
}

/**
 * Convert image using Imagick library
 */
function jsdev_simple_webp_convert_with_imagick($file_path, $webp_path, $quality) {
    try {
        $imagick = new Imagick($file_path);
        $imagick->setImageFormat('webp');
        $imagick->setImageCompressionQuality($quality);
        $result = $imagick->writeImage($webp_path);
        $imagick->clear();
        $imagick->destroy();
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate WebP versions for all registered image sizes of an attachment
 *
 * @param int $attachment_id The attachment ID
 * @return array Array of conversion results
 */
function jsdev_simple_webp_generate_all_sizes_webp($attachment_id) {
    $results = array();

    // Check if we should skip this attachment
    if (apply_filters('jsdev_simple_webp_skip_webp_for_attachment', false, $attachment_id)) {
        return $results;
    }

    // Get attachment metadata
    $metadata = wp_get_attachment_metadata($attachment_id);
    if (!$metadata || !isset($metadata['file'])) {
        return $results;
    }

    // Get quality setting
    $quality = get_option('jsdev_simple_webp_webp_quality', 80);
    $quality = apply_filters('jsdev_simple_webp_webp_quality', $quality);

    $upload_dir = wp_upload_dir();

    // Convert original/full size
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    if (file_exists($original_file)) {
        $result = jsdev_simple_webp_convert_image_to_webp($original_file, $quality);
        $results['full'] = $result;
        jsdev_simple_webp_log_conversion($attachment_id, 'full', basename($original_file), $result);
    }

    // Convert all other sizes
    if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
        $base_dir = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/';

        foreach ($metadata['sizes'] as $size_name => $size_data) {
            if (!isset($size_data['file'])) {
                continue;
            }

            $file_path = $base_dir . $size_data['file'];
            if (file_exists($file_path)) {
                $result = jsdev_simple_webp_convert_image_to_webp($file_path, $quality);
                $results[$size_name] = $result;
                jsdev_simple_webp_log_conversion($attachment_id, $size_name, $size_data['file'], $result);
            }
        }
    }

    return $results;
}

/**
 * Hook into WordPress upload process to generate WebP on upload
 */
add_filter('wp_generate_attachment_metadata', 'jsdev_simple_webp_generate_webp_on_upload', 20, 2);
function jsdev_simple_webp_generate_webp_on_upload($metadata, $attachment_id) {
    // Only process if WebP support is available
    $support = jsdev_simple_webp_check_webp_support();
    if (!$support['supported']) {
        return $metadata;
    }

    // Check if this is an image
    $mime_type = get_post_mime_type($attachment_id);
    if (!in_array($mime_type, array('image/jpeg', 'image/png'))) {
        return $metadata;
    }

    // Generate WebP versions
    jsdev_simple_webp_generate_all_sizes_webp($attachment_id);

    return $metadata;
}

/**
 * Get WebP URL if the WebP file exists, otherwise return original URL
 *
 * @param string $image_url The original image URL
 * @return string WebP URL or original URL
 */
function jsdev_simple_webp_get_webp_url_if_exists($image_url) {
    // Get path info
    $path_info = pathinfo($image_url);
    $extension = strtolower($path_info['extension']);

    // Only process JPG/PNG
    if (!in_array($extension, array('jpg', 'jpeg', 'png'))) {
        return $image_url;
    }

    // Create WebP URL
    $webp_url = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';

    // Convert URL to file path
    $upload_dir = wp_upload_dir();
    $webp_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_url);

    // Check if WebP file exists
    if (file_exists($webp_path)) {
        return $webp_url;
    }

    return $image_url;
}

/**
 * Replace images in post content with WebP versions
 */
add_filter('the_content', 'jsdev_simple_webp_replace_images_in_content', 10, 1);
function jsdev_simple_webp_replace_images_in_content($content) {
    // Get WordPress uploads directory
    $upload_dir = wp_upload_dir();
    $upload_baseurl = $upload_dir['baseurl'];
    $upload_basepath = $upload_dir['basedir'];

    // Replace <img> src attributes
    $content = preg_replace_callback('/<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png))["\'][^>]*>/i', function ($matches) use ($upload_baseurl, $upload_basepath) {
        $original_url = $matches[1];
        $webp_url = jsdev_simple_webp_get_webp_url_if_exists($original_url);

        return $webp_url !== $original_url ? str_replace($original_url, $webp_url, $matches[0]) : $matches[0];
    }, $content);

    // Replace srcset URLs
    $content = preg_replace_callback('/srcset=["\']([^"\']+)["\']/i', function ($matches) use ($upload_baseurl, $upload_basepath) {
        $srcset = $matches[1];

        $new_srcset = preg_replace_callback('/([^,\s]+)\.(jpg|jpeg|png)(\s\d+w)?/', function ($src_match) use ($upload_baseurl, $upload_basepath) {
            $original_url = $src_match[1] . '.' . $src_match[2];
            $webp_url = jsdev_simple_webp_get_webp_url_if_exists($original_url);

            return $webp_url . ($src_match[3] ?? '');
        }, $srcset);

        return 'srcset="' . esc_attr($new_srcset) . '"';
    }, $content);

    return $content;
}

/**
 * Replace featured images with WebP versions
 */
add_filter('post_thumbnail_html', 'jsdev_simple_webp_replace_featured_image', 10, 5);
function jsdev_simple_webp_replace_featured_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
    return jsdev_simple_webp_replace_images_in_content($html);
}

/**
 * Delete WebP files when attachment is deleted
 */
add_action('delete_attachment', 'jsdev_simple_webp_delete_webp_for_attachment');
function jsdev_simple_webp_delete_webp_for_attachment($attachment_id) {
    $metadata = wp_get_attachment_metadata($attachment_id);

    if (!$metadata || !isset($metadata['file'])) {
        return;
    }

    $upload_dir = wp_upload_dir();

    // Delete WebP version of original
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    $original_webp = pathinfo($original_file, PATHINFO_DIRNAME) . '/' . pathinfo($original_file, PATHINFO_FILENAME) . '.webp';

    if (file_exists($original_webp)) {
        @unlink($original_webp);
    }

    // Delete WebP versions of all sizes
    if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
        $base_dir = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/';

        foreach ($metadata['sizes'] as $size_data) {
            if (!isset($size_data['file'])) {
                continue;
            }

            $file_path = $base_dir . $size_data['file'];
            $webp_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '.webp';

            if (file_exists($webp_path)) {
                @unlink($webp_path);
            }
        }
    }
}

/**
 * Log conversion event
 */
function jsdev_simple_webp_log_conversion($attachment_id, $size, $filename, $result) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jsdev_simple_webp_log';

    $wpdb->insert(
        $table_name,
        array(
            'attachment_id' => $attachment_id,
            'size_name' => $size,
            'filename' => $filename,
            'original_size' => $result['original_size'],
            'webp_size' => $result['webp_size'],
            'success' => $result['success'] ? 1 : 0,
            'error_message' => $result['error'],
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
    );
}

/**
 * Create log table on plugin activation
 */
register_activation_hook(__FILE__, 'jsdev_simple_webp_activate');
function jsdev_simple_webp_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'jsdev_simple_webp_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        attachment_id bigint(20) NOT NULL,
        size_name varchar(50) NOT NULL,
        filename varchar(255) NOT NULL,
        original_size bigint(20) DEFAULT 0,
        webp_size bigint(20) DEFAULT 0,
        success tinyint(1) DEFAULT 0,
        error_message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY attachment_id (attachment_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Set default quality
    if (!get_option('jsdev_simple_webp_webp_quality')) {
        add_option('jsdev_simple_webp_webp_quality', 80);
    }
}

/**
 * Add Settings link to plugin action links on plugins.php page
 */
add_filter('plugin_action_links_' . JSDEV_WEBP_PLUGIN_BASENAME, 'jsdev_simple_webp_add_action_links');
function jsdev_simple_webp_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('tools.php?page=jsdev-simple-webp-converter') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Include admin files
if (is_admin()) {
    require_once JSDEV_WEBP_PLUGIN_DIR . 'includes/admin-page.php';
    require_once JSDEV_WEBP_PLUGIN_DIR . 'includes/admin-ajax.php';
    require_once JSDEV_WEBP_PLUGIN_DIR . 'includes/admin-scripts.php';
    require_once JSDEV_WEBP_PLUGIN_DIR . 'includes/media-library.php';
}
