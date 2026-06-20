<?php
/**
 * Seed blog posts mẫu — Pet Forum Blog (WordPress Posts).
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/seed-blog.php
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

$vi_categories = array(
	'tam-su-cung-boss'         => 13,
	'kinh-nghiem-nuoi-thu-cung' => 15,
	'suc-khoe-dinh-duong'      => 17,
	'khoanh-khac-dang-nho'     => 19,
);

$en_categories = array(
	'pet-stories'       => 21,
	'care-experience'   => 23,
	'health-nutrition'  => 25,
	'memorable-moments' => 27,
);

if ( function_exists( 'pll_set_term_language' ) ) {
	foreach ( $vi_categories as $slug => $term_id ) {
		pll_set_term_language( $term_id, 'vi' );
	}
	foreach ( $en_categories as $slug => $term_id ) {
		pll_set_term_language( $term_id, 'en' );
	}
}

$posts = array(
	// ── Tiếng Việt ──────────────────────────────────────────────────────────
	array(
		'title'    => '🐕 Ngày đầu tiên đón boss về nhà — mọi thứ tôi ước mình biết trước',
		'slug'     => 'ngay-dau-tien-don-boss-ve-nha',
		'category' => 'tam-su-cung-boss',
		'lang'     => 'vi',
		'content'  => '<p>Tôi vẫn còn nhớ rõ cái ngày đó — một buổi chiều tháng 3, chiếc hộp carton nhỏ trên tay run run vì bên trong là một chú Golden Retriever 2 tháng tuổi đang khóc thút thít.</p>

<p>Tôi đã chuẩn bị <em>mọi thứ</em>. Hay ít nhất tôi nghĩ vậy. Chuồng ngủ mới, bát ăn silicon, đủ loại đồ chơi, thức ăn hạt premium. Nhưng không có gì chuẩn bị tôi cho đêm đầu tiên đó.</p>

<h2>Đêm đầu tiên — thảm họa có tên Caramel</h2>
<p>Boss khóc liên tục từ 11 giờ đêm đến 4 giờ sáng. Tôi ngồi cạnh chuồng, tay thò vào cho boss ngửi, nước mắt chảy không kém gì boss. Hàng xóm gõ tường 2 lần.</p>

<p>Sau này tôi mới biết: chó con vừa rời mẹ sẽ rất stress. Mẹo đơn giản nhất là đặt một chiếc đồng hồ cũ bọc trong khăn ấm vào chuồng — tiếng tích tắc giống nhịp tim mẹ, boss sẽ dịu lại. Giá mà ai đó nói với tôi điều này sớm hơn!</p>

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
		'lang'     => 'vi',
		'content'  => '<p>Tôi từng là người nghĩ rằng nuôi mèo thì <em>dễ hơn nuôi chó</em>. Mèo tự lập, tự vệ sinh, không cần đi dạo. Sai. Hoàn toàn sai.</p>

<p>Sau 3 năm nuôi 2 chú mèo — Luna (British Shorthair) và Mochi (mèo ta thuần chủng) — đây là những sai lầm tôi đã mắc phải và cách sửa:</p>

<h2>Sai lầm 1: Chỉ dùng 1 khay vệ sinh cho 2 mèo</h2>
<p>Quy tắc vàng: <strong>số khay = số mèo + 1</strong>. Mèo rất kén chỗ vệ sinh. Nếu khay không đủ sạch hoặc không đủ số lượng, chúng sẽ... tìm chỗ khác. Thường là góc tường yêu thích của bạn.</p>

<h2>Sai lầm 2: Cho mèo ăn thức ăn chó</h2>
<p>Mèo là động vật ăn thịt bắt buộc (obligate carnivore). Chúng cần taurine — một axit amin chỉ có trong thịt động vật. Thiếu taurine gây mù và suy tim. Tôi phát hiện ra điều này khi Luna bắt đầu có dấu hiệu mắt kém sau 3 tháng ăn nhầm đồ.</p>

<h2>Sai lầm 3: Không sterilize sớm</h2>
<p>Mochi bắt đầu "động đực" lúc 5 tháng tuổi. Hàng xóm phàn nàn vì tiếng kêu. Triệt sản sớm (4-6 tháng) không chỉ giảm stress cho mèo mà còn ngăn ngừa ung thư tuyến vú và các bệnh sinh sản.</p>

<h2>Sai lầm 4: Nghĩ mèo không cần chơi</h2>
<p>Mèo trong nhà không săn mồi → tích năng lượng → stress → phá đồ. 15-20 phút chơi mỗi ngày với cần câu lông hoặc đèn laser giải quyết 80% vấn đề hành vi.</p>

<h2>Sai lầm 5: Bỏ qua nước uống</h2>
<p>Mèo có bản năng uống ít nước (tổ tiên chúng sống ở sa mạc, lấy nước từ con mồi). Thức ăn hạt khô + uống ít = sỏi thận. Giải pháp: đài phun nước tự động hoặc kết hợp thức ăn ướt.</p>

<p>Nuôi mèo khó hơn tôi nghĩ, nhưng cũng thú vị hơn gấp nhiều lần. Luna và Mochi đã dạy tôi về sự kiên nhẫn, quan sát và tôn trọng bản năng của sinh vật khác.</p>',
	),
	array(
		'title'    => '🩺 Cách đọc hiểu kết quả xét nghiệm máu cho thú cưng',
		'slug'     => 'cach-doc-ket-qua-xet-nghiem-mau-thu-cung',
		'category' => 'suc-khoe-dinh-duong',
		'lang'     => 'vi',
		'content'  => '<p>Lần đầu bác sĩ đưa cho tôi tờ kết quả xét nghiệm máu của Caramel, tôi nhìn vào một đống chữ viết tắt và con số mà không hiểu gì. BUN, ALT, CREA, HCT... Bài này giúp bạn hiểu những chỉ số quan trọng nhất trước khi gặp bác sĩ.</p>

<h2>Các chỉ số thường gặp</h2>
<ul>
<li><strong>BUN (Blood Urea Nitrogen):</strong> Đánh giá chức năng thận. Cao có thể do suy thận, mất nước hoặc chế độ ăn giàu protein.</li>
<li><strong>CREA (Creatinine):</strong> Chỉ số thận đáng tin cậy hơn BUN. Tăng đều đặn thường báo hiệu suy thận mạn.</li>
<li><strong>ALT / AST:</strong> Enzyme gan. Tăng cao gợi ý tổn thương gan, nhiễm trùng hoặc phản ứng thuốc.</li>
<li><strong>HCT (Hematocrit):</strong> Tỷ lệ tế bào máu. Thấp = thiếu máu; cao = mất nước hoặc bệnh lý khác.</li>
<li><strong>WBC / RBC:</strong> Bạch cầu và hồng cầu — giúp phát hiện nhiễm trùng, viêm hoặc thiếu máu.</li>
</ul>

<h2>Khi nào cần lo lắng?</h2>
<p>Đừng tự chẩn đoán từ một con số lệch nhẹ. Phòng khám sẽ in khoảng tham chiếu (reference range) theo loài và tuổi. Chỉ số <em>vượt rõ rệt</em> hoặc kết hợp nhiều chỉ số bất thường mới cần can thiệp sớm.</p>

<p>Mẹo của tôi: chụp ảnh mọi kết quả xét nghiệm và lưu vào album riêng trên điện thoại. Theo dõi theo thời gian giúp bác sĩ thấy xu hướng — quan trọng hơn một lần đo đơn lẻ.</p>

<p>Bạn từng bối rối với kết quả máu của boss chưa? Hỏi thêm ở phần bình luận nhé!</p>',
	),
	array(
		'title'    => '🎂 Sinh nhật đầu tiên của Milo — một năm không thể quên',
		'slug'     => 'sinh-nhat-dau-tien-cua-milo',
		'category' => 'khoanh-khac-dang-nho',
		'lang'     => 'vi',
		'content'  => '<p>Hôm nay Milo tròn 1 tuổi. Đúng một năm trước, chú cún Beagle bé xíu này bước vào căn nhà và biến mọi thứ thành một cuộc phiêu lưu.</p>

<p>Tôi làm bánh thịt (công thức an toàn cho chó — thịt bò xay, bí đỏ, trứng), treo bóng bay không latex, và mời 3 "bạn chó" cùng khu chung cư. Milo không quan tâm bánh — boss chỉ muốn chạy đuổi bóng bay.</p>

<p>Khoảnh khắc đáng nhớ nhất: buổi tối, sau khi khách về, Milo ngủ gục đầu lên đùi tôi, thở đều, đuôi vẫn run run trong mơ. Tôi nhận ra nuôi thú cưng không phải về những bữa tiệc hoành tráng — mà là những khoảnh khắc yên bình như vậy.</p>

<p>Chia sẻ kỷ niệm sinh nhật hoặc ngày adoption của boss nhà bạn nhé! 🐾</p>',
	),

	// ── English ───────────────────────────────────────────────────────────────
	array(
		'title'    => '🐕 Bringing Your Puppy Home — What I Wish I Knew on Day One',
		'slug'     => 'bringing-puppy-home-first-day',
		'category' => 'pet-stories',
		'lang'     => 'en',
		'content'  => '<p>I still remember that March afternoon — a small cardboard box in my hands, trembling because inside was a 2-month-old Golden Retriever whimpering softly.</p>

<p>I had prepared <em>everything</em>. Or so I thought. New crate, silicone bowls, toys galore, premium kibble. Nothing prepared me for that first night.</p>

<h2>First night — disaster named Biscuit</h2>
<p>He cried from 11 PM to 4 AM. I sat beside the crate with my hand inside so he could smell me. The neighbors knocked on the wall twice.</p>

<p>Later I learned: puppies separated from their mother are extremely stressed. The simplest trick is a warm towel wrapped around an old ticking clock in the crate — it mimics a heartbeat and helps them settle. I wish someone had told me sooner!</p>

<h2>Three lessons after one year</h2>
<ul>
<li><strong>Routine matters more than you think.</strong> Meals, walks, and bedtime at consistent times reduce anxiety dramatically.</li>
<li><strong>Never use punishment-based training.</strong> It destroys trust. Positive reinforcement works far better.</li>
<li><strong>Vet bills exceed every budget.</strong> Keep an emergency fund — plan for the unexpected.</li>
</ul>

<p>Biscuit is 14 months old now, 62 lbs, and still convinced he\'s a lap dog. No regrets — only wish I\'d prepared emotionally, not just materially.</p>',
	),
	array(
		'title'    => '🐈 Five Common First-Time Cat Owner Mistakes (And How I Fixed Them)',
		'slug'     => 'five-first-time-cat-mistakes',
		'category' => 'care-experience',
		'lang'     => 'en',
		'content'  => '<p>I used to think cats were <em>easier than dogs</em>. Independent, self-cleaning, no walks needed. Wrong. Completely wrong.</p>

<p>After three years with Luna (British Shorthair) and Mochi (domestic shorthair), here are the mistakes I made and how I fixed them:</p>

<h2>Mistake 1: One litter box for two cats</h2>
<p>Golden rule: <strong>number of boxes = number of cats + 1</strong>. Cats are picky about cleanliness. Not enough boxes means they find alternatives — usually your favorite corner.</p>

<h2>Mistake 2: Feeding dog food to cats</h2>
<p>Cats are obligate carnivores and need taurine from animal tissue. Deficiency can cause blindness and heart failure. Luna\'s eye issues after three months on the wrong food taught me this the hard way.</p>

<h2>Mistake 3: Delaying spay/neuter</h2>
<p>Mochi started calling at five months. Early sterilization (4–6 months) reduces stress and prevents reproductive cancers.</p>

<h2>Mistake 4: Assuming cats don\'t need playtime</h2>
<p>Indoor cats without hunting outlets build energy → stress → destructive behavior. Fifteen minutes of daily play with a wand toy solves most issues.</p>

<h2>Mistake 5: Ignoring water intake</h2>
<p>Cats naturally drink little. Dry food plus low water intake = kidney stones. Use a fountain or add wet food to the diet.</p>',
	),
	array(
		'title'    => '🩺 How to Read Your Pet\'s Blood Test Results',
		'slug'     => 'how-to-read-pet-blood-test-results',
		'category' => 'health-nutrition',
		'lang'     => 'en',
		'content'  => '<p>The first time my vet handed me Biscuit\'s blood panel, I stared at abbreviations and numbers — BUN, ALT, CREA, HCT — with no idea what they meant. This guide covers the essentials before your next appointment.</p>

<h2>Common markers</h2>
<ul>
<li><strong>BUN:</strong> Kidney function indicator. Elevated levels may suggest kidney disease, dehydration, or high-protein diet.</li>
<li><strong>Creatinine (CREA):</strong> More reliable kidney marker than BUN alone.</li>
<li><strong>ALT / AST:</strong> Liver enzymes. Elevated values may indicate liver damage, infection, or medication reaction.</li>
<li><strong>HCT (Hematocrit):</strong> Red blood cell percentage. Low = anemia; high = dehydration or other conditions.</li>
<li><strong>WBC / RBC:</strong> White and red blood cells — infection, inflammation, or anemia.</li>
</ul>

<h2>When to worry</h2>
<p>Don\'t diagnose from a single slightly off value. Clinics print species-specific reference ranges. Worry when multiple markers trend wrong or values are clearly out of range.</p>

<p>Pro tip: photograph every lab result and keep a timeline. Trends matter more than one snapshot.</p>',
	),
	array(
		'title'    => '🎂 Milo\'s First Birthday — A Year We\'ll Never Forget',
		'slug'     => 'milos-first-birthday',
		'category' => 'memorable-moments',
		'lang'     => 'en',
		'content'  => '<p>Today Milo turned one. Exactly a year ago, this tiny Beagle walked into our home and turned everything into an adventure.</p>

<p>I made a dog-safe meat cake (ground beef, pumpkin, egg), hung latex-free balloons, and invited three neighborhood dog friends. Milo ignored the cake — he just wanted to chase balloons.</p>

<p>The best moment came after everyone left: Milo asleep on my lap, tail still wagging in his dreams. That\'s what pet ownership is really about — not the party, but the quiet moments afterward.</p>

<p>Share your pet\'s birthday or adoption day memories below! 🐾</p>',
	),
);

$created = 0;
$skipped = 0;

foreach ( $posts as $post_data ) {
	$existing = get_page_by_path( $post_data['slug'], OBJECT, 'post' );
	if ( $existing ) {
		WP_CLI::log( "Skip (exists): {$post_data['slug']}" );
		++$skipped;
		continue;
	}

	$cat_slug = $post_data['category'];
	$cat_id   = ( 'vi' === $post_data['lang'] )
		? ( $vi_categories[ $cat_slug ] ?? 0 )
		: ( $en_categories[ $cat_slug ] ?? 0 );

	if ( ! $cat_id ) {
		WP_CLI::warning( "Category not found: {$cat_slug}" );
		continue;
	}

	$post_id = wp_insert_post(
		array(
			'post_title'   => $post_data['title'],
			'post_name'    => $post_data['slug'],
			'post_content' => $post_data['content'],
			'post_status'  => 'publish',
			'post_type'    => 'post',
			'post_author'  => $admin_id,
			'post_category'=> array( $cat_id ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "Failed {$post_data['slug']}: " . $post_id->get_error_message() );
		continue;
	}

	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, $post_data['lang'] );
	}

	WP_CLI::log( "Created post #{$post_id}: {$post_data['title']}" );
	++$created;
}

WP_CLI::success( "Blog seed done: {$created} created, {$skipped} skipped." );
