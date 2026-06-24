<?php
/**
 * wpForo 3.x Forum Setup — Pet Forum
 *
 * Schema (verified on wpForo 3.1.1):
 * - Boards  → wp_wpforo_boards (multiboard, locale, slug, pageid)
 * - Forums  → wp_wpforo_{boardid}_forums (per-board table)
 * - Category: parentid=0, is_cat=1  (group header, no topics)
 * - Forum:    parentid>0, is_cat=0  (postable discussion area)
 * - type column is only 'forum' | 'ticket_forum' — NOT 'board'
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/setup-forum-v3.php
 */

if ( ! function_exists( 'WPF' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'wpForo is not active.' );
	}
	exit( 1 );
}

if ( is_null( WPF()->forum ) ) {
	WPF()->init();
}

require_once WP_PLUGIN_DIR . '/wpforo/includes/installation.php';

/**
 * @param string $message Message.
 * @param string $type    log|success|warning
 */
function petforum_cli( $message, $type = 'log' ) {
	if ( ! class_exists( 'WP_CLI' ) ) {
		return;
	}
	if ( 'success' === $type ) {
		WP_CLI::success( $message );
	} elseif ( 'warning' === $type ) {
		WP_CLI::warning( $message );
	} else {
		WP_CLI::log( $message );
	}
}

/**
 * Switch board context and ensure per-board tables exist.
 *
 * @param int $boardid Board ID.
 */
function petforum_switch_board( $boardid ) {
	WPF()->change_board( $boardid );
	wpforo_create_tables();
	wpforo_alter_tables();
	WPF()->forum->reset();
}

/**
 * Remove all forums on the current board (children first).
 */
function petforum_wipe_forums() {
	$forums = (array) WPF()->forum->get_forums();
	if ( ! $forums ) {
		return;
	}

	// Delete leaf forums first, then parent categories.
	$remaining = count( $forums );
	$guard     = 0;

	while ( $remaining > 0 && $guard < 50 ) {
		$deleted = 0;
		foreach ( $forums as $forum ) {
			if ( empty( $forum['forumid'] ) ) {
				continue;
			}

			$has_child = false;
			foreach ( $forums as $other ) {
				if ( (int) $other['parentid'] === (int) $forum['forumid'] ) {
					$has_child = true;
					break;
				}
			}

			if ( ! $has_child ) {
				WPF()->forum->delete( (int) $forum['forumid'], false );
				petforum_cli( sprintf( '  🗑  Deleted: %s (#%d)', $forum['title'], $forum['forumid'] ) );
				$deleted++;
			}
		}

		if ( 0 === $deleted ) {
			break;
		}

		WPF()->forum->reset();
		$forums    = (array) WPF()->forum->get_forums();
		$remaining = count( $forums );
		$guard++;
	}
}

/**
 * @param string $slug Board slug.
 * @return array
 */
function petforum_find_board( $slug ) {
	return (array) WPF()->board->get_board(
		array(
			'slug_include' => array( $slug ),
			'status'       => null,
			'row_count'    => 1,
		)
	);
}

/**
 * @param array $config Board config.
 * @return int
 */
function petforum_ensure_board( $config ) {
	$pageid = (int) get_option( 'wpforo_pageid' );
	if ( ! $pageid ) {
		$pageid = (int) WPF()->db->get_var(
			"SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_name = 'community' AND post_type = 'page' AND post_status = 'publish' LIMIT 1"
		);
	}

	$config['pageid'] = $pageid;
	$config['status'] = true;

	$board = petforum_find_board( $config['slug'] );

	if ( ! empty( $board['boardid'] ) ) {
		$boardid = (int) $board['boardid'];
		WPF()->board->edit( $config, $boardid );
		petforum_cli( sprintf( 'Board ready: %s (ID %d)', $config['title'], $boardid ) );
	} else {
		$boardid = (int) WPF()->board->add( $config );
		if ( ! $boardid ) {
			WP_CLI::error( sprintf( 'Failed to create board "%s".', $config['title'] ) );
		}
		petforum_cli( sprintf( 'Created board: %s (ID %d)', $config['title'], $boardid ), 'success' );
	}

	petforum_switch_board( $boardid );
	petforum_wipe_forums();

	return $boardid;
}

/**
 * @param array $args Forum args.
 * @return int
 */
function petforum_add_forum( $args ) {
	$existing = WPF()->forum->get_forum( $args['slug'] );
	if ( ! empty( $existing['forumid'] ) ) {
		petforum_cli( sprintf( '  ↩  Exists: %s', $args['title'] ) );
		return (int) $existing['forumid'];
	}

	$forumid = (int) WPF()->forum->add( $args, false );
	if ( ! $forumid ) {
		petforum_cli( sprintf( '  ✗  Failed: %s', $args['title'] ), 'warning' );
		return 0;
	}

	petforum_cli( sprintf( '  ✅ [%d] %s', $forumid, $args['title'] ), 'success' );
	return $forumid;
}

/**
 * @param array $board_config Board + forums config.
 */
function petforum_setup_board_forums( $board_config ) {
	$forums_data = $board_config['forums'];
	unset( $board_config['forums'] );

	petforum_cli( '' );
	petforum_cli( '=== ' . $board_config['title'] . ' ===' );

	$boardid = petforum_ensure_board( $board_config );

	// Root category (wpForo requires is_cat=1 when parentid=0).
	$category_id = petforum_add_forum(
		array(
			'title'       => $board_config['category_title'],
			'slug'        => $board_config['category_slug'],
			'description' => $board_config['category_desc'],
			'parentid'    => 0,
			'order'       => 1,
			'icon'        => 'fas fa-paw',
			'color'       => '#27ae60',
			'status'      => 1,
		)
	);

	foreach ( $forums_data as $i => $forum ) {
		petforum_add_forum(
			array(
				'title'       => $forum['icon'] . ' ' . $forum['title'],
				'slug'        => $forum['slug'],
				'description' => $forum['description'],
				'meta_desc'   => $forum['meta_desc'],
				'parentid'    => $category_id,
				'order'       => $i + 1,
				'status'      => 1,
			)
		);
	}

	return $boardid;
}

$categories = array(
	array(
		'icon'        => '🐕',
		'slug_en'     => 'dogs',
		'slug_vi'     => 'cho-canh',
		'name_en'     => 'Dogs',
		'name_vi'     => 'Chó Cảnh',
		'desc_en'     => 'Everything about pet dogs — breeds, care, training and health tips.',
		'desc_vi'     => 'Tất cả về chó cảnh — giống chó, chăm sóc, huấn luyện và sức khỏe.',
	),
	array(
		'icon'        => '🐕‍🦺',
		'slug_en'     => 'dog-lovers-community',
		'slug_vi'     => 'hoi-cuong-cho',
		'name_en'     => 'Dog Lovers Community',
		'name_vi'     => 'Hội Cuồng Chó',
		'desc_en'     => 'A warm community for dog lovers to share stories, photos and daily moments.',
		'desc_vi'     => 'Cộng đồng dành cho những người yêu chó — chia sẻ câu chuyện và khoảnh khắc.',
	),
	array(
		'icon'        => '🐈',
		'slug_en'     => 'cats',
		'slug_vi'     => 'meo-canh',
		'name_en'     => 'Cats',
		'name_vi'     => 'Mèo Cảnh',
		'desc_en'     => 'Everything about pet cats — care, behavior, health and breed guides.',
		'desc_vi'     => 'Tất cả về mèo cảnh — chăm sóc, hành vi, sức khỏe và giống mèo.',
	),
	array(
		'icon'        => '🐱',
		'slug_en'     => 'cat-lovers-community',
		'slug_vi'     => 'hoi-cuong-meo',
		'name_en'     => 'Cat Lovers Community',
		'name_vi'     => 'Hội Cuồng Mèo',
		'desc_en'     => 'For cat lovers to connect, share memes and celebrate feline life.',
		'desc_vi'     => 'Nơi lý tưởng cho người cuồng mèo kết nối và chia sẻ.',
	),
	array(
		'icon'        => '🦜',
		'slug_en'     => 'pet-birds',
		'slug_vi'     => 'chim-canh',
		'name_en'     => 'Pet Birds',
		'name_vi'     => 'Chim Cảnh',
		'desc_en'     => 'Discussions on pet birds — species, cages, diet and vocal training.',
		'desc_vi'     => 'Thảo luận về chim cảnh — loài, lồng, dinh dưỡng và huấn luyện.',
	),
	array(
		'icon'        => '🐦',
		'slug_en'     => 'bird-lovers-community',
		'slug_vi'     => 'hoi-cuong-chim',
		'name_en'     => 'Bird Lovers Community',
		'name_vi'     => 'Hội Cuồng Chim',
		'desc_en'     => 'For bird enthusiasts to share photos, tips and breeding experiences.',
		'desc_vi'     => 'Dành cho người yêu chim — chia sẻ ảnh, mẹo và kinh nghiệm nuôi.',
	),
	array(
		'icon'        => '🩺',
		'slug_en'     => 'vet-consultation',
		'slug_vi'     => 'bac-si-tu-van',
		'name_en'     => 'Vet Consultation',
		'name_vi'     => 'Bác Sĩ Thú Y Tư Vấn',
		'desc_en'     => 'Ask veterinary questions and share preventive care knowledge.',
		'desc_vi'     => 'Đặt câu hỏi thú y và chia sẻ kiến thức chăm sóc phòng ngừa.',
	),
	array(
		'icon'        => '🛒',
		'slug_en'     => 'marketplace',
		'slug_vi'     => 'goc-mua-ban',
		'name_en'     => 'Marketplace',
		'name_vi'     => 'Góc Mua Bán',
		'desc_en'     => 'Buy, sell, trade supplies and find pets for adoption.',
		'desc_vi'     => 'Mua bán phụ kiện, thức ăn và đăng tin nhận nuôi thú cưng.',
	),
	array(
		'icon'        => '📸',
		'slug_en'     => 'photo-video-showcase',
		'slug_vi'     => 'khoe-anh-video',
		'name_en'     => 'Photo & Video Showcase',
		'name_vi'     => 'Khoe Ảnh & Video',
		'desc_en'     => 'Share adorable pet photos, funny clips and memorable moments.',
		'desc_vi'     => 'Chia sẻ ảnh, video dễ thương và khoảnh khắc đáng nhớ của boss.',
	),
);

petforum_cli( 'wpForo version: ' . WPFORO_VERSION );
petforum_cli( 'DB prefix: ' . WPF()->prefix );
petforum_cli( 'Boards table: ' . WPF()->tables->boards );

// Disable legacy default board (ID 0).
$legacy = WPF()->board->get_board( 0 );
if ( ! empty( $legacy['boardid'] ) ) {
	WPF()->board->edit(
		array(
			'title'  => 'Legacy (disabled)',
			'status' => false,
		),
		0
	);
	petforum_switch_board( 0 );
	petforum_wipe_forums();
	petforum_cli( 'Cleaned legacy board ID 0.' );
}

$en_forums = array();
$vi_forums = array();

foreach ( $categories as $cat ) {
	$en_forums[] = array(
		'icon'        => $cat['icon'],
		'title'       => $cat['name_en'],
		'slug'        => $cat['slug_en'],
		'description' => $cat['desc_en'],
		'meta_desc'   => $cat['desc_en'],
	);
	$vi_forums[] = array(
		'icon'        => $cat['icon'],
		'title'       => $cat['name_vi'],
		'slug'        => $cat['slug_vi'],
		'description' => $cat['desc_vi'],
		'meta_desc'   => $cat['desc_vi'],
	);
}

petforum_setup_board_forums(
	array(
		'title'          => 'English Section',
		'slug'           => 'english-section',
		'locale'         => 'en_US',
		'category_title' => '🐾 English Section',
		'category_slug'  => 'english-section-root',
		'category_desc'  => 'Welcome to the English section of Pet Forum.',
		'settings'       => array(
			'title' => 'English Section',
			'desc'  => 'Pet Forum — English community',
		),
		'forums'         => $en_forums,
	)
);

petforum_setup_board_forums(
	array(
		'title'          => 'Mục Tiếng Việt',
		'slug'           => 'muc-tieng-viet',
		'locale'         => 'vi',
		'category_title' => '🐾 Mục Tiếng Việt',
		'category_slug'  => 'muc-tieng-viet-root',
		'category_desc'  => 'Chào mừng đến với khu vực tiếng Việt của Pet Forum.',
		'settings'       => array(
			'title' => 'Mục Tiếng Việt',
			'desc'  => 'Diễn đàn Pet Forum — cộng đồng tiếng Việt',
		),
		'forums'         => $vi_forums,
	)
);

WPF()->forum->reset();
wpforo_clean_cache();
flush_rewrite_rules( false );

petforum_cli( '' );
petforum_cli( '🎉 Done! 2 Boards + 18 Forums (9 per board).', 'success' );
petforum_cli( '👉 http://localhost:8080/community/' );
