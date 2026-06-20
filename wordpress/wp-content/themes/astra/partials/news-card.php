<?php
/**
 * Reusable news card partial.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article class="pf-news-card">
	<?php if ( has_post_thumbnail() ) : ?>
		<a href="<?php the_permalink(); ?>">
			<div class="pf-card-img" style="background-image:url('<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) ); ?>')"></div>
		</a>
	<?php endif; ?>
	<div class="pf-card-body">
		<?php
		$cats   = get_the_terms( get_the_ID(), 'news_category' );
		$source = get_post_meta( get_the_ID(), '_pf_source_name', true );
		if ( $cats && ! is_wp_error( $cats ) ) {
			echo '<span class="pf-cat-badge">' . esc_html( $cats[0]->name ) . '</span>';
		}
		if ( $source ) {
			echo '<span class="pf-source-badge">📡 ' . esc_html( $source ) . '</span>';
		}
		?>
		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="pf-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
		<div class="pf-card-footer">
			<span class="pf-time"><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ); ?> trước</span>
			<a href="<?php the_permalink(); ?>" class="pf-read-more">Đọc thêm →</a>
		</div>
	</div>
</article>
