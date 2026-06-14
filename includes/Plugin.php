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

		// Enqueue front-end stylesheet.
		add_action(
			'wp_enqueue_scripts',
			function (): void {
				wp_enqueue_style(
					'affiliatewp-leaderboard-enhanced',
					AFFWP_LBE_PLUGIN_URL . 'assets/css/leaderboard-enhanced.css',
					array(),
					AFFWP_LBE_VERSION
				);
			}
		);
	}
}
