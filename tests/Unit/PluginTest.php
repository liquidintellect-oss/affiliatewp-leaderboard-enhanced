<?php

use AffiliateWPLeaderboardEnhanced\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase {

	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
	}

	/**
	 * Shared setup for all register() tests: stub add_shortcode and expect
	 * both add_action closures. WP_Mock uses Closure::class as the safe_offset
	 * key for any callable/closure argument.
	 */
	private function expectAllHooks(): void {
		// add_shortcode receives an [$object, 'method'] array callback — Mockery::any()
		// matches it since userFunction() returns a Mockery expectation.
		WP_Mock::userFunction( 'add_shortcode' )
			->with( 'affiliate_leaderboard_week', Mockery::any() )
			->once();

		WP_Mock::expectActionAdded( 'widgets_init', Closure::class );
		WP_Mock::expectActionAdded( 'wp_enqueue_scripts', Closure::class );
	}

	/** @test */
	public function register_adds_affiliate_leaderboard_week_shortcode(): void {
		$this->expectAllHooks();

		( new Plugin() )->register();

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function register_adds_widgets_init_action(): void {
		$this->expectAllHooks();

		( new Plugin() )->register();

		$this->addToAssertionCount( 1 );
	}

	/** @test */
	public function register_adds_wp_enqueue_scripts_action(): void {
		$this->expectAllHooks();

		( new Plugin() )->register();

		$this->addToAssertionCount( 1 );
	}
}
