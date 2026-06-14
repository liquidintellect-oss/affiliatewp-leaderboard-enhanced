<?php

use AffiliateWPLeaderboardEnhanced\Leaderboard\LeaderboardEntry;
use AffiliateWPLeaderboardEnhanced\Leaderboard\WeeklyLeaderboard;
use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;
use AffiliateWPLeaderboardEnhanced\DatePeriod;
use PHPUnit\Framework\TestCase;

class LeaderboardShortcodeTest extends TestCase {

	private DatePeriod $range;

	protected function setUp(): void {
		WP_Mock::setUp();
		$this->range = new DatePeriod( '2026-06-08 00:00:00', '2026-06-14 23:59:59', 'Jun 8–Jun 14, 2026' );
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
	}

	// ── buildHtml: empty state ────────────────────────────────────────────────

	/** @test */
	public function build_html_shows_empty_message_when_no_entries(): void {
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );

		$html = $shortcode->buildHtml( array(), $this->range, true, true, false );

		$this->assertStringContainsString( 'affwp-leaderboard-enhanced-empty', $html );
		$this->assertStringNotContainsString( '<ol', $html );
	}

	// ── buildHtml: label ──────────────────────────────────────────────────────

	/** @test */
	public function build_html_includes_date_label_when_show_label_is_true(): void {
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );

		$html = $shortcode->buildHtml( array(), $this->range, false, false, true );

		$this->assertStringContainsString( 'affwp-leaderboard-enhanced-label', $html );
		$this->assertStringContainsString( 'Jun 8', $html );
	}

	/** @test */
	public function build_html_omits_date_label_when_show_label_is_false(): void {
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );

		$html = $shortcode->buildHtml( array(), $this->range, false, false, false );

		$this->assertStringNotContainsString( 'affwp-leaderboard-enhanced-label', $html );
	}

	// ── buildHtml: entries ────────────────────────────────────────────────────

	/** @test */
	public function build_html_renders_affiliate_names(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Jane Smith', earnings: 100.0, count: 3 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'Jane Smith', $html );
	}

	/** @test */
	public function build_html_renders_multiple_entries_in_order(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 200.0, count: 5 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 100.0, count: 2 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$alice_pos = strpos( $html, 'Alice' );
		$bob_pos   = strpos( $html, 'Bob' );
		$this->assertLessThan( $bob_pos, $alice_pos, 'Alice should appear before Bob' );
	}

	/** @test */
	public function build_html_shows_earnings_when_flag_is_true(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 420.50, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, true, false, false );

		$this->assertStringContainsString( '420.50', $html );
		$this->assertStringContainsString( 'earnings', $html );
	}

	/** @test */
	public function build_html_omits_earnings_when_flag_is_false(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 420.50, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringNotContainsString( 'earnings', $html );
	}

	/** @test */
	public function build_html_shows_referral_count_when_flag_is_true(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 7 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, true, false );

		$this->assertStringContainsString( '7', $html );
		$this->assertStringContainsString( 'referrals', $html );
	}

	/** @test */
	public function build_html_omits_referral_count_when_flag_is_false(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 7 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringNotContainsString( 'referrals', $html );
	}

	/** @test */
	public function build_html_wraps_entries_in_ordered_list(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'affwp-leaderboard-enhanced', $html );
		$this->assertStringContainsString( '<ol', $html );
		$this->assertStringContainsString( '<li>', $html );
	}

	/** @test */
	public function build_html_wraps_name_in_name_span(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'class="affwp-leaderboard-name"', $html );
		$this->assertStringContainsString( '<span class="affwp-leaderboard-name">Alice</span>', $html );
	}

	/** @test */
	public function build_html_wraps_stats_in_stats_span_not_p_tag(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, true, true, false );

		$this->assertStringContainsString( 'class="affwp-leaderboard-stats"', $html );
		$this->assertStringNotContainsString( '<p>', $html );
	}

	// ── helpers ───────────────────────────────────────────────────────────────

	private function makeEntry(
		int $id,
		string $name,
		float $earnings,
		int $count
	): LeaderboardEntry {
		return new LeaderboardEntry(
			affiliate_id:   $id,
			affiliate_name: $name,
			earnings:       $earnings,
			referral_count: $count,
		);
	}

	/**
	 * @param list<LeaderboardEntry> $entries
	 */
	private function mockLeaderboard( array $entries ): WeeklyLeaderboard {
		$mock = Mockery::mock( WeeklyLeaderboard::class );
		$mock->shouldReceive( 'build' )->andReturn( $entries )->byDefault();
		return $mock;
	}
}
