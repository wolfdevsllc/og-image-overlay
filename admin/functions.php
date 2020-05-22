<?php

defined( 'ABSPATH' ) || exit;

/**
 * Add Menu Page
 */
function ogio_add_menu_link() {
    $link = add_query_arg(
        array(
            'url'           => urlencode( site_url( '/?ogio_settings=true' ) ),
            'return'        => urlencode( admin_url() ),
            'ogio_settings' => 'true',
        ),
        'customize.php?autofocus[section]=ogio_settings'
    );

    add_submenu_page( 'options-general.php',
        __( 'OG Image Overlay', 'ogio' ),
        __( 'OG Image Overlay', 'ogio' ),
        'manage_options',
        $link
    );
}

add_action ( 'admin_init', 'ogio_add_menu_link' );

/**
 * Add Template for Customizer Preview
 */
function ogio_preview_template( $template ) {
    if( is_customize_preview() && ( isset( $_GET['ogio_settings'] ) && $_GET['ogio_settings'] == 'true' ) ) {
        $template =  dirname(__DIR__) . '/template/preview.php';
    }
    return $template;
}

add_filter( 'template_include', 'ogio_preview_template', 30000 );

/**
 * Generate Open Graph Image
 * @param integer post ID
 */
function generate_og_image( $post_id ) {

    if ( ! $post_id ) {

        wp_die( 'Please correctly configure the Open Graph Image Overlay settings.' );

    } else {

        if ( get_post_thumbnail_id( $post_id ) ) {
            $featured_image = get_post_thumbnail_id( $post_id );
        } else {
            if ( get_option( 'ogio_fallback_image' ) ) {
                $featured_image = get_option( 'ogio_fallback_image' );
            } else {
                wp_die( 'Please correctly configure the Open Graph Image Overlay settings.' );
            }
        }

        if ( get_option( 'ogio_overlay_image' ) ) {
            $overlay_image  = get_option( 'ogio_overlay_image' );
        } else {
            wp_die( 'Please correctly configure the Open Graph Image Overlay settings.' );
        }

        $overlay_x      = get_option( 'ogio_overlay_position_x' );
        $overlay_y      = get_option( 'ogio_overlay_position_y' );

        $overlay_type   = get_post_mime_type( $overlay_image );

        $overlay_image_meta = wp_get_attachment_image_src( $overlay_image , 'full' );
        $overlay_width  = $overlay_image_meta['1'];
        $overlay_height = $overlay_image_meta['2'];

        $ogImage  = imagecreatefromjpeg( get_attached_file( $featured_image ) );
        if ( $overlay_type == 'image/jpeg' ) {
            $addition = imagecreatefromjpeg( get_attached_file( $overlay_image ) );
        } elseif ( $overlay_type == 'image/png' ) {
            $addition = imagecreatefrompng( get_attached_file( $overlay_image ) );
        }
        imagecopy( $ogImage, $addition, $overlay_x, $overlay_y, 0, 0, $overlay_width, $overlay_height );
        header('Content-Type: image/png');
        imagepng( $ogImage );
        imagedestroy($ogImage);
        imagedestroy($addition);
    }
}

/**
 * Change Open Graph Image URL for Yoast SEO Plugin
 */
function change_yoast_opengraph_image_url( $url ) {
    global $post;
    return $url = plugin_dir_url( __DIR__ ) . 'generate-og-image.php?p=' . $post->ID;
}

/**
 * Change Open Graph Image URL for Rank Math Plugin
 */
function change_rankmath_opengraph_image_url( $attachment_url ) {
    global $post;
    return $attachment_url = plugin_dir_url( __DIR__ ) . 'generate-og-image.php?p=' . $post->ID;
}

if ( get_option( 'ogio_select_seo_plugin' ) ) {
    $seo_plugin = get_option( 'ogio_select_seo_plugin' );
    if ( $seo_plugin == 'yoast' ) {
        add_filter( 'wpseo_opengraph_image', 'change_yoast_opengraph_image_url' );
    } elseif ( $seo_plugin == 'rankmath' ) {
        add_filter( "rank_math/opengraph/facebook/image", 'change_rankmath_opengraph_image_url');
        add_filter( "rank_math/opengraph/twitter/image", 'change_rankmath_opengraph_image_url');
    }
}
