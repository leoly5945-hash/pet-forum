<?php
/**
 * Pet Forum homepage assets.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_page_template( 'page-home.php' ) ) {
			return;
		}

		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();

		$css_path = $theme_dir . '/css/pf-home.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'pf-home',
				$theme_uri . '/css/pf-home.css',
				array(),
				filemtime( $css_path )
			);
		}

		$js_path = $theme_dir . '/js/pf-home-ajax.js';
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'pf-home-ajax',
				$theme_uri . '/js/pf-home-ajax.js',
				array( 'jquery' ),
				filemtime( $js_path ),
				true
			);
			wp_script_add_data( 'pf-home-ajax', 'defer', true );
			wp_localize_script(
				'pf-home-ajax',
				'pfHomeData',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'pf_load_more' ),
				)
			);
		}
	},
	25
);
