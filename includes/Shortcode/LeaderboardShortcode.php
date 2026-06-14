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
 * period      string  Date window to score. Default 'week'.
 *                     Accepts: week | year
 *                       week — rolling 7-day window starting on week_start day.
 *                       year — Jan 1 through Dec 31 of the current calendar year.
 * week_start  string  Day that begins the week (period=week only). Default 'monday'.
 *                     Accepts: sunday | monday | tuesday | wednesday |
 *                              thursday | friday | saturday
 * number      int     Maximum affiliates to display. Default 10.
 * orderby     string  Sort metric: 'earnings' (default) or 'referrals'.
 * order       string  Sort direction: 'DESC' (default) or 'ASC'.
 * earnings    string  Show earnings column: 'yes' (default) or 'no'.
 * referrals   string  Show referral count column: 'yes' (default) or 'no'.
 * status      string  Comma-separated referral statuses. Default 'paid,unpaid'.
 * show_label  string  Show the period label above the list: 'yes' (default) or 'no'.
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
	 * Parse shortcode attributes, query the leaderboard, and return HTML.
	 *
	 * @param array<string,string>|string $atts Raw shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ): string {
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
			),
			$atts,
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

		// Normalise boolean flags. Treat anything that is not an explicit "no"
		// as "yes" so that page-builder quote handling or extra whitespace never
		// silently disables a column.
		$show_earnings  = ! in_array( strtolower( trim( (string) $atts['earnings'] ) ), self::NO_VALUES, true );
		$show_referrals = ! in_array( strtolower( trim( (string) $atts['referrals'] ) ), self::NO_VALUES, true );
		$show_label     = ! in_array( strtolower( trim( (string) $atts['show_label'] ) ), self::NO_VALUES, true );

		$entries = $this->leaderboard->build( $range, $statuses, $number, $orderby, $order );

		return $this->buildHtml( $entries, $range, $show_earnings, $show_referrals, $show_label );
	}

	/**
	 * Assemble the leaderboard HTML from already-resolved data.
	 *
	 * Kept as a separate public method so unit tests can exercise the rendering
	 * logic without needing to mock the full WordPress + AffiliateWP stack.
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
	 * @return string HTML.
	 */
	public function buildHtml(
		array $entries,
		DatePeriod $range,
		bool $show_earnings,
		bool $show_referrals,
		bool $show_label
	): string {
		$sep  = '&nbsp;&nbsp;<span class="divider">|</span>&nbsp;&nbsp;';
		$html = '<div class="affwp-leaderboard-enhanced-wrap">' . "\n";

		if ( $show_label ) {
			$html .= '<p class="affwp-leaderboard-enhanced-label">'
				. esc_html( $range->label )
				. '</p>' . "\n";
		}

		if ( ! empty( $entries ) ) {
			$html .= '<ol class="affwp-leaderboard affwp-leaderboard-enhanced">' . "\n";

			foreach ( $entries as $entry ) {
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
				$name_span = '<span class="affwp-leaderboard-name">'
					. esc_html( $entry->affiliate_name )
					. '</span>';

				$stats_span = '';
				if ( ! empty( $parts ) ) {
					$stats_span = '<span class="affwp-leaderboard-stats">'
						. implode( $sep, $parts )
						. '</span>';
				}

				// Single compact line — no internal whitespace for wpautop to act on.
				$html .= '<li>' . $name_span . $stats_span . '</li>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$html .= '</ol>' . "\n";
		} else {
			$html .= '<p class="affwp-leaderboard-enhanced-empty">'
				. esc_html__( 'No affiliate activity for this period.', 'affiliatewp-leaderboard-enhanced' )
				. '</p>' . "\n";
		}

		$html .= '</div>';

		return $html;
	}
}
