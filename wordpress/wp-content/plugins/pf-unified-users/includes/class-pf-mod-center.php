<?php
defined( 'ABSPATH' ) || exit;

class PF_Mod_Center {

	public static function init(): void {
		add_shortcode( 'pf_mod_center', [ __CLASS__, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ __CLASS__, 'render_mod_fab' ] );

		add_action( 'wp_ajax_pf_mc_approve_post', [ __CLASS__, 'ajax_approve_post' ] );
		add_action( 'wp_ajax_pf_mc_delete_post', [ __CLASS__, 'ajax_delete_post' ] );
		add_action( 'wp_ajax_pf_mc_warn_user', [ __CLASS__, 'ajax_warn_user' ] );
		add_action( 'wp_ajax_pf_mc_ban_user', [ __CLASS__, 'ajax_ban_user' ] );
		add_action( 'wp_ajax_pf_mc_unpin_topic', [ __CLASS__, 'ajax_unpin_topic' ] );
		add_action( 'wp_ajax_pf_mc_unlock_topic', [ __CLASS__, 'ajax_unlock_topic' ] );
	}

	public static function enqueue_assets(): void {
		if ( ! is_page( 'mod-center' ) ) {
			return;
		}

		wp_enqueue_style( 'pf-mod-center', PFU_URL . 'assets/css/pf-mod-center.css', [], PFU_VERSION );
		wp_enqueue_script( 'pf-mod-center', PFU_URL . 'assets/js/pf-mod-center.js', [], PFU_VERSION, true );
		wp_localize_script(
			'pf-mod-center',
			'pfMC',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pf_mod_center' ),
				'lang'    => get_user_meta( get_current_user_id(), PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi',
			]
		);
	}

	public static function render_mod_fab(): void {
		$uid  = get_current_user_id();
		$role = PF_Constants::get_pf_role( $uid );

		if ( ! $uid || ! $role || $role === PF_Constants::ROLE_VERIFIED_VET ) {
			return;
		}

		$allowed = array_merge( [ PF_Constants::ROLE_GLOBAL_ADMIN ], PF_Constants::SECTION_MOD_ROLES );
		if ( ! in_array( $role, $allowed, true ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_page( 'mod-center' ) ) {
			return;
		}
		?>
		<a href="<?php echo esc_url( home_url( '/mod-center/' ) ); ?>" class="pf-mod-fab" title="Mod Center">
			👑 <span>Mod</span>
		</a>
		<style>
		.pf-mod-fab {
			position: fixed; bottom: 24px; left: 24px; z-index: 9999;
			background: #1e1b4b; color: #fbbf24;
			padding: 10px 18px; border-radius: 50px;
			font-size: 13px; font-weight: 700;
			text-decoration: none; box-shadow: 0 4px 16px rgba(0,0,0,.3);
			display: flex; align-items: center; gap: 6px;
			transition: transform .2s;
		}
		.pf-mod-fab:hover { transform: translateY(-2px); color: #fbbf24; }
		</style>
		<?php
	}

	public static function render(): string {
		$uid = get_current_user_id();

		if ( ! $uid ) {
			return '<div class="pf-mc-denied">🔒 <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">Đăng nhập</a> để truy cập.</div>';
		}

		$role          = PF_Constants::get_pf_role( $uid );
		$allowed_roles = array_merge( [ PF_Constants::ROLE_GLOBAL_ADMIN ], PF_Constants::SECTION_MOD_ROLES );

		if ( ! in_array( $role, $allowed_roles, true ) && ! current_user_can( 'manage_options' ) ) {
			return '<div class="pf-mc-denied">🚫 Trang này chỉ dành cho Ban quản trị.</div>';
		}

		$user          = get_userdata( $uid );
		$lang          = get_user_meta( $uid, PF_Constants::META_PREFERRED_LANG, true ) ?: 'vi';
		$forum_ids     = self::get_my_forum_ids( $uid, $role );
		$pending_count = self::count_pending_posts( $forum_ids );
		$warned_count  = self::count_warned_users();
		$log_count     = PF_Logger::count_logs( $uid );

		ob_start();
		include PFU_DIR . 'templates/mod-center.php';

		return ob_get_clean();
	}

	private static function posts_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'wpforo_posts';
	}

	private static function topics_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'wpforo_topics';
	}

	private static function forums_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'wpforo_forums';
	}

	private static function get_my_forum_ids( int $uid, ?string $role ): array {
		if ( user_can( $uid, 'manage_options' ) || $role === PF_Constants::ROLE_GLOBAL_ADMIN ) {
			global $wpdb;

			$ids = $wpdb->get_col( 'SELECT forumid FROM ' . self::forums_table() );

			return array_map( 'intval', (array) $ids );
		}

		if ( ! $role ) {
			return [];
		}

		return PF_Roles_V2::get_allowed_forum_ids( $role );
	}

	private static function count_pending_posts( array $forum_ids ): int {
		if ( empty( $forum_ids ) ) {
			return 0;
		}

		global $wpdb;
		$ph = implode( ',', array_fill( 0, count( $forum_ids ), '%d' ) );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::posts_table() . " WHERE status = 1 AND forumid IN ($ph)",
				...$forum_ids
			)
		);
	}

	private static function count_warned_users(): int {
		return count(
			get_users(
				[
					'meta_query' => [
						[
							'key'     => PF_Constants::META_WARN_LEVEL,
							'value'   => 0,
							'compare' => '>',
							'type'    => 'NUMERIC',
						],
					],
					'fields' => 'ID',
					'number' => 999,
				]
			)
		);
	}

	public static function get_pending_posts( array $forum_ids, int $limit = 20 ): array {
		if ( empty( $forum_ids ) ) {
			return [];
		}

		global $wpdb;
		$ph = implode( ',', array_fill( 0, count( $forum_ids ), '%d' ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT p.*, u.display_name, u.user_email, f.title AS forum_title
				 FROM ' . self::posts_table() . ' p
				 LEFT JOIN ' . $wpdb->users . ' u ON u.ID = p.userid
				 LEFT JOIN ' . self::forums_table() . ' f ON f.forumid = p.forumid
				 WHERE p.status = 1 AND p.forumid IN (' . $ph . ')
				 ORDER BY p.created DESC LIMIT %d',
				...array_merge( $forum_ids, [ $limit ] )
			),
			ARRAY_A
		) ?: [];
	}

	public static function get_warned_users( int $limit = 20 ): array {
		return get_users(
			[
				'meta_query' => [
					[
						'key'     => PF_Constants::META_WARN_LEVEL,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					],
				],
				'number'  => $limit,
				'orderby' => 'registered',
				'order'   => 'DESC',
			]
		);
	}

	public static function get_my_logs( int $uid, int $limit = 30 ): array {
		return PF_Logger::get_logs(
			[
				'mod_id' => $uid,
				'limit'  => $limit,
			]
		);
	}

	public static function get_pinned_topics( array $forum_ids, int $limit = 20 ): array {
		if ( empty( $forum_ids ) ) {
			return [];
		}

		global $wpdb;
		$ph = implode( ',', array_fill( 0, count( $forum_ids ), '%d' ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT t.*, f.title AS forum_title, u.display_name
				 FROM ' . self::topics_table() . ' t
				 LEFT JOIN ' . self::forums_table() . ' f ON f.forumid = t.forumid
				 LEFT JOIN ' . $wpdb->users . ' u ON u.ID = t.userid
				 WHERE (t.type = %s OR t.closed = 1)
				   AND t.forumid IN (' . $ph . ')
				 ORDER BY t.modified DESC LIMIT %d',
				...array_merge( [ 'sticky' ], $forum_ids, [ $limit ] )
			),
			ARRAY_A
		) ?: [];
	}

	public static function ajax_approve_post(): void {
		check_ajax_referer( 'pf_mod_center' );

		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		self::check_mod_permission_for_post( $post_id );

		global $wpdb;
		$wpdb->update( self::posts_table(), [ 'status' => 0 ], [ 'postid' => $post_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->post->reset();
		}

		PF_Logger::log(
			get_current_user_id(),
			'approve_post',
			'post',
			$post_id,
			"Post #{$post_id}",
			'Mod Center approval'
		);

		wp_send_json_success( [ 'message' => 'Đã duyệt bài.' ] );
	}

	public static function ajax_delete_post(): void {
		check_ajax_referer( 'pf_mod_center' );

		$post_id = (int) ( $_POST['post_id'] ?? 0 );
		$reason  = sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Vi phạm nội quy' ) );
		self::check_mod_permission_for_post( $post_id );

		if ( function_exists( 'WPF' ) && method_exists( WPF()->post, 'delete' ) ) {
			WPF()->post->delete( $post_id, true, true, [], false );
		} else {
			global $wpdb;
			$wpdb->delete( self::posts_table(), [ 'postid' => $post_id ] );
		}

		PF_Logger::log(
			get_current_user_id(),
			'delete_post',
			'post',
			$post_id,
			"Post #{$post_id}",
			$reason
		);

		wp_send_json_success( [ 'message' => 'Đã xóa bài.' ] );
	}

	public static function ajax_warn_user(): void {
		check_ajax_referer( 'pf_mod_center' );

		$target_uid = (int) ( $_POST['user_id'] ?? 0 );
		$level      = (int) ( $_POST['level'] ?? 1 );
		$reason     = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $target_uid || ! $reason ) {
			wp_send_json_error( [ 'message' => 'Thiếu thông tin user hoặc lý do.' ] );
		}

		if ( $level > PF_Constants::WARN_CAUTION && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Mod chỉ được cảnh báo tối đa cấp 2. Khóa tài khoản cần Super Admin.' ] );
		}

		PF_Roles_V2::warn_user( $target_uid, $reason, $level );

		$target = get_userdata( $target_uid );
		PF_Logger::log(
			get_current_user_id(),
			'warn_user',
			'user',
			$target_uid,
			$target ? $target->user_email : "#{$target_uid}",
			$reason,
			[ 'level' => $level ]
		);

		wp_send_json_success( [ 'message' => 'Đã gửi cảnh báo.' ] );
	}

	public static function ajax_ban_user(): void {
		check_ajax_referer( 'pf_mod_center' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Chỉ Super Admin mới được khóa tài khoản vĩnh viễn.' ] );
		}

		$target_uid = (int) ( $_POST['user_id'] ?? 0 );
		$reason     = sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) );

		if ( ! $target_uid || ! $reason ) {
			wp_send_json_error( [ 'message' => 'Thiếu thông tin user hoặc lý do.' ] );
		}

		PF_Roles_V2::warn_user( $target_uid, $reason, PF_Constants::WARN_BANNED );

		$target = get_userdata( $target_uid );
		PF_Logger::log(
			get_current_user_id(),
			'ban_user',
			'user',
			$target_uid,
			$target ? $target->user_email : "#{$target_uid}",
			$reason
		);

		wp_send_json_success( [ 'message' => 'Đã khóa tài khoản.' ] );
	}

	public static function ajax_unpin_topic(): void {
		check_ajax_referer( 'pf_mod_center' );

		$topic_id = (int) ( $_POST['topic_id'] ?? 0 );
		self::check_mod_permission_for_topic( $topic_id );

		global $wpdb;
		$wpdb->update( self::topics_table(), [ 'type' => 'topic' ], [ 'topicid' => $topic_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->topic->reset();
		}

		PF_Logger::log(
			get_current_user_id(),
			'unpin_topic',
			'topic',
			$topic_id,
			"Topic #{$topic_id}",
			'Mod Center unpin'
		);

		wp_send_json_success( [ 'message' => 'Đã bỏ ghim.' ] );
	}

	public static function ajax_unlock_topic(): void {
		check_ajax_referer( 'pf_mod_center' );

		$topic_id = (int) ( $_POST['topic_id'] ?? 0 );
		self::check_mod_permission_for_topic( $topic_id );

		global $wpdb;
		$wpdb->update( self::topics_table(), [ 'closed' => 0 ], [ 'topicid' => $topic_id ] );

		if ( function_exists( 'WPF' ) ) {
			WPF()->topic->reset();
		}

		PF_Logger::log(
			get_current_user_id(),
			'unlock_topic',
			'topic',
			$topic_id,
			"Topic #{$topic_id}",
			'Mod Center unlock'
		);

		wp_send_json_success( [ 'message' => 'Đã mở khóa topic.' ] );
	}

	private static function check_mod_permission_for_post( int $post_id ): void {
		global $wpdb;

		$forum_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT forumid FROM ' . self::posts_table() . ' WHERE postid = %d',
				$post_id
			)
		);

		if ( ! $forum_id || ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền trong forum này.' ] );
		}
	}

	private static function check_mod_permission_for_topic( int $topic_id ): void {
		global $wpdb;

		$forum_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT forumid FROM ' . self::topics_table() . ' WHERE topicid = %d',
				$topic_id
			)
		);

		if ( ! $forum_id || ! PF_Roles_V2::user_can_moderate_forum( get_current_user_id(), $forum_id ) ) {
			wp_send_json_error( [ 'message' => 'Bạn không có quyền trong forum này.' ] );
		}
	}
}
