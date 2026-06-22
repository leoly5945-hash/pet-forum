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
		global $wpdb;
		$table   = $wpdb->prefix . 'pf_mod_log';
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE $table (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				mod_id BIGINT UNSIGNED NOT NULL,
				action VARCHAR(50) NOT NULL DEFAULT '',
				target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				forum_id INT UNSIGNED NOT NULL DEFAULT 0,
				ip VARCHAR(45) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_mod (mod_id),
				KEY idx_action (action)
			) $charset;"
		);
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

	public static function ajax_pin_topic() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$topic_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id  = (int) ( $_POST['forum_id'] ?? 0 );
		$is_pinned = (int) ( $_POST['pinned'] ?? 0 );

		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		global $wpdb;
		$table    = self::wpforo_table( 'topics' );
		$new_type = $is_pinned ? 'topic' : 'sticky';

		$wpdb->update( $table, [ 'type' => $new_type ], [ 'topicid' => $topic_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->topic->reset();
		}

		self::log_mod_action( 'pin_topic', $topic_id, $forum_id );

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

		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
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

		self::log_mod_action( 'lock_topic', $topic_id, $forum_id );

		wp_send_json_success( [
			'message' => $is_locked ? 'Đã mở khóa chủ đề!' : 'Đã khóa chủ đề!',
			'locked'  => ! $is_locked,
		] );
	}

	public static function ajax_delete_post() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$post_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id = (int) ( $_POST['forum_id'] ?? 0 );

		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		if ( function_exists( 'WPF' ) && method_exists( WPF()->post, 'delete' ) ) {
			WPF()->post->delete( $post_id, true, true, [], false );
		} else {
			global $wpdb;
			$wpdb->delete( self::wpforo_table( 'posts' ), [ 'postid' => $post_id ] );
		}

		self::log_mod_action( 'delete_post', $post_id, $forum_id );
		wp_send_json_success( [ 'message' => 'Đã xóa bài viết!' ] );
	}

	public static function ajax_approve_post() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$post_id  = (int) ( $_POST['id'] ?? 0 );
		$forum_id = (int) ( $_POST['forum_id'] ?? 0 );

		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		global $wpdb;
		$table = self::wpforo_table( 'posts' );

		$wpdb->update( $table, [ 'status' => 0 ], [ 'postid' => $post_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->post->reset();
		}

		self::log_mod_action( 'approve_post', $post_id, $forum_id );
		wp_send_json_success( [ 'message' => 'Đã duyệt bài!' ] );
	}

	public static function ajax_ban_user() {
		check_ajax_referer( 'pf_mod_action', 'nonce' );

		$target_id = (int) ( $_POST['id'] ?? 0 );
		$forum_id  = (int) ( $_POST['forum_id'] ?? 0 );

		if ( ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
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

		if ( function_exists( 'WPF' ) ) {
			global $wpdb;
			$wpdb->update(
				WPF()->tables->profiles,
				[ 'status' => 'banned' ],
				[ 'userid' => $target_id ],
				[ '%s' ],
				[ '%d' ]
			);
			WPF()->member->reset( $target_id );
		}

		update_user_meta( $target_id, 'pf_banned', '1' );
		update_user_meta( $target_id, 'pf_banned_by', get_current_user_id() );
		update_user_meta( $target_id, 'pf_banned_at', current_time( 'mysql' ) );

		self::log_mod_action( 'ban_user', $target_id, $forum_id );
		wp_send_json_success( [ 'message' => "Đã ban user {$target->display_name}!" ] );
	}

	private static function log_mod_action( $action, $target_id, $forum_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pf_mod_log';

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			self::create_log_table();
		}

		$wpdb->insert(
			$table,
			[
				'mod_id'    => get_current_user_id(),
				'action'    => sanitize_key( $action ),
				'target_id' => (int) $target_id,
				'forum_id'  => (int) $forum_id,
				'ip'        => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
			],
			[ '%d', '%s', '%d', '%d', '%s' ]
		);
	}
}
