<?php defined( 'ABSPATH' ) || exit; ?>
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
                        src="<?php echo $overlay_image_url; ?>" alt="Your Open Graph Overlay Image!" />
                    <?php } ?>
                </div>
                <div class="ogio-preview-share-info">
                    <div class="preview-link-info-icon" title="Make sure you consider this icon as it can cover the overlay a bit!"></div>
                    <div class="preview-link-domain"></div>
                    <div class="preview-link-title"></div>
                    <div class="preview-link-description"></div>
                </div>
                <div class="ogio-preview-bottom"></div>
                <div class="footer-small-disclaimer">* This preview is accurate only if you use the recommended 1200px by 630px sized image. Always test your output from page source code to make sure it is appropriate.</div>
            </div>
            <div class="next-step-direction">
                <h3>How to Setup</h3>
                <p><a onClick="window.open('https://vimeo.com/437133732', '_blank')"><img style="border: 1px solid #9e9e9e !important" src="https://itsmereal.com/wp-content/uploads/2020/07/ogio-video-300x187.png" alt="Watch How to Setup Open Graph Image Overlay Plugin" /></a></p>
                <h3>Open Graph Debugger</h3>
                <p>Check how your page/posts are showing with new Open Graph Image Overlay in the following links:</p>
                <p>
                    <ul>
                        <li><a onClick="window.open('https://developers.facebook.com/tools/debug/?q=<?php echo urlencode(site_url()); ?>', '_blank')">Facebook Open Graph Debugger 🔗</a></li>
                        <li><a onClick="window.open('https://cards-dev.twitter.com/validator', '_blank')">Twitter Cards Debugger 🔗</a></li>
                    </ul>
                </p>
                <h3 style="margin-top: 30px !important">Not Using Yoast / Rank Math Plugin?</h3>
                <p>This plugin works automatically along with Yoast SEO or Rank Math SEO plugin. If you are not using one of them but still want to use overlay image on top of your open graph images, you can manually set the link.</p>
                <p>You can use the following link for the URL replacement. The <strong>POST_ID</strong> will need to be replaced with a variable.</p>
                <p><pre><?php echo plugin_dir_url( __DIR__ ); ?>generate-og-image.php?p=POST_ID</pre></p>
            </div>
        </div>
        <div class="itsmereal-footer">Made with ♥️ for WordPress by <a href='' onClick="window.open('https://itsmereal.com/?ogio', '_blank')">Al-Mamun Talukder</a></div>
    <?php if( is_customize_preview() ) wp_footer();?>
    </body>
</html>
