<?php
/**
 * OG Image Overlay - WordPress Customizer Custom Controls
 *
 * Custom controls for the WordPress Customizer including
 * info controls and other specialized UI elements.
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
