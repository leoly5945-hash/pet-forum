<?php
/**
 * Initialize Polylang with English (default) and Vietnamese.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/init-polylang-languages.php
 */

if ( ! function_exists( 'PLL' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'Polylang is not active. Install and activate it first.' );
	}
	exit( 1 );
}

$model = PLL()->model;

$languages = array(
	array(
		'locale'     => 'en_US',
		'slug'       => 'en',
		'term_group' => 0,
	),
	array(
		'locale'     => 'vi',
		'slug'       => 'vi',
		'term_group' => 1,
	),
);

foreach ( $languages as $lang ) {
	$existing = $model->get_language( $lang['slug'] );

	if ( $existing ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::log( sprintf( 'Language "%s" already exists, skipping.', $lang['slug'] ) );
		}
		continue;
	}

	$result = $model->add_language( $lang );

	if ( is_wp_error( $result ) ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::warning( sprintf( 'Failed to add %s: %s', $lang['slug'], $result->get_error_message() ) );
		}
		continue;
	}

	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::success( sprintf( 'Added language "%s" (%s).', $result->name, $lang['slug'] ) );
	}
}

if ( function_exists( 'pll_set_default_language' ) ) {
	pll_set_default_language( 'en' );
}

$options = get_option( 'polylang', array() );

if ( is_array( $options ) ) {
$options['default_lang']  = 'en';
$options['force_lang']    = 1;
$options['hide_default']  = true;
$options['redirect_lang'] = false;
	update_option( 'polylang', $options );
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( 'Polylang configured: default language = en, languages = en + vi.' );
}
