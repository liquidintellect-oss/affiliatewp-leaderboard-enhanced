<?php
/**
 * AffiliateWP-backed referral repository.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Leaderboard;

use AffiliateWPLeaderboardEnhanced\DatePeriod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches referral data from AffiliateWP using the plugin's public API.
 *
 * This class is the only place in the codebase that calls affiliate_wp().
 * All other classes depend on ReferralRepositoryInterface so they can be
 * tested with a stub that does not require a live WordPress installation.
 */
class AffWPReferralRepository implements ReferralRepositoryInterface {

	/**
	 * Return per-affiliate earnings sums for the period.
	 *
	 * Uses get_referrals() with groupby + sum_fields to collapse referrals into
	 * one aggregated row per affiliate containing the earnings sum.
	 *
	 * @param DatePeriod        $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include.
	 * @return list<object>
	 */
	public function getEarningsSummedByAffiliate( DatePeriod $range, array $statuses ): array {
		$rows = affiliate_wp()->referrals->get_referrals(
			array(
				'date'       => array(
					'start' => $range->start,
					'end'   => $range->end,
				),
				'status'     => $statuses,
				'number'     => -1,
				'groupby'    => 'affiliate_id',
				'sum_fields' => array( 'amount' ),
				'fields'     => array( 'affiliate_id', 'amount' ),
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Return a flat list of affiliate IDs, one per referral in the period.
	 *
	 * Requests only the affiliate_id field so that array_count_values() on the
	 * result gives per-affiliate referral counts cheaply.
	 *
	 * @param DatePeriod        $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include.
	 * @return list<int>
	 */
	public function getAffiliateIdsForReferrals( DatePeriod $range, array $statuses ): array {
		$ids = affiliate_wp()->referrals->get_referrals(
			array(
				'date'   => array(
					'start' => $range->start,
					'end'   => $range->end,
				),
				'status' => $statuses,
				'number' => -1,
				'fields' => 'affiliate_id',
			)
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * Return the display name for the given affiliate.
	 *
	 * Falls back to the WordPress username and then to the email local-part
	 * (everything before the `@`) when the affiliate has no first or last name.
	 *
	 * @param int $affiliate_id AffiliateWP affiliate ID.
	 * @return string
	 */
	public function getAffiliateName( int $affiliate_id ): string {
		$name = (string) affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

		if ( '' !== $name ) {
			return $name;
		}

		$affiliate = affwp_get_affiliate( $affiliate_id );
		$user      = $affiliate ? get_userdata( $affiliate->user_id ) : false;

		return self::resolveAffiliateName( $name, $user ? $user : null );
	}

	/**
	 * Resolve the best available display name given the raw name and WP user.
	 *
	 * Priority:
	 *   1. $name, if non-empty (already handled by the caller, but kept for completeness).
	 *   2. WP user_login, with any `@domain` suffix stripped.
	 *   3. WP user_email, with the `@domain` suffix stripped.
	 *   4. Empty string as a last resort.
	 *
	 * The `@domain` strip is applied to both user_login and user_email because
	 * WordPress sites commonly configure user_login as an email address.
	 *
	 * Extracted as a public static method so it can be unit-tested without a
	 * live WordPress or AffiliateWP installation.
	 *
	 * @param string        $name The affiliate's first+last name (may be empty).
	 * @param \WP_User|null $user The affiliate's WordPress user record, or null.
	 * @return string
	 */
	public static function resolveAffiliateName( string $name, ?\WP_User $user ): string {
		if ( '' !== $name ) {
			return $name;
		}

		if ( null !== $user && '' !== $user->user_login ) {
			return self::stripEmailDomain( $user->user_login );
		}

		if ( null !== $user && '' !== $user->user_email ) {
			return self::stripEmailDomain( $user->user_email );
		}

		return '';
	}

	/**
	 * Strip the `@domain` portion from a string if it contains an `@` sign.
	 *
	 * Returns the value unchanged when no `@` is present (i.e. a plain username).
	 *
	 * @param string $value A username or email address.
	 * @return string
	 */
	private static function stripEmailDomain( string $value ): string {
		if ( false === strpos( $value, '@' ) ) {
			return $value;
		}

		$local = strstr( $value, '@', true );

		return ( false !== $local && '' !== $local ) ? $local : $value;
	}
}
