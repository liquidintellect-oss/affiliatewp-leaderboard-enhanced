<?php

use AffiliateWPLeaderboardEnhanced\WeekRange;
use PHPUnit\Framework\TestCase;

class WeekRangeTest extends TestCase {

	// ── dayNameToInt ─────────────────────────────────────────────────────────

	/** @test */
	public function day_name_to_int_maps_sunday_to_0(): void {
		$this->assertSame( 0, WeekRange::dayNameToInt( 'sunday' ) );
	}

	/** @test */
	public function day_name_to_int_maps_monday_to_1(): void {
		$this->assertSame( 1, WeekRange::dayNameToInt( 'monday' ) );
	}

	/** @test */
	public function day_name_to_int_maps_saturday_to_6(): void {
		$this->assertSame( 6, WeekRange::dayNameToInt( 'saturday' ) );
	}

	/** @test */
	public function day_name_to_int_is_case_insensitive(): void {
		$this->assertSame( 3, WeekRange::dayNameToInt( 'Wednesday' ) );
		$this->assertSame( 3, WeekRange::dayNameToInt( 'WEDNESDAY' ) );
	}

	/** @test */
	public function day_name_to_int_returns_1_for_unknown_input(): void {
		$this->assertSame( 1, WeekRange::dayNameToInt( 'funday' ) );
		$this->assertSame( 1, WeekRange::dayNameToInt( '' ) );
	}

	// ── forDayOfWeek: start time ─────────────────────────────────────────────

	/** @test */
	public function week_start_is_midnight_of_chosen_day(): void {
		// Friday (dow=5) choosing Monday (dow=1) → 4 days back → Monday 2026-06-08
		$now   = new DateTimeImmutable( '2026-06-12 15:30:00', new DateTimeZone( 'UTC' ) ); // Friday
		$range = WeekRange::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-08 00:00:00', $range->start );
	}

	/** @test */
	public function week_end_is_23_59_59_six_days_after_start(): void {
		$now   = new DateTimeImmutable( '2026-06-12 15:30:00', new DateTimeZone( 'UTC' ) ); // Friday
		$range = WeekRange::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-14 23:59:59', $range->end );
	}

	/** @test */
	public function week_is_always_exactly_seven_days(): void {
		$tz  = new DateTimeZone( 'UTC' );
		$now = new DateTimeImmutable( '2026-06-12', $tz );

		foreach ( range( 0, 6 ) as $dow ) {
			$range = WeekRange::forDayOfWeek( $dow, $now );

			$start_dt = new DateTimeImmutable( $range->start, $tz );
			$end_dt   = new DateTimeImmutable( $range->end, $tz );
			$diff     = (int) $start_dt->diff( $end_dt )->days;

			$this->assertSame( 6, $diff, "Expected 6-day span (0..6) for dow={$dow}" );
		}
	}

	// ── forDayOfWeek: edge cases ─────────────────────────────────────────────

	/** @test */
	public function week_starts_today_when_today_is_chosen_day(): void {
		// Wednesday (dow=3), choosing Wednesday
		$now   = new DateTimeImmutable( '2026-06-10 09:00:00', new DateTimeZone( 'UTC' ) ); // Wednesday
		$range = WeekRange::forDayOfWeek( 3, $now );

		$this->assertSame( '2026-06-10 00:00:00', $range->start );
		$this->assertSame( '2026-06-16 23:59:59', $range->end );
	}

	/** @test */
	public function sunday_start_day_wraps_correctly_from_saturday(): void {
		// Saturday (dow=6), choosing Sunday (dow=0) → 6 days back
		$now   = new DateTimeImmutable( '2026-06-13 20:00:00', new DateTimeZone( 'UTC' ) ); // Saturday
		$range = WeekRange::forDayOfWeek( 0, $now );

		$this->assertSame( '2026-06-07 00:00:00', $range->start );
		$this->assertSame( '2026-06-13 23:59:59', $range->end );
	}

	/** @test */
	public function sunday_start_day_on_a_sunday_starts_today(): void {
		// Sunday (dow=0), choosing Sunday → days_since = 0
		$now   = new DateTimeImmutable( '2026-06-07 08:00:00', new DateTimeZone( 'UTC' ) ); // Sunday
		$range = WeekRange::forDayOfWeek( 0, $now );

		$this->assertSame( '2026-06-07 00:00:00', $range->start );
		$this->assertSame( '2026-06-13 23:59:59', $range->end );
	}

	/** @test */
	public function monday_start_on_sunday_looks_back_six_days(): void {
		// Sunday (dow=0), choosing Monday (dow=1) → 6 days back to Monday
		$now   = new DateTimeImmutable( '2026-06-14 12:00:00', new DateTimeZone( 'UTC' ) ); // Sunday
		$range = WeekRange::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-08 00:00:00', $range->start );
		$this->assertSame( '2026-06-14 23:59:59', $range->end );
	}

	/** @test */
	public function friday_start_on_thursday_looks_back_six_days(): void {
		// Thursday (dow=4), choosing Friday (dow=5) → 6 days back to last Friday
		$now   = new DateTimeImmutable( '2026-06-11 10:00:00', new DateTimeZone( 'UTC' ) ); // Thursday
		$range = WeekRange::forDayOfWeek( 5, $now );

		$this->assertSame( '2026-06-05 00:00:00', $range->start );
		$this->assertSame( '2026-06-11 23:59:59', $range->end );
	}

	// ── forDayOfWeek: label ───────────────────────────────────────────────────

	/** @test */
	public function label_contains_formatted_start_and_end_dates(): void {
		// Monday 2026-06-08 → Sunday 2026-06-14
		$now   = new DateTimeImmutable( '2026-06-12', new DateTimeZone( 'UTC' ) ); // Friday
		$range = WeekRange::forDayOfWeek( 1, $now );

		$this->assertSame( 'Jun 8–Jun 14, 2026', $range->label );
	}

	/** @test */
	public function label_spans_month_boundary_correctly(): void {
		// Friday 2026-05-29, choosing Monday → Mon May 25 – Sun May 31
		$now   = new DateTimeImmutable( '2026-05-29', new DateTimeZone( 'UTC' ) ); // Friday
		$range = WeekRange::forDayOfWeek( 1, $now );

		$this->assertSame( 'May 25–May 31, 2026', $range->label );
	}

	// ── constructor ──────────────────────────────────────────────────────────

	/** @test */
	public function constructor_exposes_start_end_and_label(): void {
		$range = new WeekRange( '2026-06-08 00:00:00', '2026-06-14 23:59:59', 'Jun 8–Jun 14, 2026' );

		$this->assertSame( '2026-06-08 00:00:00', $range->start );
		$this->assertSame( '2026-06-14 23:59:59', $range->end );
		$this->assertSame( 'Jun 8–Jun 14, 2026', $range->label );
	}
}
