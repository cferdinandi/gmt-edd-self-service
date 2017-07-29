<?php

/**
 * Plugin Name: GMT EDD Self-Service
 * Plugin URI: https://github.com/cferdinandi/gmt-edd-self-service/
 * GitHub Plugin URI: https://github.com/cferdinandi/gmt-edd-self-service/
 * Description: Let buyers access downloads if they lose their original purchase receipt.
 * Version: 1.1.2
 * Author: Chris Ferdinandi
 * Author URI: http://gomakethings.com
 * Text Domain: edd_self_service
 * License: GPLv3
 */


// Define constants
define( 'GMT_EDD_SELF_SERVICE_VERSION', '1.1.2' );


// Includes
require_once( plugin_dir_path( __FILE__ ) . 'includes/wp-session-manager/wp-session-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/helpers.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/self-service.php' );


/**
 * Check the plugin version and make updates if needed
 */
function gmt_edd_self_service_check_version() {

	// Get plugin data
	$old_version = get_site_option( 'gmt_edd_self_service_version' );

	// Update plugin to current version number
	if ( empty( $old_version ) || version_compare( $old_version, GMT_EDD_SELF_SERVICE_VERSION, '<' ) ) {
		update_site_option( 'gmt_edd_self_service_version', GMT_EDD_SELF_SERVICE_VERSION );
	}

}
add_action( 'plugins_loaded', 'gmt_edd_self_service_check_version' );