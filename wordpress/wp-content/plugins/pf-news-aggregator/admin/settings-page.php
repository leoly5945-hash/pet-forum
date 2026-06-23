<?php
/**
 * Admin settings for PF News Aggregator.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_News_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_pf_fetch_now', array( __CLASS__, 'handle_fetch_now' ) );
	}

	public static function add_menu() {
		add_menu_page(
			'PF News Aggregator',
			'📡 Pet News',
			'manage_options',
			'pf-news-aggregator',
			array( __CLASS__, 'render_page' ),
			'dashicons-rss',
			30
		);
	}

	public static function register_settings() {
		register_setting( 'pf_news_settings', 'pf_anthropic_api_key' );
		register_setting( 'pf_news_settings', 'pf_disabled_rss_sources' );
	}

	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_pf-news-aggregator' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'pf-news-admin', PF_NEWS_PLUGIN_URL . 'assets/admin.css', array(), PF_NEWS_VERSION );
	}

	public static function handle_fetch_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'pf-news-aggregator' ) );
		}
		check_admin_referer( 'pf_fetch_now' );

		$fetcher = PF_News_Aggregator::instance()->fetcher;
		$count   = $fetcher->run();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'pf-news-aggregator',
					'fetched' => $count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$disabled    = (array) get_option( 'pf_disabled_rss_sources', array() );
		$last_fetch  = get_option( 'pf_news_last_fetch', '—' );
		$news_count  = (int) wp_count_posts( 'pet_news' )->publish;
		$last_created = (int) get_option( 'pf_news_last_fetch_count', 0 );
		$fetched     = isset( $_GET['fetched'] ) ? intval( $_GET['fetched'] ) : null;
		$next_cron   = wp_next_scheduled( 'pf_fetch_rss_event' );
		?>
		<div class="wrap pf-news-admin">
			<h1>📡 PF News Aggregator</h1>

			<?php if ( null !== $fetched ) : ?>
				<div class="notice notice-success"><p>✅ Đã cập nhật tin tức — tạo mới <?php echo esc_html( (string) $fetched ); ?> bài.</p></div>
			<?php endif; ?>

			<div class="pf-admin-grid">
				<div class="pf-admin-card">
					<h2>Trạng thái</h2>
					<p>📦 <strong>Tổng tin tức:</strong> <?php echo esc_html( (string) $news_count ); ?></p>
					<p>🕐 <strong>Lần fetch gần nhất:</strong> <?php echo esc_html( $last_fetch ); ?></p>
					<p>📝 <strong>Bài tạo lần cuối:</strong> <?php echo esc_html( (string) $last_created ); ?></p>
					<p>⏰ <strong>Lần cập nhật kế tiếp:</strong>
						<?php
						if ( $next_cron ) {
							echo esc_html( human_time_diff( $next_cron, current_time( 'timestamp' ) ) . ' nữa' );
						} else {
							echo '❌ Chưa được lên lịch';
						}
						?>
					</p>
					<p>🔄 <strong>Tần suất:</strong> Mỗi 30 phút</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'pf_fetch_now' ); ?>
						<input type="hidden" name="action" value="pf_fetch_now">
						<?php submit_button( '🔄 Cập nhật tin tức ngay bây giờ', 'primary large', 'submit', false ); ?>
					</form>
				</div>

				<div class="pf-admin-card">
					<h2>Cài đặt API</h2>
					<form method="post" action="options.php">
						<?php settings_fields( 'pf_news_settings' ); ?>
						<table class="form-table">
							<tr>
								<th><label for="pf_anthropic_api_key">Anthropic API Key</label></th>
								<td>
									<input type="password" id="pf_anthropic_api_key" name="pf_anthropic_api_key"
										value="<?php echo esc_attr( get_option( 'pf_anthropic_api_key', '' ) ); ?>"
										class="regular-text" autocomplete="off">
									<p class="description">Hoặc đặt <code>ANTHROPIC_API_KEY</code> trong wp-config.php. Nếu trống → dùng Google Translate miễn phí.</p>
								</td>
							</tr>
						</table>

						<h3>Nguồn RSS</h3>
						<ul class="pf-source-list">
							<?php foreach ( PF_RSS_SOURCES as $source ) : ?>
								<li>
									<label>
										<input type="checkbox" name="pf_disabled_rss_sources[]"
											value="<?php echo esc_attr( $source['name'] ); ?>"
											<?php checked( in_array( $source['name'], $disabled, true ) ); ?>>
										<?php echo esc_html( $source['name'] ); ?> — <?php echo esc_html( $source['cat'] ); ?>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
						<p class="description">Tick = tắt nguồn đó.</p>
						<?php submit_button( 'Lưu cài đặt' ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
}
