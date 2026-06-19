<?php
/**
 * AJAX handler for background leaderboard refresh.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Ajax;

use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles wp_ajax_affwp_lbe_refresh and wp_ajax_nopriv_affwp_lbe_refresh.
 *
 * Validates the nonce, sanitises the incoming parameter bag, delegates
 * rendering to LeaderboardShortcode::renderContent(), and returns a JSON
 * response containing the inner leaderboard HTML.
 *
 * Expected POST fields
 * --------------------
 * nonce  string  WordPress nonce created with wp_create_nonce( 'affwp_lbe_refresh' ).
 * params string  JSON-encoded shortcode parameter bag (period, week_start, number, …).
 */
class LeaderboardAjaxHandler {

	/**
	 * Constructor.
	 *
	 * @param LeaderboardShortcode $shortcode Shortcode renderer to delegate to.
	 */
	public function __construct(
		private readonly LeaderboardShortcode $shortcode
	) {}

	/**
	 * Handle the incoming AJAX request.
	 *
	 * Verifies the nonce, decodes the parameter bag, and returns a JSON
	 * success response whose `data.html` key contains the rendered inner HTML.
	 *
	 * @return void
	 */
	public function handle(): void {
		check_ajax_referer( 'affwp_lbe_refresh', 'nonce' );

		// Decode the JSON parameter bag sent by the JavaScript client.
		// wp_unslash removes any magic-quotes escaping WordPress adds to $_POST,
		// then sanitize_text_field strips control characters from the raw string.
		// The individual values are sanitised again after json_decode() below.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing — nonce already verified above via check_ajax_referer
		$params_raw = isset( $_POST['params'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['params'] ) ) : '{}';
		$params     = json_decode( $params_raw, true );

		if ( ! is_array( $params ) ) {
			$params = array();
		}

		// Sanitise every value to a plain string before passing to renderContent(),
		// which will run the result through shortcode_atts() for a second layer of
		// validation and default-filling.
		$safe_params = array_map( 'sanitize_text_field', array_filter( $params, 'is_string' ) );

		$html = $this->shortcode->renderContent( $safe_params );

		wp_send_json_success( array( 'html' => $html ) );
	}
}
