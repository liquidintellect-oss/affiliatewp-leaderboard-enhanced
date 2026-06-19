<?php

use AffiliateWPLeaderboardEnhanced\Ajax\LeaderboardAjaxHandler;
use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;
use PHPUnit\Framework\TestCase;

class LeaderboardAjaxHandlerTest extends TestCase {

	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
	}

	// ── handle(): nonce verification ──────────────────────────────────────────

	/** @test */
	public function handle_calls_check_ajax_referer_with_correct_action(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )
			->once()
			->with( 'affwp_lbe_refresh', 'nonce' );

		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_timezone' )->andReturn( new DateTimeZone( 'UTC' ) );
		WP_Mock::userFunction( 'shortcode_atts' )->andReturn( $this->defaultAtts() );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_success' );

		$_POST['nonce']  = 'test-nonce';
		$_POST['params'] = '{}';

		$shortcode = $this->mockShortcode( '<p>content</p>' );
		$handler   = new LeaderboardAjaxHandler( $shortcode );
		$handler->handle();

		unset( $_POST['nonce'], $_POST['params'] );

		$this->addToAssertionCount( 1 );
	}

	// ── handle(): params forwarding ───────────────────────────────────────────

	/** @test */
	public function handle_forwards_decoded_params_to_shortcode(): void {
		WP_Mock::userFunction( 'check_ajax_referer' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_timezone' )->andReturn( new DateTimeZone( 'UTC' ) );
		WP_Mock::userFunction( 'wp_send_json_success' );
		WP_Mock::userFunction( 'shortcode_atts' )->andReturn( $this->defaultAtts( array( 'period' => 'year' ) ) );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );

		$_POST['params'] = json_encode( array( 'period' => 'year' ) );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$shortcode->shouldReceive( 'renderContent' )
			->once()
			->with(
				Mockery::on(
					function ( array $params ): bool {
						return isset( $params['period'] ) && 'year' === $params['period'];
					}
				)
			)
			->andReturn( '<p>inner</p>' );

		$handler = new LeaderboardAjaxHandler( $shortcode );
		$handler->handle();

		unset( $_POST['params'] );

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function handle_uses_empty_params_when_post_params_is_missing(): void {
		WP_Mock::userFunction( 'check_ajax_referer' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_timezone' )->andReturn( new DateTimeZone( 'UTC' ) );
		WP_Mock::userFunction( 'shortcode_atts' )->andReturn( $this->defaultAtts() );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_success' );

		unset( $_POST['params'] );

		$shortcode = $this->mockShortcode( '<p>inner</p>' );
		$handler   = new LeaderboardAjaxHandler( $shortcode );
		$handler->handle();

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function handle_uses_empty_params_when_post_params_is_invalid_json(): void {
		WP_Mock::userFunction( 'check_ajax_referer' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_timezone' )->andReturn( new DateTimeZone( 'UTC' ) );
		WP_Mock::userFunction( 'shortcode_atts' )->andReturn( $this->defaultAtts() );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_success' );

		$_POST['params'] = 'not-valid-json';

		$shortcode = $this->mockShortcode( '<p>inner</p>' );
		$handler   = new LeaderboardAjaxHandler( $shortcode );
		$handler->handle();

		unset( $_POST['params'] );

		$this->addToAssertionCount( 1 );
	}

	// ── handle(): JSON response ───────────────────────────────────────────────

	/** @test */
	public function handle_sends_json_success_with_html_key(): void {
		WP_Mock::userFunction( 'check_ajax_referer' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_timezone' )->andReturn( new DateTimeZone( 'UTC' ) );
		WP_Mock::userFunction( 'shortcode_atts' )->andReturn( $this->defaultAtts() );
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );

		$expected_html = '<ol class="affwp-leaderboard">...</ol>';

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with(
				Mockery::on(
					function ( array $data ) use ( $expected_html ): bool {
						return isset( $data['html'] ) && $expected_html === $data['html'];
					}
				)
			);

		$_POST['params'] = '{}';

		$shortcode = $this->mockShortcode( $expected_html );
		$handler   = new LeaderboardAjaxHandler( $shortcode );
		$handler->handle();

		unset( $_POST['params'] );

		$this->addToAssertionCount( 1 );
	}

	// ── helpers ───────────────────────────────────────────────────────────────

	/**
	 * @param array<string,string> $overrides
	 * @return array<string,string>
	 */
	private function defaultAtts( array $overrides = array() ): array {
		return array_merge(
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
			$overrides
		);
	}

	private function mockShortcode( string $html ): LeaderboardShortcode {
		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$shortcode->shouldReceive( 'renderContent' )
			->andReturn( $html )
			->byDefault();
		return $shortcode;
	}
}
