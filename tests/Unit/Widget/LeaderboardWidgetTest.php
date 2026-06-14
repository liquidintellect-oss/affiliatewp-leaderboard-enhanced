<?php

use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;
use AffiliateWPLeaderboardEnhanced\Widget\LeaderboardWidget;
use PHPUnit\Framework\TestCase;

class LeaderboardWidgetTest extends TestCase {

	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
	}

	// ── widget() ─────────────────────────────────────────────────────────────

	/** @test */
	public function widget_calls_shortcode_render_with_instance_settings(): void {
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'widget_title', 'Top Affiliates', Mockery::any(), Mockery::any() )
			->andReturn( 'Top Affiliates' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$shortcode->shouldReceive( 'render' )
			->once()
			->with(
				Mockery::on(
					function ( array $atts ): bool {
						return 'week' === $atts['period']
							&& 'wednesday' === $atts['week_start']
							&& '8' === $atts['number']
							&& 'referrals' === $atts['orderby']
							&& 'paid' === $atts['status'];
					}
				)
			)
			->andReturn( '<ol></ol>' );

		$widget = new LeaderboardWidget( $shortcode );

		$args = array(
			'before_widget' => '<aside>',
			'after_widget'  => '</aside>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
			'id'            => 'test-widget',
		);

		$instance = array(
			'title'      => 'Top Affiliates',
			'period'     => 'week',
			'week_start' => 'wednesday',
			'number'     => '8',
			'orderby'    => 'referrals',
			'earnings'   => 'yes',
			'referrals'  => 'yes',
			'status'     => 'paid',
			'show_label' => 'yes',
		);

		ob_start();
		$widget->widget( $args, $instance );
		ob_end_clean();

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function widget_passes_year_period_to_shortcode(): void {
		WP_Mock::userFunction( 'apply_filters' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$shortcode->shouldReceive( 'render' )
			->once()
			->with(
				Mockery::on(
					function ( array $atts ): bool {
						return 'year' === $atts['period'];
					}
				)
			)
			->andReturn( '' );

		$widget = new LeaderboardWidget( $shortcode );

		ob_start();
		$widget->widget(
			array( 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => '', 'id' => 'w' ),
			array( 'period' => 'year' )
		);
		ob_end_clean();

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function widget_uses_week_and_monday_defaults_when_instance_empty(): void {
		WP_Mock::userFunction( 'apply_filters' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$shortcode->shouldReceive( 'render' )
			->once()
			->with(
				Mockery::on(
					function ( array $atts ): bool {
						return 'week' === $atts['period']
							&& 'monday' === $atts['week_start'];
					}
				)
			)
			->andReturn( '' );

		$widget = new LeaderboardWidget( $shortcode );

		ob_start();
		$widget->widget(
			array( 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => '', 'id' => 'w' ),
			array() // empty instance → all defaults
		);
		ob_end_clean();

		$this->addToAssertionCount( 1 );
	}

	// ── update() ──────────────────────────────────────────────────────────────

	/** @test */
	public function update_sanitises_title(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )
			->with( '<b>Alert</b>' )
			->andReturn( 'Alert' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array(
				'title'      => '<b>Alert</b>',
				'period'     => 'week',
				'week_start' => 'monday',
				'number'     => '5',
				'orderby'    => 'earnings',
				'status'     => 'paid,unpaid',
			),
			array()
		);

		$this->assertSame( 'Alert', $result['title'] );
	}

	/** @test */
	public function update_accepts_week_period(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid' ),
			array()
		);

		$this->assertSame( 'week', $result['period'] );
	}

	/** @test */
	public function update_accepts_year_period(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'year', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid' ),
			array()
		);

		$this->assertSame( 'year', $result['period'] );
	}

	/** @test */
	public function update_rejects_invalid_period_and_defaults_to_week(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'decade', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid' ),
			array()
		);

		$this->assertSame( 'week', $result['period'] );
	}

	/** @test */
	public function update_rejects_invalid_week_start_and_defaults_to_monday(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'funday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid,unpaid' ),
			array()
		);

		$this->assertSame( 'monday', $result['week_start'] );
	}

	/** @test */
	public function update_accepts_all_valid_week_start_days(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		foreach ( array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ) as $day ) {
			$result = $widget->update(
				array( 'title' => '', 'period' => 'week', 'week_start' => $day, 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid,unpaid' ),
				array()
			);
			$this->assertSame( $day, $result['week_start'], "Expected '{$day}' to be accepted" );
		}
	}

	/** @test */
	public function update_clamps_number_to_minimum_of_1(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'monday', 'number' => '-5', 'orderby' => 'earnings', 'status' => 'paid,unpaid' ),
			array()
		);

		$this->assertSame( 1, $result['number'] );
	}

	/** @test */
	public function update_sets_earnings_to_no_when_checkbox_not_submitted(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid' ),
			array()
		);

		$this->assertSame( 'no', $result['earnings'] );
	}

	/** @test */
	public function update_sets_earnings_to_yes_when_checkbox_submitted(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'earnings', 'status' => 'paid', 'earnings' => 'yes' ),
			array()
		);

		$this->assertSame( 'yes', $result['earnings'] );
	}

	/** @test */
	public function update_rejects_invalid_orderby_and_defaults_to_earnings(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturn( '' );

		$shortcode = Mockery::mock( LeaderboardShortcode::class );
		$widget    = new LeaderboardWidget( $shortcode );

		$result = $widget->update(
			array( 'title' => '', 'period' => 'week', 'week_start' => 'monday', 'number' => '5', 'orderby' => 'clicks', 'status' => 'paid' ),
			array()
		);

		$this->assertSame( 'earnings', $result['orderby'] );
	}
}
