<?php
/**
 * Ensure vet consultation forum uses slug bac-si-tu-van on VI board (wpForo 3.x board 3).
 * Keeps legacy slug bac-si-thu-y as alias in PF_Constants only.
 *
 * Run:
 *   docker exec pet-forum-wpcli wp eval-file /scripts/setup-vet-forum-slug.php --path=/var/www/html --allow-root
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WPF' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'wpForo is not active.' );
	}
	exit( 1 );
}

if ( is_null( WPF()->forum ) ) {
	WPF()->init();
}

$board_id = 3;
WPF()->change_board( $board_id );
WPF()->forum->reset();

$legacy = WPF()->forum->get_forum( 'bac-si-thu-y' );
$target = WPF()->forum->get_forum( 'bac-si-tu-van' );

if ( ! empty( $target['forumid'] ) ) {
	WP_CLI::success( sprintf( 'VI vet forum already at bac-si-tu-van (forum #%d).', (int) $target['forumid'] ) );
	exit( 0 );
}

if ( empty( $legacy['forumid'] ) ) {
	// Find parent category on board 3.
	$forums   = (array) WPF()->forum->get_forums();
	$parentid = 0;
	foreach ( $forums as $forum ) {
		if ( (int) ( $forum['parentid'] ?? 0 ) === 0 && ! empty( $forum['forumid'] ) ) {
			$parentid = (int) $forum['forumid'];
			break;
		}
	}

	if ( ! $parentid ) {
		WP_CLI::error( 'No root category on board 3 — run scripts/setup-forum-v3.php first.' );
	}

	$new_id = (int) WPF()->forum->add(
		[
			'title'       => '🩺 Bác Sĩ Thú Y Tư Vấn',
			'slug'        => 'bac-si-tu-van',
			'description' => 'Đặt câu hỏi thú y và chia sẻ kiến thức chăm sóc phòng ngừa.',
			'meta_desc'   => 'Tư vấn bác sĩ thú y — Pet Forum Vietnam',
			'parentid'    => $parentid,
			'order'       => 7,
			'status'      => 1,
		],
		false
	);

	if ( ! $new_id ) {
		WP_CLI::error( 'Failed to create bac-si-tu-van forum.' );
	}

	WP_CLI::success( sprintf( 'Created bac-si-tu-van forum #%d on board 3.', $new_id ) );
	exit( 0 );
}

$forumid = (int) $legacy['forumid'];
$payload = array_merge( $legacy, [ 'slug' => 'bac-si-tu-van' ] );
$updated = WPF()->forum->edit( $payload, $forumid );

if ( ! $updated ) {
	global $wpdb;
	$table   = $wpdb->prefix . 'wpforo_3_forums';
	$written = $wpdb->update(
		$table,
		[ 'slug' => 'bac-si-tu-van' ],
		[ 'forumid' => $forumid ],
		[ '%s' ],
		[ '%d' ]
	);

	if ( false === $written ) {
		WP_CLI::error( sprintf( 'Failed to rename forum #%d from bac-si-thu-y to bac-si-tu-van.', $forumid ) );
	}

	WP_CLI::warning( 'Used direct DB update for slug rename; flushing wpForo cache.' );
}

WPF()->forum->reset();
delete_option( 'wpforo_' . $board_id . '_forums' );
flush_rewrite_rules( false );

WP_CLI::success( sprintf( 'Renamed forum #%d: bac-si-thu-y → bac-si-tu-van (board %d).', $forumid, $board_id ) );
