<?php
/**
 * Week range value object.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable value object representing a 7-day week window.
 *
 * Start/end are MySQL datetime strings in the site's configured timezone,
 * ready to be passed directly to AffiliateWP's get_referrals() date filter.
 *
 * The week always starts on the most recent occurrence of the chosen day of
 * the week (which may be today) and runs through 6 days later, inclusive.
 */
class WeekRange {

	/**
	 * MySQL datetime string for the first moment of the week (00:00:00).
	 *
	 * @var string
	 */
	public readonly string $start;

	/**
	 * MySQL datetime string for the last moment of the week (23:59:59).
	 *
	 * @var string
	 */
	public readonly string $end;

	/**
	 * Human-readable label, e.g. "Jun 10–16, 2026".
	 *
	 * @var string
	 */
	public readonly string $label;

	/**
	 * Constructor.
	 *
	 * @param string $start MySQL datetime (Y-m-d H:i:s).
	 * @param string $end   MySQL datetime (Y-m-d H:i:s).
	 * @param string $label Human-readable range label.
	 */
	public function __construct( string $start, string $end, string $label ) {
		$this->start = $start;
		$this->end   = $end;
		$this->label = $label;
	}

	/**
	 * Map a day name to a PHP date('w') integer (0 = Sunday … 6 = Saturday).
	 *
	 * Returns 1 (Monday) for any unrecognised input.
	 *
	 * @param string $name Day name, e.g. 'monday' (case-insensitive).
	 * @return int<0,6>
	 */
	public static function dayNameToInt( string $name ): int {
		$map = array(
			'sunday'    => 0,
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
		);

		return $map[ strtolower( trim( $name ) ) ] ?? 1;
	}

	/**
	 * Build a WeekRange whose week begins on $chosen_dow.
	 *
	 * Formula: days_since = (today_dow - chosen_dow + 7) % 7
	 *   - Result is 0 when today IS the chosen day → week starts today.
	 *   - Otherwise counts back to the most recent past occurrence.
	 *
	 * @param int                $chosen_dow Day-of-week integer (0=Sun … 6=Sat).
	 * @param \DateTimeImmutable $now        Reference point in the site's timezone.
	 * @return self
	 */
	public static function forDayOfWeek( int $chosen_dow, \DateTimeImmutable $now ): self {
		$today_dow  = (int) $now->format( 'w' );
		$days_since = ( $today_dow - $chosen_dow + 7 ) % 7;

		$start = $now->modify( "-{$days_since} days" )->setTime( 0, 0, 0 );
		$end   = $start->modify( '+6 days' )->setTime( 23, 59, 59 );

		return new self(
			$start->format( 'Y-m-d H:i:s' ),
			$end->format( 'Y-m-d H:i:s' ),
			$start->format( 'M j' ) . '–' . $end->format( 'M j, Y' ),
		);
	}
}
