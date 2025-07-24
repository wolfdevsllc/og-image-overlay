<?php
/**
 * OG Image Overlay - WordPress Customizer Integration
 *
 * Customizer settings and controls for plugin configuration including
 * overlay images, positioning, output formats, and SEO plugin integration.
 *
 * @package    OG_Image_Overlay
 * @subpackage Admin
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
 * Add customizer fields and controls for OG Image Overlay plugin
 *
 * This function registers the customizer section, settings, and controls
 * for the OG Image Overlay plugin configuration. It creates a dedicated
 * section in the WordPress Customizer with all necessary options.
 *
 * @since 1.0.0
 * @param WP_Customize_Manager $wp_customize The WordPress Customizer Manager instance
 * @return void
 */
function ogio_customizer_fields( $wp_customize ) {

    /**
     * Include custom controls
     */

    require_once __DIR__ .'/custom-controls.php';

    $wp_customize->add_section( 'ogio_settings' , array(
        'title'      => 'OG Image Overlay',
        'priority'   => 100,
    ) );

    $wp_customize->add_setting('ogio_fallback_image', array(
        'capability'        => 'manage_options',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
    ) );

    $wp_customize->add_control( new WP_Customize_Media_Control($wp_customize, 'ogio_fallback_image', array(
        'label'       => __('Set Fallback Image', 'ogio'),
        'description' => __( 'Select an image for the fallback. This will be used when post featured image is not found. You can try out how your image here. Recommended size 1200px by 630px', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_fallback_image',
    ) ) );

    $wp_customize->add_setting('ogio_overlay_image', array(
        'capability'        => 'manage_options',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
    ) );

    $wp_customize->add_control( new WP_Customize_Media_Control($wp_customize, 'ogio_overlay_image', array(
        'label'       => __('Overlay Image', 'ogio'),
        'description' => __( 'Select an image for the overlay. This image should not exced the width of your featured images.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_overlay_image',
    ) ) );

    $wp_customize->add_setting( 'ogio_overlay_position_x' , array(
        'capability'        => 'manage_options',
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'ogio_validate_coordinate',
        'default'           => 0,
    ) );

        $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_overlay_position_x', array(
        'label'       => __('Overlay Position X', 'ogio'),
        'description' => __( 'Overlay image position in X axis', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_overlay_position_x',
        'type'        => 'number',
        'input_attrs' => array(
            'min'     => 0
        ),
    ) ) );

    $wp_customize->add_setting( 'ogio_overlay_position_y' , array(
        'capability'        => 'manage_options',
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'ogio_validate_coordinate',
        'default'           => 0,
    ) );

    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_overlay_position_y', array(
        'label'       => __('Overlay Position Y', 'ogio'),
        'description' => __( 'Overlay image position in Y axis', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_overlay_position_y',
        'type'        => 'number',
        'input_attrs' => array(
            'min'     => 0
        ),
    ) ) );

    $wp_customize->add_setting( 'ogio_select_seo_plugin', array(
        'capability'        => 'manage_options',
        'default'           => 'other',
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'ogio_validate_seo_plugin',
    ) );

    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_select_seo_plugin', array(
        'label'        => __('Select SEO Plugin', 'ogio'),
        'section'      => 'ogio_settings',
        'settings'     => 'ogio_select_seo_plugin',
        'type'         => 'radio',
        'choices'      => array(
            'yoast'    => 'Yoast Seo',
            'rankmath' => 'Rank Math',
            'other'    => 'Other or Disable automated integration'
        ),
    ) ) );

    $wp_customize->add_setting('ogio_image_source', array(
        'capability'        => 'manage_options',
        'default'           => 'default',
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'ogio_validate_image_source',
    ) );

    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_image_source', array(
        'label'       => __('Image Source', 'ogio'),
        'description' => __( 'Select the image source for the Open Graph Image. You can use the image set under "Social" tab on your Yoast SEO settings.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_image_source',
        'type'        => 'radio',
        'choices'     => array(
            'default' => 'Default - use the featured image',
            'yoast-fb'=> 'Yoast SEO - Facebook Image',
            'yoast-x' => 'Yoast SEO - X Image',
        ),
        'active_callback' => function() use ( $wp_customize ) {
            return $wp_customize->get_setting( 'ogio_select_seo_plugin' )->value() == 'yoast';
        },
    ) ) );

    $wp_customize->add_setting( 'ogio_image_output_format', array(
        'capability'        => 'manage_options',
        'default'           => 'image/jpeg',
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'ogio_validate_image_format',
    ) );

    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_image_output_format', array(
        'label'       => __('OG Image Output Format', 'ogio'),
        'description' => __( 'Select the image output format for the Open Graph Image. You may choose WebP format for smaller image size and better compatibility on messaging apps. Note: Not all platforms/apps support WebP format. Test on your target platforms/apps to see if it works.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_image_output_format',
        'type'        => 'radio',
        'choices'     => array(
            'image/jpeg' => 'JPEG',
            'image/png'  => 'PNG',
            'image/webp' => 'WebP',
        ),
    ) ) );

    $wp_customize->add_setting( 'ogio_image_output_quality', array(
        'capability'        => 'manage_options',
        'default'           => 75,
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'ogio_validate_image_quality',
    ) );

    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'ogio_image_output_quality', array(
        'label'       => __('OG Image Output Quality', 'ogio'),
        'description' => __( 'Select the image output quality for the Open Graph Image. This option can also help you to reduce the image size for better compatibility on messaging apps.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_image_output_quality',
        'type'        => 'number',
        'input_attrs' => array(
            'min'     => 0,
            'max'     => 100,
            'step'    => 1,
        ),
    ) ) );

    $wp_customize->add_setting( 'ogio_plugin_compatibility_notice', array() );
    $wp_customize->add_control(new Info_Custom_control($wp_customize,  'ogio_plugin_compatibility_notice', array(
        'label'       => __('This plugin works automatically along with either Yoast SEO or Rank Math SEO plugin. If you are not using any one of them, you will need to manually set the generated Open Graph Image.', 'ogio'),
        'settings'    => 'ogio_plugin_compatibility_notice',
        'section'     => 'ogio_settings',
    ) ) );
}

add_action( 'customize_register', 'ogio_customizer_fields' );

/**
 * Enhanced validation for coordinate positions
 * Security Fix: Comprehensive validation with reasonable limits
 *
 * @param WP_Customize_Validity $validity Validity object
 * @param mixed $value Input value to validate
 * @return WP_Customize_Validity
 */
function ogio_validate_coordinate( $validity, $value ) {
    // Convert to integer for validation
    $value = absint( $value );

    // Check if value is within reasonable bounds (0-2000 pixels)
    if ( $value < 0 ) {
        $validity->add( 'invalid_coordinate', __( 'Coordinate must be a positive number.', 'ogio' ) );
    } elseif ( $value > 2000 ) {
        $validity->add( 'coordinate_too_large', __( 'Coordinate cannot exceed 2000 pixels for performance reasons.', 'ogio' ) );
    }

    return $validity;
}

/**
 * Enhanced validation for image quality
 * Security Fix: Validate quality values within proper range
 *
 * @param WP_Customize_Validity $validity Validity object
 * @param mixed $value Input value to validate
 * @return WP_Customize_Validity
 */
function ogio_validate_image_quality( $validity, $value ) {
    $value = absint( $value );

    if ( $value < 1 ) {
        $validity->add( 'quality_too_low', __( 'Image quality must be at least 1.', 'ogio' ) );
    } elseif ( $value > 100 ) {
        $validity->add( 'quality_too_high', __( 'Image quality cannot exceed 100.', 'ogio' ) );
    }

    return $validity;
}

/**
 * Enhanced validation for image format selection
 * Security Fix: Validate against whitelist of allowed formats
 *
 * @param WP_Customize_Validity $validity Validity object
 * @param mixed $value Input value to validate
 * @return WP_Customize_Validity
 */
function ogio_validate_image_format( $validity, $value ) {
    $allowed_formats = array( 'image/jpeg', 'image/png', 'image/webp' );

    if ( ! in_array( $value, $allowed_formats, true ) ) {
        $validity->add( 'invalid_format', __( 'Please select a valid image format.', 'ogio' ) );
    }

    return $validity;
}

/**
 * Enhanced validation for SEO plugin selection
 * Security Fix: Validate against whitelist of allowed plugins
 *
 * @param WP_Customize_Validity $validity Validity object
 * @param mixed $value Input value to validate
 * @return WP_Customize_Validity
 */
function ogio_validate_seo_plugin( $validity, $value ) {
    $allowed_plugins = array( 'yoast', 'rankmath', 'other' );

    if ( ! in_array( $value, $allowed_plugins, true ) ) {
        $validity->add( 'invalid_plugin', __( 'Please select a valid SEO plugin option.', 'ogio' ) );
    }

    return $validity;
}

/**
 * Enhanced validation for image source selection
 * Security Fix: Validate against whitelist of allowed sources
 *
 * @param WP_Customize_Validity $validity Validity object
 * @param mixed $value Input value to validate
 * @return WP_Customize_Validity
 */
function ogio_validate_image_source( $validity, $value ) {
    $allowed_sources = array( 'default', 'yoast-fb', 'yoast-x' );

    if ( ! in_array( $value, $allowed_sources, true ) ) {
        $validity->add( 'invalid_source', __( 'Please select a valid image source.', 'ogio' ) );
    }

    return $validity;
}

/**
 * Legacy validation function for backward compatibility
 * @deprecated Use ogio_validate_coordinate instead
 */
function validate_required_number( $validity, $value ) {
    return ogio_validate_coordinate( $validity, $value );
}

/**
 * Legacy validation function for backward compatibility
 * @deprecated Use specific validation functions instead
 */
function validate_required_choice( $validity, $value ) {
    if ( empty( $value ) ) {
        $validity->add( 'required', __( 'You must select one of the choices.', 'ogio' ) );
    }
    return $validity;
}
