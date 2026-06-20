<?php
/**
 * Install / verify translate button mu-plugin for wpForo.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/setup-translate-btn.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( 1 );
}

$mu_file = WP_CONTENT_DIR . '/mu-plugins/pet-forum-translate-btn.php';

if ( file_exists( $mu_file ) ) {
	WP_CLI::success( 'Translate button mu-plugin active: pet-forum-translate-btn.php' );
} else {
	WP_CLI::error( 'Missing mu-plugin: ' . $mu_file );
}

WP_CLI::log( 'Per-post translate UI loads on wpForo posts and blog entry-content.' );
