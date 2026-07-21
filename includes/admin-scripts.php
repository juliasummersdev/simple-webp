<?php
/**
 * Enqueue admin scripts and styles
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue admin styles
 */
add_action('admin_enqueue_scripts', 'jsdev_simple_webp_enqueue_admin_styles');
function jsdev_simple_webp_enqueue_admin_styles($hook) {
    // Only load on our settings page
    if ($hook === 'settings_page_jsdev-simple-webp-converter') {
        wp_enqueue_style(
            'jsdev-webp-admin',
            JSDEV_WEBP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            JSDEV_WEBP_VERSION
        );
    }

    // Load deactivation modal styles on plugins page
    if ($hook === 'plugins.php') {
        wp_enqueue_style(
            'jsdev-webp-deactivation',
            JSDEV_WEBP_PLUGIN_URL . 'assets/css/deactivation.css',
            array(),
            JSDEV_WEBP_VERSION
        );
    }
}

/**
 * Enqueue admin scripts
 */
add_action('admin_enqueue_scripts', 'jsdev_simple_webp_enqueue_admin_scripts');
function jsdev_simple_webp_enqueue_admin_scripts($hook) {
    // Only load on our settings page
    if ($hook === 'settings_page_jsdev-simple-webp-converter') {
        wp_enqueue_script(
            'jsdev-webp-admin',
            JSDEV_WEBP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            JSDEV_WEBP_VERSION,
            true
        );

        wp_localize_script('jsdev-webp-admin', 'jsdevWebp', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jsdev_webp_nonce'),
            'strings' => array(
                'regenerating' => 'Regenerating images...',
                'complete' => 'Regeneration complete!',
                'error' => 'An error occurred. Please try again.'
            )
        ));
    }

    // Load deactivation modal script on plugins page
    if ($hook === 'plugins.php') {
        wp_enqueue_script(
            'jsdev-webp-deactivation',
            JSDEV_WEBP_PLUGIN_URL . 'assets/js/deactivation.js',
            array('jquery'),
            JSDEV_WEBP_VERSION,
            true
        );

        wp_localize_script('jsdev-webp-deactivation', 'jsdevWebpDeactivation', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jsdev_webp_nonce'),
            'pluginBasename' => JSDEV_WEBP_PLUGIN_BASENAME
        ));
    }
}

/**
 * Enqueue Gutenberg block editor assets
 */
add_action('enqueue_block_editor_assets', 'jsdev_simple_webp_enqueue_block_editor_assets');
function jsdev_simple_webp_enqueue_block_editor_assets() {
    // Check WebP support
    $support = jsdev_simple_webp_check_webp_support();
    if (!$support['supported']) {
        return;
    }

    wp_enqueue_script(
        'jsdev-webp-block-controls',
        JSDEV_WEBP_PLUGIN_URL . 'assets/js/block-controls.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-hooks'),
        JSDEV_WEBP_VERSION,
        true
    );

    wp_localize_script('jsdev-webp-block-controls', 'jsdevWebpBlock', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jsdev_webp_nonce')
    ));
}
