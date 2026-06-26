<?php
/**
 * Weekly leaderboard service.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Leaderboard;

use AffiliateWPLeaderboardEnhanced\DatePeriod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds a ranked list of LeaderboardEntry objects for a given DatePeriod.
 *
 * All data access is delegated to ReferralRepositoryInterface so this class
 * contains only business logic and is fully unit-testable without WordPress.
 */
class WeeklyLeaderboard {

	/**
	 * Constructor.
	 *
	 * @param ReferralRepositoryInterface $repository Data access layer.
	 */
	public function __construct(
		private readonly ReferralRepositoryInterface $repository
	) {}

	/**
	 * Build and return a ranked leaderboard for the given week.
	 *
	 * Steps:
	 *   1. Fetch per-affiliate earnings sums for the period.
	 *   2. Fetch per-affiliate referral counts for the period.
	 *   3. Optionally exclude affiliates matching $exclude_identifiers.
	 *   4. Fetch each affiliate's display name.
	 *   5. Combine into LeaderboardEntry value objects.
	 *   6. Sort by the requested metric and direction.
	 *   7. Slice to the requested maximum count.
	 *
	 * @param DatePeriod        $range               The week to score.
	 * @param array<int,string> $statuses            Referral statuses to include (e.g. ['paid','unpaid']).
	 * @param int               $number              Maximum entries to return.
	 * @param string            $orderby             Sort key: 'earnings' or 'referrals'.
	 * @param string            $order               Sort direction: 'DESC' or 'ASC'.
	 * @param array<string>     $exclude_identifiers WP usernames or emails to exclude. Default [].
	 * @return list<LeaderboardEntry>
	 */
	public function build(
		DatePeriod $range,
		array $statuses,
		int $number,
		string $orderby,
		string $order,
		array $exclude_identifiers = array()
	): array {
		$earnings_rows = $this->repository->getEarningsSummedByAffiliate( $range, $statuses );

		if ( empty( $earnings_rows ) ) {
			return array();
		}

		$affiliate_ids = $this->repository->getAffiliateIdsForReferrals( $range, $statuses );

		if ( ! empty( $exclude_identifiers ) ) {
			$excluded_ids  = $this->repository->resolveAffiliateIdsFromUsernamesOrEmails( $exclude_identifiers );
			$earnings_rows = array_values(
				array_filter(
					$earnings_rows,
					static fn( $row ) => ! in_array( (int) $row->affiliate_id, $excluded_ids, true )
				)
			);
			$affiliate_ids = array_values(
				array_filter(
					$affiliate_ids,
					static fn( $id ) => ! in_array( $id, $excluded_ids, true )
				)
			);
		}
		$counts = array_count_values( $affiliate_ids );

		$entries = array();
		foreach ( $earnings_rows as $row ) {
			$aid       = (int) $row->affiliate_id;
			$entries[] = new LeaderboardEntry(
				affiliate_id:   $aid,
				affiliate_name: $this->repository->getAffiliateName( $aid ),
				earnings:       (float) ( $row->amount_sum ?? 0.0 ),
				referral_count: $counts[ $aid ] ?? 0,
			);
		}

		$sort_by_referrals = 'referrals' === $orderby;
		$sort_desc         = 'ASC' !== strtoupper( $order );

		usort(
			$entries,
			static function ( LeaderboardEntry $a, LeaderboardEntry $b ) use ( $sort_by_referrals, $sort_desc ): int {
				// Primary sort: chosen metric.
				$a_primary = $sort_by_referrals ? $a->referral_count : $a->earnings;
				$b_primary = $sort_by_referrals ? $b->referral_count : $b->earnings;
				$primary   = $sort_desc ? ( $b_primary <=> $a_primary ) : ( $a_primary <=> $b_primary );

				if ( 0 !== $primary ) {
					return $primary;
				}

				// Secondary sort: the other metric as a tiebreaker, same direction.
				$a_secondary = $sort_by_referrals ? $a->earnings : $a->referral_count;
				$b_secondary = $sort_by_referrals ? $b->earnings : $b->referral_count;
				return $sort_desc ? ( $b_secondary <=> $a_secondary ) : ( $a_secondary <=> $b_secondary );
			}
		);

		return array_slice( $entries, 0, $number );
	}
}
