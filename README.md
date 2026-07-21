# JSDev WebP Converter

A WordPress plugin that automatically converts uploaded images (JPG/PNG) to WebP, serves WebP in place of JPG/PNG throughout the site, and gives admins full visibility and control over the conversion process.

**Prefix:** All functions, hooks, and options use the `jsdev_simple_webp_` prefix.
**Reference:** Core media-handling logic is modeled after WordPress core's `wp-admin/includes/media.php`.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Admin Settings Page](#admin-settings-page)
- [Gutenberg Integration](#gutenberg-integration)
- [Content & Featured Image Replacement](#content--featured-image-replacement)
- [Logging](#logging)
- [Deactivation & Cleanup](#deactivation--cleanup)
- [Function Reference](#function-reference)
- [Hooks & Filters](#hooks--filters)
- [FAQ](#faq)
- [Suggested Additions](#suggested-additions-for-a-more-complete-plugin)
- [Changelog](#changelog)

---

## Features

- Server capability check for WebP support (GD / Imagick) before enabling any conversion features
- Admin dashboard page showing conversion status + a running log of every WebP creation
- Confirmation modal on plugin deactivation asking whether to delete all generated WebP files from `uploads/`
- Admin setting for WebP quality (1-100) with a "Regenerate All Images" action
- Gutenberg block toolbar option: "Convert to WebP" on selected images
- Automatic replacement of JPG/PNG with WebP in post content (front-end output)
- Automatic replacement of JPG/PNG with WebP for featured images
- Generates WebP versions for all registered image sizes (thumbnail, medium, large, custom sizes), not just the full-size original
- Single-image conversion utility usable by other functions/hooks
- WebP files deleted automatically when the original attachment is deleted (keeps `uploads/` clean)

---

## Requirements

| Requirement | Notes |
|---|---|
| WordPress | 5.8+ (Gutenberg block editor required for the image toolbar feature) |
| PHP | 7.4+ |
| Image library | GD with WebP support or Imagick compiled with WebP support |
| Server | Write access to `wp-content/uploads/` |

The plugin performs an automatic environment check (`jsdev_simple_webp_check_webp_support()`) on activation and on the admin status page. If neither GD nor Imagick supports WebP, all conversion features are disabled and an admin notice is shown with remediation steps (e.g., contact host, enable `imagick` extension).

---

## Installation

1. Upload the `jsdev-simple-webp-converter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to Settings -> WebP Converter to confirm server support and configure quality.
4. (Optional) Click "Regenerate All Images" to backfill WebP versions for your existing media library.

---

## Admin Settings Page

Located at Settings -> JSDev WebP Converter (`jsdev_simple_webp_render_admin_page()`), showing:

- Server Support Status - live result of `jsdev_simple_webp_check_webp_support()` (GD/Imagick, version, pass/fail)
- Conversion Quality - slider/number input (1-100), saved via `jsdev_simple_webp_webp_quality` option
- Regenerate All Images button - triggers `jsdev_simple_webp_regenerate_all_images()`, with a progress indicator (AJAX-polled) since large libraries can take a while
- Conversion Log - paginated table of every WebP creation event (see Logging)
- Storage Summary - count of WebP files, total disk space used/saved

---

## Gutenberg Integration

A block-editor plugin (`jsdev-webp-block-controls`) adds a "Convert to WebP" button to the Image block's toolbar (`BlockControls`) and to the media library selection UI. Clicking it:

1. Sends the attachment ID via AJAX to `jsdev_simple_webp_ajax_convert_single_image()`
2. Runs `jsdev_simple_webp_convert_image_to_webp( $file_path, $quality )`
3. Updates the block's image URL to the new `.webp` asset once conversion succeeds
4. Shows an inline success/error notice in the editor

---

## Content & Featured Image Replacement

- `jsdev_simple_webp_replace_images_in_content( $content )` - hooked to `the_content`, rewrites `<img>` src/srcset JPG/PNG URLs to their `.webp` counterpart only if the WebP file exists, otherwise leaves the original untouched (graceful fallback).
- `jsdev_simple_webp_replace_featured_image( $html, $post_id, $post_thumbnail_id, ... )` - hooked to `post_thumbnail_html`, applies the same WebP substitution logic to featured images.
- `jsdev_simple_webp_get_webp_url_if_exists( $image_url )` - shared helper used by both of the above to check for a matching `.webp` file before swapping the URL.

---

## Logging

Every conversion event (single image, bulk regenerate, Gutenberg-triggered, or automatic on-upload) is recorded with:

- Timestamp
- Attachment ID / file name
- Image size (thumbnail, medium, full, etc.)
- Original file size vs. WebP file size
- Success/failure + error message if applicable

Logs are stored in a custom table (`{$wpdb->prefix}jsdev_simple_webp_webp_log`) or as post meta / custom option array, and rendered in the admin status page with filtering (by date, status) and CSV export.

---

## Deactivation & Cleanup

On `register_deactivation_hook`, the plugin does not delete anything automatically. Instead:

1. An admin-only JS modal (`jsdev_simple_webp_deactivation_modal.js`) intercepts the deactivation click on the Plugins screen.
2. The modal asks: "Delete all generated WebP images from uploads/? This cannot be undone."
3. Yes -> AJAX call to `jsdev_simple_webp_delete_all_webp_images()`, then deactivation proceeds.
4. No -> deactivation proceeds, WebP files remain in place (so re-activating later doesn't require full regeneration).

On attachment deletion (`delete_attachment` hook), `jsdev_simple_webp_delete_webp_for_attachment( $attachment_id )` removes only the WebP files tied to that specific attachment (all sizes), regardless of the global deactivation choice.

---

## Function Reference

| Function | Purpose |
|---|---|
| `jsdev_simple_webp_check_webp_support()` | Verifies GD or Imagick WebP support on the current server |
| `jsdev_simple_webp_convert_image_to_webp( $file_path, $quality )` | Converts a single image file to WebP |
| `jsdev_simple_webp_generate_all_sizes_webp( $attachment_id )` | Generates WebP versions for every registered image size of an attachment |
| `jsdev_simple_webp_regenerate_all_images()` | Bulk-regenerates WebP for the entire media library (used by the admin button) |
| `jsdev_simple_webp_replace_images_in_content( $content )` | Filters post content, swapping JPG/PNG for WebP |
| `jsdev_simple_webp_replace_featured_image( $html, ... )` | Filters featured image HTML, swapping JPG/PNG for WebP |
| `jsdev_simple_webp_get_webp_url_if_exists( $url )` | Returns WebP URL if it exists, else original |
| `jsdev_simple_webp_delete_webp_for_attachment( $id )` | Deletes WebP files for one attachment (on attachment delete) |
| `jsdev_simple_webp_delete_all_webp_images()` | Deletes every WebP file in `uploads/` (on deactivation, if confirmed) |
| `jsdev_simple_webp_log_conversion( $data )` | Writes an entry to the conversion log |
| `jsdev_simple_webp_render_admin_page()` | Renders the Settings -> WebP Converter admin page |
| `jsdev_simple_webp_ajax_convert_single_image()` | AJAX handler for the Gutenberg "Convert to WebP" button |

---

## Hooks & Filters

For developers extending the plugin:

- `jsdev_simple_webp_webp_quality` (filter) - override the configured quality value programmatically
- `jsdev_simple_webp_before_webp_conversion` (action) - fires before a file is converted
- `jsdev_simple_webp_after_webp_conversion` (action) - fires after a file is converted, passes file paths + success state
- `jsdev_simple_webp_skip_webp_for_attachment` (filter) - return true to exclude a specific attachment from conversion/replacement
- `jsdev_simple_webp_allowed_mime_types` (filter) - customize which source mime types (`image/jpeg`, `image/png`) are eligible

---

## FAQ

**Does this delete my original JPG/PNG files?**
No. Originals are always preserved; WebP files are created alongside them. Only WebP files are ever deleted (on attachment deletion or optional deactivation cleanup).

**What happens if a browser doesn't support WebP?**
Content/featured-image replacement only swaps in WebP where it exists; consider pairing this plugin with a `<picture>`-based fallback (see suggestions below) for maximum compatibility.

**Will this slow down my site?**
Conversion happens on upload or via manual/bulk actions, not on-the-fly per page load, so front-end performance is not affected by the conversion process itself.

---

## Suggested Additions (for a more complete plugin)

1. **`<picture>` element / fallback support** - wrap output in `<picture><source type="image/webp">...<img fallback></picture>` so browsers without WebP support still get a valid image.
2. **Capability & nonce checks** - verify `current_user_can( 'manage_options' )` and a nonce on every AJAX handler and admin action.
3. **WP-CLI command** (`wp jsdev webp regenerate`) - avoids PHP timeout issues for large libraries.
4. **Batch processing via Action Scheduler or WP-Cron** - chunk/queue bulk regeneration instead of running it in one request.
5. **Disk space check before bulk regenerate** - warn the admin if available disk space is low.
6. **Media Library column** - add a "WebP" status column to `wp-admin/upload.php` with per-row "Convert now" link.
7. **Skip already-optimized/animated images** - animated GIFs and already-WebP files should be excluded automatically.
8. **Original mime-type restriction option** - let admins choose to convert only JPG, only PNG, or both.
9. **CDN / offloaded media compatibility** - handle plugins like WP Offload Media / S3 Uploads where `file_exists()` won't work.
10. **Rewrite rule / .htaccess option** - auto-serve `.webp` via server rewrite based on the browser's Accept header.
11. **Multisite support** - ensure conversion, logging table, and options are network-aware.
12. **i18n/translation-ready** - wrap all admin strings in `__()`/`_e()` with a proper text domain.
13. **Regenerate progress bar with cancel option** - show X of Y processed, allow stopping mid-way.
14. **Error resilience** - log failures clearly without halting the entire bulk job.
15. **Settings export/import** - useful for agencies deploying across multiple client sites.
16. **AVIF as a future option** - same architecture could later support AVIF.
17. **uninstall.php cleanup** - clean up options/log tables if the user fully deletes the plugin.

---

## Changelog

### 1.0.0
- Initial release: core conversion engine, admin status page, logging, Gutenberg integration, content/featured image replacement, deactivation cleanup modal.