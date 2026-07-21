# Simple WebP Converter

A comprehensive WordPress plugin that automatically converts uploaded images (JPG/PNG) to WebP format, serves WebP files throughout your site.

![Version](https://img.shields.io/badge/version-0.1-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-blue.svg)

---

## ✨ Features

### Core Functionality
- **Automatic conversion** on image upload for all registered image sizes (thumbnail, medium, large, custom)
- **Server capability check** for WebP support (GD/Imagick) with detailed diagnostics
- **Quality control** with adjustable WebP quality setting (1-100)
- **Bulk regeneration** with real-time progress tracking and batch processing
- **Smart replacement** of JPG/PNG with WebP in post content and featured images
- **Graceful fallback** - keeps original images if WebP doesn't exist
- **Automatic cleanup** when attachments are deleted

### Admin Dashboard
- **Comprehensive settings page** at Settings → WebP Converter
- **Real-time statistics** showing:
  - Total conversions
  - Successful conversions
  - Total original size
  - Total WebP size
  - Total space saved
- **Quality change detection** - prompts to regenerate images when quality setting changes
- **Bulk actions**:
  - Regenerate all images with progress bar
  - Delete all WebP images with double confirmation
- **Settings link** on plugins page for quick access

### Media Library Integration
- **WebP Status column** in list view showing:
  - Conversion status (✓ Converted / ✗ Not converted)
  - Space savings with percentage
  - One-click "Convert Now" button
- **Attachment details modal** with conversion options and statistics
- **Post edit meta box** showing all images in the post with:
  - Featured image indicator
  - Conversion status for each image
  - Bulk convert all images in post

### Gutenberg Integration
- **Block toolbar button** - "Convert to WebP" option on Image blocks
- **Inline feedback** with success/error notices in the editor
- **Automatic URL update** after successful conversion

### Conversion Log
- **Detailed logging** of all conversion events with:
  - Timestamp
  - Attachment ID (linked)
  - Filename
  - Image size (thumbnail, medium, large, etc.)
  - Original file size
  - WebP file size
  - Space saved (bytes and percentage)
  - Success/failure status with error messages
- **AJAX pagination** for smooth navigation
- **Styled pagination** with Prev/Next buttons and centered layout

### Deactivation & Cleanup
- **Smart deactivation modal** asks whether to delete WebP files
- **Double confirmation** for destructive actions
- **Automatic cleanup** of WebP files when original images are deleted
- **Completion notices** after bulk operations

### User Experience Enhancements
- **Auto-scroll** to relevant sections after actions
- **Loading indicators** with opacity transitions
- **Completion notices** that persist across page reloads
- **Quality slider** synced with number input
- **Responsive design** for mobile and tablet devices

---

## 📋 Requirements

| Requirement | Minimum Version | Notes |
|-------------|----------------|-------|
| WordPress | 5.8+ | Gutenberg block editor required |
| PHP | 7.4+ | |
| Image Library | GD with WebP or Imagick with WebP | Automatic detection |
| Server Access | Write permissions | Required for `wp-content/uploads/` |

The plugin automatically checks your server environment and displays detailed information about WebP support. If neither GD nor Imagick supports WebP, conversion features are disabled with guidance on how to enable support.

---

## 🚀 Installation

### From GitHub

1. Download the latest release or clone this repository
2. Upload the `simple-webp` folder to `/wp-content/plugins/`
3. Activate the plugin through the Plugins menu in WordPress
4. Go to **Settings → WebP Converter** to configure

### First-Time Setup

1. Check the **Server Support Status** section to confirm WebP is supported
2. Adjust the **WebP Quality** setting (default: 80)
3. Click **"Regenerate All Images"** to convert existing media library images
4. Monitor progress with the real-time progress bar

---

## 🎯 Usage

### Automatic Conversion

Once activated, the plugin automatically converts new image uploads to WebP. All WordPress registered image sizes are converted, including custom sizes defined by your theme or other plugins.

### Media Library

**List View:**
- View conversion status for all images
- See space savings at a glance
- Convert individual images with one click

**Grid View:**
- Access conversion options in attachment details
- View detailed statistics
- Regenerate WebP versions

### Post/Page Editor

**Gutenberg:**
- Select any Image block
- Click the **"Convert to WebP"** toolbar button
- View conversion status and results inline

**Post Edit Screen:**
- Check the **WebP Conversion** meta box (sidebar)
- See all images used in the post
- Convert all images at once

### Bulk Operations

**Regenerate All Images:**
- Navigate to Settings → WebP Converter
- Click **"Regenerate All Images"**
- Monitor real-time progress
- Receive completion notice when done

**Delete All WebP Images:**
- Navigate to Settings → WebP Converter
- Scroll to Bulk Actions
- Click **"Delete All WebP Images"**
- Confirm twice (safety measure)
- Receive completion notice

---

## 🔧 Configuration

### WebP Quality

Adjust the quality slider (1-100) to balance file size and image quality:
- **60-70:** Smaller files, noticeable quality loss
- **80 (default):** Good balance of size and quality
- **90-100:** Minimal quality loss, larger files

When you change the quality setting, the plugin prompts you to regenerate existing WebP images with the new setting.

### Statistics

The Storage Summary shows:
- **Total Conversions:** Number of WebP files generated
- **Successful:** Successfully converted images
- **Total Original Size:** Combined size of all original images
- **Total WebP Size:** Combined size of all WebP images
- **Total Space Saved:** Difference between original and WebP sizes

Statistics are calculated by scanning actual files in your uploads directory, ensuring accuracy.

---

## 🛠️ Technical Details

### Function Prefix

All functions, hooks, and options use the `jsdev_simple_webp_` prefix for namespace safety.

### Database

Creates a custom log table: `{$wpdb->prefix}jsdev_simple_webp_log`

Stores option: `jsdev_simple_webp_webp_quality`

### File Structure

```
simple-webp/
├── jsdev-simple-webp-converter.php  # Main plugin file
├── includes/
│   ├── admin-page.php               # Settings page
│   ├── admin-ajax.php               # AJAX handlers
│   ├── admin-scripts.php            # Script enqueuing
│   └── media-library.php            # Media integration
├── assets/
│   ├── css/
│   │   ├── admin.css                # Admin styles
│   │   └── deactivation.css         # Modal styles
│   └── js/
│       ├── admin.js                 # Admin functionality
│       ├── block-controls.js        # Gutenberg integration
│       ├── deactivation.js          # Deactivation modal
│       └── media-library.js         # Media library features
└── README.md                        # This file
```

### Key Functions

| Function | Purpose |
|----------|---------|
| `jsdev_simple_webp_check_webp_support()` | Verifies GD or Imagick WebP support |
| `jsdev_simple_webp_convert_image_to_webp()` | Converts a single image to WebP |
| `jsdev_simple_webp_generate_all_sizes_webp()` | Generates WebP for all image sizes |
| `jsdev_simple_webp_regenerate_all_images()` | Bulk regeneration via AJAX batches |
| `jsdev_simple_webp_replace_images_in_content()` | Filters post content for WebP URLs |
| `jsdev_simple_webp_replace_featured_image()` | Filters featured image HTML |
| `jsdev_simple_webp_get_webp_url_if_exists()` | Safe URL substitution helper |
| `jsdev_simple_webp_delete_webp_for_attachment()` | Cleanup on attachment deletion |
| `jsdev_simple_webp_delete_all_webp_images()` | Bulk WebP deletion |
| `jsdev_simple_webp_get_statistics()` | Calculates storage statistics |

### Hooks & Filters

**For Developers:**

```php
// Override quality programmatically
add_filter('jsdev_simple_webp_webp_quality', function($quality) {
    return 85;
});

// Hook before conversion
add_action('jsdev_simple_webp_before_webp_conversion', function($file_path, $webp_path, $quality) {
    // Your code here
}, 10, 3);

// Hook after conversion
add_action('jsdev_simple_webp_after_webp_conversion', function($file_path, $webp_path, $success, $result) {
    // Your code here
}, 10, 4);

// Skip specific attachments
add_filter('jsdev_simple_webp_skip_webp_for_attachment', function($skip, $attachment_id) {
    return $attachment_id === 123; // Skip attachment #123
}, 10, 2);

// Customize allowed MIME types
add_filter('jsdev_simple_webp_allowed_mime_types', function($types) {
    return ['jpg', 'jpeg']; // Only convert JPEGs
});
```

---

## ❓ FAQ

**Q: Does this delete my original JPG/PNG files?**
A: No. Original files are always preserved. WebP files are created alongside them. Only WebP files are deleted (on attachment deletion or optional deactivation cleanup).

**Q: What happens if a browser doesn't support WebP?**
A: The plugin only serves WebP where the file exists. Modern browsers (Chrome, Firefox, Edge, Safari 14+) all support WebP. Older browsers automatically fall back to the original image.

**Q: Will this slow down my site?**
A: No. Conversion happens on upload or via manual actions, not on page load. Front-end performance is improved because WebP files are smaller.

**Q: Can I convert existing images?**
A: Yes. Use the "Regenerate All Images" button in Settings → WebP Converter.

**Q: What quality setting should I use?**
A: 80 (default) provides an excellent balance. For photos, 75-85 works well. For graphics with text, use 85-95.

**Q: How much space will I save?**
A: Typically 25-35% for photos and 50-75% for graphics. Actual savings depend on image content and quality settings.

**Q: Can I undo conversions?**
A: Yes. Use "Delete All WebP Images" in Settings → WebP Converter. Original images are never deleted.

**Q: Does it work with multisite?**
A: Yes, each site in a multisite network has its own settings and conversions.

---

## 🐛 Known Issues

- Large media libraries (1000+ images) may require multiple attempts for full regeneration due to PHP timeouts
- CDN and remote storage plugins may require additional configuration
- Some shared hosting environments may have restrictive memory limits

---

## 🔮 Roadmap

Planned features for future releases:

- [ ] WP-CLI command support
- [ ] AVIF format support
- [ ] Picture element with fallback
- [ ] Batch processing with WP-Cron
- [ ] CSV export of conversion log
- [ ] Advanced filtering options
- [ ] Multisite network admin panel
- [ ] Translation support (i18n)
- [ ] CDN integration helpers

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Simple WebP Converter
Copyright (C) 2024 Julia Summers

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## 👩‍💻 Author

**Julia Summers**
Website: [juliasummers.dev](https://juliasummers.dev)

---

## 🙏 Acknowledgments

- Core media-handling logic inspired by WordPress core's `wp-admin/includes/media.php`
- Pagination styling follows WordPress admin design patterns
- Built with modern JavaScript (ES6+) and PHP 7.4+ features

---

## 📊 Changelog

### Version 0.1 (Initial Release)

**Core Features:**
- Automatic WebP conversion on image upload
- Support for GD and Imagick libraries
- Conversion of all WordPress image sizes
- Quality control (1-100)

**Admin Interface:**
- Comprehensive settings page with statistics
- Server capability detection and display
- Quality slider with real-time sync
- Settings link on plugins page

**Bulk Operations:**
- Regenerate all images with AJAX progress tracking
- Delete all WebP images with confirmation
- Quality change detection with regeneration prompt
- Completion notices that persist across reloads

**Media Library Integration:**
- WebP Status column in list view
- Conversion options in attachment details
- Space savings display
- One-click conversion buttons

**Post Editor Integration:**
- WebP Conversion meta box on post edit screen
- Display of all images in post
- Featured image indicator
- Bulk convert all post images

**Gutenberg Integration:**
- Convert to WebP toolbar button on Image blocks
- Inline success/error notices
- Automatic URL updates

**Logging System:**
- Detailed conversion log with all events
- AJAX pagination with styled controls
- Success/failure tracking
- File size comparisons

**Deactivation:**
- Modal confirmation for cleanup options
- Safe deactivation without data loss
- Automatic WebP cleanup on attachment deletion

**User Experience:**
- Auto-scroll to relevant sections
- Loading states with visual feedback
- Responsive design for all devices
- Smooth transitions and animations

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the issues page.

---

## ⭐ Show Your Support

Give a ⭐️ if this project helped you!
