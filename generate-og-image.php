<?php

require_once('wp-load.php');

$post_id = get_query_var('ogio');
if ( !empty( $post_id ) ) {
    generate_og_image( $post_id );
} else {
    wp_die( 'You can not access to the Open Graph Image without the post ID.' );
}
