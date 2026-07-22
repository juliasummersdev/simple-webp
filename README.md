# Simple WebP Converter

A WordPress plugin that automatically converts uploaded images (JPG/PNG) to WebP format and serves WebP files throughout your site.

![Version](https://img.shields.io/badge/version-0.1-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)
![PHP](https://img.shields.io/badge/php-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-blue.svg)

## Features

- Automatic conversion on image upload for all registered image sizes
- Server capability check for WebP support (GD/Imagick)
- Quality control with adjustable WebP quality setting (1-100)
- Bulk regeneration with real-time progress tracking
- Smart replacement of JPG/PNG with WebP in post content and featured images
- Graceful fallback - keeps original images if WebP doesn't exist
- Automatic cleanup when attachments are deleted
- WebP status column in Media Library
- Gutenberg block toolbar integration
- Detailed conversion logging
- Storage statistics and savings tracking

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```
   wp-content/plugins/simple-webp/
   ```

2. Activate the plugin through the WordPress admin panel:
   - Navigate to **Plugins** in the WordPress admin
   - Find **Simple WebP Converter**
   - Click **Activate**

3. Configure settings:
   - Go to **Settings → WebP Converter**
   - Check server support status
   - Adjust WebP quality (default: 80)
   - Click **"Regenerate All Images"** to convert existing images

## Usage

### Automatic Conversion

Once activated, the plugin automatically converts new image uploads to WebP for all WordPress registered image sizes.

### Media Library

- View conversion status for all images
- See space savings at a glance
- Convert individual images with one click

### Gutenberg Editor

- Select any Image block
- Click the **"Convert to WebP"** toolbar button
- View conversion status inline

### Bulk Operations

**Regenerate All Images:**
- Navigate to **Settings → WebP Converter**
- Click **"Regenerate All Images"**
- Monitor real-time progress

**Delete All WebP Images:**
- Navigate to **Settings → WebP Converter**
- Click **"Delete All WebP Images"**
- Confirm action (requires double confirmation)

## Configuration

### WebP Quality

Adjust the quality slider (1-100):
- **60-70:** Smaller files, noticeable quality loss
- **80 (default):** Good balance of size and quality
- **90-100:** Minimal quality loss, larger files

### Statistics

The Storage Summary shows:
- Total conversions
- Successful conversions
- Total original size
- Total WebP size
- Total space saved

## Development

### File Structure

```
simple-webp/
├── jsdev-simple-webp-converter.php  # Main plugin file
├── includes/
│   ├── admin-page.php               # Settings page
│   ├── admin-ajax.php               # AJAX handlers
│   ├── admin-scripts.php            # Script enqueuing
│   └── media-library.php            # Media integration
└── assets/
    ├── css/                         # Admin styles
    └── js/                          # Admin functionality
```

### Function Prefixes

All functions use the `jsdev_simple_webp_` prefix to avoid conflicts.

### Hooks and Filters

The plugin uses WordPress standard hooks:
- `add_attachment` - Convert images on upload
- `delete_attachment` - Clean up WebP files
- `the_content` - Replace images in post content
- `post_thumbnail_html` - Replace featured images
- `admin_menu` - Add settings page
- `admin_init` - Register settings

## FAQ

**Does this delete my original JPG/PNG files?**
No. Original files are always preserved. WebP files are created alongside them.

**What happens if a browser doesn't support WebP?**
The plugin only serves WebP where the file exists. Older browsers automatically fall back to the original image.

**Can I convert existing images?**
Yes. Use the "Regenerate All Images" button in Settings → WebP Converter.

**How much space will I save?**
Typically 25-35% for photos and 50-75% for graphics.

## License

GPL v2 or later
