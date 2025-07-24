<?php

defined( 'ABSPATH' ) || exit;

/**
 * Preview Styles
*/
function ogio_preview_style() {
    // Debug: Log CSS loading (only in debug mode)
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'OGIO: Loading preview CSS' );
    }

    wp_enqueue_style( 'ogio-preview', plugin_dir_url( __FILE__ ) . '/preview.css', array(), '1.6' );
    $plugin_images = plugin_dir_url(__DIR__) . 'images';
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

// Debug: Check if we should load preview CSS
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'OGIO: Checking if should load preview CSS' );
    error_log( 'OGIO: Function exists: ' . ( function_exists('ogio_should_load_preview_css') ? 'yes' : 'no' ) );
    if ( function_exists('ogio_should_load_preview_css') ) {
        error_log( 'OGIO: Should load CSS: ' . ( ogio_should_load_preview_css() ? 'yes' : 'no' ) );
        if ( isset( $_GET['ogio_settings'] ) ) {
            error_log( 'OGIO: ogio_settings parameter: ' . sanitize_text_field( wp_unslash( $_GET['ogio_settings'] ) ) );
        } else {
            error_log( 'OGIO: ogio_settings parameter not set' );
        }
    }
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