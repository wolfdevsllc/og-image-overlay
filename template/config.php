<?php
/**
 * OG Image Overlay - Template Configuration
 *
 * Template and preview functionality for the customizer interface
 * including CSS loading and preview styles management.
 *
 * @package    OG_Image_Overlay
 * @subpackage Template
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

defined( 'ABSPATH' ) || exit;

/**
 * Preview Styles
*/
function ogio_preview_style() {
    // Enhanced CSS loading logging
    if ( function_exists( 'ogio_logger' ) ) {
        ogio_logger()->debug( 'Loading preview CSS styles' );
    }

    wp_enqueue_style( 'ogio-preview', plugin_dir_url( __FILE__ ) . '/preview.css', array(), OGIO_VERSION );
    $plugin_images = plugin_dir_url( __DIR__ ) . 'images';
    $preview_image_css = '';
    if ( get_option( 'ogio_fallback_image' ) ) {
        $preview_image = get_option( 'ogio_fallback_image' );
        $preview_image = wp_get_attachment_url( $preview_image );
        $preview_image_css = '.ogio-preview-image { background-image: url( "'.$preview_image.'" ); }';
    }
    $dynamic_css = "
        .ogio-preview-top { background-image: url( {$plugin_images}/preview-top.jpg ); }
        .ogio-preview-bottom { background-image: url( {$plugin_images}/preview-bottom.jpg ); }
        {$preview_image_css}
        ";
    wp_add_inline_style( 'ogio-preview', $dynamic_css );
}

// Enhanced CSS loading diagnostics
if ( function_exists( 'ogio_logger' ) ) {
    ogio_logger()->debug( 'Checking CSS loading conditions', array(
        'function_exists' => function_exists('ogio_should_load_preview_css'),
        'should_load_css' => function_exists('ogio_should_load_preview_css') ? ogio_should_load_preview_css() : false,
        'ogio_settings_param' => isset( $_GET['ogio_settings'] ) ? sanitize_text_field( wp_unslash( $_GET['ogio_settings'] ) ) : 'not_set'
    ) );
}

// Robust CSS loading with fallback
$should_load_css = false;

if ( function_exists('ogio_should_load_preview_css') ) {
    $should_load_css = ogio_should_load_preview_css();
} else {
    // Fallback: Direct check (less secure but functional)
    $should_load_css = ( isset( $_GET['ogio_settings'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['ogio_settings'] ) ) );
}

if ( $should_load_css ) {
    add_action( 'wp_enqueue_scripts', 'ogio_preview_style' );
}

/**
 * Remove other style sheets while in settings
 */
function ogio_remove_other_styles() {
    global $wp_styles;
    if( function_exists('ogio_is_settings_preview') && ogio_is_settings_preview() ) {
        $wp_styles->queue = array( 'ogio-preview' );
    }
}

add_action( 'wp_print_styles', 'ogio_remove_other_styles', 99 );