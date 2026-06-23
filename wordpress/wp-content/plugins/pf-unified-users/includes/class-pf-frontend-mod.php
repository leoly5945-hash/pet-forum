<?php
defined( 'ABSPATH' ) || exit;

class PF_Frontend_Mod_V2 {

	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

		add_action( 'wpforo_tpl_post_loop_after_content', [ __CLASS__, 'render_post_toolbar' ], 20, 2 );
		add_action( 'wpforo_topic_head_right', [ __CLASS__, 'render_topic_toolbar' ], 20, 2 );

		add_action( 'wp_ajax_pf_mod_pin_topic', [ __CLASS__, 'ajax_pin_topic' ] );
		add_action( 'wp_ajax_pf_mod_lock_topic', [ __CLASS__, 'ajax_lock_topic' ] );
		add_action( 'wp_ajax_pf_mod_delete_post', [ __CLASS__, 'ajax_delete_post' ] );
		add_action( 'wp_ajax_pf_mod_approve_post', [ __CLASS__, 'ajax_approve_post' ] );
		add_action( 'wp_ajax_pf_mod_ban_user', [ __CLASS__, 'ajax_ban_user' ] );
	}

	private static function wpforo_table( $name ) {
		if ( function_exists( 'WPF' ) ) {
			if ( method_exists( WPF(), 'init' ) ) {
				WPF()->init();
			}
			if ( isset( WPF()->tables->$name ) ) {
				return WPF()->tables->$name;
			}
		}

		global $wpdb;

		return $wpdb->prefix . 'wpforo_' . $name;
	}

	public static function create_log_table() {
		PF_Logger::create_tables();
	}

	public static function enqueue_assets() {
		if ( ! PF_Constants::get_pf_role() && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'pf-mod-toolbar',
			PFU_URL . 'assets/css/pf-mod-toolbar.css',
			[],
			PFU_VERSION
		);
		wp_enqueue_script(
			'pf-mod-toolbar',
			PFU_URL . 'assets/js/pf-mod-toolbar.js',
			[ 'jquery' ],
			PFU_VERSION,
			true
		);

		wp_localize_script(
			'pf-mod-toolbar',
			'pfModData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pf_mod_action' ),
				'pf_role' => PF_Constants::get_pf_role(),
				'userId'  => get_current_user_id(),
				'strings' => [
					'confirm_delete' => 'Xóa bài viết này? Không thể khôi phục!',
					'confirm_ban'    => 'Ban user này khỏi diễn đàn?',
					'confirm_lock'   => 'Khóa chủ đề này?',
					'success'        => '✅ Thực hiện thành công!',
					'error'          => '❌ Lỗi. Vui lòng thử lại.',
				],
			]
		);
	}

	public static function render_post_toolbar( $post, $member = null ) {
		if ( ! is_user_logged_in() || ! is_array( $post ) ) {
			return;
		}

		$forum_id = (int) ( $post['forumid'] ?? 0 );
		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			return;
		}

		$post_id  = (int) ( $post['postid'] ?? 0 );
		$user_id  = (int) ( $post['userid'] ?? 0 );
		$is_first = (int) ( $post['is_first'] ?? 0 ) === 1;
		$pending  = (int) ( $post['status'] ?? 0 ) === 1;
		?>
		<div class="pf-mod-toolbar" data-post-id="<?php echo $post_id; ?>"
			data-forum-id="<?php echo $forum_id; ?>" data-user-id="<?php echo $user_id; ?>">

			<span class="pf-mod-badge">🛡 Mod</span>

			<div class="pf-mod-actions">
				<?php if ( $pending ) : ?>
				<button type="button" class="pf-mod-btn pf-btn-approve"
					data-action="pf_mod_approve_post" data-id="<?php echo $post_id; ?>"
					title="Duyệt bài">✅ Duyệt</button>
				<?php endif; ?>

				<button type="button" class="pf-mod-btn pf-btn-delete"
					data-action="pf_mod_delete_post" data-id="<?php echo $post_id; ?>"
					title="Xóa bài">🗑 Xóa</button>

				<?php if ( $user_id && $user_id !== get_current_user_id() && ! $is_first ) : ?>
				<?php $target = get_userdata( $user_id ); ?>
				<button type="button" class="pf-mod-btn pf-btn-ban"
					data-action="pf_mod_ban_user"
					data-id="<?php echo $user_id; ?>"
					data-name="<?php echo esc_attr( $target->display_name ?? '' ); ?>"
					title="Ban user này">🚫 Ban</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public static function render_topic_toolbar( $forum, $topic ) {
		if ( ! is_user_logged_in() || ! is_array( $topic ) ) {
			return;
		}

		$forum_id = (int) ( $topic['forumid'] ?? ( is_array( $forum ) ? ( $forum['forumid'] ?? 0 ) : 0 ) );
		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			return;
		}

		$topic_id  = (int) ( $topic['topicid'] ?? 0 );
		$is_pinned = ( $topic['type'] ?? '' ) === 'sticky';
		$is_locked = ! empty( $topic['closed'] );
		?>
		<div class="pf-mod-topic-bar" data-topic-id="<?php echo $topic_id; ?>"
			data-forum-id="<?php echo $forum_id; ?>">

			<button type="button" class="pf-mod-btn pf-btn-pin <?php echo $is_pinned ? 'active' : ''; ?>"
				data-action="pf_mod_pin_topic"
				data-id="<?php echo $topic_id; ?>"
				data-pinned="<?php echo $is_pinned ? '1' : '0'; ?>"
				title="<?php echo $is_pinned ? 'Bỏ ghim' : 'Ghim lên đầu'; ?>">
				<?php echo $is_pinned ? '📌 Bỏ ghim' : '📌 Ghim'; ?>
			</button>

			<button type="button" class="pf-mod-btn pf-btn-lock <?php echo $is_locked ? 'active' : ''; ?>"
				data-action="pf_mod_lock_topic"
				data-id="<?php echo $topic_id; ?>"
				data-locked="<?php echo $is_locked ? '1' : '0'; ?>"
				title="<?php echo $is_locked ? 'Mở khóa' : 'Khóa chủ đề'; ?>">
				<?php echo $is_locked ? '🔓 Mở khóa' : '🔒 Khóa'; ?>
			</button>

			<span class="pf-mod-badge">🛡 Mod Tools</span>
		</div>
		<?php
	}

	public static function check_mod_permission( int $forum_id ): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return PF_Roles_V2::user_can_moderate_forum( $user_id, $forum_id );
	}

	public static function ajax_pin_topic() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$topic_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id  = (int) ( $_POST['forum_id'] ?? 0 );
		$is_pinned = (int) ( $_POST['pinned'] ?? 0 );

		if ( ! self::check_mod_permission( $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền quản lý forum này.' ] );
		}

		global $wpdb;
		$table    = self::wpforo_table( 'topics' );
		$new_type = $is_pinned ? 'topic' : 'sticky';

		$wpdb->update( $table, [ 'type' => $new_type ], [ 'topicid' => $topic_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->topic->reset();
		}

		self::log_mod( 'pin_topic', 'topic', $topic_id, "topic #{$topic_id}", '', $forum_id );

		wp_send_json_success( [
			'message' => $is_pinned ? 'Đã bỏ ghim!' : 'Đã ghim lên đầu!',
			'pinned'  => ! $is_pinned,
		] );
	}

	public static function ajax_lock_topic() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$topic_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id  = (int) ( $_POST['forum_id'] ?? 0 );
		$is_locked = (int) ( $_POST['locked'] ?? 0 );

		if ( ! self::check_mod_permission( $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền quản lý forum này.' ] );
		}

		global $wpdb;
		$table = self::wpforo_table( 'topics' );

		$wpdb->update(
			$table,
			[ 'closed' => $is_locked ? 0 : 1 ],
			[ 'topicid' => $topic_id ]
		);

		if ( function_exists( 'WPF' ) ) {
			WPF()->topic->reset();
		}

		self::log_mod( 'lock_topic', 'topic', $topic_id, "topic #{$topic_id}", '', $forum_id );

		wp_send_json_success( [
			'message' => $is_locked ? 'Đã mở khóa chủ đề!' : 'Đã khóa chủ đề!',
			'locked'  => ! $is_locked,
		] );
	}

	public static function ajax_delete_post() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$post_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id = (int) ( $_POST['forum_id'] ?? 0 );

		if ( ! self::check_mod_permission( $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền quản lý forum này.' ] );
		}

		if ( function_exists( 'WPF' ) && method_exists( WPF()->post, 'delete' ) ) {
			WPF()->post->delete( $post_id, true, true, [], false );
		} else {
			global $wpdb;
			$wpdb->delete( self::wpforo_table( 'posts' ), [ 'postid' => $post_id ] );
		}

		self::log_mod( 'delete_post', 'post', $post_id, "post #{$post_id}", '', $forum_id );
		wp_send_json_success( [ 'message' => 'Đã xóa bài viết!' ] );
	}

	public static function ajax_approve_post() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$post_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id = (int) ( $_POST['forum_id'] ?? 0 );

		if ( ! self::check_mod_permission( $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền quản lý forum này.' ] );
		}

		global $wpdb;
		$table = self::wpforo_table( 'posts' );

		$wpdb->update( $table, [ 'status' => 0 ], [ 'postid' => $post_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->post->reset();
		}

		self::log_mod( 'approve_post', 'post', $post_id, "post #{$post_id}", '', $forum_id );
		wp_send_json_success( [ 'message' => 'Đã duyệt bài!' ] );
	}

	public static function ajax_ban_user() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$target_id = (int) ( $_POST['id'] ?? 0 );
		$forum_id  = (int) ( $_POST['forum_id'] ?? 0 );
		$reason    = sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Vi phạm nội quy forum' ) );

		if ( ! self::check_mod_permission( $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền quản lý forum này.' ] );
		}

		$target = get_userdata( $target_id );
		if ( ! $target ) {
			wp_send_json_error( [ 'message' => 'User không tồn tại.' ] );
		}

		$protected = array_merge( [ 'administrator' ], PF_Constants::ALL_PF_ROLES );
		foreach ( $protected as $role ) {
			if ( in_array( $role, (array) $target->roles, true ) ) {
				wp_send_json_error( [ 'message' => 'Không thể ban tài khoản quản trị.' ] );
			}
		}

		$is_super_admin = current_user_can( 'manage_options' );
		$warn_level     = $is_super_admin ? PF_Constants::WARN_BANNED : PF_Constants::WARN_RESTRICTED;

		PF_Roles_V2::warn_user( $target_id, $reason, $warn_level );

		self::log_mod(
			'ban_user',
			'user',
			$target_id,
			$target->user_email,
			$reason,
			$forum_id
		);

		$msg = $is_super_admin
			? "Đã khóa user {$target->display_name}!"
			: "Đã hạn chế user {$target->display_name} (bài chờ duyệt). Chỉ Admin mới khóa hoàn toàn.";

		wp_send_json_success( [ 'message' => $msg ] );
	}

	private static function log_mod(
		string $action,
		string $target_type,
		int $target_id,
		string $target_info = '',
		string $reason = '',
		int $forum_id = 0
	): void {
		PF_Logger::log(
			get_current_user_id(),
			$action,
			$target_type,
			$target_id,
			$target_info,
			$reason,
			$forum_id ? [ 'forum_id' => $forum_id ] : []
		);
	}
}
