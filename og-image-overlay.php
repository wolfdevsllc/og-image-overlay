<?php
/*
Plugin Name: Open Graph Image Overlay
Plugin URI: https://itsmereal.com/plugins/open-graph-image-overlay
Description: Add automated image overlay on top of Open Graph images. This plugin extends the Open Graph features from Yoast SEO or Rank Math plugin.
Version: 1.6
Author: Al-Mamun Talukder
Author URI: https://itsmereal.com
Requires at least: 4.3
Tested up to: 6.6.2
License: GPLv2 or later
Text Domain: ogio
*/

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants
 */
define( 'OGIO_VERSION', '1.6' );
define( 'OGIO_PLUGIN_FILE', __FILE__ );
define( 'OGIO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OGIO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OGIO_TEXT_DOMAIN', 'ogio' );

/**
 * Load plugin textdomain for internationalization
 * WordPress Standards Fix: Proper i18n implementation
 */
function ogio_load_textdomain() {
    load_plugin_textdomain(
        OGIO_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( OGIO_PLUGIN_FILE ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'ogio_load_textdomain' );

/**
 * Plugin initialization
 * WordPress Standards Fix: Proper plugin structure and hooks
 */
function ogio_init() {
    // Check minimum WordPress version
    if ( version_compare( get_bloginfo( 'version' ), '4.3', '<' ) ) {
        add_action( 'admin_notices', 'ogio_wordpress_version_notice' );
        return;
    }

    // Check required PHP extensions
    if ( ! extension_loaded( 'gd' ) ) {
        add_action( 'admin_notices', 'ogio_gd_extension_notice' );
        return;
    }

    // Plugin is ready to load
    do_action( 'ogio_loaded' );
}
add_action( 'init', 'ogio_init' );

/**
 * WordPress version compatibility notice
 * WordPress Standards Fix: Proper admin notices
 */
function ogio_wordpress_version_notice() {
    $message = sprintf(
        /* translators: %1$s: Plugin name, %2$s: Required WordPress version */
        __( '%1$s requires WordPress version %2$s or higher. Please update WordPress.', 'ogio' ),
        '<strong>' . __( 'OG Image Overlay', 'ogio' ) . '</strong>',
        '4.3'
    );

    printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * GD extension notice
 * WordPress Standards Fix: Proper admin notices
 */
function ogio_gd_extension_notice() {
    $message = sprintf(
        /* translators: %s: Plugin name */
        __( '%s requires the GD PHP extension to be installed. Please contact your hosting provider.', 'ogio' ),
        '<strong>' . __( 'OG Image Overlay', 'ogio' ) . '</strong>'
    );

    printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * Include Template Parts
 */
require_once __DIR__ . '/admin/functions.php';
require_once __DIR__ . '/admin/customizer.php';
require_once __DIR__ . '/template/config.php';

/**
 *	Plugin Update Checker
 *  @source https://github.com/YahnisElsts/plugin-update-checker
 */
require_once __DIR__ . '/includes/update-checker/plugin-update-checker.php';

$imrsmodsUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/itsmereal/og-image-overlay',
	__FILE__,
	'og-image-overlay'
);

/**
 * Add settings link to plugin actions
 */
function ogio_add_plugin_link ( $links ) {
	$link = add_query_arg(
        array(
            'url'           => urlencode( site_url( '/?ogio_settings=true' ) ),
            'return'        => urlencode( admin_url() ),
            'ogio_settings' => 'true',
        ),
        'customize.php?autofocus[section]=ogio_settings'
    );
    $settings_link = array(
        '<a href="' . esc_url( admin_url( $link ) ) . '">' . esc_html__( 'Settings', OGIO_TEXT_DOMAIN ) . '</a>',
    );
    return array_merge( $links, $settings_link );
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ogio_add_plugin_link' );


/**
 * Plugin Activation Hook
 * WordPress Standards Fix: Proper activation handling with default options
 */
function ogio_activate() {
    // Set flag to flush rewrite rules on next init
    if ( ! get_option( 'ogio_flush_rewrite_rules_flag' ) ) {
        add_option( 'ogio_flush_rewrite_rules_flag', true );
    }

    // Set default options if they don't exist
    if ( false === get_option( 'ogio_image_output_format' ) ) {
        add_option( 'ogio_image_output_format', 'image/jpeg' );
    }

    if ( false === get_option( 'ogio_image_output_quality' ) ) {
        add_option( 'ogio_image_output_quality', 75 );
    }

    if ( false === get_option( 'ogio_select_seo_plugin' ) ) {
        add_option( 'ogio_select_seo_plugin', 'other' );
    }

    if ( false === get_option( 'ogio_image_source' ) ) {
        add_option( 'ogio_image_source', 'default' );
    }

    if ( false === get_option( 'ogio_overlay_position_x' ) ) {
        add_option( 'ogio_overlay_position_x', 0 );
    }

    if ( false === get_option( 'ogio_overlay_position_y' ) ) {
        add_option( 'ogio_overlay_position_y', 0 );
    }
}

register_activation_hook( __FILE__, 'ogio_activate' );