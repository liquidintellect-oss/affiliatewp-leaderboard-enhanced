<?php

use AffiliateWPLeaderboardEnhanced\DatePeriod;
use PHPUnit\Framework\TestCase;

class DatePeriodTest extends TestCase {

	// ── dayNameToInt ─────────────────────────────────────────────────────────

	/** @test */
	public function day_name_to_int_maps_sunday_to_0(): void {
		$this->assertSame( 0, DatePeriod::dayNameToInt( 'sunday' ) );
	}

	/** @test */
	public function day_name_to_int_maps_monday_to_1(): void {
		$this->assertSame( 1, DatePeriod::dayNameToInt( 'monday' ) );
	}

	/** @test */
	public function day_name_to_int_maps_saturday_to_6(): void {
		$this->assertSame( 6, DatePeriod::dayNameToInt( 'saturday' ) );
	}

	/** @test */
	public function day_name_to_int_is_case_insensitive(): void {
		$this->assertSame( 3, DatePeriod::dayNameToInt( 'Wednesday' ) );
		$this->assertSame( 3, DatePeriod::dayNameToInt( 'WEDNESDAY' ) );
	}

	/** @test */
	public function day_name_to_int_returns_1_for_unknown_input(): void {
		$this->assertSame( 1, DatePeriod::dayNameToInt( 'funday' ) );
		$this->assertSame( 1, DatePeriod::dayNameToInt( '' ) );
	}

	// ── forDayOfWeek: start / end times ──────────────────────────────────────

	/** @test */
	public function week_start_is_midnight_of_chosen_day(): void {
		// Friday (dow=5) choosing Monday (dow=1) → 4 days back → Mon 2026-06-08
		$now   = new DateTimeImmutable( '2026-06-12 15:30:00', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-08 00:00:00', $range->start );
	}

	/** @test */
	public function week_end_is_23_59_59_six_days_after_start(): void {
		$now   = new DateTimeImmutable( '2026-06-12 15:30:00', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-14 23:59:59', $range->end );
	}

	/** @test */
	public function week_is_always_exactly_seven_days(): void {
		$tz  = new DateTimeZone( 'UTC' );
		$now = new DateTimeImmutable( '2026-06-12', $tz );

		foreach ( range( 0, 6 ) as $dow ) {
			$range    = DatePeriod::forDayOfWeek( $dow, $now );
			$start_dt = new DateTimeImmutable( $range->start, $tz );
			$end_dt   = new DateTimeImmutable( $range->end, $tz );
			$diff     = (int) $start_dt->diff( $end_dt )->days;

			$this->assertSame( 6, $diff, "Expected 6-day span (0..6) for dow={$dow}" );
		}
	}

	// ── forDayOfWeek: edge cases ──────────────────────────────────────────────

	/** @test */
	public function week_starts_today_when_today_is_chosen_day(): void {
		$now   = new DateTimeImmutable( '2026-06-10 09:00:00', new DateTimeZone( 'UTC' ) ); // Wednesday
		$range = DatePeriod::forDayOfWeek( 3, $now ); // chose Wednesday

		$this->assertSame( '2026-06-10 00:00:00', $range->start );
		$this->assertSame( '2026-06-16 23:59:59', $range->end );
	}

	/** @test */
	public function sunday_start_day_wraps_correctly_from_saturday(): void {
		$now   = new DateTimeImmutable( '2026-06-13 20:00:00', new DateTimeZone( 'UTC' ) ); // Saturday
		$range = DatePeriod::forDayOfWeek( 0, $now ); // chose Sunday

		$this->assertSame( '2026-06-07 00:00:00', $range->start );
		$this->assertSame( '2026-06-13 23:59:59', $range->end );
	}

	/** @test */
	public function sunday_start_day_on_a_sunday_starts_today(): void {
		$now   = new DateTimeImmutable( '2026-06-07 08:00:00', new DateTimeZone( 'UTC' ) ); // Sunday
		$range = DatePeriod::forDayOfWeek( 0, $now );

		$this->assertSame( '2026-06-07 00:00:00', $range->start );
		$this->assertSame( '2026-06-13 23:59:59', $range->end );
	}

	/** @test */
	public function monday_start_on_sunday_looks_back_six_days(): void {
		$now   = new DateTimeImmutable( '2026-06-14 12:00:00', new DateTimeZone( 'UTC' ) ); // Sunday
		$range = DatePeriod::forDayOfWeek( 1, $now );

		$this->assertSame( '2026-06-08 00:00:00', $range->start );
		$this->assertSame( '2026-06-14 23:59:59', $range->end );
	}

	/** @test */
	public function friday_start_on_thursday_looks_back_six_days(): void {
		$now   = new DateTimeImmutable( '2026-06-11 10:00:00', new DateTimeZone( 'UTC' ) ); // Thursday
		$range = DatePeriod::forDayOfWeek( 5, $now );

		$this->assertSame( '2026-06-05 00:00:00', $range->start );
		$this->assertSame( '2026-06-11 23:59:59', $range->end );
	}

	// ── forDayOfWeek: label ───────────────────────────────────────────────────

	/** @test */
	public function week_label_contains_formatted_start_and_end_dates(): void {
		$now   = new DateTimeImmutable( '2026-06-12', new DateTimeZone( 'UTC' ) ); // Friday
		$range = DatePeriod::forDayOfWeek( 1, $now );

		$this->assertSame( 'Jun 8–Jun 14, 2026', $range->label );
	}

	/** @test */
	public function week_label_spans_month_boundary_correctly(): void {
		$now   = new DateTimeImmutable( '2026-05-29', new DateTimeZone( 'UTC' ) ); // Friday
		$range = DatePeriod::forDayOfWeek( 1, $now );

		$this->assertSame( 'May 25–May 31, 2026', $range->label );
	}

	// ── forCurrentYear ────────────────────────────────────────────────────────

	/** @test */
	public function year_start_is_jan_1_midnight(): void {
		$now   = new DateTimeImmutable( '2026-06-14 15:30:00', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forCurrentYear( $now );

		$this->assertSame( '2026-01-01 00:00:00', $range->start );
	}

	/** @test */
	public function year_end_is_dec_31_at_23_59_59(): void {
		$now   = new DateTimeImmutable( '2026-06-14 15:30:00', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forCurrentYear( $now );

		$this->assertSame( '2026-12-31 23:59:59', $range->end );
	}

	/** @test */
	public function year_label_is_the_four_digit_year(): void {
		$now   = new DateTimeImmutable( '2026-06-14', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forCurrentYear( $now );

		$this->assertSame( '2026', $range->label );
	}

	/** @test */
	public function year_covers_correct_year_on_jan_1(): void {
		$now   = new DateTimeImmutable( '2026-01-01 00:00:00', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forCurrentYear( $now );

		$this->assertSame( '2026-01-01 00:00:00', $range->start );
		$this->assertSame( '2026-12-31 23:59:59', $range->end );
	}

	/** @test */
	public function year_covers_correct_year_on_dec_31(): void {
		$now   = new DateTimeImmutable( '2026-12-31 23:59:59', new DateTimeZone( 'UTC' ) );
		$range = DatePeriod::forCurrentYear( $now );

		$this->assertSame( '2026-01-01 00:00:00', $range->start );
		$this->assertSame( '2026-12-31 23:59:59', $range->end );
	}

	/** @test */
	public function year_changes_correctly_across_new_year(): void {
		$now2025 = new DateTimeImmutable( '2025-12-31 23:59:58', new DateTimeZone( 'UTC' ) );
		$now2026 = new DateTimeImmutable( '2026-01-01 00:00:01', new DateTimeZone( 'UTC' ) );

		$this->assertSame( '2025', DatePeriod::forCurrentYear( $now2025 )->label );
		$this->assertSame( '2026', DatePeriod::forCurrentYear( $now2026 )->label );
	}

	// ── constructor ───────────────────────────────────────────────────────────

	/** @test */
	public function constructor_exposes_start_end_and_label(): void {
		$period = new DatePeriod( '2026-06-08 00:00:00', '2026-06-14 23:59:59', 'Jun 8–Jun 14, 2026' );

		$this->assertSame( '2026-06-08 00:00:00', $period->start );
		$this->assertSame( '2026-06-14 23:59:59', $period->end );
		$this->assertSame( 'Jun 8–Jun 14, 2026', $period->label );
	}
}
