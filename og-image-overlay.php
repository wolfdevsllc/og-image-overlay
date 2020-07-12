<?php
/*
Plugin Name: Open Graph Image Overlay
Plugin URI: https://itsmereal.com/plugins/open-graph-image-overlay
Description: Add automated image overlay on top of Open Graph images. This plugin extends the Open Graph features from Yoast SEO or Rank Math plugin.
Version: 1.2
Author: Al-Mamun Talukder
Author URI: https://itsmereal.com
Requires at least: 4.3
Tested up to: 5.4.1
License: GPLv2 or later
Text Domain: ogio
*/

defined( 'ABSPATH' ) || exit;

/**
 * Include Template Parts
 */

require_once __DIR__ . '/admin/functions.php';
require_once __DIR__ . '/admin/customizer.php';
require_once __DIR__ . '/template/config.php';


/**
 *	Plugin Update Checker
 *  @source https://github.com/YahnisElsts/plugin-update-checker
 */

require_once __DIR__ . '/includes/update-checker/plugin-update-checker.php';

$imrsmodsUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/itsmereal/og-image-overlay',
	__FILE__,
	'og-image-overlay'
);

/**
 * Add settings link to plugin actions
 */

function ogio_add_plugin_link ( $links ) {
	$link = add_query_arg(
        array(
            'url'           => urlencode( site_url( '/?ogio_settings=true' ) ),
            'return'        => urlencode( admin_url() ),
            'ogio_settings' => 'true',
        ),
        'customize.php?autofocus[section]=ogio_settings'
    );
    $settings_link = array(
        '<a href="' . admin_url( $link ) . '">Settings</a>',
    );
    return array_merge( $links, $settings_link );
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ogio_add_plugin_link' );
