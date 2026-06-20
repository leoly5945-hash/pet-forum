<?php
/**
 * Template Name: Unified Blog Page
 * Hiển thị tất cả bài viết không phân biệt ngôn ngữ Polylang.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_cat = isset( $_GET['category'] ) ? sanitize_title( wp_unslash( $_GET['category'] ) ) : '';

$query_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

if ( function_exists( 'pll_current_language' ) ) {
	$query_args['lang'] = '';
}

if ( $current_cat ) {
	$query_args['category_name'] = $current_cat;
}

$blog_query = new WP_Query( $query_args );

$filter_categories = array(
	array( 'slug' => '', 'label' => '✨ Tất cả / All' ),
	array( 'slug' => 'tam-su-cung-boss', 'label' => '💬 Tâm Sự Cùng Boss' ),
	array( 'slug' => 'kinh-nghiem-nuoi-thu-cung', 'label' => '📚 Kinh Nghiệm Nuôi Thú Cưng' ),
	array( 'slug' => 'suc-khoe-dinh-duong', 'label' => '🩺 Sức Khỏe & Dinh Dưỡng' ),
	array( 'slug' => 'khoanh-khac-dang-nho', 'label' => '📸 Khoảnh Khắc Đáng Nhớ' ),
	array( 'slug' => 'pet-stories', 'label' => '💬 Pet Stories' ),
	array( 'slug' => 'care-experience', 'label' => '📚 Care & Experience' ),
	array( 'slug' => 'health-nutrition', 'label' => '🩺 Health & Nutrition' ),
	array( 'slug' => 'memorable-moments', 'label' => '📸 Memorable Moments' ),
);

$page_url = get_permalink();
?>

<style>
.page-template-page-blog .ast-container,
.unified-blog-page .ast-container {
	display: block;
	max-width: 100%;
	padding: 0;
}
.unified-blog-wrap {
	width: 100%;
}
.page-template-page-blog #primary,
.unified-blog-page #primary {
	width: 100%;
	max-width: 100%;
}
.page-template-page-blog .entry-header,
.page-template-page-blog .entry-content,
.unified-blog-page .entry-header,
.unified-blog-page .entry-content {
	display: none;
}

.blog-hero {
	background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
	color: #fff;
	text-align: center;
	padding: 48px 20px 40px;
	margin-bottom: 40px;
}
.blog-hero h1 {
	font-size: 2.2rem;
	font-weight: 800;
	color: #fff;
	margin: 0 0 8px;
}
.blog-hero p {
	color: #94a3b8;
	margin: 0;
	font-size: 1rem;
}

.blog-filter-bar {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	justify-content: center;
	margin-bottom: 36px;
	padding: 0 20px;
}
.filter-btn {
	padding: 8px 18px;
	border-radius: 20px;
	border: 2px solid #e2e8f0;
	background: #fff;
	color: #475569;
	cursor: pointer;
	font-size: 0.88rem;
	font-weight: 500;
	text-decoration: none;
	transition: all 0.2s;
}
.filter-btn:hover,
.filter-btn.active {
	background: #0ea5e9;
	border-color: #0ea5e9;
	color: #fff;
	text-decoration: none;
}

.blog-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 24px;
	max-width: 1100px;
	margin: 0 auto 48px;
	padding: 0 24px;
}
@media (max-width: 900px) {
	.blog-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}
@media (max-width: 580px) {
	.blog-grid {
		grid-template-columns: 1fr;
		padding: 0 16px;
	}
	.blog-hero h1 {
		font-size: 1.75rem;
	}
}

.blog-card {
	background: #fff;
	border-radius: 14px;
	overflow: hidden;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.07);
	border: 1px solid #e2e8f0;
	transition: transform 0.2s, box-shadow 0.2s;
	display: flex;
	flex-direction: column;
}
.blog-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 28px rgba(14, 165, 233, 0.15);
}

.blog-card-meta {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 14px 18px 0;
	gap: 8px;
	flex-wrap: wrap;
}
.blog-card-cat {
	font-size: 0.78rem;
	font-weight: 600;
	color: #0ea5e9;
	text-decoration: none;
}
.blog-card-cat:hover {
	color: #0284c7;
}
.lang-badge {
	font-size: 0.72rem;
	font-weight: 700;
	padding: 3px 10px;
	border-radius: 12px;
	background: #f1f5f9;
	color: #475569;
	white-space: nowrap;
}
.lang-badge.lang-vi {
	background: #fef3c7;
	color: #92400e;
}
.lang-badge.lang-en {
	background: #dbeafe;
	color: #1e40af;
}

.blog-card-body {
	padding: 12px 18px 18px;
	flex: 1;
	display: flex;
	flex-direction: column;
}
.blog-card-title {
	font-size: 1.05rem;
	font-weight: 700;
	line-height: 1.4;
	margin: 0 0 10px;
}
.blog-card-title a {
	color: #1e293b;
	text-decoration: none;
}
.blog-card-title a:hover {
	color: #0ea5e9;
}
.blog-card-excerpt {
	font-size: 0.88rem;
	color: #64748b;
	line-height: 1.55;
	margin: 0 0 14px;
	flex: 1;
}
.blog-card-footer {
	font-size: 0.78rem;
	color: #94a3b8;
	display: flex;
	align-items: center;
	gap: 6px;
}

.blog-empty {
	text-align: center;
	padding: 60px 20px;
	color: #64748b;
	font-size: 1.1rem;
}
.blog-count {
	text-align: center;
	color: #94a3b8;
	font-size: 0.9rem;
	margin: -20px 0 28px;
}
</style>

<div class="unified-blog-wrap">
<div class="blog-hero">
	<h1>✍️ Blog — Tâm Sự & Kinh Nghiệm</h1>
	<p>Chia sẻ câu chuyện, kinh nghiệm nuôi thú cưng — song ngữ Việt &amp; English</p>
</div>

<div class="blog-filter-bar">
	<?php foreach ( $filter_categories as $cat ) : ?>
		<?php
		$is_active = ( $current_cat === $cat['slug'] );
		$href      = $cat['slug'] ? add_query_arg( 'category', $cat['slug'], $page_url ) : $page_url;
		?>
		<a href="<?php echo esc_url( $href ); ?>"
		   class="filter-btn<?php echo $is_active ? ' active' : ''; ?>">
			<?php echo esc_html( $cat['label'] ); ?>
		</a>
	<?php endforeach; ?>
</div>

<?php if ( $blog_query->have_posts() ) : ?>
	<p class="blog-count">
		<?php
		printf(
			/* translators: %d: number of posts */
			esc_html( _n( '%d bài viết', '%d bài viết', $blog_query->post_count, 'astra' ) ),
			(int) $blog_query->post_count
		);
		?>
	</p>

	<div class="blog-grid">
		<?php
		while ( $blog_query->have_posts() ) :
			$blog_query->the_post();
			$post_id   = get_the_ID();
			$lang      = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $post_id ) : '';
			$lang_cls  = $lang ? ' lang-' . esc_attr( $lang ) : '';
			$lang_lbl  = ( 'vi' === $lang ) ? '🇻🇳 VI' : ( ( 'en' === $lang ) ? '🇺🇸 EN' : '🌐' );
			$categories = get_the_category();
			$cat_name  = ! empty( $categories ) ? $categories[0]->name : '';
			$cat_link  = ! empty( $categories ) ? get_category_link( $categories[0]->term_id ) : '';
			?>
			<article class="blog-card">
				<div class="blog-card-meta">
					<?php if ( $cat_name && $cat_link ) : ?>
						<a class="blog-card-cat" href="<?php echo esc_url( $cat_link ); ?>">
							<?php echo esc_html( $cat_name ); ?>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
					<span class="lang-badge<?php echo esc_attr( $lang_cls ); ?>"><?php echo esc_html( $lang_lbl ); ?></span>
				</div>
				<div class="blog-card-body">
					<h2 class="blog-card-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<p class="blog-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
					<div class="blog-card-footer">
						<span><?php echo esc_html( get_the_date() ); ?></span>
						<span>·</span>
						<span><?php the_author(); ?></span>
					</div>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
<?php else : ?>
	<p class="blog-empty">Chưa có bài viết nào trong danh mục này.</p>
<?php endif; ?>

<?php
wp_reset_postdata();
?>
</div><!-- .unified-blog-wrap -->
<?php
get_footer();
