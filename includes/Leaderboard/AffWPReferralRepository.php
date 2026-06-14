<?php
/**
 * AffiliateWP-backed referral repository.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Leaderboard;

use AffiliateWPLeaderboardEnhanced\WeekRange;

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
	 * @param WeekRange         $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include.
	 * @return list<object>
	 */
	public function getEarningsSummedByAffiliate( WeekRange $range, array $statuses ): array {
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
	 * @param WeekRange         $range    The date window to query.
	 * @param array<int,string> $statuses Referral statuses to include.
	 * @return list<int>
	 */
	public function getAffiliateIdsForReferrals( WeekRange $range, array $statuses ): array {
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
	 * @param int $affiliate_id AffiliateWP affiliate ID.
	 * @return string
	 */
	public function getAffiliateName( int $affiliate_id ): string {
		return (string) affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );
	}
}
