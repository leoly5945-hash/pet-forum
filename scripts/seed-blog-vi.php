<?php
/**
 * Seed bài blog tiếng Việt — Pet Forum Blog.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/seed-blog-vi.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( 1 );
}

global $wpdb;

$admin_id = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->users} ORDER BY ID LIMIT 1" );
if ( ! $admin_id ) {
	WP_CLI::error( 'No WordPress user found.' );
}
WP_CLI::log( "Author ID: {$admin_id}" );

$posts_vi = array(
	array(
		'title'    => '🐕 Ngày đầu tiên đón boss về nhà — mọi thứ tôi ước mình biết trước',
		'slug'     => 'ngay-dau-tien-don-boss-ve-nha',
		'category' => 'tam-su-cung-boss',
		'en_slug'  => 'bringing-puppy-home-first-day',
		'content'  => '<p>Tôi vẫn còn nhớ rõ cái ngày đó — một buổi chiều tháng 3, chiếc hộp carton nhỏ trên tay run run vì bên trong là một chú Golden Retriever 2 tháng tuổi đang khóc thút thít.</p>

<p>Tôi đã chuẩn bị <em>mọi thứ</em>. Hay ít nhất tôi nghĩ vậy. Chuồng ngủ mới, bát ăn silicon, đủ loại đồ chơi, thức ăn hạt premium. Nhưng không có gì chuẩn bị tôi cho đêm đầu tiên đó.</p>

<h2>Đêm đầu tiên — thảm họa có tên Caramel</h2>
<p>Boss khóc liên tục từ 11 giờ đêm đến 4 giờ sáng. Tôi ngồi cạnh chuồng, tay thò vào cho boss ngửi, nước mắt chảy không kém gì boss. Hàng xóm gõ tường 2 lần.</p>

<p>Sau này tôi mới biết: mẹo đơn giản nhất là đặt một chiếc đồng hồ cũ bọc trong khăn ấm vào chuồng — tiếng tích tắc giống nhịp tim mẹ, boss sẽ dịu lại ngay.</p>

<h2>3 điều tôi học được sau 1 năm nuôi Caramel</h2>
<ul>
<li><strong>Thú cưng cần routine hơn bạn nghĩ.</strong> Ăn đúng giờ, đi dạo đúng giờ, ngủ đúng giờ — một lịch sinh hoạt ổn định giúp boss ít lo âu hơn hẳn.</li>
<li><strong>Đừng bao giờ dùng đòn roi.</strong> Không hiệu quả và phá hủy lòng tin. Positive reinforcement (khen + thưởng) hiệu quả gấp 10 lần.</li>
<li><strong>Chi phí thú y luôn cao hơn dự tính.</strong> Lập quỹ khẩn cấp riêng cho boss — ít nhất 3-5 triệu/năm.</li>
</ul>

<p>Caramel giờ đã 14 tháng tuổi, nặng 28kg và vẫn nghĩ mình là chó con. Nhìn lại, tôi không hối hận một ngày nào — chỉ ước mình đã chuẩn bị kỹ hơn về <em>mặt tâm lý</em>, không phải vật chất.</p>

<p>Bạn có câu chuyện ngày đầu tiên nào không? Chia sẻ bên dưới nhé!</p>',
	),
	array(
		'title'    => '🐈 5 sai lầm phổ biến khi nuôi mèo lần đầu (và cách tôi đã sửa)',
		'slug'     => '5-sai-lam-nuoi-meo-lan-dau',
		'category' => 'kinh-nghiem-nuoi-thu-cung',
		'en_slug'  => 'five-first-time-cat-mistakes',
		'content'  => '<p>Tôi từng là người nghĩ rằng nuôi mèo thì <em>dễ hơn nuôi chó</em>. Mèo tự lập, tự vệ sinh, không cần đi dạo. Sai. Hoàn toàn sai.</p>

<p>Sau 3 năm nuôi 2 chú mèo — Luna (British Shorthair) và Mochi (mèo ta thuần chủng) — đây là những sai lầm tôi đã mắc phải và cách sửa:</p>

<h2>Sai lầm 1: Chỉ dùng 1 khay vệ sinh cho 2 mèo</h2>
<p>Quy tắc vàng: <strong>số khay = số mèo + 1</strong>. Mèo rất kén chỗ vệ sinh. Nếu khay không đủ sạch hoặc không đủ số lượng, chúng sẽ tìm chỗ khác — thường là góc tường yêu thích của bạn.</p>

<h2>Sai lầm 2: Cho mèo ăn thức ăn chó</h2>
<p>Mèo là động vật ăn thịt bắt buộc. Chúng cần taurine — axit amin chỉ có trong thịt động vật. Thiếu taurine gây mù và suy tim.</p>

<h2>Sai lầm 3: Không triệt sản sớm</h2>
<p>Mochi bắt đầu "động đực" lúc 5 tháng tuổi. Triệt sản sớm (4-6 tháng) giảm stress và ngăn ung thư tuyến vú.</p>

<h2>Sai lầm 4: Nghĩ mèo không cần chơi</h2>
<p>15-20 phút chơi mỗi ngày với cần câu lông giải quyết 80% vấn đề hành vi.</p>

<h2>Sai lầm 5: Bỏ qua nước uống</h2>
<p>Thức ăn hạt khô + uống ít = sỏi thận. Giải pháp: đài phun nước hoặc thức ăn ướt.</p>

<p>Nuôi mèo khó hơn tôi nghĩ, nhưng cũng thú vị hơn gấp nhiều lần.</p>',
	),
	array(
		'title'    => '🩺 Cách đọc hiểu kết quả xét nghiệm máu cho thú cưng',
		'slug'     => 'cach-doc-ket-qua-xet-nghiem-mau-thu-cung',
		'category' => 'suc-khoe-dinh-duong',
		'en_slug'  => 'how-to-read-pet-blood-test-results',
		'content'  => '<p>Lần đầu bác sĩ đưa cho tôi tờ kết quả xét nghiệm máu của Caramel, tôi nhìn vào một đống chữ viết tắt và con số mà không hiểu gì. BUN, ALT, CREA, HCT... Bài này giúp bạn hiểu những chỉ số quan trọng nhất trước khi gặp bác sĩ.</p>

<h2>Các chỉ số thường gặp</h2>
<ul>
<li><strong>BUN:</strong> Đánh giá chức năng thận. Cao có thể do suy thận, mất nước hoặc chế độ ăn giàu protein.</li>
<li><strong>CREA (Creatinine):</strong> Chỉ số thận đáng tin cậy hơn BUN.</li>
<li><strong>ALT / AST:</strong> Enzyme gan — tăng cao gợi ý tổn thương gan hoặc nhiễm trùng.</li>
<li><strong>HCT (Hematocrit):</strong> Tỷ lệ tế bào máu. Thấp = thiếu máu; cao = mất nước.</li>
<li><strong>WBC / RBC:</strong> Bạch cầu và hồng cầu — phát hiện nhiễm trùng hoặc thiếu máu.</li>
</ul>

<h2>Khi nào cần lo lắng?</h2>
<p>Đừng tự chẩn đoán từ một con số lệch nhẹ. Chỉ số vượt rõ rệt hoặc nhiều chỉ số bất thường cùng lúc mới cần can thiệp sớm.</p>

<p>Mẹo: chụp ảnh mọi kết quả xét nghiệm và lưu album riêng — theo dõi xu hướng quan trọng hơn một lần đo đơn lẻ.</p>',
	),
	array(
		'title'    => '🎂 Sinh nhật đầu tiên của Milo — một năm không thể quên',
		'slug'     => 'sinh-nhat-dau-tien-cua-milo',
		'category' => 'khoanh-khac-dang-nho',
		'en_slug'  => 'milos-first-birthday',
		'content'  => '<p>Hôm nay Milo tròn 1 tuổi. Đúng một năm trước, chú cún Beagle bé xíu này bước vào căn nhà và biến mọi thứ thành một cuộc phiêu lưu.</p>

<p>Tôi làm bánh thịt an toàn cho chó, treo bóng bay không latex, và mời 3 "bạn chó" cùng khu chung cư. Milo không quan tâm bánh — boss chỉ muốn chạy đuổi bóng bay.</p>

<p>Khoảnh khắc đáng nhớ nhất: buổi tối, sau khi khách về, Milo ngủ gục đầu lên đùi tôi, thở đều, đuôi vẫn run run trong mơ.</p>

<p>Chia sẻ kỷ niệm sinh nhật hoặc ngày adoption của boss nhà bạn nhé! 🐾</p>',
	),
	array(
		'title'    => '🐾 Boss cứu hộ Bắp — từ đường phố về nhà sau 3 tháng chăm sóc',
		'slug'     => 'boss-cuu-ho-bap-tu-duong-pho-ve-nha',
		'category' => 'tam-su-cung-boss',
		'en_slug'  => '',
		'content'  => '<p>Tôi gặp Bắp — một chú chó ta gầy trơ xương — ở góc đường Nguyễn Văn Cừ một buổi tối mưa. Boss run lại, không sủa, chỉ nhìn tôi bằng đôi mắt mệt mỏi.</p>

<p>3 tháng đầu là cuộc chiến: giun sán, da nấm, sợ người, không dám ăn thức ăn khô. Tôi cho ăn cháo gà nhỏ, đưa đi khám từng tuần, và quan trọng nhất — <em>không ép boss làm gì boss chưa sẵn sàng</em>.</p>

<h2>Những dấu hiệu boss đang tin tưởng lại</h2>
<ul>
<li>Tuần 2: Boss tự ngủ gần chân giường thay vì trốn dưới bàn.</li>
<li>Tuần 6: Lần đầu boss đuổi theo bóng tennis — tôi khóc vì vui.</li>
<li>Tháng 3: Boss chủ động đặt đầu lên đùi tôi khi xem phim.</li>
</ul>

<p>Bắp giờ nặng 12kg, lông bóng mượt, và là "ông hoàng" của cả khu chung cư. Cứu một sinh mạng không cần phải lớn lao — đôi khi chỉ cần một bữa ăn ấm và kiên nhẫn.</p>

<p>Bạn từng nhận nuôi boss cứu hộ chưa? Kể câu chuyện của bạn nhé!</p>',
	),
);

$created = 0;
$updated = 0;
$skipped = 0;

foreach ( $posts_vi as $post_data ) {
	$term = get_term_by( 'slug', $post_data['category'], 'category' );
	if ( ! $term ) {
		WP_CLI::warning( "Category not found: {$post_data['category']}" );
		continue;
	}

	$existing = get_page_by_path( $post_data['slug'], OBJECT, 'post' );
	if ( $existing ) {
		$post_id = (int) $existing->ID;
		WP_CLI::log( "Exists #{$post_id}: {$post_data['slug']}" );
		++$skipped;

		if ( function_exists( 'pll_get_post_language' ) && 'vi' !== pll_get_post_language( $post_id ) ) {
			pll_set_post_language( $post_id, 'vi' );
			WP_CLI::log( "  → Set language vi" );
			++$updated;
		}
	} else {
		$post_id = wp_insert_post(
			array(
				'post_title'    => $post_data['title'],
				'post_name'     => $post_data['slug'],
				'post_content'  => $post_data['content'],
				'post_status'   => 'publish',
				'post_type'     => 'post',
				'post_author'   => $admin_id,
				'post_category' => array( (int) $term->term_id ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "Failed {$post_data['slug']}: " . $post_id->get_error_message() );
			continue;
		}

		WP_CLI::log( "Created #{$post_id}: {$post_data['title']}" );
		++$created;

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $post_id, 'vi' );
		}
	}

	if ( ! empty( $post_data['en_slug'] ) && function_exists( 'pll_save_post_translations' ) ) {
		$en_post = get_page_by_path( $post_data['en_slug'], OBJECT, 'post' );
		if ( $en_post ) {
			$translations = pll_get_post_translations( $en_post->ID );
			$translations['en'] = (int) $en_post->ID;
			$translations['vi'] = $post_id;
			pll_save_post_translations( $translations );
			WP_CLI::log( "  → Linked vi#{$post_id} ↔ en#{$en_post->ID}" );
		}
	}
}

// Trang blog tiếng Việt (bản dịch của page_for_posts).
if ( function_exists( 'pll_get_post' ) ) {
	$en_blog_id = (int) get_option( 'page_for_posts' );
	if ( $en_blog_id ) {
		$vi_blog_id = (int) pll_get_post( $en_blog_id, 'vi' );

		if ( ! $vi_blog_id ) {
			$vi_blog_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_title'   => 'Blog — Tâm Sự & Kinh Nghiệm',
					'post_name'    => 'blog',
					'post_status'  => 'publish',
					'post_content' => '',
					'post_author'  => $admin_id,
				),
				true
			);

			if ( ! is_wp_error( $vi_blog_id ) ) {
				pll_set_post_language( $vi_blog_id, 'vi' );
				pll_save_post_translations(
					array(
						'en' => $en_blog_id,
						'vi' => $vi_blog_id,
					)
				);
				WP_CLI::log( "Created VI blog page #{$vi_blog_id} (translation of #{$en_blog_id})" );
			}
		} else {
			WP_CLI::log( "VI blog page already exists: #{$vi_blog_id}" );
		}
	}
}

WP_CLI::success( "VI blog seed: {$created} created, {$updated} updated, {$skipped} already existed." );
