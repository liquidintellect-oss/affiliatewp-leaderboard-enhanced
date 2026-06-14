<?php

use AffiliateWPLeaderboardEnhanced\Leaderboard\LeaderboardEntry;
use AffiliateWPLeaderboardEnhanced\Leaderboard\ReferralRepositoryInterface;
use AffiliateWPLeaderboardEnhanced\Leaderboard\WeeklyLeaderboard;
use AffiliateWPLeaderboardEnhanced\DatePeriod;
use PHPUnit\Framework\TestCase;

// ── Fixtures ─────────────────────────────────────────────────────────────────

/**
 * Stub repository that returns programmer-supplied data.
 */
class StubReferralRepository implements ReferralRepositoryInterface {

	/** @var list<object> */
	private array $earningsRows = array();

	/** @var list<int> */
	private array $affiliateIds = array();

	/** @var array<int,string> */
	private array $names = array();

	/** @param list<object> $rows */
	public function setEarningsRows( array $rows ): void {
		$this->earningsRows = $rows;
	}

	/** @param list<int> $ids */
	public function setAffiliateIds( array $ids ): void {
		$this->affiliateIds = $ids;
	}

	/** @param array<int,string> $names */
	public function setNames( array $names ): void {
		$this->names = $names;
	}

	public function getEarningsSummedByAffiliate( DatePeriod $range, array $statuses ): array {
		return $this->earningsRows;
	}

	public function getAffiliateIdsForReferrals( DatePeriod $range, array $statuses ): array {
		return $this->affiliateIds;
	}

	public function getAffiliateName( int $affiliate_id ): string {
		return $this->names[ $affiliate_id ] ?? "Affiliate #{$affiliate_id}";
	}
}

// ── Tests ─────────────────────────────────────────────────────────────────────

class WeeklyLeaderboardTest extends TestCase {

	private StubReferralRepository $repo;
	private WeeklyLeaderboard $leaderboard;
	private DatePeriod $range;

	protected function setUp(): void {
		WP_Mock::setUp();
		$this->repo        = new StubReferralRepository();
		$this->leaderboard = new WeeklyLeaderboard( $this->repo );
		$this->range       = new DatePeriod( '2026-06-08 00:00:00', '2026-06-14 23:59:59', 'Jun 8–Jun 14, 2026' );
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
	}

	// ── empty state ───────────────────────────────────────────────────────────

	/** @test */
	public function returns_empty_array_when_no_referrals_in_period(): void {
		$this->repo->setEarningsRows( array() );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( array(), $result );
	}

	// ── earnings sorting ──────────────────────────────────────────────────────

	/** @test */
	public function sorts_by_earnings_descending_by_default(): void {
		$this->seedTwoAffiliates( earnings_a: 100.0, earnings_b: 200.0 );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 2, $result[0]->affiliate_id ); // higher earner first
		$this->assertSame( 1, $result[1]->affiliate_id );
	}

	/** @test */
	public function sorts_by_earnings_ascending_when_requested(): void {
		$this->seedTwoAffiliates( earnings_a: 100.0, earnings_b: 200.0 );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'ASC' );

		$this->assertSame( 1, $result[0]->affiliate_id ); // lower earner first
		$this->assertSame( 2, $result[1]->affiliate_id );
	}

	// ── referral count sorting ────────────────────────────────────────────────

	/** @test */
	public function sorts_by_referral_count_descending(): void {
		// Affiliate 1: $50, 5 referrals   Affiliate 2: $200, 2 referrals
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 50.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 200.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 1, 1, 1, 2, 2 ) ); // 5 for #1, 2 for #2
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'referrals', 'DESC' );

		$this->assertSame( 1, $result[0]->affiliate_id ); // 5 referrals wins
		$this->assertSame( 5, $result[0]->referral_count );
	}

	/** @test */
	public function sorts_by_referral_count_ascending(): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 50.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 200.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 1, 1, 1, 2, 2 ) );
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'referrals', 'ASC' );

		$this->assertSame( 2, $result[0]->affiliate_id ); // 2 referrals first in ASC
	}

	// ── tiebreaker sorting ───────────────────────────────────────────────────

	/** @test */
	public function earnings_tie_broken_by_referral_count_descending(): void {
		// Both affiliates earned $100; affiliate 2 has more referrals → ranks first.
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 100.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 100.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 2, 2, 2 ) ); // 2 for #1, 3 for #2
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 2, $result[0]->affiliate_id ); // more referrals breaks tie
		$this->assertSame( 1, $result[1]->affiliate_id );
	}

	/** @test */
	public function earnings_tie_broken_by_referral_count_ascending(): void {
		// Both earned $100; affiliate 1 has fewer referrals → ranks first in ASC.
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 100.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 100.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 2, 2, 2 ) ); // 2 for #1, 3 for #2
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'ASC' );

		$this->assertSame( 1, $result[0]->affiliate_id ); // fewer referrals first in ASC
		$this->assertSame( 2, $result[1]->affiliate_id );
	}

	/** @test */
	public function referral_count_tie_broken_by_earnings_descending(): void {
		// Both have 3 referrals; affiliate 2 earned more → ranks first.
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 50.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 200.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 1, 2, 2, 2 ) ); // 3 each
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'referrals', 'DESC' );

		$this->assertSame( 2, $result[0]->affiliate_id ); // higher earnings breaks tie
		$this->assertSame( 1, $result[1]->affiliate_id );
	}

	/** @test */
	public function referral_count_tie_broken_by_earnings_ascending(): void {
		// Both have 3 referrals; affiliate 1 earned less → ranks first in ASC.
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 50.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 200.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 1, 1, 2, 2, 2 ) ); // 3 each
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'referrals', 'ASC' );

		$this->assertSame( 1, $result[0]->affiliate_id ); // lower earnings first in ASC
		$this->assertSame( 2, $result[1]->affiliate_id );
	}

	// ── number limit ──────────────────────────────────────────────────────────

	/** @test */
	public function slices_results_to_requested_number(): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 300.0 ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => 200.0 ),
				(object) array( 'affiliate_id' => 3, 'amount_sum' => 100.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 2, 3 ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 2, 'earnings', 'DESC' );

		$this->assertCount( 2, $result );
		$this->assertSame( 1, $result[0]->affiliate_id );
		$this->assertSame( 2, $result[1]->affiliate_id );
	}

	// ── entry properties ──────────────────────────────────────────────────────

	/** @test */
	public function entry_carries_affiliate_name_from_repository(): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 7, 'amount_sum' => 99.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 7, 7, 7 ) );
		$this->repo->setNames( array( 7 => 'Jane Smith' ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 'Jane Smith', $result[0]->affiliate_name );
	}

	/** @test */
	public function entry_earnings_match_amount_sum_from_repository(): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => 420.50 ),
			)
		);
		$this->repo->setAffiliateIds( array( 1 ) );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 420.50, $result[0]->earnings );
	}

	/** @test */
	public function entry_referral_count_equals_occurrences_in_id_list(): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 5, 'amount_sum' => 10.0 ),
			)
		);
		$this->repo->setAffiliateIds( array( 5, 5, 5, 5 ) ); // 4 referrals

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 4, $result[0]->referral_count );
	}

	/** @test */
	public function entry_referral_count_defaults_to_zero_when_not_in_id_list(): void {
		// Earnings row exists but no id in the flat list (edge case).
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 9, 'amount_sum' => 50.0 ),
			)
		);
		$this->repo->setAffiliateIds( array() );

		$result = $this->leaderboard->build( $this->range, array( 'paid' ), 10, 'earnings', 'DESC' );

		$this->assertSame( 0, $result[0]->referral_count );
	}

	// ── helpers ───────────────────────────────────────────────────────────────

	private function seedTwoAffiliates( float $earnings_a, float $earnings_b ): void {
		$this->repo->setEarningsRows(
			array(
				(object) array( 'affiliate_id' => 1, 'amount_sum' => $earnings_a ),
				(object) array( 'affiliate_id' => 2, 'amount_sum' => $earnings_b ),
			)
		);
		$this->repo->setAffiliateIds( array( 1, 2 ) );
		$this->repo->setNames( array( 1 => 'Alice', 2 => 'Bob' ) );
	}
}
