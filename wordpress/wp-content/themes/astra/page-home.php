<?php
/**
 * Template Name: PF Home
 *
 * Hybrid homepage — 80% pet news + 20% community feed.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$featured_news = new WP_Query(
	array(
		'post_type'      => 'pet_news',
		'posts_per_page' => 7,
		'meta_key'       => '_pf_featured',
		'meta_value'     => '1',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'post_status'    => 'publish',
	)
);

if ( ! $featured_news->have_posts() ) {
	wp_reset_postdata();
	$featured_news = new WP_Query(
		array(
			'post_type'      => 'pet_news',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
		)
	);
}

$featured_ids = wp_list_pluck( $featured_news->posts, 'ID' );

$latest_news = new WP_Query(
	array(
		'post_type'      => 'pet_news',
		'posts_per_page' => 12,
		'post__not_in'   => $featured_ids,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'post_status'    => 'publish',
	)
);

$trending_news = new WP_Query(
	array(
		'post_type'      => 'pet_news',
		'posts_per_page' => 8,
		'orderby'        => 'rand',
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => 'news_category',
				'field'    => 'slug',
				'terms'    => array( 'products-trends', 'dog-news', 'cat-news' ),
			),
		),
	)
);

get_header();
?>

<div class="pf-home-wrapper">

	<section class="pf-hero">
		<?php if ( $featured_news->have_posts() ) : ?>
		<div class="pf-hero-grid">
			<?php $featured_news->the_post(); ?>
			<article class="pf-hero-main">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="pf-hero-img" style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>')"></div>
				<?php else : ?>
					<div class="pf-hero-img pf-hero-img--placeholder"></div>
				<?php endif; ?>
				<div class="pf-hero-content">
					<?php
					$cats = get_the_terms( get_the_ID(), 'news_category' );
					if ( $cats && ! is_wp_error( $cats ) ) {
						echo '<span class="pf-cat-badge">' . esc_html( $cats[0]->name ) . '</span>';
					}
					?>
					<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
					<span class="pf-time"><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> trước</span>
				</div>
			</article>

			<div class="pf-hero-sub">
				<?php
				for ( $i = 0; $i < 2 && $featured_news->have_posts(); $i++ ) :
					$featured_news->the_post();
					?>
				<article class="pf-hero-sub-item">
					<?php if ( has_post_thumbnail() ) : ?>
						<img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ); ?>" alt="">
					<?php endif; ?>
					<div>
						<?php
						$cats = get_the_terms( get_the_ID(), 'news_category' );
						if ( $cats && ! is_wp_error( $cats ) ) {
							echo '<span class="pf-cat-badge small">' . esc_html( $cats[0]->name ) . '</span>';
						}
						?>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<span class="pf-time"><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> trước</span>
					</div>
				</article>
				<?php endfor; ?>
			</div>
		</div>
		<?php else : ?>
		<div class="pf-hero-empty">
			<h2>📰 Tin tức thú cưng đang được cập nhật...</h2>
			<p>Chạy <code>wp pf-news fetch</code> để lấy tin từ RSS quốc tế.</p>
		</div>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</section>

	<div class="pf-main-layout">
		<main class="pf-news-grid-section">
			<h2 class="pf-section-title">📰 Tin Tức Thú Cưng Thế Giới</h2>
			<div class="pf-news-grid" id="pfNewsGrid">
				<?php
				if ( $latest_news->have_posts() ) :
					while ( $latest_news->have_posts() ) :
						$latest_news->the_post();
						get_template_part( 'partials/news', 'card' );
					endwhile;
					wp_reset_postdata();
				else :
					?>
					<p class="pf-empty-news">Chưa có tin tức. Hãy fetch RSS từ WP Admin → 📡 Pet News.</p>
				<?php endif; ?>
			</div>

			<?php if ( $latest_news->found_posts > 0 || $featured_news->found_posts > 0 ) : ?>
			<div class="pf-load-more-wrap">
				<button id="pfLoadMore" class="pf-btn-load" data-page="2" type="button">⬇ Xem thêm tin tức</button>
			</div>
			<?php endif; ?>
		</main>

		<aside class="pf-community-sidebar" id="pfCommunitySidebar" data-loaded="false">
			<div class="pf-sidebar-skeleton">⏳ Đang tải thảo luận...</div>
		</aside>
	</div>

	<?php if ( $trending_news->have_posts() ) : ?>
	<section class="pf-trending-section">
		<h2 class="pf-section-title">🔥 Xu Hướng</h2>
		<div class="pf-trending-scroll">
			<?php
			while ( $trending_news->have_posts() ) :
				$trending_news->the_post();
				$cats = get_the_terms( get_the_ID(), 'news_category' );
				?>
			<a href="<?php the_permalink(); ?>" class="pf-trending-card">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="pf-trending-img" style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ); ?>')"></div>
				<?php endif; ?>
				<div class="pf-trending-body">
					<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
						<span class="pf-cat-badge small"><?php echo esc_html( $cats[0]->name ); ?></span>
					<?php endif; ?>
					<h4><?php the_title(); ?></h4>
				</div>
			</a>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>
	<?php endif; ?>

</div>

<?php get_footer(); ?>
