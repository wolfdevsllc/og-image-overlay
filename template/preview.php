<?php
/**
 * OG Image Overlay - Customizer Preview Template
 *
 * HTML template for displaying the Open Graph image overlay preview
 * in the WordPress Customizer interface.
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
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_bloginfo('charset');?>" />
        <title><?php echo get_bloginfo('name'); ?></title>
        <?php if( is_customize_preview() ) wp_head();?>
	</head>
    <body class="ogio-preview" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
        <div id="ogio_preview_wrapper">
            <div class="ogio-preview-fbbox">
                <div class="ogio-preview-top"></div>
                <div class="ogio-preview-caption">Lorem ipsum dolor sit amet consectetur adipisicing elit. Amet alias nostrum magnam temporibus dolores laborum consequuntur accusamus..</div>
                <div class="ogio-preview-image">
                    <?php
                        if ( get_option( 'ogio_overlay_image' ) ) {
                        $overlay_image        = get_option( 'ogio_overlay_image' );
                        $overlay_image_url    = wp_get_attachment_url( $overlay_image );
                        $overlay_position_x   = get_option( 'ogio_overlay_position_x' );
                        $overlay_position_y   = get_option( 'ogio_overlay_position_y' );
                    ?>
                        <img
                        style="left: <?php echo $overlay_position_x ? $overlay_position_x : '0' ?>px; top: <?php echo  $overlay_position_y ? $overlay_position_y : '0' ?>px"
                        src="<?php echo esc_url( $overlay_image_url ); ?>" alt="<?php echo esc_attr__( 'Your Open Graph Overlay Image!', 'ogio' ); ?>" />
                    <?php } ?>
                </div>
                <div class="ogio-preview-share-info">
                    <div class="preview-link-info-icon" title="<?php echo esc_attr__( 'Make sure you consider this icon as it can cover the overlay a bit!', 'ogio' ); ?>"></div>
                    <div class="preview-link-domain"></div>
                    <div class="preview-link-title"></div>
                    <div class="preview-link-description"></div>
                </div>
                <div class="ogio-preview-bottom"></div>
                <div class="footer-small-disclaimer"><?php echo esc_html__( '* This preview is accurate only if you use the recommended 1200px by 630px sized image. Always test your output from page source code to make sure it is appropriate.', 'ogio' ); ?></div>
            </div>
            <div class="next-step-direction">
                <h3><?php echo esc_html__( 'How to Setup', 'ogio' ); ?></h3>
                <p><a onClick="window.open('https://vimeo.com/437133732', '_blank')"><img style="border: 1px solid #9e9e9e !important" src="https://itsmereal.com/wp-content/uploads/2020/07/ogio-video-300x187.png" alt="<?php echo esc_attr__( 'Watch How to Setup Open Graph Image Overlay Plugin', 'ogio' ); ?>" /></a></p>
                <h3><?php echo esc_html__( 'Open Graph Debugger', 'ogio' ); ?></h3>
                <p><?php echo esc_html__( 'Check how your page/posts are showing with new Open Graph Image Overlay in the following links:', 'ogio' ); ?></p>
                <p>
                    <ul>
                        <li><a onClick="window.open('https://developers.facebook.com/tools/debug/?q=<?php echo urlencode(site_url()); ?>', '_blank')"><?php echo esc_html__( 'Facebook Open Graph Debugger 🔗', 'ogio' ); ?></a></li>
                        <li><a onClick="window.open('https://cards-dev.twitter.com/validator', '_blank')"><?php echo esc_html__( 'Twitter Cards Debugger 🔗', 'ogio' ); ?></a></li>
                    </ul>
                </p>
                <h3 style="margin-top: 30px !important"><?php echo esc_html__( 'Not Using Yoast / Rank Math Plugin?', 'ogio' ); ?></h3>
                <p><?php echo esc_html__( 'This plugin works automatically along with Yoast SEO or Rank Math SEO plugin. If you are not using one of them but still want to use overlay image on top of your open graph images, you can manually set the link.', 'ogio' ); ?></p>
                <p><?php
                    printf(
                        /* translators: %s: POST_ID placeholder text */
                        esc_html__( 'You can use the following link for the URL replacement. The %s will need to be replaced with a variable.', 'ogio' ),
                        '<strong>' . esc_html__( 'POST_ID', 'ogio' ) . '</strong>'
                    );
                ?></p>
                <p><pre><?php echo esc_url( site_url() ); ?>/ogio/POST_ID</pre></p>
            </div>
        </div>
        <div class="itsmereal-footer"><?php
            printf(
                /* translators: %s: Author name link */
                esc_html__( 'Made with ♥️ for WordPress by %s', 'ogio' ),
                '<a href="" onClick="window.open(\'https://itsmereal.com/?ogio\', \'_blank\')">' . esc_html__( 'Al-Mamun Talukder', 'ogio' ) . '</a>'
            );
        ?></div>
    <?php if( is_customize_preview() ) wp_footer();?>
    </body>
</html>
