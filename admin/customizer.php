<?php

defined( 'ABSPATH' ) || exit;

/**
 * Add Customizer Stuff
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
        'capability' => 'edit_theme_options',
        'type'       => 'option',
    ) );

    $wp_customize->add_control( new WP_Customize_Media_Control($wp_customize, 'ogio_fallback_image', array(
        'label'       => __('Set Fallback Image', 'ogio'),
        'description' => __( 'Select an image for the fallback. This will be used when post featured image is not found.You can try out how your image here.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_fallback_image',
    ) ) );

    $wp_customize->add_setting('ogio_overlay_image', array(
        'capability' => 'edit_theme_options',
        'type'       => 'option',
    ) );

    $wp_customize->add_control( new WP_Customize_Media_Control($wp_customize, 'ogio_overlay_image', array(
        'label'       => __('Overlay Image', 'ogio'),
        'description' => __( 'Select an image for the overlay. For full width, use 1200px wide image.', 'ogio' ),
        'section'     => 'ogio_settings',
        'settings'    => 'ogio_overlay_image',
    ) ) );

    $wp_customize->add_setting( 'ogio_overlay_position_x' , array(
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'validate_required_number',
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
        'transport'         => 'refresh',
        'type'              => 'option',
        'sanitize_callback' => 'absint',
        'validate_callback' => 'validate_required_number',
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
        'default'   => 'other',
        'transport' => 'refresh',
        'type'      => 'option',
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

    $wp_customize->add_setting( 'ogio_plugin_compatibility_notice', array() );
    $wp_customize->add_control(new Info_Custom_control($wp_customize,  'ogio_plugin_compatibility_notice', array(
        'label'       => __('This plugin works automatically along with either Yoast SEO or Rank Math SEO plugin. If you are not using any one of them, you will need to manually set the generated Open Graph Image.', 'ogio'),
        'settings'    => 'ogio_plugin_compatibility_notice',
        'section'     => 'ogio_settings',
    ) ) );
}

add_action( 'customize_register', 'ogio_customizer_fields' );

function validate_required_number( $validity, $value ) {
    if ( $value < 0 || $value == '' ) {
        $validity->add( 'required', __( 'You must supply a number.' ) );
    }
    return $validity;
}

function validate_required_choice( $validity, $value ) {
    if ( empty( $value ) ) {
        $validity->add( 'required', __( 'You must select one of the choices.' ) );
    }
    return $validity;
}