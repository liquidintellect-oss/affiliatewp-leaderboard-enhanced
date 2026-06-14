<?php
/**
 * Referral repository contract.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Leaderboard;

use AffiliateWPLeaderboardEnhanced\WeekRange;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstraction over the AffiliateWP referral data store.
 *
 * Decoupling data access from WeeklyLeaderboard lets unit tests inject a
 * lightweight stub without bootstrapping a full WordPress environment.
 */
interface ReferralRepositoryInterface {

	/**
	 * Return one record per affiliate containing the sum of their referral
	 * amounts within the given date window and statuses.
	 *
	 * Each returned object must expose:
	 *   - affiliate_id (int)
	 *   - amount_sum   (float|string castable to float)
	 *
	 * @param WeekRange         $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include, e.g. ['paid','unpaid'].
	 * @return list<object>
	 */
	public function getEarningsSummedByAffiliate( WeekRange $range, array $statuses ): array;

	/**
	 * Return the flat list of affiliate IDs for all referrals in the window —
	 * one entry per referral (duplicates intact) — so per-affiliate referral
	 * counts can be derived cheaply with array_count_values().
	 *
	 * @param WeekRange         $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include.
	 * @return list<int>
	 */
	public function getAffiliateIdsForReferrals( WeekRange $range, array $statuses ): array;

	/**
	 * Return the display name for the given affiliate.
	 *
	 * @param int $affiliate_id AffiliateWP affiliate ID.
	 * @return string
	 */
	public function getAffiliateName( int $affiliate_id ): string;
}
