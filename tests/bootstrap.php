<?php
/**
 * PHPUnit bootstrap file.
 *
 * Initialises WP_Mock, defines the WordPress constants and function stubs
 * needed by the plugin classes under test, and registers the PSR-4 autoloader
 * so plugin classes can be resolved without a running WordPress installation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// ── WordPress constants ──────────────────────────────────────────────────────

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/affwp-lbe-test/' );
}

// Plugin constants normally set by the main plugin file.
if ( ! defined( 'AFFWP_LBE_VERSION' ) ) {
	define( 'AFFWP_LBE_VERSION', '1.0.0.0-test' );
}
if ( ! defined( 'AFFWP_LBE_PLUGIN_URL' ) ) {
	define( 'AFFWP_LBE_PLUGIN_URL', 'https://example.com/wp-content/plugins/affiliatewp-leaderboard-enhanced/' );
}
if ( ! defined( 'AFFWP_LBE_PLUGIN_FILE' ) ) {
	define( 'AFFWP_LBE_PLUGIN_FILE', dirname( __DIR__ ) . '/affiliatewp-leaderboard-enhanced.php' );
}

// ── WordPress function stubs ─────────────────────────────────────────────────

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $str Input.
	 * @return string
	 */
	function sanitize_text_field( string $str ): string {
		return strip_tags( $str );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $maybeint Value.
	 * @return int
	 */
	function absint( mixed $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function esc_html__( string $text, string $domain = 'default' ): string {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return void
	 */
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo esc_html( $text );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * @param array<string,mixed>|string $args     New arguments.
	 * @param array<string,mixed>        $defaults Default values.
	 * @return array<string,mixed>
	 */
	function wp_parse_args( array|string $args, array $defaults = array() ): array {
		if ( is_string( $args ) ) {
			parse_str( $args, $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Minimal stub: allows basic HTML tags used in leaderboard output.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	function wp_kses_post( string $content ): string {
		return $content;
	}
}

// ── AffiliateWP function stubs ───────────────────────────────────────────────

if ( ! function_exists( 'affwp_currency_filter' ) ) {
	/**
	 * @param string $amount Formatted amount.
	 * @return string
	 */
	function affwp_currency_filter( string $amount ): string {
		return '$' . $amount;
	}
}

if ( ! function_exists( 'affwp_format_amount' ) ) {
	/**
	 * @param float $amount Amount.
	 * @return string
	 */
	function affwp_format_amount( float $amount ): string {
		return number_format( $amount, 2 );
	}
}

// ── WordPress class stubs ────────────────────────────────────────────────────

if ( ! class_exists( 'WP_Widget' ) ) {
	/**
	 * Minimal WP_Widget stub for tests.
	 */
	class WP_Widget {
		/** @var string */
		public string $id_base = '';

		/**
		 * @param string               $id_base         Widget ID base.
		 * @param string               $name            Widget display name.
		 * @param array<string,string> $widget_options  Widget options.
		 * @return void
		 */
		public function __construct(
			string $id_base = '',
			string $name = '',
			array $widget_options = array()
		) {
			$this->id_base = $id_base;
		}

		/**
		 * @param string $field_name Field name.
		 * @return string
		 */
		public function get_field_id( string $field_name ): string {
			return "widget-{$this->id_base}-{$field_name}";
		}

		/**
		 * @param string $field_name Field name.
		 * @return string
		 */
		public function get_field_name( string $field_name ): string {
			return "widget[{$this->id_base}][{$field_name}]";
		}
	}
}

// ── WP_Mock bootstrap ────────────────────────────────────────────────────────

WP_Mock::bootstrap();
