<?php
/**
 * Thêm Board Đa Ngôn Ngữ vào wpForo — post bằng bất kỳ ngôn ngữ nào.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/setup-multilingual-board.php
 */

if ( ! function_exists( 'WPF' ) ) {
	WP_CLI::error( 'wpForo is not active.' );
}

if ( is_null( WPF()->forum ) ) {
	WPF()->init();
}

require_once WP_PLUGIN_DIR . '/wpforo/includes/installation.php';

/**
 * @param string $slug Board slug.
 * @return array
 */
function petforum_ml_find_board( $slug ) {
	return (array) WPF()->board->get_board(
		array(
			'slug_include' => array( $slug ),
			'status'       => null,
			'row_count'    => 1,
		)
	);
}

/**
 * @param int $boardid Board ID.
 */
function petforum_ml_switch_board( $boardid ) {
	WPF()->change_board( $boardid );
	wpforo_create_tables();
	wpforo_alter_tables();
	WPF()->forum->reset();
}

/**
 * @param array $args Forum args.
 * @return int
 */
function petforum_ml_add_forum( $args ) {
	$existing = WPF()->forum->get_forum( $args['slug'] );
	if ( ! empty( $existing['forumid'] ) ) {
		WP_CLI::log( '  ↩  Exists: ' . $args['title'] );
		return (int) $existing['forumid'];
	}

	$forumid = (int) WPF()->forum->add( $args, false );
	if ( ! $forumid ) {
		WP_CLI::warning( '  ✗  Failed: ' . $args['title'] );
		return 0;
	}

	WP_CLI::log( '  ✅ [' . $forumid . '] ' . $args['title'] );
	return $forumid;
}

$pageid = (int) get_option( 'wpforo_pageid' );
$config = array(
	'title'    => '🌐 World Languages',
	'slug'     => 'world-languages',
	'locale'   => '',
	'pageid'   => $pageid,
	'status'   => true,
	'settings' => array(
		'title' => 'World Languages',
		'desc'  => 'Multilingual pet community',
	),
);

$board = petforum_ml_find_board( 'world-languages' );

if ( ! empty( $board['boardid'] ) ) {
	$boardid = (int) $board['boardid'];
	WPF()->board->edit( $config, $boardid );
	WP_CLI::log( 'Board ready: World Languages (ID ' . $boardid . ')' );
} else {
	$boardid = (int) WPF()->board->add( $config );
	if ( ! $boardid ) {
		WP_CLI::error( 'Failed to create World Languages board.' );
	}
	WP_CLI::success( 'Created board: World Languages (ID ' . $boardid . ')' );
}

petforum_ml_switch_board( $boardid );

$category_id = petforum_ml_add_forum(
	array(
		'title'       => '🌐 Đa Ngôn Ngữ — World Languages',
		'slug'        => 'world-languages-root',
		'description' => 'Post in any language — readers can translate with one click.',
		'parentid'    => 0,
		'order'       => 1,
		'icon'        => 'fas fa-globe',
		'color'       => '#0ea5e9',
		'status'      => 1,
	)
);

$forums = array(
	array(
		'icon'        => '🗣️',
		'title'       => 'General Discussion (Any Language)',
		'slug'        => 'general-any-lang',
		'description' => 'Share anything about pets — write in your own language.',
	),
	array(
		'icon'        => '❓',
		'title'       => 'Ask & Answer / Hỏi Đáp',
		'slug'        => 'ask-answer-multilingual',
		'description' => 'Ask questions in any language, get answers from the global community.',
	),
	array(
		'icon'        => '📸',
		'title'       => 'Photos & Stories / Ảnh & Câu Chuyện',
		'slug'        => 'photos-stories-world',
		'description' => 'Share photos and stories — language no barrier.',
	),
	array(
		'icon'        => '🩺',
		'title'       => 'Health Tips Worldwide',
		'slug'        => 'health-tips-world',
		'description' => 'Vet tips and health knowledge from around the world.',
	),
);

foreach ( $forums as $i => $forum ) {
	petforum_ml_add_forum(
		array(
			'title'       => $forum['icon'] . ' ' . $forum['title'],
			'slug'        => $forum['slug'],
			'description' => $forum['description'],
			'meta_desc'   => $forum['description'],
			'parentid'    => $category_id,
			'order'       => $i + 1,
			'status'      => 1,
		)
	);
}

WPF()->forum->reset();
wp_cache_flush();
flush_rewrite_rules( false );

WP_CLI::success( 'Multilingual board → http://localhost:8080/world-languages/' );
