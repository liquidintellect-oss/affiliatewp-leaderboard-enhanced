<?php

use AffiliateWPLeaderboardEnhanced\Leaderboard\LeaderboardEntry;
use PHPUnit\Framework\TestCase;

class LeaderboardEntryTest extends TestCase {

	/** @test */
	public function constructor_stores_all_properties(): void {
		$entry = new LeaderboardEntry(
			affiliate_id:   42,
			affiliate_name: 'Jane Smith',
			earnings:       123.45,
			referral_count: 7,
		);

		$this->assertSame( 42, $entry->affiliate_id );
		$this->assertSame( 'Jane Smith', $entry->affiliate_name );
		$this->assertSame( 123.45, $entry->earnings );
		$this->assertSame( 7, $entry->referral_count );
	}

	/** @test */
	public function zero_values_are_stored_correctly(): void {
		$entry = new LeaderboardEntry(
			affiliate_id:   1,
			affiliate_name: 'New Affiliate',
			earnings:       0.0,
			referral_count: 0,
		);

		$this->assertSame( 0.0, $entry->earnings );
		$this->assertSame( 0, $entry->referral_count );
	}
}
