<?php
defined( 'ABSPATH' ) || exit;

class PF_Upload {

	public static function init(): void {
		add_filter( 'wp_handle_upload_prefilter', [ __CLASS__, 'validate_upload' ] );
		add_filter( 'upload_mimes', [ __CLASS__, 'restrict_mimes' ] );
		add_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'verify_real_mime' ], 10, 4 );
		add_filter( 'wpforo_attachment_upload', [ __CLASS__, 'validate_forum_attachment' ], 10, 2 );
		add_action( 'init', [ __CLASS__, 'set_upload_size_limit' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ], 20 );
	}

	public static function validate_upload( array $file ): array {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			$file['error'] = 'Bạn cần đăng nhập để tải file lên.';
			return $file;
		}

		$role = PF_Constants::get_pf_role( $uid );
		$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( in_array( $ext, PF_Constants::BLOCKED_EXTENSIONS, true ) ) {
			$file['error'] = "Định dạng .{$ext} không được phép. Vui lòng liên hệ quản trị viên.";
			return $file;
		}

		if ( empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
			return $file;
		}

		$real_mime = self::get_real_mime( $file['tmp_name'] );
		$allowed   = array_keys( PF_Constants::get_allowed_all_mimes() );

		if ( ! in_array( $real_mime, $allowed, true ) ) {
			$file['error'] = "Loại file không được hỗ trợ ({$real_mime}). Chỉ chấp nhận: JPG, PNG, GIF, WEBP, PDF.";
			return $file;
		}

		if ( 0 === strpos( $real_mime, 'video/' ) ) {
			$file['error'] = 'Không hỗ trợ upload video trực tiếp. Vui lòng đăng link YouTube/TikTok.';
			return $file;
		}

		$max_size = self::get_max_size_for_user( $uid, $role );
		if ( (int) $file['size'] > $max_size ) {
			$mb            = round( $max_size / 1024 / 1024, 1 );
			$file['error'] = "File quá lớn. Giới hạn của bạn là {$mb}MB.";
			return $file;
		}

		$used  = self::get_user_disk_usage( $uid );
		$quota = PF_Constants::UPLOAD_USER_QUOTA;
		if ( ( $used + (int) $file['size'] ) > $quota ) {
			$used_mb       = round( $used / 1024 / 1024, 1 );
			$quota_mb      = round( $quota / 1024 / 1024, 1 );
			$file['error'] = "Bạn đã dùng {$used_mb}MB / {$quota_mb}MB quota. Vui lòng xóa bớt file cũ.";
			return $file;
		}

		if ( 0 === strpos( $real_mime, 'image/' ) && self::contains_php_code( $file['tmp_name'] ) ) {
			$file['error'] = 'File ảnh chứa nội dung đáng ngờ và đã bị từ chối.';
			self::log_suspicious_upload( $uid, $file['name'], 'php_in_image' );
			return $file;
		}

		return $file;
	}

	public static function restrict_mimes( array $mimes ): array {
		if ( current_user_can( 'manage_options' ) ) {
			return $mimes;
		}

		$allowed = [];
		foreach ( PF_Constants::get_allowed_all_mimes() as $mime => $exts ) {
			$allowed[ implode( '|', $exts ) ] = $mime;
		}

		return $allowed;
	}

	public static function verify_real_mime( array $data, string $file, string $filename, $mimes ): array {
		if ( ! $file || ! file_exists( $file ) ) {
			return $data;
		}

		$real = self::get_real_mime( $file );
		$ext  = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$map  = PF_Constants::get_allowed_all_mimes();

		$expected_exts = $map[ $real ] ?? [];

		if ( ! empty( $expected_exts ) && ! in_array( $ext, $expected_exts, true ) ) {
			$data['ext']             = $expected_exts[0];
			$data['type']            = $real;
			$data['proper_filename'] = pathinfo( $filename, PATHINFO_FILENAME ) . '.' . $expected_exts[0];
		}

		return $data;
	}

	public static function validate_forum_attachment( $attachment, $post_data ) {
		if ( is_array( $attachment ) && isset( $attachment['tmp_name'] ) ) {
			$validated = self::validate_upload(
				[
					'name'     => $attachment['name'] ?? '',
					'tmp_name' => $attachment['tmp_name'],
					'size'     => $attachment['size'] ?? 0,
				]
			);
			if ( ! empty( $validated['error'] ) ) {
				return new WP_Error( 'pf_upload_rejected', $validated['error'] );
			}
		}

		return $attachment;
	}

	public static function set_upload_size_limit(): void {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return;
		}

		$role = PF_Constants::get_pf_role( $uid );
		$max  = self::get_max_size_for_user( $uid, $role );

		add_filter(
			'upload_size_limit',
			static function () use ( $max ) {
				return $max;
			}
		);
	}

	private static function get_max_size_for_user( int $uid, ?string $role ): int {
		if ( current_user_can( 'manage_options' ) || in_array( $role, PF_Constants::SECTION_MOD_ROLES, true ) || PF_Constants::ROLE_GLOBAL_ADMIN === $role ) {
			return 20 * 1024 * 1024;
		}

		if ( PF_Constants::ROLE_VERIFIED_VET === $role ) {
			return 10 * 1024 * 1024;
		}

		$user = get_userdata( $uid );
		if ( $user ) {
			$days_old = ( time() - strtotime( $user->user_registered ) ) / DAY_IN_SECONDS;
			if ( $days_old < PF_Constants::UPLOAD_NEW_USER_DAYS ) {
				return PF_Constants::UPLOAD_NEW_USER_MAX;
			}
		}

		return PF_Constants::UPLOAD_MAX_IMAGE_SIZE;
	}

	public static function get_real_mime( string $file_path ): string {
		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			if ( $finfo ) {
				$mime = finfo_file( $finfo, $file_path );
				finfo_close( $finfo );
				if ( $mime ) {
					return $mime;
				}
			}
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $file_path );
			if ( $mime ) {
				return $mime;
			}
		}

		return 'application/octet-stream';
	}

	private static function contains_php_code( string $file_path ): bool {
		$content = file_get_contents( $file_path, false, null, 0, 1024 );
		if ( false === $content ) {
			return false;
		}

		return false !== strpos( $content, '<?php' ) || false !== strpos( $content, '<?=' );
	}

	public static function get_user_disk_usage( int $uid ): int {
		$attachments = get_posts(
			[
				'post_type'   => 'attachment',
				'author'      => $uid,
				'numberposts' => -1,
				'fields'      => 'ids',
				'post_status' => 'inherit',
			]
		);

		$total = 0;
		foreach ( $attachments as $att_id ) {
			$path = get_attached_file( (int) $att_id );
			if ( $path && file_exists( $path ) ) {
				$total += (int) filesize( $path );
			}
		}

		return $total;
	}

	private static function log_suspicious_upload( int $uid, string $filename, string $reason ): void {
		$log   = get_option( 'pf_suspicious_uploads', [] );
		$log[] = [
			'uid'      => $uid,
			'email'    => get_userdata( $uid )->user_email ?? '',
			'filename' => $filename,
			'reason'   => $reason,
			'time'     => current_time( 'mysql' ),
			'ip'       => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		];
		$log = array_slice( $log, -100 );
		update_option( 'pf_suspicious_uploads', $log, false );

		wp_mail(
			get_option( 'admin_email' ),
			'[Pet Forum] Upload đáng ngờ bị chặn',
			"User #{$uid} cố upload file đáng ngờ: {$filename} ({$reason})\nIP: " . ( $_SERVER['REMOTE_ADDR'] ?? '' )
		);
	}

	public static function add_admin_page(): void {
		add_submenu_page(
			PF_Admin_V2::MENU_SLUG,
			'Disk & Upload',
			'Disk Usage',
			'manage_options',
			'pf-disk-usage',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	public static function render_admin_page(): void {
		$users  = get_users( [ 'number' => 200, 'fields' => [ 'ID', 'display_name', 'user_email' ] ] );
		$usages = [];
		foreach ( $users as $u ) {
			$bytes = self::get_user_disk_usage( (int) $u->ID );
			if ( $bytes > 0 ) {
				$usages[ $u->ID ] = [ 'user' => $u, 'bytes' => $bytes ];
			}
		}
		uasort(
			$usages,
			static function ( $a, $b ) {
				return $b['bytes'] - $a['bytes'];
			}
		);
		$usages     = array_slice( $usages, 0, 20, true );
		$suspicious = get_option( 'pf_suspicious_uploads', [] );
		$quota_mb   = round( PF_Constants::UPLOAD_USER_QUOTA / 1024 / 1024 );
		?>
		<div class="wrap">
			<h1>Disk Usage & Upload Security</h1>

			<h2>Top 20 Users — Dung lượng upload</h2>
			<table class="widefat striped">
				<thead><tr><th>#</th><th>User</th><th>Đã dùng</th><th>Quota</th><th>%</th></tr></thead>
				<tbody>
				<?php
				$i = 1;
				foreach ( $usages as $data ) :
					$mb        = round( $data['bytes'] / 1024 / 1024, 1 );
					$pct       = round( $data['bytes'] / PF_Constants::UPLOAD_USER_QUOTA * 100, 1 );
					$bar_color = $pct > 80 ? '#dc2626' : ( $pct > 50 ? '#f59e0b' : '#40916c' );
					?>
				<tr>
					<td><?php echo (int) $i++; ?></td>
					<td>
						<strong><?php echo esc_html( $data['user']->display_name ); ?></strong><br>
						<small><?php echo esc_html( $data['user']->user_email ); ?></small>
					</td>
					<td><?php echo esc_html( (string) $mb ); ?> MB</td>
					<td><?php echo esc_html( (string) $quota_mb ); ?> MB</td>
					<td>
						<div style="background:#f1f5f9;border-radius:4px;height:8px;width:120px">
							<div style="background:<?php echo esc_attr( $bar_color ); ?>;width:<?php echo esc_attr( (string) min( $pct, 100 ) ); ?>%;height:8px;border-radius:4px"></div>
						</div>
						<small><?php echo esc_html( (string) $pct ); ?>%</small>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $suspicious ) ) : ?>
			<h2 style="margin-top:30px">Upload đáng ngờ bị chặn gần đây</h2>
			<table class="widefat striped">
				<thead><tr><th>Thời gian</th><th>User</th><th>File</th><th>Lý do</th><th>IP</th></tr></thead>
				<tbody>
				<?php foreach ( array_reverse( $suspicious ) as $s ) : ?>
				<tr>
					<td><?php echo esc_html( $s['time'] ?? '' ); ?></td>
					<td><?php echo esc_html( $s['email'] ?? '' ); ?></td>
					<td style="color:#dc2626;font-weight:600"><?php echo esc_html( $s['filename'] ?? '' ); ?></td>
					<td><?php echo esc_html( $s['reason'] ?? '' ); ?></td>
					<td><?php echo esc_html( $s['ip'] ?? '' ); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
