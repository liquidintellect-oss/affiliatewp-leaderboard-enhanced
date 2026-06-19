<?php
/**
 * Shortcode: [affiliate_leaderboard_enhanced]
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Shortcode;

use AffiliateWPLeaderboardEnhanced\DatePeriod;
use AffiliateWPLeaderboardEnhanced\Leaderboard\LeaderboardEntry;
use AffiliateWPLeaderboardEnhanced\Leaderboard\WeeklyLeaderboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles attribute parsing and HTML rendering for [affiliate_leaderboard_enhanced].
 *
 * Shortcode attributes
 * --------------------
 * period           string  Date window to score. Default 'week'.
 *                          Accepts: week | year
 *                            week — rolling 7-day window starting on week_start day.
 *                            year — Jan 1 through Dec 31 of the current calendar year.
 * week_start       string  Day that begins the week (period=week only). Default 'monday'.
 *                          Accepts: sunday | monday | tuesday | wednesday |
 *                                   thursday | friday | saturday
 * number           int     Maximum affiliates to display. Default 10.
 * orderby          string  Sort metric: 'earnings' (default) or 'referrals'.
 * order            string  Sort direction: 'DESC' (default) or 'ASC'.
 * earnings         string  Show earnings column: 'yes' (default) or 'no'.
 * referrals        string  Show referral count column: 'yes' (default) or 'no'.
 * status           string  Comma-separated referral statuses. Default 'paid,unpaid'.
 * show_label       string  Show the period label above the list: 'yes' (default) or 'no'.
 * anonymize        string  Abbreviate affiliate last names: 'no' (default) or 'yes'.
 *                          e.g. "John Doe" becomes "John D.". Single-word names are unchanged.
 * refresh_interval int     Seconds between background AJAX refreshes. Default 0 (disabled).
 *
 * The rendered HTML applies position-specific CSS classes to the top 3 list items:
 *   .affwp-leaderboard-position-1  (1st place / gold)
 *   .affwp-leaderboard-position-2  (2nd place / silver)
 *   .affwp-leaderboard-position-3  (3rd place / bronze)
 * These classes are always emitted when there are enough entries — no shortcode
 * attribute is needed.  Theme authors and page-builder users can target them with
 * custom CSS to add badges, icons, background colours, etc.
 */
class LeaderboardShortcode {

	/**
	 * Attribute values that mean "do not show".
	 *
	 * Everything else (including the default 'yes', 'on', '1', or any unexpected
	 * value a page-builder might inject) is treated as "show".
	 *
	 * @var list<string>
	 */
	private const NO_VALUES = array( 'no', 'false', '0', '' );

	/**
	 * Constructor.
	 *
	 * @param WeeklyLeaderboard $leaderboard Leaderboard service.
	 */
	public function __construct(
		private readonly WeeklyLeaderboard $leaderboard
	) {}

	/**
	 * Parse shortcode attributes and return an empty AJAX wrapper.
	 *
	 * Content is loaded (and periodically refreshed) by the companion JavaScript
	 * via a `wp_ajax_nopriv_affwp_lbe_refresh` AJAX call.  The wrapper carries
	 * all query parameters as a JSON data attribute so the script can pass them
	 * back to the server on each request.
	 *
	 * @param array<string,string>|string $atts Raw shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'period'           => 'week',
				'week_start'       => 'monday',
				'number'           => '10',
				'orderby'          => 'earnings',
				'order'            => 'DESC',
				'earnings'         => 'yes',
				'referrals'        => 'yes',
				'status'           => 'paid,unpaid',
				'show_label'       => 'yes',
				'anonymize'        => 'no',
				'refresh_interval' => '0',
			),
			$atts,
			'affiliate_leaderboard_enhanced'
		);

		// Build the query-parameter bag that the JS will echo back on every
		// AJAX call.  refresh_interval is intentionally excluded — it controls
		// the browser timer only and the server never needs it.
		$params = array(
			'period'     => $atts['period'],
			'week_start' => $atts['week_start'],
			'number'     => $atts['number'],
			'orderby'    => $atts['orderby'],
			'order'      => $atts['order'],
			'earnings'   => $atts['earnings'],
			'referrals'  => $atts['referrals'],
			'status'     => $atts['status'],
			'show_label' => $atts['show_label'],
			'anonymize'  => $atts['anonymize'],
		);

		$refresh_interval = max( 0, (int) $atts['refresh_interval'] );

		return sprintf(
			'<div class="affwp-leaderboard-enhanced-wrap" data-affwp-lbe-params="%s" data-affwp-lbe-refresh="%d"></div>',
			esc_attr( (string) wp_json_encode( $params ) ),
			$refresh_interval
		);
	}

	/**
	 * Parse parameters and return the inner leaderboard HTML (no outer wrapper).
	 *
	 * Called by the AJAX handler on every background-refresh request.  The
	 * $params array is expected to carry the same keys as the shortcode
	 * attribute bag (minus `refresh_interval`).
	 *
	 * @param array<string,string> $params Shortcode-style parameter array.
	 * @return string Inner HTML (label + list, or empty-state paragraph).
	 */
	public function renderContent( array $params ): string {
		// Re-run through shortcode_atts so unknown/missing keys get safe defaults.
		$atts = shortcode_atts(
			array(
				'period'     => 'week',
				'week_start' => 'monday',
				'number'     => '10',
				'orderby'    => 'earnings',
				'order'      => 'DESC',
				'earnings'   => 'yes',
				'referrals'  => 'yes',
				'status'     => 'paid,unpaid',
				'show_label' => 'yes',
				'anonymize'  => 'no',
			),
			$params,
			'affiliate_leaderboard_enhanced'
		);

		$now    = new \DateTimeImmutable( 'now', wp_timezone() );
		$period = strtolower( trim( (string) $atts['period'] ) );

		if ( 'year' === $period ) {
			$range = DatePeriod::forCurrentYear( $now );
		} else {
			$dow   = DatePeriod::dayNameToInt( $atts['week_start'] );
			$range = DatePeriod::forDayOfWeek( $dow, $now );
		}

		$statuses = array_values( array_filter( array_map( 'trim', explode( ',', $atts['status'] ) ) ) );
		$number   = max( 1, (int) $atts['number'] );
		$orderby  = in_array( $atts['orderby'], array( 'earnings', 'referrals' ), true )
			? $atts['orderby']
			: 'earnings';
		$order    = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';

		$show_earnings  = ! in_array( strtolower( trim( (string) $atts['earnings'] ) ), self::NO_VALUES, true );
		$show_referrals = ! in_array( strtolower( trim( (string) $atts['referrals'] ) ), self::NO_VALUES, true );
		$show_label     = ! in_array( strtolower( trim( (string) $atts['show_label'] ) ), self::NO_VALUES, true );
		$anonymize      = ! in_array( strtolower( trim( (string) $atts['anonymize'] ) ), self::NO_VALUES, true );

		$entries = $this->leaderboard->build( $range, $statuses, $number, $orderby, $order );

		return $this->buildInnerHtml( $entries, $range, $show_earnings, $show_referrals, $show_label, $anonymize );
	}

	/**
	 * Assemble the leaderboard HTML from already-resolved data.
	 *
	 * Kept as a separate public method so unit tests can exercise the rendering
	 * logic without needing to mock the full WordPress + AffiliateWP stack.
	 *
	 * The outer wrapper div is included so that callers (e.g. the widget) get a
	 * self-contained HTML fragment.  The AJAX refresh path uses buildInnerHtml()
	 * directly so that the JS can inject only the inner content into an existing
	 * wrapper element without disturbing its data attributes.
	 *
	 * @param array<int,LeaderboardEntry> $entries        Ranked affiliate entries.
	 * @param DatePeriod                  $range          The date period (for the label).
	 * @param bool                        $show_earnings  Whether to show the earnings column.
	 * @param bool                        $show_referrals Whether to show the referral count column.
	 * @param bool                        $show_label     Whether to show the period label.
	 * @param bool                        $anonymize      Whether to abbreviate affiliate last names.
	 * @return string HTML (outer wrapper div + inner content).
	 */
	public function buildHtml(
		array $entries,
		DatePeriod $range,
		bool $show_earnings,
		bool $show_referrals,
		bool $show_label,
		bool $anonymize = false
	): string {
		return '<div class="affwp-leaderboard-enhanced-wrap">' . "\n"
			. $this->buildInnerHtml( $entries, $range, $show_earnings, $show_referrals, $show_label, $anonymize )
			. '</div>';
	}

	/**
	 * Build the inner leaderboard content — the label and ordered list (or empty
	 * state paragraph) — without the outer wrapper div.
	 *
	 * This is the fragment returned by the AJAX endpoint on every background
	 * refresh so the JavaScript can set wrapper.innerHTML without touching the
	 * wrapper's own data attributes.
	 *
	 * HTML is built by string concatenation rather than a mixed PHP/HTML
	 * template so that each <li> is a single compact line with no internal
	 * whitespace.  This prevents Elementor (and any other host that runs
	 * wpautop on shortcode output) from injecting spurious <p></p> tags
	 * into the list items.
	 *
	 * @param array<int,LeaderboardEntry> $entries        Ranked affiliate entries.
	 * @param DatePeriod                  $range          The date period (for the label).
	 * @param bool                        $show_earnings  Whether to show the earnings column.
	 * @param bool                        $show_referrals Whether to show the referral count column.
	 * @param bool                        $show_label     Whether to show the period label.
	 * @param bool                        $anonymize      Whether to abbreviate affiliate last names.
	 * @return string Inner HTML (no outer wrapper div).
	 */
	private function buildInnerHtml(
		array $entries,
		DatePeriod $range,
		bool $show_earnings,
		bool $show_referrals,
		bool $show_label,
		bool $anonymize = false
	): string {
		$sep  = '&nbsp;&nbsp;<span class="divider">|</span>&nbsp;&nbsp;';
		$html = '';

		if ( $show_label ) {
			$html .= '<p class="affwp-leaderboard-enhanced-label">'
				. esc_html( $range->label )
				. '</p>' . "\n";
		}

		if ( ! empty( $entries ) ) {
			$html .= '<ol class="affwp-leaderboard affwp-leaderboard-enhanced">' . "\n";

			$position = 0;
			foreach ( $entries as $entry ) {
				++$position;
				$parts = array();

				if ( $show_earnings ) {
					// wp_kses_post sanitises the currency output before it enters the
					// parts array so that later implode/concatenation stays safe.
					$parts[] = wp_kses_post( affwp_currency_filter( affwp_format_amount( $entry->earnings ) ) )
						. ' ' . esc_html__( 'earnings', 'affiliatewp-leaderboard-enhanced' );
				}

				if ( $show_referrals ) {
					$parts[] = absint( $entry->referral_count )
						. ' ' . esc_html__( 'referrals', 'affiliatewp-leaderboard-enhanced' );
				}

				// Name and stats spans are built entirely from individually-escaped
				// values so no outer sanitisation pass is needed — and avoiding one
				// prevents server-side kses configurations from stripping our spans.
				//
				// $name_span:  affiliate_name escaped by esc_html().
				// $stats_span: currency escaped by wp_kses_post(); count by absint();
				// labels escaped by esc_html__().
				$display_name = $anonymize
					? self::anonymizeName( $entry->affiliate_name )
					: $entry->affiliate_name;

				$name_span = '<span class="affwp-leaderboard-name">'
					. esc_html( $display_name )
					. '</span>';

				$stats_span = '';
				if ( ! empty( $parts ) ) {
					$stats_span = '<span class="affwp-leaderboard-stats">'
						. implode( $sep, $parts )
						. '</span>';
				}

				// Single compact line — no internal whitespace for wpautop to act on.
				// Top 3 positions receive a unique class for badge/styling customisation.
				$position_class = $position <= 3 ? ' class="affwp-leaderboard-position-' . $position . '"' : '';
				$html          .= '<li' . $position_class . '>' . $name_span . $stats_span . '</li>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$html .= '</ol>' . "\n";
		} else {
			$html .= '<p class="affwp-leaderboard-enhanced-empty">'
				. esc_html__( 'No affiliate activity for this period.', 'affiliatewp-leaderboard-enhanced' )
				. '</p>' . "\n";
		}

		return $html;
	}

	/**
	 * Abbreviate the last word of a name to its first letter followed by a period.
	 *
	 * Only applies when the name contains more than one word.  Single-word names
	 * (e.g. usernames or mononyms) are returned unchanged.
	 *
	 * Examples:
	 *   "John Doe"       → "John D."
	 *   "Mary Jane Watson" → "Mary Jane W."
	 *   "Alice"          → "Alice"
	 *
	 * @param string $name The affiliate's display name.
	 * @return string The anonymized name.
	 */
	public static function anonymizeName( string $name ): string {
		$parts = preg_split( '/\s+/', trim( $name ) );

		if ( false === $parts || count( $parts ) < 2 ) {
			return $name;
		}

		$last       = array_pop( $parts );
		$first_char = mb_substr( $last, 0, 1 );
		$parts[]    = $first_char . '.';

		return implode( ' ', $parts );
	}
}
