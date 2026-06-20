<?php
/**
 * Seed content — tạo topics và posts mẫu cho Pet Forum (wpForo 3.x multiboard).
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/seed-content.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'WPF' ) ) {
	WP_CLI::error( 'wpForo is not active.' );
}

if ( is_null( WPF()->forum ) ) {
	WPF()->init();
}

require_once WP_PLUGIN_DIR . '/wpforo/includes/installation.php';

global $wpdb;

$admin_id = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->users} ORDER BY ID LIMIT 1" );
WP_CLI::log( "Admin ID: {$admin_id}" );

$sample_topics = array(
	'dogs'                  => array(
		array( 'Labrador vs Golden Retriever — which breed for first-time owners?', 'Hi everyone! I\'m planning to get my first dog and torn between a Labrador and a Golden Retriever. Any advice from experienced owners? Both seem so friendly and gentle.' ),
		array( 'Best dog food brands for large breeds in 2024', 'After months of research and trying different brands, I\'ve compiled a list of the best dog foods for large breeds. Sharing my findings here for the community!' ),
	),
	'dog-lovers-community'  => array(
		array( 'Introducing my new puppy — meet Biscuit! 🐶', 'Just brought home this little guy yesterday. He\'s a 2-month-old Beagle and already causing chaos. Anyone have tips for the first week with a new puppy?' ),
	),
	'cats'                  => array(
		array( 'Why does my cat knock things off the table?', 'My Maine Coon has developed a habit of knocking everything off my desk. Is this normal cat behavior? How do I stop it before my laptop goes next!' ),
		array( 'Persian vs British Shorthair — grooming differences', 'Thinking of adopting a Persian but worried about grooming needs. Anyone have experience with both breeds? How much time does daily grooming actually take?' ),
	),
	'cat-lovers-community'  => array(
		array( 'My cat\'s Monday morning face 😂', 'Every Monday, Luna gives me this look that says "you\'re going to work again??" Sharing the most relatable cat photo ever.' ),
	),
	'pet-birds'             => array(
		array( 'African Grey Parrot — is the intelligence level really that high?', 'I visited a friend who has an African Grey and was absolutely blown away. The bird was having full conversations! Are they really this smart or was I just lucky to see a good day?' ),
	),
	'vet-consultation'      => array(
		array( 'My dog has been limping for 2 days — when to see a vet?', 'My 3-year-old Labrador started limping after our morning walk 2 days ago. No visible wounds, eats normally, but favors the left front leg. Should I wait or go to the vet immediately?' ),
	),
	'marketplace'           => array(
		array( '[FOR ADOPTION] 2 domestic shorthair kittens — Hanoi', 'Found these 2 kittens near my apartment 3 weeks ago. Both vaccinated and dewormed. Looking for a loving forever home. Located in Cầu Giấy, Hanoi. Contact via DM.' ),
	),
	'photo-video-showcase'  => array(
		array( 'Photo dump — my dog\'s first beach trip 🏖️', 'Took Charlie to the beach for the first time last weekend. The joy on his face when he discovered waves was absolutely priceless. Sharing the whole album here!' ),
	),
	'cho-canh'              => array(
		array( 'Chó Shiba Inu có hợp với khí hậu Việt Nam không?', 'Mình đang cân nhắc nuôi Shiba Inu nhưng nghe nói chúng không hợp với thời tiết nóng ẩm. Các bạn ở TP.HCM có nuôi Shiba không? Chia sẻ kinh nghiệm với mình nhé!' ),
		array( 'Thức ăn cho chó con 2 tháng tuổi — nên cho ăn gì?', 'Mình mới đón boss Golden về nhà được 1 tuần, 2 tháng tuổi. Đang phân vân giữa cháo tự nấu và thức ăn hạt. Mọi người tư vấn giúp mình với ạ!' ),
	),
	'hoi-cuong-cho'         => array(
		array( 'Giới thiệu boss nhà mình — Cu Mực siêu đáng yêu 🐶', 'Đây là Cu Mực, Corgi 6 tháng tuổi nhà mình. Vừa tắm xong nên trông bông bềnh lắm ạ 😂 Mọi người có muốn xem thêm ảnh boss không?' ),
	),
	'meo-canh'              => array(
		array( 'Mèo Anh lông ngắn BSH có khó nuôi không?', 'Mình sắp nhận boss British Shorthair về. Đây là lần đầu nuôi mèo nên khá lo lắng. Bạn nào có kinh nghiệm nuôi BSH chỉ mình với ạ, đặc biệt về chế độ ăn và sức khỏe.' ),
		array( 'Boss mèo nhà mình suốt ngày ngủ — có bình thường không?', 'Luna nhà mình ngủ tầm 16-18 tiếng/ngày. Googled thì thấy mèo ngủ nhiều là bình thường nhưng vẫn lo. Mèo nhà các bạn ngủ bao nhiêu tiếng?' ),
	),
	'hoi-cuong-meo'         => array(
		array( 'Hội mèo béo check in nào! 😂🐱', 'Boss nhà mình 8kg rồi mà bác sĩ bảo vẫn khỏe mạnh 😅 Ai có boss mèo béo không chia sẻ ảnh đây mình cùng ngắm với!' ),
	),
	'chim-canh'             => array(
		array( 'Vẹt Cockatiel có dễ nuôi cho người mới bắt đầu?', 'Mình rất thích vẹt nhưng chưa có kinh nghiệm nuôi chim. Nghe nói Cockatiel thân thiện và dễ nuôi. Mọi người tư vấn giúp mình nên bắt đầu với loài nào?' ),
	),
	'bac-si-thu-y'          => array(
		array( 'Boss chó bỏ ăn 2 ngày — cần làm gì?', 'Lab nhà mình 3 tuổi, đột nhiên bỏ ăn 2 ngày nay. Vẫn uống nước, không nôn, đi vệ sinh bình thường. Có nên đưa đi khám ngay không hay đợi thêm?' ),
	),
	'goc-mua-ban'           => array(
		array( '[CHO NHẬN NUÔI] 3 bé mèo tam thể — Hà Nội', 'Mình nhặt được 3 bé mèo tam thể khoảng 6 tuần tuổi gần chỗ làm. Đã tẩy giun và tiêm phòng. Tìm gia đình có tâm để nhận nuôi. Khu vực Đống Đa, Hà Nội.' ),
	),
	'khoe-anh-video'        => array(
		array( 'Album ảnh boss chó đi biển lần đầu 🏖️', 'Cuối tuần vừa rồi mình đưa Milo ra Vũng Tàu lần đầu. Nhìn mặt boss lúc thấy sóng mà cười không nín được. Chia sẻ cả album đây mọi người ơi!' ),
	),
);

/**
 * @param int $boardid Board ID.
 */
function petforum_seed_switch_board( $boardid ) {
	WPF()->change_board( $boardid );
	wpforo_create_tables();
	wpforo_alter_tables();
	WPF()->forum->reset();
}

/**
 * @param array $args    Topic args.
 * @param int   $user_id Author user ID.
 * @return int
 */
function petforum_seed_add_topic( $args, $user_id ) {
	$original_user = get_current_user_id();
	wp_set_current_user( $user_id );
	if ( isset( WPF()->current_userid ) ) {
		WPF()->current_userid = $user_id;
	}

	WPF()->notice->clear();

	$args['userid']          = $user_id;
	$args['is_ai_generated'] = 1;
	$args['body']            = '<p>' . esc_html( $args['body'] ) . '</p>';

	$topic_id = (int) WPF()->topic->add( $args );

	wp_set_current_user( $original_user );
	if ( isset( WPF()->current_userid ) ) {
		WPF()->current_userid = $original_user;
	}

	return $topic_id;
}

/**
 * @param int    $boardid       Board ID.
 * @param array  $sample_topics Slug => topics map.
 * @param int    $admin_id      Author user ID.
 * @return int Inserted count.
 */
function petforum_seed_board( $boardid, $sample_topics, $admin_id ) {
	petforum_seed_switch_board( $boardid );

	$forums = WPF()->forum->get_forums();
	$inserted = 0;

	foreach ( $forums as $forum ) {
		if ( empty( $forum['parentid'] ) || empty( $forum['slug'] ) ) {
			continue;
		}

		$forum_slug = $forum['slug'];
		$forum_id   = (int) $forum['forumid'];

		if ( empty( $sample_topics[ $forum_slug ] ) ) {
			continue;
		}

		foreach ( $sample_topics[ $forum_slug ] as $idx => $topic_data ) {
			list( $title, $content ) = $topic_data;
			$slug = sanitize_title( $title ) . '-' . $forum_id . '-' . $idx;

			$existing = WPF()->topic->get_topic( array( 'slug' => $slug ), false );
			if ( ! empty( $existing['topicid'] ) ) {
				WP_CLI::log( "  ↩  Exists [{$forum_slug}]: {$title}" );
				continue;
			}

			$topic_id = petforum_seed_add_topic(
				array(
					'forumid' => $forum_id,
					'title'   => $title,
					'slug'    => $slug,
					'body'    => $content,
				),
				$admin_id
			);

			if ( ! $topic_id ) {
				$notices = wp_strip_all_tags( WPF()->notice->get_notices() );
				WP_CLI::warning( "  ✗  [{$forum_slug}] {$title} — {$notices}" );
				continue;
			}

			WP_CLI::log( "  ✅ [{$forum_slug}] {$title}" );
			$inserted++;
		}
	}

	return $inserted;
}

$en_slugs = array(
	'dogs',
	'dog-lovers-community',
	'cats',
	'cat-lovers-community',
	'pet-birds',
	'vet-consultation',
	'marketplace',
	'photo-video-showcase',
);
$vi_slugs = array(
	'cho-canh',
	'hoi-cuong-cho',
	'meo-canh',
	'hoi-cuong-meo',
	'chim-canh',
	'bac-si-thu-y',
	'goc-mua-ban',
	'khoe-anh-video',
);

$en_topics = array_intersect_key( $sample_topics, array_flip( $en_slugs ) );
$vi_topics = array_intersect_key( $sample_topics, array_flip( $vi_slugs ) );

WP_CLI::log( 'Seeding English Section (board 2)...' );
$en_count = petforum_seed_board( 2, $en_topics, $admin_id );

WP_CLI::log( 'Seeding Mục Tiếng Việt (board 3)...' );
$vi_count = petforum_seed_board( 3, $vi_topics, $admin_id );

$inserted = $en_count + $vi_count;

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wpforo%'" );
wpforo_clean_cache();
if ( function_exists( 'wpforo_clear_cache' ) ) {
	wpforo_clear_cache();
}

WP_CLI::log( '' );
WP_CLI::success( "🎉 Đã tạo {$inserted} topics mẫu thành công!" );
WP_CLI::log( '👉 Kiểm tra: http://localhost:8080/english-section/' );
WP_CLI::log( '👉 Kiểm tra: http://localhost:8080/muc-tieng-viet/' );
