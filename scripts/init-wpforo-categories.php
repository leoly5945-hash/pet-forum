<?php
/**
 * Create wpForo boards + 9 forum categories per language section.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/init-wpforo-categories.php
 */

if ( ! function_exists( 'WPF' ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'wpForo is not active. Install and activate wpForo first.' );
	}
	exit( 1 );
}

if ( is_null( WPF()->forum ) ) {
	WPF()->init();
}

require_once WP_PLUGIN_DIR . '/wpforo/includes/installation.php';

/**
 * @param string $message Log message.
 * @param string $type    log|success|warning
 */
function petforum_log( $message, $type = 'log' ) {
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
 * @param int $boardid Board ID.
 */
function petforum_prepare_board( $boardid ) {
	WPF()->change_board( $boardid );
	wpforo_create_tables();
	wpforo_alter_tables();
	WPF()->forum->reset();
}

/**
 * @param string $slug Board slug.
 * @return array
 */
function petforum_get_board_by_slug( $slug ) {
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
	$board = petforum_get_board_by_slug( $config['slug'] );

	if ( ! empty( $board['boardid'] ) ) {
		$boardid = (int) $board['boardid'];
		WPF()->board->edit( $config, $boardid );
		petforum_log( sprintf( 'Board "%s" ready (ID %d).', $config['title'], $boardid ) );
	} else {
		$boardid = (int) WPF()->board->add( $config );
		if ( ! $boardid ) {
			if ( class_exists( 'WP_CLI' ) ) {
				WP_CLI::error( sprintf( 'Failed to create board "%s".', $config['title'] ) );
			}
			exit( 1 );
		}
		petforum_log( sprintf( 'Created board "%s" (ID %d).', $config['title'], $boardid ), 'success' );
	}

	petforum_prepare_board( $boardid );

	return $boardid;
}

/**
 * @param string $slug Forum slug.
 * @return bool
 */
function petforum_forum_exists( $slug ) {
	$forum = WPF()->forum->get_forum( $slug );

	return ! empty( $forum['forumid'] );
}

/**
 * @param array $args Forum args for wpForo.
 * @return int
 */
function petforum_ensure_forum( $args ) {
	$slug = $args['slug'];

	if ( petforum_forum_exists( $slug ) ) {
		petforum_log( sprintf( '  - %s (exists)', $slug ) );
		return (int) WPF()->forum->get_forum( $slug )['forumid'];
	}

	$forumid = (int) WPF()->forum->add( $args, false );

	if ( ! $forumid ) {
		petforum_log( sprintf( '  - %s (failed)', $slug ), 'warning' );
		return 0;
	}

	petforum_log( sprintf( '  + %s', $slug ), 'success' );

	return $forumid;
}

/**
 * @param array $groups Parent + child forum definitions.
 */
function petforum_create_forum_tree( $groups ) {
	foreach ( $groups as $group ) {
		$parent = $group['parent'];
		$parent['parentid'] = 0;
		$parent['order']    = (int) $parent['order'];

		$parentid = petforum_ensure_forum( $parent );

		foreach ( $group['forums'] as $forum ) {
			$forum['parentid'] = $parentid;
			$forum['order']    = (int) $forum['order'];
			petforum_ensure_forum( $forum );
		}
	}
}

$boards = array(
	array(
		'title'    => 'English Section',
		'slug'     => 'english-section',
		'locale'   => 'en_US',
		'status'   => true,
		'settings' => array(
			'title' => 'English Section',
			'desc'  => 'PetNest community forum in English',
		),
		'groups'   => array(
			array(
				'parent' => array(
					'title'       => 'Cats',
					'slug'        => 'cats',
					'description' => 'Everything about raising happy, healthy cats — from kittens to seniors.',
					'meta_desc'   => 'Cat community board: care, health, and breed discussions for cat lovers.',
					'icon'        => 'fas fa-cat',
					'color'       => '#f39c12',
					'order'       => 1,
				),
				'forums' => array(
					array(
						'title'       => 'Cat Care',
						'slug'        => 'cat-care',
						'description' => 'Daily routines, grooming, litter box setup, feeding schedules, and enrichment ideas for indoor and outdoor cats.',
						'meta_desc'   => 'Share cat care tips: nutrition, grooming, litter training, and home setup.',
						'order'       => 1,
					),
					array(
						'title'       => 'Cat Health',
						'slug'        => 'cat-health',
						'description' => 'Vaccination schedules, parasite prevention, symptoms to watch for, vet visits, and recovery stories.',
						'meta_desc'   => 'Cat health forum: vaccines, illness signs, treatment experiences, and preventive care.',
						'order'       => 2,
					),
					array(
						'title'       => 'Cat Breeds',
						'slug'        => 'cat-breeds',
						'description' => 'Breed traits, temperament, grooming needs, and advice for choosing the right cat for your lifestyle.',
						'meta_desc'   => 'Discuss cat breeds, personalities, and how to pick the right companion cat.',
						'order'       => 3,
					),
				),
			),
			array(
				'parent' => array(
					'title'       => 'Dogs',
					'slug'        => 'dogs',
					'description' => 'Training, nutrition, behavior, and breed guides for dog parents at every stage.',
					'meta_desc'   => 'Dog community board: care, training, health, and breed conversations.',
					'icon'        => 'fas fa-dog',
					'color'       => '#3498db',
					'order'       => 2,
				),
				'forums' => array(
					array(
						'title'       => 'Dog Care',
						'slug'        => 'dog-care',
						'description' => 'Feeding plans, coat care, exercise routines, puppy socialization, and senior dog comfort.',
						'meta_desc'   => 'Dog care tips: food, exercise, grooming, and day-to-day routines.',
						'order'       => 1,
					),
					array(
						'title'       => 'Dog Training',
						'slug'        => 'dog-training',
						'description' => 'Obedience basics, leash walking, crate training, behavior correction, and positive reinforcement.',
						'meta_desc'   => 'Dog training forum: obedience, behavior issues, and reward-based methods.',
						'order'       => 2,
					),
					array(
						'title'       => 'Dog Breeds',
						'slug'        => 'dog-breeds',
						'description' => 'Compare breeds by energy level, size, grooming, and family fit before adopting or buying.',
						'meta_desc'   => 'Dog breed discussions: traits, compatibility, and real owner experiences.',
						'order'       => 3,
					),
				),
			),
			array(
				'parent' => array(
					'title'       => 'Community',
					'slug'        => 'community',
					'description' => 'Meet members, ask general questions, and find adoption or rescue opportunities.',
					'meta_desc'   => 'PetNest community hub: introductions, Q&A, and adoption support.',
					'icon'        => 'fas fa-users',
					'color'       => '#27ae60',
					'order'       => 3,
				),
				'forums' => array(
					array(
						'title'       => 'Questions & Answers',
						'slug'        => 'questions-and-answers',
						'description' => 'Ask anything about pets — the community and moderators will help you find answers quickly.',
						'meta_desc'   => 'Pet Q&A forum for quick help from experienced pet owners.',
						'order'       => 1,
					),
					array(
						'title'       => 'New Members',
						'slug'        => 'new-members',
						'description' => 'Introduce yourself, share your pets, and tell us what brought you to PetNest.',
						'meta_desc'   => 'Welcome new PetNest members and share your pet stories.',
						'order'       => 2,
					),
					array(
						'title'       => 'Adoption & Rescue',
						'slug'        => 'adoption-and-rescue',
						'description' => 'Post adoption listings, rescue updates, foster experiences, and responsible rehoming advice.',
						'meta_desc'   => 'Adoption and rescue forum for pets looking for loving homes.',
						'order'       => 3,
					),
				),
			),
		),
	),
	array(
		'title'    => 'Mục Tiếng Việt',
		'slug'     => 'muc-tieng-viet',
		'locale'   => 'vi',
		'status'   => true,
		'settings' => array(
			'title' => 'Mục Tiếng Việt',
			'desc'  => 'Diễn đàn PetNest dành cho cộng đồng nói tiếng Việt',
		),
		'groups'   => array(
			array(
				'parent' => array(
					'title'       => 'Mèo',
					'slug'        => 'meo',
					'description' => 'Chia sẻ kinh nghiệm nuôi mèo khỏe mạnh — từ mèo con đến mèo già.',
					'meta_desc'   => 'Chuyên mục mèo: chăm sóc, sức khỏe và giống mèo cho người yêu mèo.',
					'icon'        => 'fas fa-cat',
					'color'       => '#f39c12',
					'order'       => 1,
				),
				'forums' => array(
					array(
						'title'       => 'Chăm sóc mèo',
						'slug'        => 'cham-soc-meo',
						'description' => 'Thói quen hằng ngày, vệ sinh lông, khay cát, khẩu phần ăn và cách làm mèo vui vẻ trong nhà.',
						'meta_desc'   => 'Mẹo chăm sóc mèo: dinh dưỡng, vệ sinh, khay cát và môi trường sống.',
						'order'       => 1,
					),
					array(
						'title'       => 'Sức khỏe mèo',
						'slug'        => 'suc-khoe-meo',
						'description' => 'Lịch tiêm phòng, tẩy giun ve, dấu hiệu bệnh, đi khám thú y và kinh nghiệm hồi phục.',
						'meta_desc'   => 'Diễn đàn sức khỏe mèo: vaccine, triệu chứng bệnh và chăm sóc phòng ngừa.',
						'order'       => 2,
					),
					array(
						'title'       => 'Giống mèo',
						'slug'        => 'giong-meo',
						'description' => 'Đặc điểm từng giống, tính cách, nhu cầu chăm sóc và gợi ý chọn mèo phù hợp.',
						'meta_desc'   => 'Thảo luận giống mèo, tính cách và cách chọn bạn mèo phù hợp.',
						'order'       => 3,
					),
				),
			),
			array(
				'parent' => array(
					'title'       => 'Chó',
					'slug'        => 'cho',
					'description' => 'Huấn luyện, dinh dưỡng, hành vi và kinh nghiệm theo từng giống chó.',
					'meta_desc'   => 'Chuyên mục chó: chăm sóc, huấn luyện, sức khỏe và giống chó.',
					'icon'        => 'fas fa-dog',
					'color'       => '#3498db',
					'order'       => 2,
				),
				'forums' => array(
					array(
						'title'       => 'Chăm sóc chó',
						'slug'        => 'cham-soc-cho',
						'description' => 'Khẩu phần ăn, tắm chải lông, vận động, làm quen xã hội cho chó con và chăm chó già.',
						'meta_desc'   => 'Mẹo chăm sóc chó: thức ăn, vận động, vệ sinh và sinh hoạt hằng ngày.',
						'order'       => 1,
					),
					array(
						'title'       => 'Huấn luyện chó',
						'slug'        => 'huan-luyen-cho',
						'description' => 'Nghe lệnh cơ bản, dắt dây, chuồng crate, sửa hành vi và huấn luyện tích cực.',
						'meta_desc'   => 'Diễn đàn huấn luyện chó: nghe lệnh, sửa hành vi và phương pháp khen thưởng.',
						'order'       => 2,
					),
					array(
						'title'       => 'Giống chó',
						'slug'        => 'giong-cho',
						'description' => 'So sánh giống chó theo năng lượng, kích thước, độ chải lông và phù hợp gia đình.',
						'meta_desc'   => 'Thảo luận giống chó, tính cách và kinh nghiệm nuôi thực tế.',
						'order'       => 3,
					),
				),
			),
			array(
				'parent' => array(
					'title'       => 'Cộng đồng',
					'slug'        => 'cong-dong',
					'description' => 'Làm quen thành viên, hỏi đáp chung và kết nối nhận nuôi – cứu hộ.',
					'meta_desc'   => 'Trung tâm cộng đồng PetNest: giới thiệu, hỏi đáp và nhận nuôi thú cưng.',
					'icon'        => 'fas fa-users',
					'color'       => '#27ae60',
					'order'       => 3,
				),
				'forums' => array(
					array(
						'title'       => 'Hỏi đáp',
						'slug'        => 'hoi-dap',
						'description' => 'Đặt mọi câu hỏi về thú cưng — cộng đồng và mod sẽ hỗ trợ bạn nhanh nhất có thể.',
						'meta_desc'   => 'Diễn đàn hỏi đáp thú cưng — nhận tư vấn từ người nuôi có kinh nghiệm.',
						'order'       => 1,
					),
					array(
						'title'       => 'Giới thiệu thành viên',
						'slug'        => 'gioi-thieu-thanh-vien',
						'description' => 'Giới thiệu bản thân, kể về boss mèo/chó của bạn và lý do gia nhập PetNest.',
						'meta_desc'   => 'Chào mừng thành viên mới PetNest — chia sẻ câu chuyện thú cưng của bạn.',
						'order'       => 2,
					),
					array(
						'title'       => 'Nhận nuôi & cứu hộ',
						'slug'        => 'nhan-nuoi-cuu-ho',
						'description' => 'Đăng tin nhận nuôi, cập nhật cứu hộ, foster và kinh nghiệm tìm nhà mới có trách nhiệm.',
						'meta_desc'   => 'Diễn đàn nhận nuôi và cứu hộ thú cưng tại Việt Nam.',
						'order'       => 3,
					),
				),
			),
		),
	),
);

// Disable legacy boards that are not part of the bilingual setup.
$configured_slugs = array( 'english-section', 'muc-tieng-viet' );
foreach ( WPF()->board->get_boards( array( 'status' => null ) ) as $legacy_board ) {
	if ( ! array_key_exists( 'boardid', $legacy_board ) ) {
		continue;
	}

	if ( in_array( $legacy_board['slug'], $configured_slugs, true ) ) {
		continue;
	}

	WPF()->board->edit(
		array(
			'title'  => $legacy_board['title'] . ' (disabled)',
			'status' => false,
		),
		(int) $legacy_board['boardid']
	);
	petforum_log( sprintf( 'Disabled legacy board "%s" (ID %d).', $legacy_board['slug'], $legacy_board['boardid'] ) );
}

foreach ( $boards as $board_config ) {
	$groups = $board_config['groups'];
	unset( $board_config['groups'] );

	petforum_log( sprintf( '=== %s ===', $board_config['title'] ) );
	$boardid = petforum_ensure_board( $board_config );
	petforum_prepare_board( $boardid );
	petforum_create_forum_tree( $groups );
}

WPF()->forum->reset();
wpforo_clean_cache();

petforum_log( 'wpForo categories ready: 2 boards x 9 discussion forums each.', 'success' );
