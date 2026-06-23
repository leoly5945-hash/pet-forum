<?php
defined( 'ABSPATH' ) || exit;

class PF_Logger {

	public static function init(): void {
		if ( get_option( 'pf_logger_db_ver' ) !== '2' ) {
			self::create_tables();
			update_option( 'pf_logger_db_ver', '2' );
		}

		add_action( 'wp_ajax_pf_export_mod_log', [ __CLASS__, 'ajax_export_csv' ] );
		add_action( 'wp_ajax_pf_confirm_handover', [ __CLASS__, 'ajax_confirm_handover' ] );
	}

	public static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'pf_mod_log';

		$sql = "CREATE TABLE $table (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			mod_id      BIGINT UNSIGNED NOT NULL,
			action      VARCHAR(50)     NOT NULL,
			target_type VARCHAR(30)     NOT NULL DEFAULT '',
			target_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
			target_info TEXT            NOT NULL,
			reason      TEXT            NOT NULL,
			extra_data  TEXT            NOT NULL,
			ip_address  VARCHAR(45)     NOT NULL DEFAULT '',
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY mod_id (mod_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Migrate legacy columns from older schema if present.
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM $table", 0 );
		if ( is_array( $columns ) && in_array( 'forum_id', $columns, true ) && ! in_array( 'target_type', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE $table ADD COLUMN target_type VARCHAR(30) NOT NULL DEFAULT '' AFTER action" );
			$wpdb->query( "ALTER TABLE $table ADD COLUMN target_info TEXT NOT NULL AFTER target_id" );
			$wpdb->query( "ALTER TABLE $table ADD COLUMN reason TEXT NOT NULL AFTER target_info" );
			$wpdb->query( "ALTER TABLE $table ADD COLUMN extra_data TEXT NOT NULL AFTER reason" );
			$wpdb->query( "UPDATE $table SET extra_data = CONCAT('{\"forum_id\":', forum_id, '}') WHERE forum_id > 0 AND (extra_data = '' OR extra_data IS NULL)" );
		}
		if ( is_array( $columns ) && in_array( 'ip', $columns, true ) && ! in_array( 'ip_address', $columns, true ) ) {
			$wpdb->query( "ALTER TABLE $table CHANGE ip ip_address VARCHAR(45) NOT NULL DEFAULT ''" );
		}
	}

	public static function log(
		int $mod_id,
		string $action,
		string $target_type = '',
		int $target_id = 0,
		string $target_info = '',
		string $reason = '',
		array $extra = []
	): void {
		global $wpdb;
		$table = $wpdb->prefix . 'pf_mod_log';

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			self::create_tables();
		}

		$wpdb->insert(
			$table,
			[
				'mod_id'      => $mod_id,
				'action'      => sanitize_key( $action ),
				'target_type' => sanitize_key( $target_type ),
				'target_id'   => $target_id,
				'target_info' => $target_info,
				'reason'      => $reason,
				'extra_data'  => $extra ? wp_json_encode( $extra, JSON_UNESCAPED_UNICODE ) : '',
				'ip_address'  => self::get_ip(),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	public static function get_logs( array $args = [] ): array {
		global $wpdb;
		$table  = $wpdb->prefix . 'pf_mod_log';
		$where  = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['mod_id'] ) ) {
			$where[]  = 'mod_id = %d';
			$params[] = (int) $args['mod_id'];
		}
		if ( ! empty( $args['action'] ) ) {
			$where[]  = 'action = %s';
			$params[] = $args['action'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['date_from'] . ' 00:00:00';
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['date_to'] . ' 23:59:59';
		}

		$limit  = (int) ( $args['limit'] ?? 50 );
		$offset = (int) ( $args['offset'] ?? 0 );
		$sql    = "SELECT * FROM $table WHERE " . implode( ' AND ', $where )
			. ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		if ( empty( $params ) ) {
			return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
	}

	public static function count_logs( int $mod_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}pf_mod_log WHERE mod_id = %d",
				$mod_id
			)
		);
	}

	public static function action_labels(): array {
		return [
			'ban_user'           => '🚫 Khóa user',
			'warn_user'          => '⚠️ Cảnh báo',
			'clear_warning'      => '✅ Gỡ cảnh báo',
			'delete_post'        => '🗑 Xóa bài',
			'approve_post'       => '✔ Duyệt bài',
			'pin_topic'          => '📌 Ghim topic',
			'lock_topic'         => '🔒 Khóa topic',
			'approve_vet'        => '🩺 Duyệt bác sĩ',
			'reject_vet'         => '❌ Từ chối bác sĩ',
			'assign_role'        => '👑 Gán quyền',
			'revoke_role'        => '🔓 Thu hồi quyền',
			'2fa_success'        => '🔐 2FA Login',
			'2fa_failed'         => '🔴 2FA Thất bại',
			'handover_confirmed' => '🚪 Bàn giao',
			'unpin_topic'        => '📌 Bỏ ghim',
			'unlock_topic'       => '🔓 Mở khóa',
		];
	}

	public static function ajax_export_csv(): void {
		check_ajax_referer( 'pf_admin_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không có quyền.' );
		}

		$mod_id    = (int) ( $_GET['mod_id'] ?? 0 );
		$date_from = sanitize_text_field( wp_unslash( $_GET['date_from'] ?? '' ) );
		$date_to   = sanitize_text_field( wp_unslash( $_GET['date_to'] ?? '' ) );

		$args = [
			'date_from' => $date_from ?: null,
			'date_to'   => $date_to ?: null,
			'limit'     => 9999,
		];
		if ( $mod_id ) {
			$args['mod_id'] = $mod_id;
		}

		$logs     = self::get_logs( $args );
		$filename = 'pf-mod-log-' . ( $mod_id ? "user{$mod_id}-" : '' ) . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $out, [ 'ID', 'Mod ID', 'Mod Name', 'Action', 'Target Type', 'Target ID', 'Target Info', 'Reason', 'Extra Data', 'IP', 'Time' ] );

		foreach ( $logs as $row ) {
			$mod = get_userdata( (int) $row['mod_id'] );
			fputcsv( $out, [
				$row['id'],
				$row['mod_id'],
				$mod ? $mod->display_name : "#{$row['mod_id']}",
				$row['action'],
				$row['target_type'] ?? '',
				$row['target_id'],
				$row['target_info'] ?? '',
				$row['reason'] ?? '',
				$row['extra_data'] ?? '',
				$row['ip_address'] ?? '',
				$row['created_at'],
			] );
		}
		fclose( $out );
		exit;
	}

	public static function ajax_confirm_handover(): void {
		check_ajax_referer( 'pf_admin_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Không có quyền.' ] );
		}

		$uid = (int) ( $_POST['user_id'] ?? 0 );
		if ( ! $uid ) {
			wp_send_json_error( [ 'message' => 'User ID không hợp lệ.' ] );
		}

		$target = get_userdata( $uid );
		if ( ! $target ) {
			wp_send_json_error( [ 'message' => 'Không tìm thấy user.' ] );
		}

		update_user_meta( $uid, PF_Constants::META_HANDOVER_DONE, '1' );
		update_user_meta( $uid, PF_Constants::META_HANDOVER_AT, current_time( 'mysql' ) );
		update_user_meta( $uid, PF_Constants::META_HANDOVER_BY, get_current_user_id() );

		$user = new WP_User( $uid );
		$user->set_role( 'subscriber' );
		PF_Roles_V2::revoke_mod( $uid );

		if ( class_exists( 'WP_Session_Tokens' ) ) {
			WP_Session_Tokens::get_instance( $uid )->destroy_all();
		}

		self::log(
			get_current_user_id(),
			'handover_confirmed',
			'user',
			$uid,
			$target->user_email,
			'Sub-admin handover completed by Super Admin'
		);

		wp_send_json_success( [ 'message' => 'Bàn giao hoàn tất. Tài khoản đã bị thu hồi quyền.' ] );
	}

	private static function get_ip(): string {
		foreach ( [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				return sanitize_text_field( explode( ',', (string) $_SERVER[ $key ] )[0] );
			}
		}

		return 'unknown';
	}
}
