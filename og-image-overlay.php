<?php
/*
Plugin Name: Open Graph Image Overlay
Plugin URI: https://itsmereal.com/plugins/open-graph-image-overlay
Description: Add automated image overlay on top of Open Graph images. This plugin extends the Open Graph features from Yoast SEO or Rank Math plugin.
Version: 1.0
Author: Al-Mamun Talukder
Author URI: https://itsmereal.com
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

$imrsmodsUpdateChecker->setAuthentication('9a66fe1daed377d241d6f7e51c6a32a65f129fb8');