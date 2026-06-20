<?php
defined( 'ABSPATH' ) || exit;

class PF_AntiSpam {

	private static $disposable_domains = [
		'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'throwam.com',
		'sharklasers.com', 'guerrillamailblock.com', 'grr.la', 'guerrillamail.info',
		'spam4.me', 'yopmail.com', 'trashmail.com', 'dispostable.com',
		'maildrop.cc', 'mailnull.com', 'spamgourmet.com', 'getairmail.com',
		'fakeinbox.com', 'tempr.email', 'discard.email', 'spamhere.net',
		'trbvm.com', 'spamwc.de', 'binkmail.com', 'haltospam.com', 'spamex.com',
		'10minutemail.com', 'temp-mail.org', 'tempemail.net',
		'mailtemp.info', 'spamgob.com', 'spoofmail.de', 'example.com',
	];

	private static $banned_keywords = [
		'casino', 'baccarat', 'xổ số', 'cá độ', 'cá cược', 'bet', 'poker',
		'slot game', 'nổ hũ', 'jackpot', 'keno', 'lô đề', 'vé số',
		'cho vay', 'vay tiền nhanh', 'lãi suất thấp', 'đầu tư sinh lời',
		'forex', 'bitcoin', 'crypto đầu tư', 'gấp vốn', 'làm giàu nhanh',
		'mmo', 'kiếm tiền online', 'passive income',
		'liên hệ ngay', 'hotline:', 'zalo:', 'telegram:', 'whatsapp:',
		'click here', 'limited offer', 'free gift', 'prize winner',
		'giảm giá', 'khuyến mãi', 'miễn phí 100%', 'tải ngay',
		'đéo', 'dcm', 'dm ', 'vãi', 'clgt', 'địt', 'chó chết',
	];

	public static function init() {
		add_action( 'wp_ajax_nopriv_pf_register', [ __CLASS__, 'verify_turnstile_on_register' ], 1 );

		add_filter( 'registration_errors', [ __CLASS__, 'block_disposable_email' ], 10, 3 );

		add_filter( 'wpforo_add_post_data_filter', [ __CLASS__, 'moderate_post' ], 15, 1 );
		add_filter( 'wpforo_add_topic_data_filter', [ __CLASS__, 'moderate_topic' ], 15, 2 );

		add_action( 'wpforo_after_add_post', [ __CLASS__, 'flag_after_post' ], 10, 3 );
		add_action( 'wpforo_after_add_topic', [ __CLASS__, 'flag_after_topic' ], 10, 2 );

		add_action( 'admin_menu', [ __CLASS__, 'register_admin_menu' ], 50 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'pf-spam report', [ __CLASS__, 'cli_spam_report' ] );
		}
	}

	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pf_spam_log (
			id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id     BIGINT UNSIGNED NOT NULL,
			post_id     BIGINT UNSIGNED,
			post_type   VARCHAR(20) NOT NULL DEFAULT 'post',
			reason      VARCHAR(255),
			keyword     VARCHAR(100),
			content     TEXT,
			ip_address  VARCHAR(45),
			created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_user (user_id),
			INDEX idx_created (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function verify_turnstile_on_register() {
		$secret_key = get_option( 'pf_turnstile_secret_key', '' );
		if ( ! $secret_key ) {
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_POST['cf_token'] ?? '' ) );
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => 'Vui lòng hoàn thành xác minh bảo mật (Turnstile).' ] );
		}

		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
			'body' => [
				'secret'   => $secret_key,
				'response' => $token,
				'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			],
		] );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['success'] ) ) {
			wp_send_json_error( [ 'message' => 'Xác minh bảo mật thất bại. Vui lòng thử lại.' ] );
		}
	}

	public static function is_disposable_email( $email ) {
		$domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );
		return in_array( $domain, self::$disposable_domains, true );
	}

	public static function block_disposable_email( $errors, $sanitized_user_login, $user_email ) {
		if ( self::is_disposable_email( $user_email ) ) {
			$errors->add(
				'disposable_email',
				'<strong>Lỗi:</strong> Email tạm thời không được chấp nhận. Vui lòng dùng Gmail, Yahoo, hoặc Outlook.'
			);
		}
		return $errors;
	}

	private static function should_quarantine( $user_id ) {
		$registered = get_user_meta( $user_id, 'pf_registered_at', true );
		if ( ! $registered ) {
			$user = get_userdata( $user_id );
			$registered = $user ? $user->user_registered : '';
		}
		$days_old = ( time() - strtotime( $registered ) ) / DAY_IN_SECONDS;

		global $wpdb;
		$table = self::get_posts_table();
		$post_count = 0;
		if ( $table ) {
			$post_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE userid = %d AND status = 0",
				$user_id
			) );
		}

		return ( $days_old < 3 || $post_count < 3 );
	}

	private static function get_posts_table() {
		if ( function_exists( 'WPF' ) && isset( WPF()->tables->posts ) ) {
			return WPF()->tables->posts;
		}
		global $wpdb;
		return $wpdb->prefix . 'wpforo_posts';
	}

	private static function content_has_url_or_image( $content ) {
		if ( preg_match( '/(https?:\/\/|www\.)[^\s]+/i', $content ) ) {
			return true;
		}
		if ( preg_match( '/<img[^>]+>/i', $content ) ) {
			return true;
		}
		if ( preg_match( '/\[img\]/i', $content ) ) {
			return true;
		}
		return false;
	}

	public static function moderate_post( $data ) {
		if ( ! is_user_logged_in() ) {
			return $data;
		}

		$user_id = get_current_user_id();
		$content = $data['body'] ?? '';

		if ( self::should_quarantine( $user_id ) && self::content_has_url_or_image( $content ) ) {
			$data['status'] = 1;
			self::log_spam( $user_id, null, 'post', 'new_user_link_image', '' );
			return $data;
		}

		return self::apply_keyword_filter( $data, 'post' );
	}

	public static function moderate_topic( $data, $forum ) {
		unset( $forum );
		if ( ! is_user_logged_in() ) {
			return $data;
		}

		$user_id = get_current_user_id();
		$content = ( $data['body'] ?? '' ) . ' ' . ( $data['title'] ?? '' );

		if ( self::should_quarantine( $user_id ) && self::content_has_url_or_image( $content ) ) {
			$data['status'] = 1;
			self::log_spam( $user_id, null, 'topic', 'new_user_link_image', '' );
			return $data;
		}

		return self::apply_keyword_filter( $data, 'topic' );
	}

	private static function apply_keyword_filter( $data, $type ) {
		$custom_keywords = get_option( 'pf_banned_keywords', [] );
		$all_keywords    = array_merge( self::$banned_keywords, (array) $custom_keywords );

		$content  = strtolower( $data['body'] ?? '' );
		$title    = strtolower( $data['title'] ?? '' );
		$combined = $content . ' ' . $title;

		foreach ( $all_keywords as $kw ) {
			$kw = strtolower( trim( $kw ) );
			if ( $kw && strpos( $combined, $kw ) !== false ) {
				$data['status'] = 1;
				self::log_spam( get_current_user_id(), null, $type, 'banned_keyword', $kw );
				break;
			}
		}

		return $data;
	}

	public static function flag_after_post( $post, $topic, $forum ) {
		unset( $topic, $forum );
		if ( (int) ( $post['status'] ?? 0 ) !== 1 ) {
			return;
		}
		self::notify_admin_pending( $post['body'] ?? '' );
	}

	public static function flag_after_topic( $topic, $forum ) {
		unset( $forum );
		if ( (int) ( $topic['status'] ?? 0 ) !== 1 ) {
			return;
		}
		$content = ( $topic['title'] ?? '' ) . "\n" . ( $topic['body'] ?? '' );
		self::notify_admin_pending( $content );
	}

	private static function notify_admin_pending( $content ) {
		$user        = wp_get_current_user();
		$admin_email = get_option( 'admin_email' );
		$mod_url     = function_exists( 'wpforo_prefix_slug' )
			? admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'moderations' ) )
			: admin_url();

		$subject = '[PetForum] Bài cần duyệt từ: ' . $user->display_name;
		$message = "Thành viên {$user->display_name} ({$user->user_email}) vừa đăng bài cần duyệt.\n\n"
			. "Xem và duyệt tại: {$mod_url}\n\n"
			. 'Bài: ' . substr( $content, 0, 200 );

		wp_mail( $admin_email, $subject, $message );
	}

	private static function log_spam( $user_id, $post_id, $type, $reason, $keyword ) {
		global $wpdb;
		$wpdb->insert(
			"{$wpdb->prefix}pf_spam_log",
			[
				'user_id'    => $user_id,
				'post_id'    => $post_id,
				'post_type'  => $type,
				'reason'     => $reason,
				'keyword'    => $keyword,
				'ip_address' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s' ]
		);
	}

	public static function register_admin_menu() {
		add_submenu_page(
			'wpforo-overview',
			'PF Anti-Spam',
			'🛡 Anti-Spam',
			'manage_options',
			'pf-antispam',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page() {
		if ( ! empty( $_POST['pf_save_antispam'] ) ) {
			check_admin_referer( 'pf_antispam_save' );

			$keywords = array_filter( array_map( 'trim', explode( "\n", wp_unslash( $_POST['pf_banned_keywords'] ?? '' ) ) ) );
			update_option( 'pf_banned_keywords', $keywords );
			update_option( 'pf_turnstile_site_key', sanitize_text_field( wp_unslash( $_POST['pf_turnstile_site_key'] ?? '' ) ) );
			update_option( 'pf_turnstile_secret_key', sanitize_text_field( wp_unslash( $_POST['pf_turnstile_secret_key'] ?? '' ) ) );

			echo '<div class="notice notice-success"><p>✅ Đã lưu cài đặt Anti-Spam!</p></div>';
		}

		$keywords  = implode( "\n", (array) get_option( 'pf_banned_keywords', [] ) );
		$ts_site   = get_option( 'pf_turnstile_site_key', '' );
		$ts_secret = get_option( 'pf_turnstile_secret_key', '' );

		global $wpdb;
		$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pf_spam_log ORDER BY created_at DESC LIMIT 50" );
		?>
		<div class="wrap">
			<h1>🛡 PetForum Anti-Spam Settings</h1>
			<form method="POST">
				<?php wp_nonce_field( 'pf_antispam_save' ); ?>
				<h2>Cloudflare Turnstile</h2>
				<table class="form-table">
					<tr>
						<th>Site Key</th>
						<td><input type="text" name="pf_turnstile_site_key" value="<?php echo esc_attr( $ts_site ); ?>" class="regular-text" placeholder="0x4AAAAAAA..."></td>
					</tr>
					<tr>
						<th>Secret Key</th>
						<td><input type="password" name="pf_turnstile_secret_key" value="<?php echo esc_attr( $ts_secret ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th colspan="2">
							<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">
								→ Lấy key miễn phí tại Cloudflare Turnstile Dashboard
							</a>
						</th>
					</tr>
				</table>

				<h2>Từ khóa cấm (mỗi dòng 1 từ)</h2>
				<textarea name="pf_banned_keywords" rows="12" cols="60" class="large-text"><?php echo esc_textarea( $keywords ); ?></textarea>
				<p class="description">Bài chứa các từ này sẽ tự động chuyển sang Pending để Mod duyệt.</p>

				<p><input type="submit" name="pf_save_antispam" class="button button-primary" value="💾 Lưu cài đặt"></p>
			</form>

			<hr>
			<h2>📋 Spam Log (50 mục gần nhất)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>User</th><th>Loại</th><th>Lý do</th><th>Keyword</th><th>IP</th><th>Thời gian</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
					<?php $log_user = get_userdata( $log->user_id ); ?>
					<tr>
						<td><?php echo esc_html( $log_user ? $log_user->display_name : "User #{$log->user_id}" ); ?></td>
						<td><?php echo esc_html( $log->post_type ); ?></td>
						<td><?php echo esc_html( $log->reason ); ?></td>
						<td><?php echo esc_html( $log->keyword ); ?></td>
						<td><?php echo esc_html( $log->ip_address ); ?></td>
						<td><?php echo esc_html( $log->created_at ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function cli_spam_report( $args, $assoc_args ) {
		global $wpdb;
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pf_spam_log" );
		$recent = $wpdb->get_results( "SELECT reason, COUNT(*) as cnt FROM {$wpdb->prefix}pf_spam_log GROUP BY reason ORDER BY cnt DESC LIMIT 10" );

		WP_CLI::log( "Total spam log entries: {$count}" );
		WP_CLI::log( 'Top reasons:' );
		foreach ( $recent as $row ) {
			WP_CLI::log( "  - {$row->reason}: {$row->cnt}" );
		}
	}
}
