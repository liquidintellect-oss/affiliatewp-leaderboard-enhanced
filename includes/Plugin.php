<?php
/**
 * Main plugin class.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AffiliateWPLeaderboardEnhanced\Ajax\LeaderboardAjaxHandler;
use AffiliateWPLeaderboardEnhanced\Leaderboard\AffWPReferralRepository;
use AffiliateWPLeaderboardEnhanced\Leaderboard\WeeklyLeaderboard;
use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;
use AffiliateWPLeaderboardEnhanced\Widget\LeaderboardWidget;

/**
 * Wires all components together and registers WordPress hooks.
 */
class Plugin {

	/**
	 * Register all plugin hooks and filters.
	 *
	 * @return void
	 */
	public function register(): void {
		$leaderboard = new WeeklyLeaderboard( new AffWPReferralRepository() );
		$shortcode   = new LeaderboardShortcode( $leaderboard );

		// Register the [affiliate_leaderboard_enhanced] shortcode.
		add_shortcode( 'affiliate_leaderboard_enhanced', array( $shortcode, 'render' ) );

		// Register the sidebar widget, passing the same shortcode instance so
		// widget output goes through the same render path.
		add_action(
			'widgets_init',
			function () use ( $shortcode ): void {
				register_widget( new LeaderboardWidget( $shortcode ) );
			}
		);

		// Register the AJAX actions that serve background-refresh requests.
		// Both nopriv (public visitors) and standard (logged-in users) are needed
		// because leaderboards typically appear on public-facing pages.
		$ajax_handler = new LeaderboardAjaxHandler( $shortcode );
		add_action( 'wp_ajax_affwp_lbe_refresh', array( $ajax_handler, 'handle' ) );
		add_action( 'wp_ajax_nopriv_affwp_lbe_refresh', array( $ajax_handler, 'handle' ) );

		// Enqueue front-end assets.
		add_action(
			'wp_enqueue_scripts',
			function (): void {
				wp_enqueue_style(
					'affiliatewp-leaderboard-enhanced',
					AFFWP_LBE_PLUGIN_URL . 'assets/css/leaderboard-enhanced.css',
					array(),
					AFFWP_LBE_VERSION
				);

				wp_enqueue_script(
					'affiliatewp-leaderboard-enhanced',
					AFFWP_LBE_PLUGIN_URL . 'assets/js/leaderboard-enhanced.js',
					array(),
					AFFWP_LBE_VERSION,
					true // Load in footer so the DOM is ready before the script runs.
				);

				wp_localize_script(
					'affiliatewp-leaderboard-enhanced',
					'affwpLbeData',
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'affwp_lbe_refresh' ),
					)
				);
			}
		);
	}
}
