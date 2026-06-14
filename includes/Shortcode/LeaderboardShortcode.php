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
		$period = strtolower( $atts['period'] );

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

		$show_earnings  = 'yes' === strtolower( $atts['earnings'] );
		$show_referrals = 'yes' === strtolower( $atts['referrals'] );
		$show_label     = 'yes' === strtolower( $atts['show_label'] );

		$entries = $this->leaderboard->build( $range, $statuses, $number, $orderby, $order );

		return $this->buildHtml( $entries, $range, $show_earnings, $show_referrals, $show_label );
	}

	/**
	 * Assemble the leaderboard HTML from already-resolved data.
	 *
	 * Kept as a separate public method so unit tests can exercise the rendering
	 * logic without needing to mock the full WordPress + AffiliateWP stack.
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
		ob_start();
		?>
		<div class="affwp-leaderboard-week-wrap">
		<?php if ( $show_label ) : ?>
			<p class="affwp-leaderboard-week-label"><?php echo esc_html( $range->label ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $entries ) ) : ?>
			<ol class="affwp-leaderboard affwp-leaderboard-week">
			<?php foreach ( $entries as $entry ) : ?>
				<li>
					<?php echo esc_html( $entry->affiliate_name ); ?>
					<?php
					$parts = array();

					if ( $show_earnings ) {
						$parts[] = affwp_currency_filter( affwp_format_amount( $entry->earnings ) )
							. ' ' . esc_html__( 'earnings', 'affiliatewp-leaderboard-enhanced' );
					}

					if ( $show_referrals ) {
						$parts[] = absint( $entry->referral_count )
							. ' ' . esc_html__( 'referrals', 'affiliatewp-leaderboard-enhanced' );
					}

					if ( ! empty( $parts ) ) :
						$sep    = '&nbsp;&nbsp;<span class="divider">|</span>&nbsp;&nbsp;';
						$detail = implode( $sep, $parts );
						?>
						<p><?php echo wp_kses_post( $detail ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<p class="affwp-leaderboard-week-empty">
				<?php esc_html_e( 'No affiliate activity for this period.', 'affiliatewp-leaderboard-enhanced' ); ?>
			</p>
		<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
