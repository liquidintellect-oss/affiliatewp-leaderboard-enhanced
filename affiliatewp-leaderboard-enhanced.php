<?php
/**
 * Plugin Name:       AffiliateWP - Leaderboard Enhanced
 * Plugin URI:        https://liquidintellect.com
 * Description:       Displays an affiliate leaderboard scoped to a rolling current week, with the week-start day configurable by the site admin.
 * Version:           @projectVersion@
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Liquid Intellect, Inc. - OSS
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       affiliatewp-leaderboard-enhanced
 * Domain Path:       /languages
 * Requires Plugins:  affiliate-wp
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants — available to all classes that need paths / URLs / version.
define( 'AFFWP_LBE_VERSION', '@projectVersion@' );
define( 'AFFWP_LBE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFFWP_LBE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFFWP_LBE_PLUGIN_FILE', __FILE__ );

// Autoloader — maps AffiliateWPLeaderboardEnhanced\ to includes/.
spl_autoload_register(
	function ( string $class_name ): void {
		$prefix   = 'AffiliateWPLeaderboardEnhanced\\';
		$base_dir = __DIR__ . '/includes/';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

// Bootstrap after all plugins are loaded so AffiliateWP is available.
add_action(
	'plugins_loaded',
	function (): void {
		if ( ! function_exists( 'affiliate_wp' ) ) {
			return;
		}
		( new AffiliateWPLeaderboardEnhanced\Plugin() )->register();
	},
	100
);
