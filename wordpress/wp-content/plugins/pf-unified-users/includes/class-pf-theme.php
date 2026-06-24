<?php
defined( 'ABSPATH' ) || exit;

/**
 * Site-wide nature theme + WP Admin accent (loaded from plugin, not theme).
 */
class PF_Theme {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend' ], 5 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
	}

	public static function enqueue_frontend(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'pf-inter-font',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			[],
			null
		);

		wp_enqueue_style(
			'pf-nature-theme',
			PFU_URL . 'assets/css/pf-nature-theme.css',
			[ 'pf-inter-font' ],
			PFU_VERSION
		);
	}

	public static function enqueue_admin( $hook_suffix ): void {
		unset( $hook_suffix );

		wp_enqueue_style(
			'pf-admin-accent',
			PFU_URL . 'assets/css/pf-admin-accent.css',
			[],
			PFU_VERSION
		);
	}
}
