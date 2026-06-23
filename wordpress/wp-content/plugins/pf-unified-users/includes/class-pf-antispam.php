<?php
defined( 'ABSPATH' ) || exit;

class PF_AntiSpam_V2 {

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
		add_action( 'wp_ajax_nopriv_pf_register_member', [ __CLASS__, 'verify_turnstile_on_register' ], 1 );
		add_action( 'wp_ajax_nopriv_pf_register_vet', [ __CLASS__, 'verify_turnstile_on_register' ], 1 );

		add_filter( 'registration_errors', [ __CLASS__, 'block_disposable_email' ], 10, 3 );

		add_filter( 'wpforo_add_post_data_filter', [ __CLASS__, 'moderate_post' ], 15, 1 );
		add_filter( 'wpforo_add_topic_data_filter', [ __CLASS__, 'moderate_topic' ], 15, 2 );

		add_action( 'wpforo_after_add_post', [ __CLASS__, 'flag_after_post' ], 10, 3 );
		add_action( 'wpforo_after_add_topic', [ __CLASS__, 'flag_after_topic' ], 10, 2 );

		add_filter( 'pre_user_display_name', [ __CLASS__, 'block_fake_vet_display_name' ], 10, 1 );
		add_action( 'personal_options_update', [ __CLASS__, 'block_self_role_meta' ], 1 );
		add_action( 'edit_user_profile_update', [ __CLASS__, 'block_self_role_meta' ], 1 );
		add_filter( 'wpforo_edit_profile', [ __CLASS__, 'sanitize_wpforo_profile' ], 10, 2 );
		add_action( 'wp_footer', [ __CLASS__, 'render_warning_banner' ] );

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
		$domain  = strtolower( substr( strrchr( $email, '@' ), 1 ) );
		$domains = array_merge( self::$disposable_domains, PF_Constants::DISPOSABLE_DOMAINS );

		return in_array( $domain, array_unique( $domains ), true );
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

		if ( PF_Constants::is_user_banned( $user_id ) ) {
			wp_die( esc_html__( 'Tài khoản của bạn đã bị khóa.', 'pf' ), '', [ 'response' => 403 ] );
		}

		if ( PF_Constants::is_user_restricted( $user_id ) ) {
			$data['status'] = 1;
		}

		$content = $data['body'] ?? '';
		$data    = self::check_fake_vet_content( $data, $user_id, $content, 'post' );

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

		if ( PF_Constants::is_user_banned( $user_id ) ) {
			wp_die( esc_html__( 'Tài khoản của bạn đã bị khóa.', 'pf' ), '', [ 'response' => 403 ] );
		}

		if ( PF_Constants::is_user_restricted( $user_id ) ) {
			$data['status'] = 1;
		}

		$content = ( $data['body'] ?? '' ) . ' ' . ( $data['title'] ?? '' );
		$data    = self::check_fake_vet_content( $data, $user_id, $content, 'topic' );

		if ( self::should_quarantine( $user_id ) && self::content_has_url_or_image( $content ) ) {
			$data['status'] = 1;
			self::log_spam( $user_id, null, 'topic', 'new_user_link_image', '' );
			return $data;
		}

		return self::apply_keyword_filter( $data, 'topic' );
	}

	private static function apply_keyword_filter( $data, $type ) {
		$custom_keywords = get_option( 'pf_banned_keywords', [] );
		$all_keywords    = array_merge( self::$banned_keywords, PF_Constants::DEFAULT_BANNED_KEYWORDS, (array) $custom_keywords );

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

	public static function log( $user_id, $type, $reason, $keyword, $ip = '' ) {
		global $wpdb;
		$wpdb->insert(
			"{$wpdb->prefix}pf_spam_log",
			[
				'user_id'    => $user_id,
				'post_id'    => null,
				'post_type'  => $type,
				'reason'     => $reason,
				'keyword'    => $keyword,
				'ip_address' => $ip ?: sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s' ]
		);
	}

	public static function block_self_role_meta( $user_id ) {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( (int) get_current_user_id() !== (int) $user_id ) {
			return;
		}

		unset( $_POST['pf_user_type'], $_POST['pf_vet_status'] );
	}

	public static function block_fake_vet_display_name( $display_name ) {
		if ( current_user_can( 'manage_options' ) ) {
			return $display_name;
		}

		$uid = get_current_user_id();
		if ( ! $uid || PF_Constants::is_verified_vet( $uid ) ) {
			return $display_name;
		}

		$vet_prefixes = [ 'bs.', 'dr.', 'bác sĩ', 'thú y', 'ths.', 'veterinarian', 'vet ' ];
		$lower        = mb_strtolower( $display_name );

		foreach ( $vet_prefixes as $prefix ) {
			if ( str_contains( $lower, $prefix ) ) {
				$current = get_userdata( $uid );
				return $current ? $current->display_name : $display_name;
			}
		}

		return $display_name;
	}

	public static function sanitize_wpforo_profile( $data, $userid ) {
		unset( $userid );
		if ( PF_Constants::is_verified_vet( get_current_user_id() ) ) {
			return $data;
		}

		$sig = mb_strtolower( $data['signature'] ?? '' );
		$vet_claims = [ 'bác sĩ thú y', 'bs. ', 'dr. ', 'veterinarian', 'thú y' ];

		foreach ( $vet_claims as $claim ) {
			if ( str_contains( $sig, $claim ) ) {
				$data['signature'] = '';
				break;
			}
		}

		return $data;
	}

	private static function check_fake_vet_content( $data, $user_id, $content, $type ) {
		if ( PF_Constants::is_verified_vet( $user_id ) ) {
			return $data;
		}

		$lower = mb_strtolower( strip_tags( $content ) );
		$fake_vet_phrases = [
			'với tư cách bác sĩ', 'tôi là bác sĩ', 'i am a vet', 'as a veterinarian',
			'với kinh nghiệm bác sĩ', 'theo chuyên môn của tôi',
		];

		foreach ( $fake_vet_phrases as $phrase ) {
			if ( str_contains( $lower, $phrase ) ) {
				$data['status'] = 1;
				self::log( $user_id, $type, 'fake_vet_claim', $phrase, sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );
				break;
			}
		}

		return $data;
	}

	public static function render_warning_banner() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$level   = PF_Constants::get_warn_level( $user_id );
		if ( $level < PF_Constants::WARN_WARNING || $level >= PF_Constants::WARN_BANNED ) {
			return;
		}

		$reason = get_user_meta( $user_id, PF_Constants::META_WARN_REASON, true );
		$lang   = get_user_meta( $user_id, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';

		$messages = [
			1 => [
				'vi' => '⚠️ Tài khoản của bạn nhận cảnh báo.',
				'en' => '⚠️ Your account has received a warning.',
			],
			2 => [
				'vi' => '🔴 Tài khoản của bạn nhận cảnh cáo nghiêm trọng.',
				'en' => '🔴 Your account has received a serious caution.',
			],
			3 => [
				'vi' => '🔒 Bài đăng của bạn sẽ được duyệt trước khi hiển thị.',
				'en' => '🔒 Your posts require approval before they appear.',
			],
		];

		$msg  = $messages[ $level ][ $lang ] ?? $messages[ $level ]['vi'];
		$bg   = $level >= 2 ? '#fef2f2' : '#fffbeb';
		$border = $level >= 2 ? '#fca5a5' : '#fcd34d';
		?>
		<div id="pf-warn-banner" style="position:fixed;bottom:16px;right:16px;z-index:99999;max-width:360px;padding:14px 18px;background:<?php echo esc_attr( $bg ); ?>;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.12);font-size:14px;line-height:1.5">
			<strong><?php echo esc_html( $msg ); ?></strong>
			<?php if ( $reason ) : ?>
				<p style="margin:6px 0 0;color:#64748b"><?php echo esc_html( $reason ); ?></p>
			<?php endif; ?>
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
