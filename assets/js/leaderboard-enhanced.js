/* AffiliateWP Leaderboard Enhanced — AJAX background refresh
 *
 * On DOMContentLoaded, every wrapper element that carries a
 * `data-affwp-lbe-params` attribute is populated via a background AJAX call.
 * If the element also carries a non-zero `data-affwp-lbe-refresh` value the
 * call repeats on that interval (in seconds).
 *
 * Global dependencies (provided by wp_localize_script):
 *   window.affwpLbeData.ajaxUrl  — WordPress admin-ajax.php URL
 *   window.affwpLbeData.nonce    — Nonce for 'affwp_lbe_refresh'
 */
(function () {
	'use strict';

	var data    = window.affwpLbeData || {};
	var ajaxUrl = data.ajaxUrl || '';
	var nonce   = data.nonce   || '';

	/**
	 * Fetch fresh leaderboard HTML for a single wrapper element and inject it.
	 *
	 * @param {HTMLElement} wrapper
	 */
	function fetchLeaderboard( wrapper ) {
		var rawParams = wrapper.getAttribute( 'data-affwp-lbe-params' ) || '{}';
		var params;

		try {
			params = JSON.parse( rawParams );
		} catch ( e ) {
			return;
		}

		var body = new URLSearchParams();
		body.append( 'action', 'affwp_lbe_refresh' );
		body.append( 'nonce',  nonce );
		body.append( 'params', JSON.stringify( params ) );

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        body,
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					return null;
				}
				return response.json();
			} )
			.then( function ( json ) {
				if ( json && json.success && json.data && typeof json.data.html === 'string' ) {
					wrapper.innerHTML = json.data.html;
				}
			} )
			.catch( function () {
				/* Silent failure — the existing (empty) wrapper remains in place. */
			} );
	}

	/**
	 * Initialise all leaderboard wrappers found in the current document.
	 */
	function init() {
		var wrappers = document.querySelectorAll( '[data-affwp-lbe-params]' );

		wrappers.forEach( function ( wrapper ) {
			// Trigger the first (immediate) content load.
			fetchLeaderboard( wrapper );

			// Schedule recurring refreshes when a positive interval is configured.
			var interval = parseInt( wrapper.getAttribute( 'data-affwp-lbe-refresh' ) || '0', 10 );
			if ( interval > 0 ) {
				setInterval( function () {
					fetchLeaderboard( wrapper );
				}, interval * 1000 );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		// DOMContentLoaded already fired (e.g. script loaded in footer after parse).
		init();
	}
}() );
