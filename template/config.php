<?php

defined( 'ABSPATH' ) || exit;

/**
 * Preview Styles
*/
function ogio_preview_style() {
    wp_enqueue_style( 'ogio-preview', plugin_dir_url( __FILE__ ). '/preview.css', '1.0' );
    $plugin_images = plugin_dir_url(__DIR__). 'images';
    $preivew_image_css = '';
    if ( get_option( 'ogio_fallback_image' ) ) {
        $preview_image = get_option( 'ogio_fallback_image' );
        $preview_image = wp_get_attachment_url( $preview_image );
        $preivew_image_css = '.ogio-preview-image { background-image: url( "'.$preview_image.'" ); }';
    }
    $dynamic_css = "
        .ogio-preview-top { background-image: url( {$plugin_images}/preview-top.jpg ); }
        .ogio-preview-bottom { background-image: url( {$plugin_images}/preview-bottom.jpg ); }
        {$preivew_image_css}
        ";
    wp_add_inline_style( 'ogio-preview', $dynamic_css );
}

if( isset( $_GET['ogio_settings'] ) && $_GET['ogio_settings'] == 'true' ) {
    add_action( 'wp_enqueue_scripts', 'ogio_preview_style' );
}

/**
 * Remove other style sheets while in settings
 */
function ogio_remove_other_styles() {
    global $wp_styles;
    if( is_customize_preview() && ( isset( $_GET['ogio_settings'] ) && $_GET['ogio_settings'] == 'true' ) ) {
        $wp_styles->queue = array( 'ogio-preview' );
    }
}

add_action( 'wp_print_styles', 'ogio_remove_other_styles', 99 );