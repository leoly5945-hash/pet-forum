<?php
defined( 'ABSPATH' ) || exit;

class PF_Engagement {

	const INACTIVE_DAYS   = 30;
	const CRON_HOOK       = 'pf_reengagement_cron';
	const META_LAST_NUDGE = 'pf_last_nudge_email';
	const META_LAST_LOGIN = 'pf_last_login';
	const META_EMAIL_OPT_OUT = 'pf_email_opt_out';

	public static function init(): void {
		add_shortcode( 'pf_feedback', [ __CLASS__, 'render_feedback_form' ] );

		add_action( 'wp_ajax_pf_submit_feedback', [ __CLASS__, 'ajax_submit_feedback' ] );
		add_action( 'wp_ajax_nopriv_pf_submit_feedback', [ __CLASS__, 'ajax_submit_feedback' ] );

		add_action( 'wp_login', [ __CLASS__, 'record_last_login' ], 10, 2 );

		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}

		add_action( self::CRON_HOOK, [ __CLASS__, 'run_reengagement_emails' ] );

		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
		add_action( 'wp_ajax_pf_trigger_reengagement', [ __CLASS__, 'ajax_trigger_reengagement' ] );

		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
		add_action( 'init', [ __CLASS__, 'ensure_feedback_menu_item' ], 25 );
	}

	public static function enqueue(): void {
		if ( ! self::page_has_feedback_form() ) {
			return;
		}

		wp_enqueue_style( 'pf-engagement', PFU_URL . 'assets/css/pf-engagement.css', [], PFU_VERSION );
	}

	private static function page_has_feedback_form(): bool {
		if ( is_page( 'gop-y' ) || is_page( 'feedback' ) ) {
			return true;
		}

		global $post;

		return $post instanceof WP_Post && has_shortcode( $post->post_content, 'pf_feedback' );
	}

	/**
	 * Add "Góp ý" to primary nav if missing.
	 */
	public static function ensure_feedback_menu_item(): void {
		$page = get_page_by_path( 'gop-y' );
		if ( ! $page ) {
			return;
		}

		$locations = get_nav_menu_locations();
		if ( empty( $locations['primary'] ) ) {
			return;
		}

		$menu_id = (int) $locations['primary'];
		$items   = wp_get_nav_menu_items( $menu_id );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( 'page' === $item->object && (int) $item->object_id === (int) $page->ID ) {
					return;
				}
			}
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-title'     => '💬 Góp ý',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => (int) $page->ID,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => 9,
			]
		);
	}

	public static function render_feedback_form( array $atts = [] ): string {
		unset( $atts );

		$lang = class_exists( 'PF_I18n' ) ? PF_I18n::current_lang() : 'vi';
		$uid  = get_current_user_id();
		$user = $uid ? get_userdata( $uid ) : null;

		$strings = [
			'vi' => [
				'title'       => '💬 Góp ý cho Pet Forum',
				'subtitle'    => 'Ý kiến của bạn giúp chúng tôi cải thiện mỗi ngày',
				'name'        => 'Họ tên',
				'email'       => 'Email',
				'type'        => 'Loại góp ý',
				'types'       => [ 'Lỗi tính năng', 'Đề xuất tính năng mới', 'Nội dung không phù hợp', 'Hỗ trợ tài khoản', 'Khác' ],
				'message'     => 'Nội dung góp ý',
				'placeholder' => 'Mô tả chi tiết để chúng tôi có thể hỗ trợ bạn tốt hơn...',
				'submit'      => 'Gửi góp ý',
				'rating'      => 'Đánh giá tổng thể',
				'success'     => '✅ Cảm ơn bạn đã góp ý! Chúng tôi sẽ phản hồi trong vòng 24–48 giờ.',
				'error'       => '❌ Có lỗi xảy ra. Vui lòng thử lại.',
			],
			'en' => [
				'title'       => '💬 Give Us Feedback',
				'subtitle'    => 'Your feedback helps us improve every day',
				'name'        => 'Full name',
				'email'       => 'Email',
				'type'        => 'Feedback type',
				'types'       => [ 'Bug report', 'Feature request', 'Inappropriate content', 'Account support', 'Other' ],
				'message'     => 'Message',
				'placeholder' => 'Please describe in detail so we can help you better...',
				'submit'      => 'Send feedback',
				'rating'      => 'Overall rating',
				'success'     => '✅ Thank you for your feedback! We\'ll respond within 24–48 hours.',
				'error'       => '❌ Something went wrong. Please try again.',
			],
		];

		$s = $strings[ $lang ] ?? $strings['vi'];

		ob_start();
		?>
		<div class="pf-feedback-wrap" id="pf-feedback-form">
			<div class="pf-feedback-header">
				<h2><?php echo esc_html( $s['title'] ); ?></h2>
				<p><?php echo esc_html( $s['subtitle'] ); ?></p>
			</div>

			<form id="pf-feedback" novalidate>
				<?php wp_nonce_field( 'pf_feedback', 'pf_fb_nonce' ); ?>
				<input type="hidden" name="action" value="pf_submit_feedback">
				<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">

				<div class="pf-fb-row pf-fb-row-2">
					<div class="pf-fb-field">
						<label for="pf-fb-name"><?php echo esc_html( $s['name'] ); ?> *</label>
						<input type="text" name="fb_name" id="pf-fb-name" required
							value="<?php echo $user ? esc_attr( $user->display_name ) : ''; ?>"
							placeholder="<?php echo esc_attr( $s['name'] ); ?>">
					</div>
					<div class="pf-fb-field">
						<label for="pf-fb-email"><?php echo esc_html( $s['email'] ); ?> *</label>
						<input type="email" name="fb_email" id="pf-fb-email" required
							value="<?php echo $user ? esc_attr( $user->user_email ) : ''; ?>"
							placeholder="email@example.com">
					</div>
				</div>

				<div class="pf-fb-field">
					<label for="pf-fb-type"><?php echo esc_html( $s['type'] ); ?></label>
					<select name="fb_type" id="pf-fb-type">
						<?php foreach ( $s['types'] as $type ) : ?>
						<option><?php echo esc_html( $type ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="pf-fb-field">
					<label for="pf-fb-message"><?php echo esc_html( $s['message'] ); ?> *</label>
					<textarea name="fb_message" id="pf-fb-message" rows="5" required
						placeholder="<?php echo esc_attr( $s['placeholder'] ); ?>"></textarea>
				</div>

				<div class="pf-fb-field">
					<label><?php echo esc_html( $s['rating'] ); ?></label>
					<div class="pf-fb-stars" id="pf-fb-stars">
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<input type="radio" name="fb_rating" id="star<?php echo (int) $i; ?>"
							value="<?php echo (int) $i; ?>" <?php checked( $i, 5 ); ?>>
						<label for="star<?php echo (int) $i; ?>">⭐</label>
						<?php endfor; ?>
					</div>
				</div>

				<div class="pf-fb-submit">
					<button type="submit" class="pf-fb-btn" id="pf-fb-submit">
						<span class="pf-fb-btn-text"><?php echo esc_html( $s['submit'] ); ?></span>
						<span class="pf-fb-spinner" style="display:none">⏳</span>
					</button>
				</div>

				<div id="pf-fb-result" style="display:none" role="status"></div>
			</form>
		</div>

		<script>
		document.getElementById('pf-feedback').addEventListener('submit', function(e) {
			e.preventDefault();
			var form = this;
			var btn  = document.getElementById('pf-fb-submit');
			var res  = document.getElementById('pf-fb-result');
			var data = new FormData(form);

			btn.disabled = true;
			btn.querySelector('.pf-fb-spinner').style.display = 'inline';

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: data
			})
			.then(function(r) { return r.json(); })
			.then(function(r) {
				res.style.display = 'block';
				res.className = r.success ? 'pf-fb-success' : 'pf-fb-error';
				res.textContent = r.data && r.data.message ? r.data.message : 'Error';
				if (r.success) form.reset();
			})
			.catch(function() {
				res.style.display = 'block';
				res.className = 'pf-fb-error';
				res.textContent = <?php echo wp_json_encode( $s['error'] ); ?>;
			})
			.finally(function() {
				btn.disabled = false;
				btn.querySelector('.pf-fb-spinner').style.display = 'none';
			});
		});
		</script>
		<?php

		return ob_get_clean();
	}

	public static function ajax_submit_feedback(): void {
		check_ajax_referer( 'pf_feedback', 'pf_fb_nonce' );

		$name    = sanitize_text_field( wp_unslash( $_POST['fb_name'] ?? '' ) );
		$email   = sanitize_email( wp_unslash( $_POST['fb_email'] ?? '' ) );
		$type    = sanitize_text_field( wp_unslash( $_POST['fb_type'] ?? 'Khác' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['fb_message'] ?? '' ) );
		$rating  = max( 1, min( 5, (int) ( $_POST['fb_rating'] ?? 5 ) ) );
		$lang    = ( wp_unslash( $_POST['lang'] ?? 'vi' ) === 'en' ) ? 'en' : 'vi';

		if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
			wp_send_json_error(
				[
					'message' => $lang === 'vi'
						? 'Vui lòng điền đầy đủ thông tin.'
						: 'Please fill in all required fields.',
				]
			);
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Email không hợp lệ.' ] );
		}

		$admin_email = get_option( 'admin_email' );
		$stars       = str_repeat( '⭐', $rating );
		$ip          = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		$subject = "[Pet Forum Góp ý] [{$type}] từ {$name}";
		$body    = "
<h2>💬 Góp ý mới từ Pet Forum</h2>
<table style='border-collapse:collapse;width:100%'>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600;width:30%'>Họ tên</td><td style='padding:8px'>" . esc_html( $name ) . "</td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>Email</td><td style='padding:8px'><a href='mailto:" . esc_attr( $email ) . "'>" . esc_html( $email ) . "</a></td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>Loại</td><td style='padding:8px'>" . esc_html( $type ) . "</td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>Đánh giá</td><td style='padding:8px'>{$stars} ({$rating}/5)</td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>Nội dung</td><td style='padding:8px'>" . nl2br( esc_html( $message ) ) . "</td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>Thời gian</td><td style='padding:8px'>" . esc_html( current_time( 'd/m/Y H:i' ) ) . "</td></tr>
  <tr><td style='padding:8px;background:#f0f7ee;font-weight:600'>IP</td><td style='padding:8px'>" . esc_html( $ip ) . "</td></tr>
</table>
<p style='margin-top:20px'>
  <a href='" . esc_url( admin_url( 'users.php?s=' . rawurlencode( $email ) ) ) . "' style='color:#2d6a4f'>Xem user trong WP Admin →</a>
</p>";

		wp_mail(
			$admin_email,
			$subject,
			$body,
			[
				'Content-Type: text/html; charset=UTF-8',
				'Reply-To: ' . $name . ' <' . $email . '>',
			]
		);

		$confirm_subject = $lang === 'vi'
			? '[Pet Forum] Cảm ơn bạn đã góp ý!'
			: '[Pet Forum] Thank you for your feedback!';

		$confirm_body = $lang === 'vi'
			? "<p>Xin chào <strong>" . esc_html( $name ) . "</strong>,</p>
			   <p>Chúng tôi đã nhận được góp ý của bạn về: <strong>" . esc_html( $type ) . "</strong>.</p>
			   <p>Đội ngũ Pet Forum sẽ xem xét và phản hồi trong vòng 24–48 giờ.</p>
			   <p>Cảm ơn bạn đã dành thời gian giúp chúng tôi cải thiện! 🐾</p>"
			: "<p>Hello <strong>" . esc_html( $name ) . "</strong>,</p>
			   <p>We've received your feedback about: <strong>" . esc_html( $type ) . "</strong>.</p>
			   <p>Our team will review and respond within 24–48 hours.</p>
			   <p>Thank you for helping us improve! 🐾</p>";

		wp_mail( $email, $confirm_subject, $confirm_body, [ 'Content-Type: text/html; charset=UTF-8' ] );

		wp_send_json_success(
			[
				'message' => $lang === 'vi'
					? '✅ Cảm ơn bạn đã góp ý! Chúng tôi sẽ phản hồi trong vòng 24–48 giờ.'
					: '✅ Thank you! We\'ll get back to you within 24–48 hours.',
			]
		);
	}

	public static function record_last_login( string $login, WP_User $user ): void {
		unset( $login );
		update_user_meta( $user->ID, self::META_LAST_LOGIN, current_time( 'mysql' ) );
	}

	public static function add_cron_schedule( array $schedules ): array {
		if ( ! isset( $schedules['daily'] ) ) {
			$schedules['daily'] = [
				'interval' => DAY_IN_SECONDS,
				'display'  => 'Once Daily',
			];
		}

		return $schedules;
	}

	public static function run_reengagement_emails(): void {
		$users = self::get_inactive_users( 50 );
		$sent  = 0;

		foreach ( $users as $user ) {
			$last_login = get_user_meta( $user->ID, self::META_LAST_LOGIN, true );
			$reference  = $last_login ?: $user->user_registered;
			$days_ago   = (int) floor( ( time() - strtotime( $reference ) ) / DAY_IN_SECONDS );
			$lang       = get_user_meta( $user->ID, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';

			if ( self::send_reengagement_email( $user, max( $days_ago, self::INACTIVE_DAYS ), $lang ) ) {
				update_user_meta( $user->ID, self::META_LAST_NUDGE, current_time( 'mysql' ) );
				$sent++;
			}

			usleep( 500000 );
		}

		$log   = get_option( 'pf_reengagement_log', [] );
		$log[] = [
			'date'          => current_time( 'mysql' ),
			'sent'          => $sent,
			'total_checked' => count( $users ),
		];
		update_option( 'pf_reengagement_log', array_slice( $log, -30 ) );
	}

	private static function get_inactive_users( int $limit = 100 ): array {
		$cutoff       = gmdate( 'Y-m-d H:i:s', strtotime( '-' . self::INACTIVE_DAYS . ' days' ) );
		$nudge_cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-45 days' ) );

		$candidates = get_users(
			[
				'role__not_in' => [ 'administrator' ],
				'number'       => max( $limit * 4, 100 ),
				'orderby'      => 'registered',
				'order'        => 'ASC',
			]
		);

		$result = [];

		foreach ( $candidates as $user ) {
			if ( count( $result ) >= $limit ) {
				break;
			}

			if ( get_user_meta( $user->ID, self::META_EMAIL_OPT_OUT, true ) ) {
				continue;
			}

			if ( (int) get_user_meta( $user->ID, PF_Constants::META_WARN_LEVEL, true ) >= PF_Constants::WARN_BANNED ) {
				continue;
			}

			if ( get_user_meta( $user->ID, PF_Constants::META_BANNED, true ) ) {
				continue;
			}

			if ( strtotime( $user->user_registered ) > strtotime( $cutoff ) ) {
				continue;
			}

			$last_login     = get_user_meta( $user->ID, self::META_LAST_LOGIN, true );
			$inactive_since = $last_login ?: $user->user_registered;

			if ( strtotime( $inactive_since ) > strtotime( $cutoff ) ) {
				continue;
			}

			$last_nudge = get_user_meta( $user->ID, self::META_LAST_NUDGE, true );
			if ( $last_nudge && strtotime( $last_nudge ) > strtotime( $nudge_cutoff ) ) {
				continue;
			}

			$result[] = $user;
		}

		return $result;
	}

	private static function send_reengagement_email( WP_User $user, int $days_ago, string $lang ): bool {
		$forum_url   = home_url( '/forum/' );
		$unsubscribe = add_query_arg(
			[
				'pf_unsub' => 1,
				'uid'      => $user->ID,
				'token'    => wp_hash( $user->ID . $user->user_email ),
			],
			home_url( '/' )
		);

		if ( $lang === 'en' ) {
			$subject = 'We miss you at Pet Forum! 🐾 Come back and join the conversation';
			$body    = "
<div style='font-family:sans-serif;max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #d8f3dc'>
  <div style='background:linear-gradient(135deg,#1b4332,#2d6a4f);padding:32px;text-align:center'>
    <h1 style='color:#fff;margin:0;font-size:24px'>🌿 Pet Forum</h1>
    <p style='color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px'>The community for pet lovers</p>
  </div>
  <div style='padding:32px'>
    <p style='font-size:18px;color:#1b4332;font-weight:700'>Hello, " . esc_html( $user->display_name ) . "! 👋</p>
    <p style='color:#2d3d2e;line-height:1.7'>
      It's been <strong>{$days_ago} days</strong> since we last saw you on Pet Forum.
      Your furry friends are waiting!
    </p>
    <div style='background:#f0f7ee;border-radius:10px;padding:20px;margin:20px 0'>
      <p style='margin:0;color:#2d6a4f;font-weight:600'>🐕 New discussions in Dog section</p>
      <p style='margin:6px 0 0;color:#2d6a4f;font-weight:600'>🐈 Fresh tips in Cat section</p>
      <p style='margin:6px 0 0;color:#2d6a4f;font-weight:600'>💬 Members are waiting for your input</p>
    </div>
    <div style='text-align:center;margin:28px 0'>
      <a href='" . esc_url( $forum_url ) . "'
         style='background:#40916c;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block'>
        🐾 Return to Forum
      </a>
    </div>
    <p style='color:#6b7c6e;font-size:13px;text-align:center'>
      <a href='" . esc_url( $unsubscribe ) . "' style='color:#6b7c6e'>Unsubscribe from reminder emails</a>
    </p>
  </div>
</div>";
		} else {
			$subject = 'Chúng tôi nhớ bạn! 🐾 Pet Forum đang chờ bạn trở lại';
			$body    = "
<div style='font-family:sans-serif;max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #d8f3dc'>
  <div style='background:linear-gradient(135deg,#1b4332,#2d6a4f);padding:32px;text-align:center'>
    <h1 style='color:#fff;margin:0;font-size:24px'>🌿 Pet Forum</h1>
    <p style='color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px'>Cộng đồng yêu thú cưng Việt Nam</p>
  </div>
  <div style='padding:32px'>
    <p style='font-size:18px;color:#1b4332;font-weight:700'>Chào " . esc_html( $user->display_name ) . "! 👋</p>
    <p style='color:#2d3d2e;line-height:1.7'>
      Đã <strong>{$days_ago} ngày</strong> rồi bạn chưa ghé thăm Pet Forum.
      Cộng đồng đang nhớ bạn!
    </p>
    <div style='background:#f0f7ee;border-radius:10px;padding:20px;margin:20px 0'>
      <p style='margin:0;color:#2d6a4f;font-weight:600'>🐕 Có nhiều chủ đề mới trong mục Chó</p>
      <p style='margin:6px 0 0;color:#2d6a4f;font-weight:600'>🐈 Mẹo hay mới trong mục Mèo</p>
      <p style='margin:6px 0 0;color:#2d6a4f;font-weight:600'>💬 Các thành viên đang chờ bạn góp ý</p>
    </div>
    <div style='text-align:center;margin:28px 0'>
      <a href='" . esc_url( $forum_url ) . "'
         style='background:#40916c;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block'>
        🐾 Quay lại Forum ngay
      </a>
    </div>
    <p style='color:#6b7c6e;font-size:13px;line-height:1.6'>
      Bạn nhận email này vì đã đăng ký tại <a href='" . esc_url( home_url() ) . "' style='color:#40916c'>Pet Forum</a>.<br>
      <a href='" . esc_url( $unsubscribe ) . "' style='color:#6b7c6e'>Hủy nhận email nhắc nhở</a>
    </p>
  </div>
</div>";
		}

		return wp_mail(
			$user->user_email,
			$subject,
			$body,
			[ 'Content-Type: text/html; charset=UTF-8' ]
		);
	}

	public static function handle_unsubscribe(): void {
		if ( ! isset( $_GET['pf_unsub'], $_GET['uid'], $_GET['token'] ) ) {
			return;
		}

		$uid   = (int) $_GET['uid'];
		$token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
		$user  = get_userdata( $uid );

		if ( ! $user || ! hash_equals( wp_hash( $uid . $user->user_email ), $token ) ) {
			wp_die( 'Link không hợp lệ.', 'Lỗi', [ 'response' => 400 ] );
		}

		update_user_meta( $uid, self::META_EMAIL_OPT_OUT, '1' );

		wp_die(
			'✅ Bạn đã hủy nhận email nhắc nhở. <a href="' . esc_url( home_url() ) . '">Về trang chủ</a>',
			'Đã hủy đăng ký',
			[ 'response' => 200 ]
		);
	}

	public static function add_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_submenu_page(
			PF_Admin_V2::MENU_SLUG,
			'User không hoạt động',
			'📬 Re-engagement',
			'manage_options',
			'pfu-reengagement',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page(): void {
		$users     = self::get_inactive_users( 100 );
		$log       = get_option( 'pf_reengagement_log', [] );
		$next_cron = wp_next_scheduled( self::CRON_HOOK );
		$nonce     = wp_create_nonce( 'pf_trigger_re' );
		?>
		<div class="wrap">
			<h1>📬 User không hoạt động (≥ <?php echo (int) self::INACTIVE_DAYS; ?> ngày)</h1>

			<div style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap">
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:20px;min-width:160px">
					<div style="font-size:2rem;font-weight:800;color:#1b4332"><?php echo count( $users ); ?></div>
					<div style="color:#6b7c6e;font-size:13px">User đủ điều kiện nhắc</div>
				</div>
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:20px;min-width:160px">
					<div style="font-size:1.1rem;font-weight:700;color:#1b4332">
						<?php echo $next_cron ? esc_html( gmdate( 'd/m/Y H:i', $next_cron ) ) : 'Chưa lên lịch'; ?>
					</div>
					<div style="color:#6b7c6e;font-size:13px">Lần chạy tiếp theo</div>
				</div>
				<?php if ( ! empty( $log ) ) :
					$last = end( $log );
					?>
				<div style="background:#fff;border:1px solid #d8f3dc;border-radius:10px;padding:20px;min-width:160px">
					<div style="font-size:1.1rem;font-weight:700;color:#1b4332"><?php echo (int) $last['sent']; ?> emails</div>
					<div style="color:#6b7c6e;font-size:13px">Lần cuối: <?php echo esc_html( gmdate( 'd/m', strtotime( $last['date'] ) ) ); ?></div>
				</div>
				<?php endif; ?>
			</div>

			<button type="button" id="pf-trigger-reengagement" class="button button-primary">
				🚀 Gửi email ngay (test)
			</button>
			<span id="pf-reengagement-result" style="margin-left:12px"></span>

			<h2 style="margin-top:30px">Danh sách user (<?php echo count( $users ); ?>)</h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Tên</th><th>Email</th><th>Đăng ký</th>
						<th>Lần cuối login</th><th>Đã nhắc</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $users as $u ) :
					$last_login = get_user_meta( $u->ID, self::META_LAST_LOGIN, true );
					$last_nudge = get_user_meta( $u->ID, self::META_LAST_NUDGE, true );
					?>
				<tr>
					<td><strong><?php echo esc_html( $u->display_name ); ?></strong></td>
					<td><?php echo esc_html( $u->user_email ); ?></td>
					<td><?php echo esc_html( gmdate( 'd/m/Y', strtotime( $u->user_registered ) ) ); ?></td>
					<td><?php echo $last_login ? esc_html( gmdate( 'd/m/Y', strtotime( $last_login ) ) ) : '<em>Chưa login</em>'; ?></td>
					<td><?php echo $last_nudge ? esc_html( gmdate( 'd/m/Y', strtotime( $last_nudge ) ) ) : '—'; ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<script>
		document.getElementById('pf-trigger-reengagement').addEventListener('click', function() {
			var btn = this;
			var res = document.getElementById('pf-reengagement-result');
			btn.disabled = true;
			res.textContent = '⏳ Đang gửi...';
			fetch(ajaxurl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: 'action=pf_trigger_reengagement&_ajax_nonce=<?php echo esc_js( $nonce ); ?>'
			})
			.then(function(r) { return r.json(); })
			.then(function(r) {
				res.textContent = r.success ? '✅ ' + r.data.message : '❌ ' + (r.data && r.data.message ? r.data.message : 'Lỗi');
				btn.disabled = false;
			});
		});
		</script>
		<?php
	}

	public static function ajax_trigger_reengagement(): void {
		check_ajax_referer( 'pf_trigger_re' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		self::run_reengagement_emails();

		$log  = get_option( 'pf_reengagement_log', [] );
		$last = end( $log ) ?: [ 'sent' => 0, 'total_checked' => 0 ];

		wp_send_json_success(
			[
				'message' => "Đã gửi {$last['sent']} email (kiểm tra {$last['total_checked']} user).",
			]
		);
	}

	public static function schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	public static function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	public static function backfill_last_login(): int {
		$users = get_users(
			[
				'meta_query' => [
					[
						'key'     => self::META_LAST_LOGIN,
						'compare' => 'NOT EXISTS',
					],
				],
				'fields' => 'all',
				'number' => 5000,
			]
		);

		foreach ( $users as $user ) {
			update_user_meta( $user->ID, self::META_LAST_LOGIN, $user->user_registered );
		}

		return count( $users );
	}
}
