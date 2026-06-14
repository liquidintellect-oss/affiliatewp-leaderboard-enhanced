<?php
/**
 * Leaderboard entry value object.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Leaderboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a single affiliate's position in the weekly leaderboard.
 *
 * All properties are read-only and set at construction time — this is an
 * immutable value object with no behaviour of its own.
 */
class LeaderboardEntry {

	/**
	 * Constructor.
	 *
	 * @param int    $affiliate_id   AffiliateWP affiliate ID.
	 * @param string $affiliate_name Display name for the affiliate.
	 * @param float  $earnings       Sum of approved referral amounts in the period.
	 * @param int    $referral_count Number of approved referrals in the period.
	 */
	public function __construct(
		public readonly int $affiliate_id,
		public readonly string $affiliate_name,
		public readonly float $earnings,
		public readonly int $referral_count,
	) {}
}
