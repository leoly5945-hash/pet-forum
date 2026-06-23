<?php
/**
 * Community sidebar partial (lazy-loaded via AJAX).
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sidebar   = class_exists( 'PF_Community_Sidebar' ) ? new PF_Community_Sidebar() : null;
$discussions = $sidebar ? $sidebar->get_recent_discussions( 10 ) : array();
$stats     = $sidebar ? $sidebar->get_stats() : array( 'members' => 0, 'topics' => 0 );
$news_count = (int) wp_count_posts( 'pet_news' )->publish;
$community_url = home_url( '/community/' );
?>
<?php if ( class_exists( 'PF_Ads' ) && PF_Ads::ads_enabled() ) : ?>
<div class="pf-sidebar-box pf-sidebar-ads-box">
	<?php echo PF_Ads::render_sidebar_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php endif; ?>
<div class="pf-sidebar-box">
	<h3 class="pf-sidebar-title">🔥 Thảo Luận Mới Nhất</h3>
	<?php if ( empty( $discussions ) ) : ?>
		<p class="pf-empty-sidebar">Chưa có thảo luận nào. <a href="<?php echo esc_url( $community_url ); ?>">Tham gia ngay →</a></p>
	<?php else : ?>
		<ul class="pf-discussion-list">
			<?php foreach ( $discussions as $disc ) : ?>
				<li class="pf-discussion-item">
					<div class="pf-disc-avatar">
						<?php echo get_avatar( (int) ( $disc['userid'] ?? 0 ), 36, '', '', array( 'class' => 'pf-avatar' ) ); ?>
					</div>
					<div class="pf-disc-content">
						<a href="<?php echo esc_url( $disc['topic_url'] ?? $community_url ); ?>" class="pf-disc-title">
							<?php echo esc_html( wp_trim_words( $disc['title'] ?? '', 10 ) ); ?>
						</a>
						<div class="pf-disc-meta">
							<span class="pf-disc-author"><?php echo esc_html( $disc['author_name'] ?? '' ); ?></span>
							<span class="pf-disc-forum">📂 <?php echo esc_html( $disc['forum_name'] ?? '' ); ?></span>
							<span class="pf-disc-replies">💬 <?php echo (int) ( $disc['reply_count'] ?? 0 ); ?> trả lời</span>
						</div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<a href="<?php echo esc_url( $community_url ); ?>" class="pf-view-all">Xem tất cả thảo luận →</a>
</div>

<div class="pf-sidebar-box pf-stats-box">
	<h3 class="pf-sidebar-title">📊 Cộng Đồng</h3>
	<div class="pf-stat-row"><span>👥 Thành viên</span><strong><?php echo esc_html( number_format_i18n( $stats['members'] ) ); ?></strong></div>
	<div class="pf-stat-row"><span>💬 Chủ đề</span><strong><?php echo esc_html( number_format_i18n( $stats['topics'] ) ); ?></strong></div>
	<div class="pf-stat-row"><span>📰 Tin tức</span><strong id="pfNewsCount"><?php echo esc_html( number_format_i18n( $news_count ) ); ?></strong></div>
	<?php if ( is_user_logged_in() ) : ?>
	<a href="<?php echo esc_url( $community_url ); ?>" class="pf-btn-join">Tham gia thảo luận →</a>
	<?php else : ?>
	<div class="pf-sidebar-auth">
		<a href="<?php echo esc_url( class_exists( 'PF_Register_V2' ) ? PF_Register_V2::register_url( 'member' ) : home_url( '/register/?type=member' ) ); ?>" class="pf-btn-join">🐾 Đăng ký thành viên</a>
		<a href="<?php echo esc_url( class_exists( 'PF_Register_V2' ) ? PF_Register_V2::register_url( 'vet' ) : home_url( '/register/?type=vet' ) ); ?>" class="pf-btn-join pf-btn-join--vet">🩺 Đăng ký bác sĩ</a>
		<a href="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>" class="pf-sidebar-login">Đã có tài khoản? Đăng nhập →</a>
	</div>
	<?php endif; ?>
</div>
