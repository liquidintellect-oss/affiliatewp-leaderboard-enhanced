<?php

use AffiliateWPLeaderboardEnhanced\Leaderboard\AffWPReferralRepository;
use PHPUnit\Framework\TestCase;

// ── WP_User stub ──────────────────────────────────────────────────────────────

if ( ! class_exists( 'WP_User' ) ) {
	/**
	 * Minimal WP_User stub for unit tests.
	 */
	class WP_User {
		public int $ID            = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		public string $user_login = '';
		public string $user_email = '';

		public function __construct( string $user_login = '', string $user_email = '', int $ID = 0 ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			$this->user_login = $user_login;
			$this->user_email = $user_email;
			$this->ID         = $ID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		}
	}
}

// ── Tests ─────────────────────────────────────────────────────────────────────

class AffWPReferralRepositoryTest extends TestCase {

	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
		Mockery::close();
	}

	// ── resolveAffiliateName ──────────────────────────────────────────────────

	/** @test */
	public function returns_name_when_non_empty(): void {
		$user = new WP_User( 'jsmith', 'jsmith@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( 'Jane Smith', $user );

		$this->assertSame( 'Jane Smith', $result );
	}

	/** @test */
	public function strips_domain_from_name_when_name_is_an_email_address(): void {
		$user = new WP_User( 'jsmith@example.com', 'jsmith@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( 'jsmith@example.com', $user );

		$this->assertSame( 'jsmith', $result );
	}

	/** @test */
	public function falls_back_to_username_when_name_is_empty(): void {
		$user = new WP_User( 'jsmith', 'jsmith@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'jsmith', $result );
	}

	/** @test */
	public function falls_back_to_email_local_part_when_name_and_username_are_empty(): void {
		$user = new WP_User( '', 'jsmith@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'jsmith', $result );
	}

	/** @test */
	public function strips_domain_from_email_leaving_only_local_part(): void {
		$user = new WP_User( '', 'affiliate.user+tag@mycompany.org' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'affiliate.user+tag', $result );
	}

	/** @test */
	public function returns_empty_string_when_user_is_null(): void {
		$result = AffWPReferralRepository::resolveAffiliateName( '', null );

		$this->assertSame( '', $result );
	}

	/** @test */
	public function returns_empty_string_when_user_has_no_login_or_email(): void {
		$user = new WP_User( '', '' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( '', $result );
	}

	/** @test */
	public function prefers_username_over_email_when_both_available(): void {
		$user = new WP_User( 'myusername', 'myemail@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'myusername', $result );
	}

	/** @test */
	public function strips_domain_from_user_login_when_login_is_an_email_address(): void {
		$user = new WP_User( 'jsmith@example.com', 'jsmith@example.com' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'jsmith', $result );
	}

	/** @test */
	public function leaves_plain_username_unchanged_when_it_contains_no_at_sign(): void {
		$user = new WP_User( 'jsmith', '' );

		$result = AffWPReferralRepository::resolveAffiliateName( '', $user );

		$this->assertSame( 'jsmith', $result );
	}

	// ── resolveAffiliateIdsFromUsernamesOrEmails ──────────────────────────────

	/** @test */
	public function resolves_username_to_affiliate_id(): void {
		$user      = new WP_User( 'alice', 'alice@example.com', 42 );
		$affiliate = (object) array( 'affiliate_id' => 7 );

		WP_Mock::userFunction( 'get_user_by' )
			->with( 'login', 'alice' )
			->andReturn( $user );

		$affiliates_mock = Mockery::mock();
		$affiliates_mock->shouldReceive( 'get_by' )
			->with( 'user_id', 42 )
			->andReturn( $affiliate );

		WP_Mock::userFunction( 'affiliate_wp' )
			->andReturn( (object) array( 'affiliates' => $affiliates_mock ) );

		$repo   = new AffWPReferralRepository();
		$result = $repo->resolveAffiliateIdsFromUsernamesOrEmails( array( 'alice' ) );

		$this->assertSame( array( 7 ), $result );
	}

	/** @test */
	public function resolves_email_to_affiliate_id(): void {
		$user      = new WP_User( 'bob', 'bob@example.com', 99 );
		$affiliate = (object) array( 'affiliate_id' => 12 );

		WP_Mock::userFunction( 'get_user_by' )
			->with( 'email', 'bob@example.com' )
			->andReturn( $user );

		$affiliates_mock = Mockery::mock();
		$affiliates_mock->shouldReceive( 'get_by' )
			->with( 'user_id', 99 )
			->andReturn( $affiliate );

		WP_Mock::userFunction( 'affiliate_wp' )
			->andReturn( (object) array( 'affiliates' => $affiliates_mock ) );

		$repo   = new AffWPReferralRepository();
		$result = $repo->resolveAffiliateIdsFromUsernamesOrEmails( array( 'bob@example.com' ) );

		$this->assertSame( array( 12 ), $result );
	}

	/** @test */
	public function skips_unknown_identifier_when_user_not_found(): void {
		WP_Mock::userFunction( 'get_user_by' )
			->with( 'login', 'nobody' )
			->andReturn( false );

		$repo   = new AffWPReferralRepository();
		$result = $repo->resolveAffiliateIdsFromUsernamesOrEmails( array( 'nobody' ) );

		$this->assertSame( array(), $result );
	}

	/** @test */
	public function deduplicates_resolved_ids_when_same_identifier_listed_twice(): void {
		$user      = new WP_User( 'alice', 'alice@example.com', 42 );
		$affiliate = (object) array( 'affiliate_id' => 7 );

		WP_Mock::userFunction( 'get_user_by' )
			->with( 'login', 'alice' )
			->andReturn( $user );

		$affiliates_mock = Mockery::mock();
		$affiliates_mock->shouldReceive( 'get_by' )
			->with( 'user_id', 42 )
			->andReturn( $affiliate );

		WP_Mock::userFunction( 'affiliate_wp' )
			->andReturn( (object) array( 'affiliates' => $affiliates_mock ) );

		$repo   = new AffWPReferralRepository();
		$result = $repo->resolveAffiliateIdsFromUsernamesOrEmails( array( 'alice', 'alice' ) );

		$this->assertSame( array( 7 ), $result );
	}
}
