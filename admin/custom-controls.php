<?php

defined( 'ABSPATH' ) || exit;

/**
 * Custom Controls for WP Customizer
 * Following are some nice sources incase needed
 * @source https://github.com/maddisondesigns/customizer-custom-controls
 * @source https://madebydenis.com/adding-custom-controls-to-your-customization-api/
 * @source https://github.com/bueltge/Wordpress-Theme-Customizer-Custom-Controls
 */

if ( class_exists('WP_Customize_Control') ){
    /**
     * Info Custom Control
     * @parms title, description
     */
    class Info_Custom_control extends WP_Customize_Control{
        public $type = 'info';
        public function render_content() {
            ?>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <p><?php echo wp_kses_post($this->description); ?></p>
            <?php
        }
    }
}
