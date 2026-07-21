<?php
// Replace JPG and PNG with WEBP in content
add_filter('the_content', 'js_replace_jpg_with_webp');
//add_filter('wp_footer', 'js_replace_images_with_webp', 99);
add_filter('widget_text', 'js_replace_images_with_webp');
function js_replace_jpg_with_webp($content)
{
    // Get WordPress uploads directory
    $upload_dir = wp_upload_dir();
    $upload_baseurl = $upload_dir['baseurl'];
    $upload_basepath = $upload_dir['basedir'];
    
    // Replace <img> src attributes
    $content = preg_replace_callback('/<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png))["\'][^>]*>/i', function ($matches) use ($upload_baseurl, $upload_basepath) {
        $original_url = $matches[1];
        $webp_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_url);
        
        // Convert URL to file path
        $original_path = str_replace($upload_baseurl, $upload_basepath, $original_url);
        $webp_path = str_replace($upload_baseurl, $upload_basepath, $webp_url);
        
        return file_exists($webp_path) ? str_replace($original_url, $webp_url, $matches[0]) : $matches[0];
    }, $content);
    
    // Replace srcset URLs dynamically
    $content = preg_replace_callback('/srcset=["\']([^"\']+)["\']/i', function ($matches) use ($upload_baseurl, $upload_basepath) {
        $srcset = $matches[1];
        
        // Replace each jpg/jpeg/png with webp if available
        $new_srcset = preg_replace_callback('/([^,\s]+)\.(jpg|jpeg|png)(\s\d+w)?/', function ($src_match) use ($upload_baseurl, $upload_basepath) {
            $original_url = $src_match[1] . '.' . $src_match[2];
            $webp_url = $src_match[1] . '.webp';
            
            // Convert URL to file path
            $original_path = str_replace($upload_baseurl, $upload_basepath, $original_url);
            $webp_path = str_replace($upload_baseurl, $upload_basepath, $webp_url);
            
            return file_exists($webp_path) ? $webp_url . ($src_match[3] ?? '') : $original_url . ($src_match[3] ?? '');
        }, $srcset);
        
        return 'srcset="' . esc_attr($new_srcset) . '"';
    }, $content);
    
    // Replace URLs inside Swiper's data-swiper-options
    $content = preg_replace_callback('/data-swiper-options=["\']([^"\']+)["\']/i', function ($matches) use ($upload_baseurl, $upload_basepath) {
        $json_data = $matches[1];
        
        // Replace all image URLs in the JSON string
        $new_json_data = preg_replace_callback('/([^,\s]+)\.(jpg|jpeg|png)/', function ($json_match) use ($upload_baseurl, $upload_basepath) {
            $original_url = $json_match[1] . '.' . $json_match[2];
            $webp_url = $json_match[1] . '.webp';
            
            // Convert URL to file path
            $original_path = str_replace($upload_baseurl, $upload_basepath, $original_url);
            $webp_path = str_replace($upload_baseurl, $upload_basepath, $webp_url);
            
            return file_exists($webp_path) ? $webp_url : $original_url;
        }, $json_data);
        
        return 'data-swiper-options="' . esc_attr($new_json_data) . '"';
    }, $content);
    
    return $content;
}

// Replace JPG and PNG with WEBP in featured images
add_filter('post_thumbnail_html', 'js_replace_featured_image_with_webp', 10, 5);
function js_replace_featured_image_with_webp($html, $post_id, $post_thumbnail_id, $size, $attr)
{
    return js_replace_jpg_with_webp($html);
}

/**
 * Generate WebP versions of all image sizes
 *
 * @param array $metadata      The attachment metadata
 * @param int   $attachment_id The attachment ID
 *
 * @return array The modified metadata
 */
function generate_webp_images($metadata, $attachment_id)
{
    // Only process image attachments
    if (!isset($metadata['file']) || !isset($metadata['sizes']))
    {
        return $metadata;
    }
    
    // Get the upload directory information
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/';
    
    // Convert the original image to WebP
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    convert_to_webp($original_file);
    
    // Convert each image size to WebP
    foreach ($metadata['sizes'] as $size_name => $size_data)
    {
        if (!isset($size_data['file']))
        {
            continue;
        }
        
        $file_path = $base_dir . $size_data['file'];
        convert_to_webp($file_path);
    }
    
    return $metadata;
}

/**
 * Convert a single image file to WebP format
 *
 * @param string $file_path The full path to the image file
 *
 * @return bool True on success, false on failure
 */
function convert_to_webp($file_path)
{
    // Check if file exists
    if (!file_exists($file_path))
    {
        return false;
    }
    
    // Get the file extension
    $file_info = pathinfo($file_path);
    $extension = strtolower($file_info['extension']);
    
    // Only process jpeg, jpg, and png images
    if (!in_array($extension, array('jpeg', 'jpg', 'png')))
    {
        return false;
    }
    
    // Set the output WebP file path (clean filename without original extension)
    $webp_file_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
    
    // Create image resource based on file type
    $image = null;
    
    switch ($extension)
    {
        case 'jpeg':
        case 'jpg':
            $image = @imagecreatefromjpeg($file_path);
            break;
        
        case 'png':
            $image = @imagecreatefrompng($file_path);
            // Handle PNG transparency
            if ($image)
            {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        
        default:
            return false;
    }
    
    // If image resource creation failed, return false
    if (!$image)
    {
        return false;
    }
    
    // Save the WebP image (quality 80%)
    $result = imagewebp($image, $webp_file_path, 80);
    
    // Free memory
    imagedestroy($image);
    
    return $result;
}

/**
 * Optional: Add WebP support for image replacement in content
 * This function will replace image URLs with their WebP version if available
 */
function webp_replace_images_in_content($content)
{
    // Only make replacements if the browser supports WebP
    if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') === false)
    {
        return $content;
    }
    
    // Regular expression to find image tags
    $pattern = '/<img[^>]+src=([\'"])(?<src>.*?\.(?:jpeg|jpg|png))\\1[^>]*>/i';
    
    // Replace image src with WebP version if it exists
    $content = preg_replace_callback($pattern, function ($matches) {
        $src = $matches['src'];
        // Get the path parts to create a clean filename
        $path_parts = pathinfo($src);
        $webp_src = $path_parts['dirname'] . '/' . $path_parts['filename'] . '.webp';
        
        // Get the file path for the WebP image
        $upload_dir = wp_upload_dir();
        $webp_file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $webp_src);
        
        // Check if WebP version exists
        if (file_exists($webp_file_path))
        {
            return str_replace($src, $webp_src, $matches[0]);
        }
        
        // Return original image tag if WebP doesn't exist
        return $matches[0];
    }, $content);
    
    return $content;
}

/**
 * Delete WebP images when the original attachment is deleted
 *
 * @param int $post_id The ID of the attachment being deleted
 */
function delete_webp_images($post_id)
{
    // Get attachment metadata
    $metadata = wp_get_attachment_metadata($post_id);
    
    // Check if it's an image with proper metadata
    if (!isset($metadata['file']) || !isset($metadata['sizes']))
    {
        return;
    }
    
    // Get upload directory information
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/';
    
    // Delete WebP version of the original image
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    $original_webp = pathinfo($original_file, PATHINFO_DIRNAME) . '/' . pathinfo($original_file, PATHINFO_FILENAME) . '.webp';
    
    // Try different methods to delete the file for shared hosting environments
    if (file_exists($original_webp))
    {
        // Method 1: Direct unlink
        if (!@unlink($original_webp))
        {
            // Method 2: Try WordPress filesystem API
            global $wp_filesystem;
            if (!isset($wp_filesystem))
            {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }
            
            if (isset($wp_filesystem))
            {
                $wp_filesystem->delete($original_webp, false, 'f');
            }
        }
    }
    
    // Delete WebP versions of all image sizes
    foreach ($metadata['sizes'] as $size_name => $size_data)
    {
        if (!isset($size_data['file']))
        {
            continue;
        }
        
        $file_path = $base_dir . $size_data['file'];
        $webp_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . pathinfo($file_path, PATHINFO_FILENAME) . '.webp';
        
        if (file_exists($webp_path))
        {
            // Method 1: Direct unlink
            if (!@unlink($webp_path))
            {
                // Method 2: Try WordPress filesystem API
                global $wp_filesystem;
                if (!isset($wp_filesystem))
                {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    WP_Filesystem();
                }
                
                if (isset($wp_filesystem))
                {
                    $wp_filesystem->delete($webp_path, false, 'f');
                }
            }
        }
    }
    
    // Add error logging for debugging (optional - remove in production)
    if (defined('WP_DEBUG') && WP_DEBUG)
    {
        error_log('WebP deletion attempted for attachment ID: ' . $post_id);
    }
}

// Hook the WebP generation function after WordPress generates all image sizes
add_filter('wp_generate_attachment_metadata', 'generate_webp_images', 20, 2);

// Hook the WebP deletion function when an attachment is permanently deleted
add_action('delete_attachment', 'delete_webp_images');
