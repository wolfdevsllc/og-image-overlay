<?php

require_once('wp-load.php');

if ( isset( $_GET ) && isset( $_GET['p'] ) ) {
    $post_id = intval( esc_html( $_GET['p'] ) );
    generate_og_image( $post_id );
} else {
    wp_die( 'You can not access to the Open Graph Image without the post ID. Read the instructions properly and try again!' );
}
