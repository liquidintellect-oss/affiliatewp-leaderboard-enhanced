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
		$this->assertStringContainsString( '<li', $html );
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

	// ── buildHtml: anonymize ──────────────────────────────────────────────────

	/** @test */
	public function build_html_shows_full_name_when_anonymize_is_false(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'John Doe', earnings: 100.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false, false );

		$this->assertStringContainsString( 'John Doe', $html );
	}

	/** @test */
	public function build_html_abbreviates_last_name_when_anonymize_is_true(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'John Doe', earnings: 100.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false, true );

		$this->assertStringContainsString( 'John D.', $html );
		$this->assertStringNotContainsString( 'John Doe', $html );
	}

	/** @test */
	public function build_html_leaves_single_word_name_unchanged_when_anonymize_is_true(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false, true );

		$this->assertStringContainsString( 'Alice', $html );
	}

	// ── anonymizeName ─────────────────────────────────────────────────────────

	/** @test */
	public function anonymize_name_abbreviates_last_name(): void {
		$this->assertSame( 'John D.', LeaderboardShortcode::anonymizeName( 'John Doe' ) );
	}

	/** @test */
	public function anonymize_name_abbreviates_last_word_only_for_multi_word_names(): void {
		$this->assertSame( 'Mary Jane W.', LeaderboardShortcode::anonymizeName( 'Mary Jane Watson' ) );
	}

	/** @test */
	public function anonymize_name_leaves_single_word_name_unchanged(): void {
		$this->assertSame( 'Alice', LeaderboardShortcode::anonymizeName( 'Alice' ) );
	}

	/** @test */
	public function anonymize_name_handles_extra_internal_whitespace(): void {
		$this->assertSame( 'John D.', LeaderboardShortcode::anonymizeName( 'John  Doe' ) );
	}

	// ── buildHtml: position classes ──────────────────────────────────────────

	/** @test */
	public function build_html_adds_position_1_class_to_first_entry(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 300.0, count: 5 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 200.0, count: 3 ),
			$this->makeEntry( id: 3, name: 'Carol', earnings: 100.0, count: 1 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'class="affwp-leaderboard-position-1"', $html );
	}

	/** @test */
	public function build_html_adds_position_2_class_to_second_entry(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 300.0, count: 5 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 200.0, count: 3 ),
			$this->makeEntry( id: 3, name: 'Carol', earnings: 100.0, count: 1 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'class="affwp-leaderboard-position-2"', $html );
	}

	/** @test */
	public function build_html_adds_position_3_class_to_third_entry(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 300.0, count: 5 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 200.0, count: 3 ),
			$this->makeEntry( id: 3, name: 'Carol', earnings: 100.0, count: 1 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'class="affwp-leaderboard-position-3"', $html );
	}

	/** @test */
	public function build_html_does_not_add_position_class_to_fourth_entry_and_beyond(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 400.0, count: 8 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 300.0, count: 6 ),
			$this->makeEntry( id: 3, name: 'Carol', earnings: 200.0, count: 4 ),
			$this->makeEntry( id: 4, name: 'Dave', earnings: 100.0, count: 2 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringNotContainsString( 'affwp-leaderboard-position-4', $html );
	}

	/** @test */
	public function build_html_position_classes_appear_in_correct_order(): void {
		$entries   = array(
			$this->makeEntry( id: 1, name: 'Alice', earnings: 300.0, count: 5 ),
			$this->makeEntry( id: 2, name: 'Bob', earnings: 200.0, count: 3 ),
			$this->makeEntry( id: 3, name: 'Carol', earnings: 100.0, count: 1 ),
		);
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$pos1 = strpos( $html, 'affwp-leaderboard-position-1' );
		$pos2 = strpos( $html, 'affwp-leaderboard-position-2' );
		$pos3 = strpos( $html, 'affwp-leaderboard-position-3' );

		$this->assertLessThan( $pos2, $pos1, 'Position 1 should appear before position 2' );
		$this->assertLessThan( $pos3, $pos2, 'Position 2 should appear before position 3' );
	}

	/** @test */
	public function build_html_adds_only_position_1_class_when_single_entry(): void {
		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 100.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );

		$html = $shortcode->buildHtml( $entries, $this->range, false, false, false );

		$this->assertStringContainsString( 'affwp-leaderboard-position-1', $html );
		$this->assertStringNotContainsString( 'affwp-leaderboard-position-2', $html );
		$this->assertStringNotContainsString( 'affwp-leaderboard-position-3', $html );
	}

	// ── render: AJAX wrapper output ──────────────────────────────────────────
	//
	// render() now outputs an empty wrapper div with data attributes that the
	// companion JS uses to fire background AJAX requests.  Content is no longer
	// rendered server-side by render() — see the renderContent() tests below.

	/** @test */
	public function render_outputs_wrapper_with_data_params_attribute(): void {
		$this->setupRenderWrapperMocks();

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringContainsString( 'data-affwp-lbe-params', $html );
	}

	/** @test */
	public function render_outputs_wrapper_with_data_refresh_attribute(): void {
		$this->setupRenderWrapperMocks();

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringContainsString( 'data-affwp-lbe-refresh', $html );
	}

	/** @test */
	public function render_wrapper_is_empty_on_initial_page_load(): void {
		$this->setupRenderWrapperMocks();

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringNotContainsString( '<ol', $html );
		$this->assertStringNotContainsString( '<p', $html );
	}

	/** @test */
	public function render_refresh_interval_defaults_to_zero(): void {
		$this->setupRenderWrapperMocks();

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringContainsString( 'data-affwp-lbe-refresh="0"', $html );
	}

	/** @test */
	public function render_encodes_refresh_interval_in_data_attribute(): void {
		$this->setupRenderWrapperMocks( array( 'refresh_interval' => '30' ) );

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringContainsString( 'data-affwp-lbe-refresh="30"', $html );
	}

	/** @test */
	public function render_encodes_period_in_data_params(): void {
		$this->setupRenderWrapperMocks( array( 'period' => 'year' ) );

		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( array() ) );
		$html      = $shortcode->render( array() );

		$this->assertStringContainsString( 'year', $html );
	}

	// ── renderContent: attribute flag parsing ─────────────────────────────────
	//
	// These tests verify that renderContent() correctly converts shortcode
	// attribute strings to show/hide flags before querying and rendering.
	// The key behaviour: anything that is NOT in the explicit "no" list is
	// treated as "show", so stray whitespace or unexpected values never silently
	// hide data.

	/** @test */
	public function render_content_hides_earnings_when_attribute_is_no(): void {
		$this->setupRenderContentMocks( array( 'earnings' => 'no', 'referrals' => 'yes' ) );

		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 100.0, count: 3 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );
		$html      = $shortcode->renderContent( array() );

		$this->assertStringNotContainsString( 'earnings', $html );
		$this->assertStringContainsString( 'referrals', $html );
	}

	/** @test */
	public function render_content_shows_earnings_when_attribute_is_yes(): void {
		$this->setupRenderContentMocks( array( 'earnings' => 'yes', 'referrals' => 'no' ) );

		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 420.50, count: 3 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );
		$html      = $shortcode->renderContent( array() );

		$this->assertStringContainsString( 'earnings', $html );
		$this->assertStringNotContainsString( 'referrals', $html );
	}

	/** @test */
	public function render_content_shows_earnings_when_attribute_has_surrounding_whitespace(): void {
		// Page builders can inject extra whitespace around attribute values.
		$this->setupRenderContentMocks( array( 'earnings' => '  yes  ', 'referrals' => 'no' ) );

		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );
		$html      = $shortcode->renderContent( array() );

		$this->assertStringContainsString( 'earnings', $html );
	}

	/** @test */
	public function render_content_returns_inner_html_without_outer_wrapper(): void {
		$this->setupRenderContentMocks();

		$entries   = array( $this->makeEntry( id: 1, name: 'Alice', earnings: 50.0, count: 1 ) );
		$shortcode = new LeaderboardShortcode( $this->mockLeaderboard( $entries ) );
		$html      = $shortcode->renderContent( array() );

		$this->assertStringNotContainsString( 'affwp-leaderboard-enhanced-wrap', $html );
		$this->assertStringContainsString( '<ol', $html );
	}

	// ── helpers ───────────────────────────────────────────────────────────────

	/**
	 * Set up WP_Mock stubs needed when render() is called directly in a test.
	 * render() outputs an AJAX wrapper and calls wp_json_encode + esc_attr.
	 *
	 * @param array<string,string> $attr_overrides Values to merge into the defaults returned by shortcode_atts.
	 */
	private function setupRenderWrapperMocks( array $attr_overrides = array() ): void {
		$defaults = array(
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
		);

		WP_Mock::userFunction( 'shortcode_atts' )
			->andReturn( array_merge( $defaults, $attr_overrides ) );

		WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing(
				function ( $value ) {
					return (string) json_encode( $value );
				}
			);
	}

	/**
	 * Set up WP_Mock stubs needed when renderContent() is called directly in a test.
	 *
	 * @param array<string,string> $attr_overrides Values to merge into the defaults returned by shortcode_atts.
	 */
	private function setupRenderContentMocks( array $attr_overrides = array() ): void {
		$defaults = array(
			'period'     => 'week',
			'week_start' => 'monday',
			'number'     => '5',
			'orderby'    => 'earnings',
			'order'      => 'DESC',
			'earnings'   => 'yes',
			'referrals'  => 'yes',
			'status'     => 'paid,unpaid',
			'show_label' => 'no',
			'anonymize'  => 'no',
		);

		WP_Mock::userFunction( 'shortcode_atts' )
			->andReturn( array_merge( $defaults, $attr_overrides ) );

		WP_Mock::userFunction( 'wp_timezone' )
			->andReturn( new DateTimeZone( 'UTC' ) );

		WP_Mock::userFunction( 'wp_kses_post' )
			->andReturnArg( 0 );
	}

	/**
	 * @deprecated Use setupRenderContentMocks() for renderContent() tests.
	 *             Kept as an alias so any test that still calls it resolves.
	 *
	 * @param array<string,string> $attr_overrides
	 */
	private function setupRenderMocks( array $attr_overrides = array() ): void {
		$this->setupRenderContentMocks( $attr_overrides );
	}

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
