<?php
defined( 'ABSPATH' ) || exit;

class PF_AI_Chatbot {

	const MAX_MESSAGES_PER_HOUR = 30;
	const MAX_MESSAGES_PER_DAY  = 100;

	public static function init(): void {
		add_action( 'wp_footer', [ __CLASS__, 'inject_chat_widget' ], 99 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_ajax_pf_ai_chat', [ __CLASS__, 'ajax_chat_proxy' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ], 20 );
	}

	public static function enqueue_assets(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! get_option( 'pf_ai_chatbot_enabled', true ) ) {
			return;
		}

		wp_enqueue_style( 'pf-chatbot', PFU_URL . 'assets/css/pf-chatbot.css', [], PFU_VERSION );
		wp_enqueue_script( 'pf-chatbot', PFU_URL . 'assets/js/pf-chatbot.js', [], PFU_VERSION, true );

		$config = self::get_client_config();
		if ( $config ) {
			wp_localize_script( 'pf-chatbot', 'pfChatbotConfig', $config );
		}
	}

	/**
	 * @return array|null
	 */
	private static function get_client_config(): ?array {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return null;
		}

		$user = get_userdata( $uid );
		if ( ! $user ) {
			return null;
		}

		$lang = class_exists( 'PF_I18n' ) ? PF_I18n::current_lang() : 'vi';
		$pref = get_user_meta( $uid, PF_Constants::META_PREFERRED_LANG, true );
		if ( in_array( $pref, [ 'vi', 'en' ], true ) ) {
			$lang = $pref;
		}

		$role   = PF_Constants::get_pf_role( $uid ) ?? 'subscriber';
		$is_vet = PF_Constants::ROLE_VERIFIED_VET === $role;
		$is_mod = in_array( $role, PF_Constants::SECTION_MOD_ROLES, true ) || PF_Constants::ROLE_GLOBAL_ADMIN === $role;

		return [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pf_ai_chat' ),
			'mode'    => self::get_ai_mode(),
			'lang'    => $lang,
			'metadata' => self::build_metadata( $uid, $user, $role, $lang, $is_vet, $is_mod ),
			'strings' => [
				'errorNetwork' => 'vi' === $lang ? 'Lỗi kết nối. Vui lòng thử lại.' : 'Connection error. Please try again.',
				'errorLimit'   => 'vi' === $lang ? 'Bạn đã đạt giới hạn tin nhắn hôm nay.' : "You have reached today's message limit.",
				'clearConfirm' => 'vi' === $lang ? 'Xóa toàn bộ hội thoại?' : 'Clear all messages?',
				'thinking'     => 'vi' === $lang ? 'Đang suy nghĩ...' : 'Thinking...',
			],
		];
	}

	private static function get_ai_mode(): string {
		return defined( 'PF_AI_MODE' ) ? (string) PF_AI_MODE : 'dify';
	}

	/**
	 * @param WP_User $user User object.
	 */
	private static function build_metadata( int $uid, $user, ?string $role, string $lang, bool $is_vet, bool $is_mod ): array {
		if ( $is_vet ) {
			$role_label_vi = 'Bác sĩ thú y';
			$role_label_en = 'Veterinarian';
		} elseif ( $is_mod ) {
			$role_label_vi = 'Quản trị viên';
			$role_label_en = 'Moderator';
		} else {
			$role_label_vi = 'Thành viên';
			$role_label_en = 'Member';
		}

		return [
			'user_id'         => $uid,
			'username'        => $user->display_name,
			'email_hash'      => md5( strtolower( trim( $user->user_email ) ) ),
			'role'            => $role,
			'role_label_vi'   => $role_label_vi,
			'role_label_en'   => $role_label_en,
			'preferred_lang'  => $lang,
			'is_vet'          => $is_vet,
			'forum_url'       => home_url( '/community/' ),
			'vet_section_url' => home_url( '/forum/bac-si-tu-van' ),
		];
	}

	public static function inject_chat_widget(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! get_option( 'pf_ai_chatbot_enabled', true ) ) {
			return;
		}

		$uid  = get_current_user_id();
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return;
		}

		$lang   = class_exists( 'PF_I18n' ) ? PF_I18n::current_lang() : 'vi';
		$pref   = get_user_meta( $uid, PF_Constants::META_PREFERRED_LANG, true );
		if ( in_array( $pref, [ 'vi', 'en' ], true ) ) {
			$lang = $pref;
		}
		$role   = PF_Constants::get_pf_role( $uid ) ?? 'subscriber';
		$is_vet = PF_Constants::ROLE_VERIFIED_VET === $role;
		$is_vi  = 'vi' === $lang;
		?>
		<div id="pf-chatbot-bubble" class="pf-chatbot-bubble" aria-label="<?php echo esc_attr( $is_vi ? 'Hỗ trợ AI' : 'AI Support' ); ?>">
			<span class="pf-chatbot-icon">🐾</span>
			<span class="pf-chatbot-badge" id="pf-chatbot-badge" style="display:none">1</span>
		</div>

		<div id="pf-chatbot-panel" class="pf-chatbot-panel" role="dialog" aria-label="AI Assistant" hidden>
			<div class="pf-chatbot-header">
				<div class="pf-chatbot-header-info">
					<span class="pf-chatbot-avatar">🌿</span>
					<div>
						<div class="pf-chatbot-name">
							<?php echo esc_html( $is_vi ? 'Trợ lý Pet Forum' : 'Pet Forum Assistant' ); ?>
						</div>
						<div class="pf-chatbot-status">
							<span class="pf-chatbot-dot"></span>
							<?php echo esc_html( $is_vi ? 'Đang hoạt động' : 'Online' ); ?>
						</div>
					</div>
				</div>
				<div class="pf-chatbot-header-actions">
					<button type="button" id="pf-chatbot-clear" title="<?php echo esc_attr( $is_vi ? 'Xóa hội thoại' : 'Clear chat' ); ?>" aria-label="Clear chat">🗑</button>
					<button type="button" id="pf-chatbot-close" title="<?php echo esc_attr( $is_vi ? 'Đóng' : 'Close' ); ?>" aria-label="Close">✕</button>
				</div>
			</div>

			<div class="pf-chatbot-messages" id="pf-chatbot-messages" role="log" aria-live="polite">
				<div class="pf-chatbot-msg pf-chatbot-msg-bot pf-fade-in">
					<span class="pf-chatbot-msg-avatar">🌿</span>
					<div class="pf-chatbot-msg-content">
						<?php if ( $is_vi ) : ?>
							Xin chào <strong><?php echo esc_html( $user->display_name ); ?></strong>!
							<?php echo $is_vet ? 'Cảm ơn bạn đã đồng hành cùng diễn đàn với vai trò Bác sĩ thú y. ' : ''; ?>
							Tôi có thể giúp bạn về <strong>sơ cứu thú cưng khẩn cấp</strong>
							và <strong>nội quy diễn đàn</strong>. Bạn cần hỗ trợ gì? 🐾
						<?php else : ?>
							Hello <strong><?php echo esc_html( $user->display_name ); ?></strong>!
							<?php echo $is_vet ? 'Thank you for being a verified veterinarian on our forum. ' : ''; ?>
							I can help with <strong>pet first aid</strong>
							and <strong>forum guidelines</strong>. How can I help? 🐾
						<?php endif; ?>
					</div>
				</div>

				<div class="pf-chatbot-quick-actions">
					<?php if ( $is_vi ) : ?>
						<button type="button" class="pf-chatbot-quick" data-msg="Hướng dẫn nội quy diễn đàn">📋 Nội quy</button>
						<button type="button" class="pf-chatbot-quick" data-msg="Thú cưng của tôi bị ngộ độc, cần sơ cứu ngay">🚨 Ngộ độc</button>
						<button type="button" class="pf-chatbot-quick" data-msg="Cách thực hiện CPR cho chó/mèo">❤️ CPR</button>
						<button type="button" class="pf-chatbot-quick" data-msg="Thú cưng bị sốc nhiệt phải làm gì">🌡️ Sốc nhiệt</button>
					<?php else : ?>
						<button type="button" class="pf-chatbot-quick" data-msg="Explain the forum rules">📋 Rules</button>
						<button type="button" class="pf-chatbot-quick" data-msg="My pet is poisoned, need first aid now">🚨 Poisoning</button>
						<button type="button" class="pf-chatbot-quick" data-msg="How to perform CPR on a dog or cat">❤️ CPR</button>
						<button type="button" class="pf-chatbot-quick" data-msg="My pet has heat stroke, what to do">🌡️ Heat stroke</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="pf-chatbot-typing" id="pf-chatbot-typing" style="display:none">
				<span class="pf-chatbot-msg-avatar">🌿</span>
				<div class="pf-chatbot-typing-dots"><span></span><span></span><span></span></div>
			</div>

			<div class="pf-chatbot-input-wrap">
				<textarea
					id="pf-chatbot-input"
					placeholder="<?php echo esc_attr( $is_vi ? 'Nhập câu hỏi... (Enter để gửi)' : 'Type your question... (Enter to send)' ); ?>"
					rows="1"
					maxlength="1000"
					aria-label="Chat input"
				></textarea>
				<button type="button" id="pf-chatbot-send" aria-label="Send">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
						<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
					</svg>
				</button>
			</div>
			<div class="pf-chatbot-footer-note">
				<?php
				echo esc_html(
					$is_vi
						? '⚕️ Bot AI hỗ trợ sơ cứu — không thay thế bác sĩ thú y'
						: '⚕️ AI first aid assistant — not a substitute for a vet'
				);
				?>
			</div>
		</div>
		<?php
	}

	public static function ajax_chat_proxy(): void {
		check_ajax_referer( 'pf_ai_chat', 'nonce' );

		$uid = get_current_user_id();
		if ( ! $uid ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$hourly_key   = 'pf_ai_rate_' . $uid . '_' . gmdate( 'YmdH' );
		$daily_key    = 'pf_ai_rate_' . $uid . '_' . gmdate( 'Ymd' );
		$hourly_count = (int) get_transient( $hourly_key );
		$daily_count  = (int) get_transient( $daily_key );

		if ( $hourly_count >= self::MAX_MESSAGES_PER_HOUR ) {
			wp_send_json_error( [ 'message' => 'rate_limit_hour', 'retry_after' => 3600 ] );
		}
		if ( $daily_count >= self::MAX_MESSAGES_PER_DAY ) {
			wp_send_json_error( [ 'message' => 'rate_limit_day', 'retry_after' => 86400 ] );
		}

		$message         = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$conversation_id = isset( $_POST['conversation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['conversation_id'] ) ) : '';
		$metadata_raw    = isset( $_POST['metadata'] ) ? wp_unslash( $_POST['metadata'] ) : '{}';
		$metadata        = json_decode( is_string( $metadata_raw ) ? $metadata_raw : '{}', true );
		if ( ! is_array( $metadata ) ) {
			$metadata = [];
		}

		if ( '' === $message ) {
			wp_send_json_error( [ 'message' => 'Empty message' ] );
		}
		if ( strlen( $message ) > 1000 ) {
			wp_send_json_error( [ 'message' => 'Message too long' ] );
		}

		$mode   = self::get_ai_mode();
		$result = 'n8n' === $mode
			? self::call_n8n( $message, $metadata, $conversation_id )
			: self::call_dify( $message, $metadata, $conversation_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		set_transient( $hourly_key, $hourly_count + 1, HOUR_IN_SECONDS );
		set_transient( $daily_key, $daily_count + 1, DAY_IN_SECONDS );

		if ( get_option( 'pf_ai_log_enabled', false ) ) {
			self::log_conversation( $uid, $message, $result['answer'] ?? '' );
		}

		wp_send_json_success( $result );
	}

	/**
	 * @return array|WP_Error
	 */
	private static function call_dify( string $message, array $metadata, string $conv_id ) {
		if ( ! defined( 'PF_DIFY_APP_TOKEN' ) || ! PF_DIFY_APP_TOKEN ) {
			return new WP_Error( 'config', 'Dify API token chưa được cấu hình.' );
		}

		$api_url = defined( 'PF_DIFY_API_URL' ) ? PF_DIFY_API_URL : 'https://api.dify.ai/v1';

		$body = [
			'inputs'          => [
				'username'       => $metadata['username'] ?? '',
				'preferred_lang' => $metadata['preferred_lang'] ?? 'vi',
				'role'           => $metadata['role'] ?? 'subscriber',
				'is_vet'         => ! empty( $metadata['is_vet'] ),
			],
			'query'           => $message,
			'response_mode'   => 'blocking',
			'conversation_id' => $conv_id ? $conv_id : null,
			'user'            => 'wp_user_' . ( $metadata['user_id'] ?? '0' ),
		];

		$response = wp_remote_post(
			untrailingslashit( $api_url ) . '/chat-messages',
			[
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Bearer ' . PF_DIFY_APP_TOKEN,
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			return new WP_Error( 'dify_error', $data['message'] ?? "Dify API error {$code}" );
		}

		return [
			'answer'          => $data['answer'] ?? '',
			'conversation_id' => $data['conversation_id'] ?? '',
			'message_id'      => $data['id'] ?? '',
		];
	}

	/**
	 * @return array|WP_Error
	 */
	private static function call_n8n( string $message, array $metadata, string $conv_id ) {
		if ( ! defined( 'PF_N8N_WEBHOOK' ) || ! PF_N8N_WEBHOOK ) {
			return new WP_Error( 'config', 'n8n webhook URL chưa được cấu hình.' );
		}

		$payload = [
			'message'         => $message,
			'conversation_id' => $conv_id,
			'metadata'        => $metadata,
			'timestamp'       => current_time( 'c' ),
			'source'          => 'pet_forum_widget',
		];

		$response = wp_remote_post(
			PF_N8N_WEBHOOK,
			[
				'timeout' => 30,
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $data ) ? (string) ( $data['message'] ?? '' ) : '';
			if ( 404 === $code ) {
				return new WP_Error(
					'n8n_error',
					'Chatbot chưa kết nối n8n. Vào n8n → bật Active workflow "Pet Forum — AI Chatbot", giữ n8n đang chạy.'
				);
			}
			return new WP_Error(
				'n8n_error',
				'n8n webhook lỗi ' . $code . ( $detail ? ': ' . $detail : '' )
			);
		}

		$answer = $data['output'] ?? $data['answer'] ?? $data['text'] ?? '';
		if ( '' === $answer ) {
			return new WP_Error( 'n8n_error', 'n8n trả về rỗng — kiểm tra node AI Respond (Claude) và API key.' );
		}

		return [
			'answer'          => $answer,
			'conversation_id' => $data['conversation_id'] ?? $conv_id,
			'message_id'      => $data['message_id'] ?? uniqid( 'n8n_', false ),
		];
	}

	private static function log_conversation( int $uid, string $question, string $answer ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'pf_ai_log';
		$wpdb->insert(
			$table,
			[
				'user_id'    => $uid,
				'question'   => mb_substr( $question, 0, 1000 ),
				'answer'     => mb_substr( $answer, 0, 3000 ),
				'created_at' => current_time( 'mysql' ),
				'ip'         => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);
	}

	public static function create_log_table(): void {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'pf_ai_log';
		$sql     = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			question TEXT NOT NULL,
			answer TEXT,
			created_at DATETIME NOT NULL,
			ip VARCHAR(45),
			INDEX idx_user (user_id),
			INDEX idx_date (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function add_admin_page(): void {
		add_submenu_page(
			PF_Admin_V2::MENU_SLUG,
			'AI Chatbot',
			'AI Chatbot',
			'manage_options',
			'pf-ai-chatbot',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page(): void {
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && check_admin_referer( 'pf_ai_settings' ) ) {
			update_option( 'pf_ai_chatbot_enabled', isset( $_POST['enabled'] ) );
			update_option( 'pf_ai_log_enabled', isset( $_POST['log_enabled'] ) );
			echo '<div class="notice notice-success"><p>Đã lưu.</p></div>';
		}

		$enabled     = (bool) get_option( 'pf_ai_chatbot_enabled', true );
		$log_enabled = (bool) get_option( 'pf_ai_log_enabled', false );
		$mode        = defined( 'PF_AI_MODE' ) ? PF_AI_MODE : 'chưa cấu hình';
		$dify_ok     = defined( 'PF_DIFY_APP_TOKEN' ) && PF_DIFY_APP_TOKEN;
		$n8n_ok      = defined( 'PF_N8N_WEBHOOK' ) && PF_N8N_WEBHOOK;
		?>
		<div class="wrap" style="max-width:700px">
			<h1>AI Chatbot — Cấu hình</h1>

			<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:20px 0">
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:16px;text-align:center">
					<div style="font-size:1.5rem"><?php echo $dify_ok ? '✅' : '❌'; ?></div>
					<div style="font-size:13px;font-weight:700;margin-top:4px">Dify Token</div>
					<div style="font-size:11px;color:#6b7c6e"><?php echo $dify_ok ? 'Đã cấu hình' : 'Thiếu trong wp-config / .env'; ?></div>
				</div>
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:16px;text-align:center">
					<div style="font-size:1.5rem"><?php echo $n8n_ok ? '✅' : '⚠️'; ?></div>
					<div style="font-size:13px;font-weight:700;margin-top:4px">n8n Webhook</div>
					<div style="font-size:11px;color:#6b7c6e"><?php echo $n8n_ok ? 'Đã cấu hình' : 'Tùy chọn'; ?></div>
				</div>
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:16px;text-align:center">
					<div style="font-size:1.5rem">⚙️</div>
					<div style="font-size:13px;font-weight:700;margin-top:4px">Mode hiện tại</div>
					<div style="font-size:11px;color:#2d6a4f;font-weight:700"><?php echo esc_html( strtoupper( (string) $mode ) ); ?></div>
				</div>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'pf_ai_settings' ); ?>
				<table class="form-table">
					<tr>
						<th>Bật Chatbot</th>
						<td><label><input type="checkbox" name="enabled" <?php checked( $enabled ); ?>> Hiện widget chat cho user đã đăng nhập</label></td>
					</tr>
					<tr>
						<th>Log hội thoại</th>
						<td>
							<label><input type="checkbox" name="log_enabled" <?php checked( $log_enabled ); ?>> Lưu câu hỏi/trả lời vào database</label>
							<p class="description">Dùng để kiểm tra nội dung vi phạm. Nên thông báo user trong Terms.</p>
						</td>
					</tr>
				</table>
				<p><button type="submit" class="button button-primary">Lưu</button></p>
			</form>

			<hr>
			<h3>Hướng dẫn cấu hình</h3>
			<p>Thêm vào <code>.env</code> hoặc <code>wp-config.php</code>:</p>
			<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;font-size:13px;overflow-x:auto">PF_DIFY_API_URL=https://api.dify.ai/v1
PF_DIFY_APP_TOKEN=app-YOUR_TOKEN_HERE
PF_N8N_WEBHOOK=https://your-n8n.com/webhook/pet-bot
PF_AI_MODE=dify</pre>
		</div>
		<?php
	}
}
