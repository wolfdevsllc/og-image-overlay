<?php

defined( 'ABSPATH' ) || exit;

/**
 * Security Fix: Safe helper function to check ogio_settings parameter
 * Replaces direct $_GET usage with proper sanitization and capability checks
 */
function ogio_is_settings_preview() {
    // Check if we're in a customize preview context and user has proper capabilities
    if ( ! is_customize_preview() ) {
        return false;
    }

    // Verify user has permission to manage options
    if ( ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    // Safely check and sanitize the parameter
    if ( ! isset( $_GET['ogio_settings'] ) ) {
        return false;
    }

    return 'true' === sanitize_text_field( wp_unslash( $_GET['ogio_settings'] ) );
}

/**
 * Less restrictive check for CSS loading during customizer
 * Used only for enqueueing styles, not for sensitive operations
 */
function ogio_should_load_preview_css() {
    // Basic check for the ogio_settings parameter
    if ( ! isset( $_GET['ogio_settings'] ) ) {
        return false;
    }

    // Sanitize the parameter
    $ogio_settings = sanitize_text_field( wp_unslash( $_GET['ogio_settings'] ) );

    // Allow CSS loading for customizer context
    return 'true' === $ogio_settings;
}

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
    if( ogio_is_settings_preview() ) {
        $template =  dirname(__DIR__) . '/template/preview.php';
    }
    return $template;
}

add_filter( 'template_include', 'ogio_preview_template', 30000 );

/**
 * Generate Open Graph Image with enhanced input validation and error handling
 * Security Fix: Added comprehensive input validation, sanitization, and proper error handling
 *
 * @param int $post_id The post ID to generate image for
 * @return void Outputs image directly or returns 404/500 error
 */
function generate_og_image( $post_id ) {
    // Enhanced Input Validation
    $post_id = absint( $post_id );
    if ( $post_id <= 0 ) {
        ogio_handle_image_error( 'Invalid post ID provided', 400 );
        return;
    }

    // Verify post exists and is published
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) {
        ogio_handle_image_error( 'Post not found or not published', 404 );
        return;
    }

    // Sanitize and validate configuration options
    $config = ogio_get_validated_config();
    if ( is_wp_error( $config ) ) {
        ogio_handle_image_error( $config->get_error_message(), 500 );
        return;
    }

    // Get and validate featured image
    $featured_image_id = ogio_get_featured_image_id( $post_id, $config['image_source'] );
    if ( ! $featured_image_id ) {
        ogio_handle_image_error( 'No valid featured image found', 404 );
        return;
    }

    // Validate overlay image
    if ( ! $config['overlay_image_id'] ) {
        ogio_handle_image_error( 'Overlay image not configured', 500 );
        return;
    }

    // Get validated image paths and metadata
    $image_data = ogio_prepare_image_data( $featured_image_id, $config );
    if ( is_wp_error( $image_data ) ) {
        ogio_handle_image_error( $image_data->get_error_message(), 500 );
        return;
    }

    // Process and output the image
    $result = ogio_process_and_output_image( $image_data, $config );
    if ( is_wp_error( $result ) ) {
        ogio_handle_image_error( $result->get_error_message(), 500 );
        return;
    }
}

/**
 * Handle image generation errors with proper HTTP responses
 * Security Fix: Replaces wp_die() with proper error handling
 *
 * @param string $message Error message for logging
 * @param int $status_code HTTP status code
 */
function ogio_handle_image_error( $message, $status_code = 500 ) {
    // Log error for debugging (only in debug mode)
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'OGIO Error: ' . $message );
    }

    // Set appropriate HTTP status
    status_header( $status_code );
    nocache_headers();

    // Return appropriate error response
    if ( 404 === $status_code ) {
        include( get_query_template( '404' ) );
    } else {
        // For 500 errors, return a generic message to avoid information disclosure
        header( 'Content-Type: text/plain' );
        echo 'Image generation failed';
    }
    exit;
}

/**
 * Get and validate plugin configuration options
 * Security Fix: Comprehensive validation of all configuration options
 *
 * @return array|WP_Error Validated configuration array or error
 */
function ogio_get_validated_config() {
    $config = array();

    // Validate image source
    $image_source = sanitize_text_field( get_option( 'ogio_image_source', 'default' ) );
    $valid_sources = array( 'default', 'yoast-fb', 'yoast-x' );
    $config['image_source'] = in_array( $image_source, $valid_sources, true ) ? $image_source : 'default';

    // Validate overlay image
    $overlay_image_id = absint( get_option( 'ogio_overlay_image' ) );
    if ( $overlay_image_id <= 0 || ! wp_attachment_is_image( $overlay_image_id ) ) {
        return new WP_Error( 'invalid_overlay', 'Invalid or missing overlay image configuration' );
    }
    $config['overlay_image_id'] = $overlay_image_id;

    // Validate fallback image
    $fallback_image_id = absint( get_option( 'ogio_fallback_image' ) );
    $config['fallback_image_id'] = ( $fallback_image_id > 0 && wp_attachment_is_image( $fallback_image_id ) ) ? $fallback_image_id : 0;

    // Validate overlay positions (must be non-negative integers)
    $overlay_x = absint( get_option( 'ogio_overlay_position_x', 0 ) );
    $overlay_y = absint( get_option( 'ogio_overlay_position_y', 0 ) );
    $config['overlay_x'] = min( $overlay_x, 2000 ); // Reasonable maximum
    $config['overlay_y'] = min( $overlay_y, 2000 ); // Reasonable maximum

    // Validate output format
    $output_format = sanitize_text_field( get_option( 'ogio_image_output_format', 'image/jpeg' ) );
    $valid_formats = array( 'image/jpeg', 'image/png', 'image/webp' );
    $config['output_format'] = in_array( $output_format, $valid_formats, true ) ? $output_format : 'image/jpeg';

    // Validate output quality (1-100)
    $output_quality = absint( get_option( 'ogio_image_output_quality', 75 ) );
    $config['output_quality'] = max( 1, min( $output_quality, 100 ) );

    return $config;
}

/**
 * Get featured image ID with validation based on image source setting
 * Security Fix: Proper sanitization and validation of post meta queries
 *
 * @param int $post_id Post ID
 * @param string $image_source Image source setting
 * @return int|false Featured image ID or false if none found
 */
function ogio_get_featured_image_id( $post_id, $image_source ) {
    $featured_image_id = 0;

    // Get image based on source setting
    if ( 'yoast-fb' === $image_source ) {
        $yoast_image_id = absint( get_post_meta( $post_id, '_yoast_wpseo_opengraph-image-id', true ) );
        $featured_image_id = ( $yoast_image_id > 0 ) ? $yoast_image_id : get_post_thumbnail_id( $post_id );
    } elseif ( 'yoast-x' === $image_source ) {
        $yoast_image_id = absint( get_post_meta( $post_id, '_yoast_wpseo_twitter-image-id', true ) );
        $featured_image_id = ( $yoast_image_id > 0 ) ? $yoast_image_id : get_post_thumbnail_id( $post_id );
    } else {
        $featured_image_id = get_post_thumbnail_id( $post_id );
    }

    // If no featured image, try fallback
    if ( ! $featured_image_id ) {
        $fallback_id = absint( get_option( 'ogio_fallback_image' ) );
        $featured_image_id = ( $fallback_id > 0 && wp_attachment_is_image( $fallback_id ) ) ? $fallback_id : 0;
    }

    // Validate the image exists and is actually an image
    if ( $featured_image_id > 0 && wp_attachment_is_image( $featured_image_id ) ) {
        return $featured_image_id;
    }

    return false;
}

/**
 * Prepare and validate image data for processing
 * Security Fix: Comprehensive file validation and path security
 *
 * @param int $featured_image_id Featured image attachment ID
 * @param array $config Validated configuration array
 * @return array|WP_Error Image data array or error
 */
function ogio_prepare_image_data( $featured_image_id, $config ) {
    $image_data = array();

    // Get and validate featured image path
    $featured_image_path = get_attached_file( $featured_image_id );
    if ( ! $featured_image_path || ! file_exists( $featured_image_path ) ) {
        return new WP_Error( 'missing_featured_image', 'Featured image file not found' );
    }

    // Validate featured image type
    $featured_image_type = wp_check_filetype( $featured_image_path );
    if ( ! in_array( $featured_image_type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
        return new WP_Error( 'unsupported_featured_format', 'Unsupported featured image format' );
    }

    $image_data['featured_path'] = $featured_image_path;
    $image_data['featured_type'] = $featured_image_type['type'];

    // Get and validate overlay image path
    $overlay_image_path = get_attached_file( $config['overlay_image_id'] );
    if ( ! $overlay_image_path || ! file_exists( $overlay_image_path ) ) {
        return new WP_Error( 'missing_overlay_image', 'Overlay image file not found' );
    }

    // Validate overlay image type
    $overlay_image_type = wp_check_filetype( $overlay_image_path );
    if ( ! in_array( $overlay_image_type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
        return new WP_Error( 'unsupported_overlay_format', 'Unsupported overlay image format' );
    }

    $image_data['overlay_path'] = $overlay_image_path;
    $image_data['overlay_type'] = $overlay_image_type['type'];

    // Get overlay image dimensions
    $overlay_meta = wp_get_attachment_image_src( $config['overlay_image_id'], 'full' );
    if ( ! $overlay_meta || ! isset( $overlay_meta[1], $overlay_meta[2] ) ) {
        return new WP_Error( 'invalid_overlay_meta', 'Could not get overlay image dimensions' );
    }

    $image_data['overlay_width'] = absint( $overlay_meta[1] );
    $image_data['overlay_height'] = absint( $overlay_meta[2] );

    // Validate dimensions are reasonable
    if ( $image_data['overlay_width'] > 3000 || $image_data['overlay_height'] > 3000 ) {
        return new WP_Error( 'overlay_too_large', 'Overlay image dimensions too large' );
    }

    return $image_data;
}

/**
 * Process images and output the result with proper error handling
 * Security Fix: Safe image processing with resource management and error handling
 *
 * @param array $image_data Validated image data
 * @param array $config Validated configuration
 * @return true|WP_Error Success or error
 */
function ogio_process_and_output_image( $image_data, $config ) {
    $og_image = null;
    $overlay_image = null;

    try {
        // Create base image resource
        switch ( $image_data['featured_type'] ) {
            case 'image/jpeg':
                $og_image = imagecreatefromjpeg( $image_data['featured_path'] );
                break;
            case 'image/png':
                $og_image = imagecreatefrompng( $image_data['featured_path'] );
                break;
            case 'image/webp':
                $og_image = imagecreatefromwebp( $image_data['featured_path'] );
                break;
        }

        if ( ! $og_image ) {
            return new WP_Error( 'failed_create_base', 'Failed to create base image resource' );
        }

        // Create overlay image resource
        switch ( $image_data['overlay_type'] ) {
            case 'image/jpeg':
                $overlay_image = imagecreatefromjpeg( $image_data['overlay_path'] );
                break;
            case 'image/png':
                $overlay_image = imagecreatefrompng( $image_data['overlay_path'] );
                break;
            case 'image/webp':
                $overlay_image = imagecreatefromwebp( $image_data['overlay_path'] );
                break;
        }

        if ( ! $overlay_image ) {
            return new WP_Error( 'failed_create_overlay', 'Failed to create overlay image resource' );
        }

        // Apply overlay
        $copy_result = imagecopy(
            $og_image,
            $overlay_image,
            $config['overlay_x'],
            $config['overlay_y'],
            0,
            0,
            $image_data['overlay_width'],
            $image_data['overlay_height']
        );

        if ( ! $copy_result ) {
            return new WP_Error( 'failed_overlay', 'Failed to apply overlay to image' );
        }

        // Clear output buffer and set headers
        ob_clean();
        flush();
        nocache_headers();
        header( 'Content-Type: ' . $config['output_format'] );

        // Output image in specified format
        $output_success = false;
        switch ( $config['output_format'] ) {
            case 'image/webp':
                $output_success = imagewebp( $og_image, null, $config['output_quality'] );
                break;
            case 'image/png':
                $png_quality = 9 - round( ( $config['output_quality'] / 100 ) * 9 );
                $output_success = imagepng( $og_image, null, $png_quality );
                break;
            case 'image/jpeg':
            default:
                $output_success = imagejpeg( $og_image, null, $config['output_quality'] );
                break;
        }

        if ( ! $output_success ) {
            return new WP_Error( 'failed_output', 'Failed to output processed image' );
        }

        return true;

    } finally {
        // Always clean up resources
        if ( $og_image ) {
            imagedestroy( $og_image );
        }
        if ( $overlay_image ) {
            imagedestroy( $overlay_image );
        }
    }
}

/**
 * Rewrite Rule for Open Graph Image URL
 */
function ogio_image_rewrite_rule() {
    add_rewrite_rule( 'ogio/([^/]+)','index.php?ogio=$matches[1]', 'top' );
}

add_action( 'init', 'ogio_image_rewrite_rule', 10 );

/**
 * Flus Rewrite Rules
 */
function ogio_flush_rewrite_rules_maybe() {
	if ( get_option( 'ogio_flush_rewrite_rules_flag' ) ) {
		flush_rewrite_rules();
        delete_option( 'ogio_flush_rewrite_rules_flag' );
    }

}

add_action( 'init', 'ogio_flush_rewrite_rules_maybe', 20 );

/**
 * Filter Query Var for Open Graph Image
 */
function ogio_register_query_var( $vars ) {
    $vars[] = 'ogio';
    return $vars;
}

add_filter( 'query_vars', 'ogio_register_query_var' );

/**
 * Handle Open Graph Image Generation via Template Redirect
 * Security Fix: Removed direct wp-load.php inclusion, using proper WordPress hooks
 */
function ogio_handle_image_request() {
    $post_id = get_query_var( 'ogio' );
    if ( ! empty( $post_id ) ) {
        // Sanitize and validate the post ID
        $post_id = absint( $post_id );
        if ( $post_id > 0 && get_post( $post_id ) ) {
            // Generate and output the image
            generate_og_image( $post_id );
            exit; // Prevent WordPress from continuing to process
        } else {
            // Invalid post ID - return 404
            status_header( 404 );
            nocache_headers();
            include( get_query_template( '404' ) );
            exit;
        }
    }
}

add_action( 'template_redirect', 'ogio_handle_image_request' );

/**
 * Change Open Graph Image URL for Yoast SEO Plugin
 */
function change_yoast_opengraph_image_url( $url ) {
    global $post;
    return $url = site_url().'/ogio/'.$post->ID;
}

/**
 * Change Open Graph Image URL for Rank Math Plugin
 */
function change_rankmath_opengraph_image_url( $attachment_url ) {
    global $post;
    return $attachment_url = site_url().'/ogio/'.$post->ID;
}

/**
 * Filter SEO Plugin's Open Graph Image URL With Ours
 */
if ( get_option( 'ogio_select_seo_plugin' ) ) {
    $seo_plugin = get_option( 'ogio_select_seo_plugin' );
    if ( $seo_plugin == 'yoast' ) {
        add_filter( 'wpseo_opengraph_image', 'change_yoast_opengraph_image_url' );
    } elseif ( $seo_plugin == 'rankmath' ) {
        add_filter( "rank_math/opengraph/facebook/image", 'change_rankmath_opengraph_image_url');
        add_filter( "rank_math/opengraph/twitter/image", 'change_rankmath_opengraph_image_url');
    }
}
