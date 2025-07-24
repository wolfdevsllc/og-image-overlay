<?php
/**
 * OG Image Overlay - Uninstall
 *
 * Fired when the plugin is uninstalled. Cleans up all plugin data
 * and options to ensure no traces are left in the database.
 *
 * @package    OG_Image_Overlay
 * @subpackage Uninstall
 * @since      1.0.0
 * @version    1.6.0
 * @author     Al-Mamun Talukder
 * @link       https://itsmereal.com/plugins/open-graph-image-overlay
 * @license    GPLv2 or later
 * @copyright  2024 Al-Mamun Talukder
 *
 * WordPress Coding Standards: This file follows WordPress PHP coding standards.
 * Text Domain: ogio
 * Domain Path: /languages
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete all plugin options
 * WordPress Standards Fix: Proper cleanup on plugin removal
 */
function ogio_cleanup_options() {
    // Plugin settings
    delete_option( 'ogio_fallback_image' );
    delete_option( 'ogio_overlay_image' );
    delete_option( 'ogio_overlay_position_x' );
    delete_option( 'ogio_overlay_position_y' );
    delete_option( 'ogio_select_seo_plugin' );
    delete_option( 'ogio_image_source' );
    delete_option( 'ogio_image_output_format' );
    delete_option( 'ogio_image_output_quality' );

    // Error log data
    delete_option( 'ogio_admin_errors' );

    // Internal flags
    delete_option( 'ogio_flush_rewrite_rules_flag' );

    // Clean up any transients
    delete_transient( 'ogio_health_status' );
    delete_transient( 'ogio_config_validation' );
}

/**
 * Clean up any custom database tables
 * WordPress Standards Fix: Complete database cleanup
 */
function ogio_cleanup_database() {
    global $wpdb;

    // Currently the plugin doesn't create custom tables
    // This function is here for future extensibility

    // If we had custom tables, we would drop them like:
    // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ogio_custom_table" );
}

/**
 * Clean up uploaded files if any
 * WordPress Standards Fix: File system cleanup
 */
function ogio_cleanup_files() {
    // The plugin doesn't create any custom files in wp-content
    // All images are handled through WordPress media library
    // This function is here for future extensibility
}

/**
 * Main uninstall function
 * WordPress Standards Fix: Organized uninstall process
 */
function ogio_uninstall() {
    // Only run uninstall for users with proper permissions
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    // Verify the action is legitimate
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        return;
    }

    // Clean up options
    ogio_cleanup_options();

    // Clean up database
    ogio_cleanup_database();

    // Clean up files
    ogio_cleanup_files();

    // Flush rewrite rules one last time
    flush_rewrite_rules();

    // Clear any remaining caches
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
}

// Execute the uninstall
ogio_uninstall();