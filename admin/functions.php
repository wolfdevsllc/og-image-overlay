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
 * Prepare and validate image data for processing with enhanced security
 * Security Fix: File size limits, memory estimation, and comprehensive validation
 *
 * @param int $featured_image_id Featured image attachment ID
 * @param array $config Validated configuration array
 * @return array|WP_Error Image data array or error
 */
function ogio_prepare_image_data( $featured_image_id, $config ) {
    $image_data = array();

        // Validate featured image with comprehensive security checks
    $featured_validation = ogio_validate_image_file( $featured_image_id, 'featured' );
    if ( is_wp_error( $featured_validation ) ) {
        return $featured_validation;
    }

    // Add featured data with proper prefixes
    foreach ( $featured_validation as $key => $value ) {
        $image_data[ 'featured_' . $key ] = $value;
    }

    // Validate overlay image with comprehensive security checks
    $overlay_validation = ogio_validate_image_file( $config['overlay_image_id'], 'overlay' );
    if ( is_wp_error( $overlay_validation ) ) {
        return $overlay_validation;
    }

    // Add overlay data with proper prefixes
    foreach ( $overlay_validation as $key => $value ) {
        $image_data[ 'overlay_' . $key ] = $value;
    }

    // Calculate total memory requirements
    $memory_check = ogio_check_memory_requirements( $image_data );
    if ( is_wp_error( $memory_check ) ) {
        return $memory_check;
    }

    // Validate overlay positioning doesn't exceed base image bounds
    $positioning_check = ogio_validate_overlay_positioning( $image_data, $config );
    if ( is_wp_error( $positioning_check ) ) {
        return $positioning_check;
    }

    return $image_data;
}

/**
 * Comprehensive image file validation with security checks
 * Security Fix: File size limits, content validation, and malicious file detection
 *
 * @param int $attachment_id WordPress attachment ID
 * @param string $type Image type (featured/overlay) for error context
 * @return array|WP_Error Validated image data or error
 */
function ogio_validate_image_file( $attachment_id, $type = 'image' ) {
    // Get file path
    $file_path = get_attached_file( $attachment_id );
    if ( ! $file_path || ! file_exists( $file_path ) ) {
        return new WP_Error( "missing_{$type}_file", "{$type} image file not found" );
    }

    // Security: Validate file path is within uploads directory
    $upload_dir = wp_upload_dir();
    $upload_basedir = wp_normalize_path( $upload_dir['basedir'] );
    $file_path_normalized = wp_normalize_path( $file_path );

    if ( strpos( $file_path_normalized, $upload_basedir ) !== 0 ) {
        return new WP_Error( "invalid_{$type}_path", "{$type} image path is outside uploads directory" );
    }

    // Check file size limits (default: 10MB max)
    $max_file_size = apply_filters( 'ogio_max_file_size', 10 * 1024 * 1024 ); // 10MB
    $file_size = filesize( $file_path );

    if ( $file_size === false ) {
        return new WP_Error( "cannot_read_{$type}_size", "Cannot read {$type} image file size" );
    }

    if ( $file_size > $max_file_size ) {
        return new WP_Error( "{$type}_too_large", "{$type} image file too large (max: " . size_format( $max_file_size ) . ")" );
    }

    // Validate MIME type from file extension
    $file_type = wp_check_filetype( $file_path );
    $allowed_types = array( 'image/jpeg', 'image/png', 'image/webp' );

    if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
        return new WP_Error( "unsupported_{$type}_format", "Unsupported {$type} image format: {$file_type['type']}" );
    }

    // Security: Validate actual file content matches MIME type
    $content_validation = ogio_validate_image_content( $file_path, $file_type['type'] );
    if ( is_wp_error( $content_validation ) ) {
        return new WP_Error( "invalid_{$type}_content", "Invalid {$type} image content: " . $content_validation->get_error_message() );
    }

    // Get and validate image dimensions
    $image_info = getimagesize( $file_path );
    if ( $image_info === false ) {
        return new WP_Error( "invalid_{$type}_dimensions", "Cannot read {$type} image dimensions" );
    }

    list( $width, $height, $image_type ) = $image_info;

    // Validate dimensions are reasonable (prevent memory exhaustion)
    $max_width = apply_filters( 'ogio_max_image_width', 4000 );
    $max_height = apply_filters( 'ogio_max_image_height', 4000 );

    if ( $width > $max_width || $height > $max_height ) {
        return new WP_Error( "{$type}_dimensions_too_large", "{$type} image dimensions too large (max: {$max_width}x{$max_height})" );
    }

    // Validate minimum dimensions
    $min_width = apply_filters( 'ogio_min_image_width', 50 );
    $min_height = apply_filters( 'ogio_min_image_height', 50 );

    if ( $width < $min_width || $height < $min_height ) {
        return new WP_Error( "{$type}_dimensions_too_small", "{$type} image dimensions too small (min: {$min_width}x{$min_height})" );
    }

    // Calculate estimated memory usage
    $channels = isset( $image_info['channels'] ) ? $image_info['channels'] : 3;
    $bits = isset( $image_info['bits'] ) ? $image_info['bits'] : 8;
    $memory_estimate = ( $width * $height * $channels * $bits / 8 ) * 2; // *2 for safety margin

    return array(
        'path' => $file_path,
        'type' => $file_type['type'],
        'width' => $width,
        'height' => $height,
        'size' => $file_size,
        'memory' => $memory_estimate,
        'image_type' => $image_type,
    );
}

/**
 * Validate image file content matches declared MIME type
 * Security Fix: Prevent malicious files disguised as images
 *
 * @param string $file_path Path to image file
 * @param string $expected_mime Expected MIME type
 * @return true|WP_Error True if valid, error if invalid
 */
function ogio_validate_image_content( $file_path, $expected_mime ) {
    // Read first few bytes to check file signature
    $file_handle = fopen( $file_path, 'rb' );
    if ( ! $file_handle ) {
        return new WP_Error( 'cannot_read_file', 'Cannot read image file' );
    }

    $file_header = fread( $file_handle, 12 );
    fclose( $file_handle );

    if ( $file_header === false ) {
        return new WP_Error( 'cannot_read_header', 'Cannot read file header' );
    }

    // Check file signatures against expected MIME types
    $valid_signature = false;

    switch ( $expected_mime ) {
        case 'image/jpeg':
            // JPEG signature: FF D8 FF
            $valid_signature = ( substr( $file_header, 0, 3 ) === "\xFF\xD8\xFF" );
            break;
        case 'image/png':
            // PNG signature: 89 50 4E 47 0D 0A 1A 0A
            $valid_signature = ( substr( $file_header, 0, 8 ) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" );
            break;
        case 'image/webp':
            // WebP signature: RIFF....WEBP
            $valid_signature = ( substr( $file_header, 0, 4 ) === 'RIFF' && substr( $file_header, 8, 4 ) === 'WEBP' );
            break;
    }

    if ( ! $valid_signature ) {
        return new WP_Error( 'invalid_signature', 'File signature does not match declared type' );
    }

    return true;
}

/**
 * Check total memory requirements for image processing
 * Security Fix: Prevent memory exhaustion attacks
 *
 * @param array $image_data Validated image data
 * @return true|WP_Error True if within limits, error if exceeds
 */
function ogio_check_memory_requirements( $image_data ) {
    // Calculate total memory needed
    $featured_memory = isset( $image_data['featured_memory'] ) ? $image_data['featured_memory'] : 0;
    $overlay_memory = isset( $image_data['overlay_memory'] ) ? $image_data['overlay_memory'] : 0;
    $total_memory_needed = $featured_memory + $overlay_memory;

    // Get current memory limit
    $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
    $memory_usage = memory_get_usage( true );
    $available_memory = $memory_limit - $memory_usage;

    // Apply safety margin (use only 70% of available memory)
    $safe_memory_limit = $available_memory * 0.7;

    if ( $total_memory_needed > $safe_memory_limit ) {
        return new WP_Error(
            'insufficient_memory',
            sprintf(
                'Insufficient memory for image processing. Required: %s, Available: %s',
                size_format( $total_memory_needed ),
                size_format( $safe_memory_limit )
            )
        );
    }

    return true;
}

/**
 * Validate overlay positioning relative to base image
 * Security Fix: Prevent out-of-bounds overlay positioning
 *
 * @param array $image_data Validated image data
 * @param array $config Plugin configuration
 * @return true|WP_Error True if valid, error if invalid
 */
function ogio_validate_overlay_positioning( $image_data, $config ) {
    $base_width = $image_data['featured_width'];
    $base_height = $image_data['featured_height'];
    $overlay_width = $image_data['overlay_width'];
    $overlay_height = $image_data['overlay_height'];

    $overlay_x = $config['overlay_x'];
    $overlay_y = $config['overlay_y'];

    // Check if overlay extends beyond base image boundaries
    if ( ( $overlay_x + $overlay_width ) > $base_width ) {
        return new WP_Error( 'overlay_exceeds_width', 'Overlay extends beyond base image width' );
    }

    if ( ( $overlay_y + $overlay_height ) > $base_height ) {
        return new WP_Error( 'overlay_exceeds_height', 'Overlay extends beyond base image height' );
    }

    return true;
}

/**
 * Process images and output with enhanced security and resource management
 * Security Fix: Execution time limits, memory monitoring, and secure GD library usage
 *
 * @param array $image_data Validated image data
 * @param array $config Validated configuration
 * @return true|WP_Error Success or error
 */
function ogio_process_and_output_image( $image_data, $config ) {
    $og_image = null;
    $overlay_image = null;
    $start_time = microtime( true );
    $start_memory = memory_get_usage( true );

    try {
        // Security: Set execution time limit for image processing
        $max_execution_time = apply_filters( 'ogio_max_execution_time', 30 );
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( $max_execution_time );
        }

        // Monitor memory usage throughout processing
        $memory_monitor = new OGIO_Memory_Monitor( $start_memory );

        // Create base image resource with enhanced error handling
        $og_image = ogio_create_image_resource( $image_data['featured_path'], $image_data['featured_type'] );
        if ( is_wp_error( $og_image ) ) {
            return $og_image;
        }

        $memory_monitor->check_memory( 'after base image creation' );

        // Verify base image dimensions match expected
        $actual_width = imagesx( $og_image );
        $actual_height = imagesy( $og_image );

        if ( $actual_width !== $image_data['featured_width'] || $actual_height !== $image_data['featured_height'] ) {
            return new WP_Error( 'dimension_mismatch', 'Base image dimensions do not match metadata' );
        }

        // Create overlay image resource with enhanced error handling
        $overlay_image = ogio_create_image_resource( $image_data['overlay_path'], $image_data['overlay_type'] );
        if ( is_wp_error( $overlay_image ) ) {
            return $overlay_image;
        }

        $memory_monitor->check_memory( 'after overlay image creation' );

        // Verify overlay image dimensions match expected
        $overlay_actual_width = imagesx( $overlay_image );
        $overlay_actual_height = imagesy( $overlay_image );

        if ( $overlay_actual_width !== $image_data['overlay_width'] || $overlay_actual_height !== $image_data['overlay_height'] ) {
            return new WP_Error( 'overlay_dimension_mismatch', 'Overlay image dimensions do not match metadata' );
        }

        // Security: Check execution time before heavy processing
        if ( ( microtime( true ) - $start_time ) > ( $max_execution_time - 5 ) ) {
            return new WP_Error( 'processing_timeout', 'Image processing taking too long' );
        }

        // Apply overlay with enhanced error checking
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

        $memory_monitor->check_memory( 'after overlay application' );

        // Clean up overlay resource immediately to free memory
        imagedestroy( $overlay_image );
        $overlay_image = null;

        // Prepare for output with security headers
        $output_result = ogio_output_processed_image( $og_image, $config, $memory_monitor );
        if ( is_wp_error( $output_result ) ) {
            return $output_result;
        }

        return true;

    } catch ( Exception $e ) {
        // Log exception for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'OGIO Exception: ' . $e->getMessage() );
        }
        return new WP_Error( 'processing_exception', 'Image processing failed due to exception' );

    } finally {
        // Always clean up resources
        if ( $og_image && is_resource( $og_image ) ) {
            imagedestroy( $og_image );
        }
        if ( $overlay_image && is_resource( $overlay_image ) ) {
            imagedestroy( $overlay_image );
        }

        // Log processing time for monitoring
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $processing_time = microtime( true ) - $start_time;
            $memory_used = memory_get_usage( true ) - $start_memory;
            error_log( sprintf( 'OGIO Processing: %.2fs, Memory: %s', $processing_time, size_format( $memory_used ) ) );
        }
    }
}

/**
 * Create image resource with enhanced error handling and security checks
 * Security Fix: Safe GD library usage with comprehensive error checking
 *
 * @param string $file_path Path to image file
 * @param string $mime_type MIME type of image
 * @return resource|WP_Error Image resource or error
 */
function ogio_create_image_resource( $file_path, $mime_type ) {
    // Final security check before processing
    if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
        return new WP_Error( 'file_not_accessible', 'Image file not accessible' );
    }

    // Turn off error reporting for GD functions to prevent information disclosure
    $old_error_reporting = error_reporting( 0 );

    try {
        $image_resource = false;

        switch ( $mime_type ) {
            case 'image/jpeg':
                $image_resource = imagecreatefromjpeg( $file_path );
                break;
            case 'image/png':
                $image_resource = imagecreatefrompng( $file_path );
                break;
            case 'image/webp':
                if ( function_exists( 'imagecreatefromwebp' ) ) {
                    $image_resource = imagecreatefromwebp( $file_path );
                } else {
                    return new WP_Error( 'webp_not_supported', 'WebP format not supported on this server' );
                }
                break;
            default:
                return new WP_Error( 'unsupported_format', 'Unsupported image format: ' . $mime_type );
        }

        if ( ! $image_resource || ! is_resource( $image_resource ) ) {
            return new WP_Error( 'gd_creation_failed', 'GD library failed to create image resource' );
        }

        return $image_resource;

    } finally {
        // Restore error reporting
        error_reporting( $old_error_reporting );
    }
}

/**
 * Output processed image with security headers and compression optimization
 * Security Fix: Secure headers and output validation
 *
 * @param resource $image_resource GD image resource
 * @param array $config Plugin configuration
 * @param OGIO_Memory_Monitor $memory_monitor Memory monitoring instance
 * @return true|WP_Error Success or error
 */
function ogio_output_processed_image( $image_resource, $config, $memory_monitor ) {
    // Clear any previous output and set security headers
    if ( ob_get_level() ) {
        ob_clean();
    }

    // Security headers
    nocache_headers();
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: DENY' );
    header( 'Content-Type: ' . $config['output_format'] );

    // Disable compression for images to avoid double compression
    if ( function_exists( 'apache_setenv' ) ) {
        @apache_setenv( 'no-gzip', '1' );
    }

    // Turn off error reporting for image output
    $old_error_reporting = error_reporting( 0 );

    try {
        $output_success = false;

        switch ( $config['output_format'] ) {
            case 'image/webp':
                if ( function_exists( 'imagewebp' ) ) {
                    $output_success = imagewebp( $image_resource, null, $config['output_quality'] );
                } else {
                    return new WP_Error( 'webp_output_not_supported', 'WebP output not supported' );
                }
                break;

            case 'image/png':
                // Convert quality (0-100) to PNG compression (0-9)
                $png_quality = 9 - round( ( $config['output_quality'] / 100 ) * 9 );
                $png_quality = max( 0, min( 9, $png_quality ) ); // Ensure valid range
                $output_success = imagepng( $image_resource, null, $png_quality );
                break;

            case 'image/jpeg':
            default:
                $quality = max( 1, min( 100, $config['output_quality'] ) ); // Ensure valid range
                $output_success = imagejpeg( $image_resource, null, $quality );
                break;
        }

        if ( ! $output_success ) {
            return new WP_Error( 'output_failed', 'Failed to output processed image' );
        }

        $memory_monitor->check_memory( 'after image output' );

        return true;

    } finally {
        // Restore error reporting
        error_reporting( $old_error_reporting );
    }
}

/**
 * Memory monitoring class for image processing
 * Security Fix: Prevent memory exhaustion during processing
 */
class OGIO_Memory_Monitor {
    private $start_memory;
    private $max_memory_increase;

    public function __construct( $start_memory ) {
        $this->start_memory = $start_memory;
        $this->max_memory_increase = apply_filters( 'ogio_max_memory_increase', 64 * 1024 * 1024 ); // 64MB default
    }

    public function check_memory( $checkpoint = '' ) {
        $current_memory = memory_get_usage( true );
        $memory_increase = $current_memory - $this->start_memory;

        if ( $memory_increase > $this->max_memory_increase ) {
            throw new Exception( "Memory limit exceeded at {$checkpoint}: " . size_format( $memory_increase ) );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $checkpoint ) {
            error_log( "OGIO Memory {$checkpoint}: " . size_format( $memory_increase ) );
        }
    }
}

/**
 * Get centralized security configuration for image processing
 * Security Fix: Centralized security settings with reasonable defaults
 *
 * @return array Security configuration array
 */
function ogio_get_security_config() {
    static $config = null;

    if ( null === $config ) {
        $config = array(
            // File size limits
            'max_file_size' => apply_filters( 'ogio_max_file_size', 10 * 1024 * 1024 ), // 10MB
            'max_memory_increase' => apply_filters( 'ogio_max_memory_increase', 64 * 1024 * 1024 ), // 64MB

            // Dimension limits
            'max_image_width' => apply_filters( 'ogio_max_image_width', 4000 ),
            'max_image_height' => apply_filters( 'ogio_max_image_height', 4000 ),
            'min_image_width' => apply_filters( 'ogio_min_image_width', 50 ),
            'min_image_height' => apply_filters( 'ogio_min_image_height', 50 ),

            // Processing limits
            'max_execution_time' => apply_filters( 'ogio_max_execution_time', 30 ), // 30 seconds

            // Allowed formats
            'allowed_mime_types' => apply_filters( 'ogio_allowed_mime_types', array(
                'image/jpeg',
                'image/png',
                'image/webp'
            ) ),

            // Memory safety margin (percentage of available memory to use)
            'memory_safety_margin' => apply_filters( 'ogio_memory_safety_margin', 0.7 ), // 70%
        );
    }

    return $config;
}

/**
 * Rewrite Rule for Open Graph Image URL
 */
function ogio_image_rewrite_rule() {
    add_rewrite_rule( 'ogio/([^/]+)','index.php?ogio=$matches[1]', 'top' );
}

add_action( 'init', 'ogio_image_rewrite_rule', 10 );

/**
 * Flush Rewrite Rules
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
