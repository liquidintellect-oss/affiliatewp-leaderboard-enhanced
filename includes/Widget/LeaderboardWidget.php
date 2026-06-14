<?php
/**
 * Sidebar widget: Affiliate Leaderboard Enhanced.
 *
 * @package AffiliateWPLeaderboardEnhanced
 */

namespace AffiliateWPLeaderboardEnhanced\Widget;

use AffiliateWPLeaderboardEnhanced\Shortcode\LeaderboardShortcode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the affiliate leaderboard as a configurable sidebar widget.
 *
 * The widget delegates all leaderboard logic to LeaderboardShortcode::render()
 * by converting its saved settings to the equivalent shortcode attribute array.
 * This keeps the rendering path identical for both the shortcode and widget.
 */
class LeaderboardWidget extends \WP_Widget {

	/**
	 * Constructor.
	 *
	 * @param LeaderboardShortcode $shortcode Shared shortcode / renderer instance.
	 */
	public function __construct( private readonly LeaderboardShortcode $shortcode ) {
		parent::__construct(
			'affiliatewp_leaderboard_enhanced',
			esc_html__( 'Affiliate Leaderboard Enhanced', 'affiliatewp-leaderboard-enhanced' ),
			array(
				'description' => esc_html__( 'Displays an affiliate leaderboard for the current week or year.', 'affiliatewp-leaderboard-enhanced' ),
			)
		);
	}

	/**
	 * Front-end display of the widget.
	 *
	 * @param array<string,string> $args     Theme-supplied before/after wrappers.
	 * @param array<string,mixed>  $instance Widget settings saved in the database.
	 * @return void
	 */
	public function widget( $args, $instance ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $args['id'] );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		// render() returns already-escaped HTML; $instance values are shortcode
		// attribute strings, not raw output — they are validated inside render().
		echo $this->shortcode->render(
			array(
				'period'     => $instance['period'] ?? 'week',
				'week_start' => $instance['week_start'] ?? 'monday',
				'number'     => $instance['number'] ?? '5',
				'orderby'    => $instance['orderby'] ?? 'earnings',
				'order'      => 'DESC',
				'earnings'   => $instance['earnings'] ?? 'yes',
				'referrals'  => $instance['referrals'] ?? 'yes',
				'status'     => $instance['status'] ?? 'paid,unpaid',
				'show_label' => $instance['show_label'] ?? 'yes',
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Back-end widget form displayed in the Widgets admin screen.
	 *
	 * @param array<string,mixed> $instance Previously saved widget settings.
	 * @return string
	 */
	public function form( $instance ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$defaults = array(
			'title'      => esc_html__( 'Top Affiliates', 'affiliatewp-leaderboard-enhanced' ),
			'period'     => 'week',
			'week_start' => 'monday',
			'number'     => '5',
			'orderby'    => 'earnings',
			'earnings'   => 'yes',
			'referrals'  => 'yes',
			'status'     => 'paid,unpaid',
			'show_label' => 'yes',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$periods = array(
			'week' => esc_html__( 'Current Week', 'affiliatewp-leaderboard-enhanced' ),
			'year' => esc_html__( 'Current Year', 'affiliatewp-leaderboard-enhanced' ),
		);

		$days = array(
			'sunday'    => esc_html__( 'Sunday', 'affiliatewp-leaderboard-enhanced' ),
			'monday'    => esc_html__( 'Monday', 'affiliatewp-leaderboard-enhanced' ),
			'tuesday'   => esc_html__( 'Tuesday', 'affiliatewp-leaderboard-enhanced' ),
			'wednesday' => esc_html__( 'Wednesday', 'affiliatewp-leaderboard-enhanced' ),
			'thursday'  => esc_html__( 'Thursday', 'affiliatewp-leaderboard-enhanced' ),
			'friday'    => esc_html__( 'Friday', 'affiliatewp-leaderboard-enhanced' ),
			'saturday'  => esc_html__( 'Saturday', 'affiliatewp-leaderboard-enhanced' ),
		);

		$statuses = array(
			'paid,unpaid' => esc_html__( 'Paid + Unpaid', 'affiliatewp-leaderboard-enhanced' ),
			'paid'        => esc_html__( 'Paid only', 'affiliatewp-leaderboard-enhanced' ),
		);
		?>

		<!-- Title -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<!-- Period -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>">
				<?php esc_html_e( 'Period:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'period' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'period' ) ); ?>">
				<?php foreach ( $periods as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"
						<?php selected( $instance['period'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<!-- Week Starts On (only relevant when period = week) -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'week_start' ) ); ?>">
				<?php esc_html_e( 'Week Starts On:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'week_start' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'week_start' ) ); ?>">
				<?php foreach ( $days as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"
						<?php selected( $instance['week_start'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<small><?php esc_html_e( 'Applies to Current Week only.', 'affiliatewp-leaderboard-enhanced' ); ?></small>
		</p>

		<!-- Number of Affiliates -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				<?php esc_html_e( 'Affiliates to Show:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
				type="number"
				min="1"
				value="<?php echo esc_attr( $instance['number'] ); ?>" />
		</p>

		<!-- Order By -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>">
				<?php esc_html_e( 'Order By:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>">
				<option value="earnings" <?php selected( $instance['orderby'], 'earnings' ); ?>>
					<?php esc_html_e( 'Earnings', 'affiliatewp-leaderboard-enhanced' ); ?>
				</option>
				<option value="referrals" <?php selected( $instance['orderby'], 'referrals' ); ?>>
					<?php esc_html_e( 'Referrals', 'affiliatewp-leaderboard-enhanced' ); ?>
				</option>
			</select>
		</p>

		<!-- Show Earnings -->
		<p>
			<input type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'earnings' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'earnings' ) ); ?>"
				value="yes"
				<?php checked( $instance['earnings'], 'yes' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'earnings' ) ); ?>">
				<?php esc_html_e( 'Show Earnings', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
		</p>

		<!-- Show Referrals -->
		<p>
			<input type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'referrals' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'referrals' ) ); ?>"
				value="yes"
				<?php checked( $instance['referrals'], 'yes' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'referrals' ) ); ?>">
				<?php esc_html_e( 'Show Referrals', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
		</p>

		<!-- Referral Status -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>">
				<?php esc_html_e( 'Referral Status:', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'status' ) ); ?>">
				<?php foreach ( $statuses as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"
						<?php selected( $instance['status'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<!-- Show Date Label -->
		<p>
			<input type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_label' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_label' ) ); ?>"
				value="yes"
				<?php checked( $instance['show_label'], 'yes' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_label' ) ); ?>">
				<?php esc_html_e( 'Show Period Label', 'affiliatewp-leaderboard-enhanced' ); ?>
			</label>
		</p>

		<?php
		return '';
	}

	/**
	 * Sanitise and save widget settings submitted from the admin form.
	 *
	 * @param array<string,mixed> $new_instance Submitted form values.
	 * @param array<string,mixed> $old_instance Previously saved values.
	 * @return array<string,mixed> Sanitised instance to persist.
	 */
	public function update( $new_instance, $old_instance ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$valid_periods  = array( 'week', 'year' );
		$valid_days     = array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' );
		$valid_orderbys = array( 'earnings', 'referrals' );
		$valid_statuses = array( 'paid,unpaid', 'paid' );

		return array(
			'title'      => sanitize_text_field( $new_instance['title'] ?? '' ),
			'period'     => in_array( $new_instance['period'] ?? '', $valid_periods, true )
				? $new_instance['period']
				: 'week',
			'week_start' => in_array( $new_instance['week_start'] ?? '', $valid_days, true )
				? $new_instance['week_start']
				: 'monday',
			'number'     => max( 1, (int) ( $new_instance['number'] ?? 10 ) ),
			'orderby'    => in_array( $new_instance['orderby'] ?? '', $valid_orderbys, true )
				? $new_instance['orderby']
				: 'earnings',
			'earnings'   => isset( $new_instance['earnings'] ) ? 'yes' : 'no',
			'referrals'  => isset( $new_instance['referrals'] ) ? 'yes' : 'no',
			'status'     => in_array( $new_instance['status'] ?? '', $valid_statuses, true )
				? $new_instance['status']
				: 'paid,unpaid',
			'show_label' => isset( $new_instance['show_label'] ) ? 'yes' : 'no',
		);
	}
}
