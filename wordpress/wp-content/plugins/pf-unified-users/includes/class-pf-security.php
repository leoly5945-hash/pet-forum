<?php
defined( 'ABSPATH' ) || exit;

/**
 * WordPress hardening: login lockout, custom login URL, header hygiene, audit log.
 * Production: set PF_HIDE_DEFAULT_LOGIN + PF_LOGIN_SLUG in wp-config-extra.php; Nginx blocks /wp-login.php.
 */
class PF_Security {

	const MAX_LOGIN_ATTEMPTS = 5;
	const LOCKOUT_DURATION   = 900; // 15 minutes

	public static function init(): void {
		add_action( 'wp_login_failed', [ __CLASS__, 'on_login_failed' ] );
		add_filter( 'authenticate', [ __CLASS__, 'check_login_lockout' ], 30, 3 );
		add_action( 'wp_login', [ __CLASS__, 'on_login_success' ], 10, 2 );

		add_filter( 'login_errors', [ __CLASS__, 'generic_login_error' ] );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		add_filter( 'the_generator', '__return_empty_string' );

		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', [ __CLASS__, 'block_xmlrpc_methods' ] );

		add_action( 'init', [ __CLASS__, 'block_user_enumeration' ], 1 );
		add_action( 'send_headers', [ __CLASS__, 'add_security_headers' ] );
		add_action( 'admin_notices', [ __CLASS__, 'check_file_permissions' ] );

		add_action( 'switch_theme', [ __CLASS__, 'log_admin_action' ] );
		add_action( 'activated_plugin', [ __CLASS__, 'log_admin_action' ] );
		add_action( 'deactivated_plugin', [ __CLASS__, 'log_admin_action' ] );
		add_action( 'profile_update', [ __CLASS__, 'log_profile_change' ], 10, 2 );

		add_filter( 'login_url', [ __CLASS__, 'custom_login_url' ], 10, 3 );
		add_filter( 'logout_url', [ __CLASS__, 'custom_logout_url' ], 10, 2 );
		add_action( 'init', [ __CLASS__, 'maybe_serve_custom_login' ], 1 );
		add_action( 'login_init', [ __CLASS__, 'block_direct_wp_login' ] );
	}

	public static function login_slug(): string {
		return defined( 'PF_LOGIN_SLUG' ) ? (string) PF_LOGIN_SLUG : 'dang-nhap-pet';
	}

	public static function on_login_failed( string $username ): void {
		$ip       = self::get_client_ip();
		$key      = 'pf_login_fail_' . md5( $ip );
		$attempts = (int) get_transient( $key ) + 1;
		set_transient( $key, $attempts, self::LOCKOUT_DURATION );

		if ( $attempts >= self::MAX_LOGIN_ATTEMPTS ) {
			self::log_security_event(
				'login_locked',
				[
					'ip'       => $ip,
					'username' => $username,
					'attempts' => $attempts,
				]
			);

			if ( self::MAX_LOGIN_ATTEMPTS === $attempts ) {
				wp_mail(
					get_option( 'admin_email' ),
					'[Pet Forum] Phát hiện Brute Force Login',
					sprintf(
						"IP %s đã thất bại %d lần đăng nhập.\nUsername thử: %s\nThời gian: %s",
						$ip,
						self::MAX_LOGIN_ATTEMPTS,
						$username,
						current_time( 'mysql' )
					)
				);
			}
		}
	}

	/**
	 * @param mixed $user User object or WP_Error.
	 */
	public static function check_login_lockout( $user, string $username, string $password ) {
		if ( empty( $username ) || is_wp_error( $user ) ) {
			return $user;
		}

		$key      = 'pf_login_fail_' . md5( self::get_client_ip() );
		$attempts = (int) get_transient( $key );

		if ( $attempts >= self::MAX_LOGIN_ATTEMPTS ) {
			$minutes = (int) ceil( self::LOCKOUT_DURATION / 60 );
			return new WP_Error(
				'too_many_attempts',
				sprintf(
					'Quá nhiều lần đăng nhập thất bại. IP bị khóa %d phút. / Too many failed attempts. IP locked for %d minutes.',
					$minutes,
					$minutes
				)
			);
		}

		return $user;
	}

	public static function on_login_success( string $user_login, $user ): void {
		unset( $user );
		delete_transient( 'pf_login_fail_' . md5( self::get_client_ip() ) );
		self::log_security_event(
			'login_success',
			[
				'username' => $user_login,
				'ip'       => self::get_client_ip(),
			]
		);
	}

	public static function generic_login_error( string $error ): string {
		unset( $error );
		return 'Thông tin đăng nhập không chính xác. / Invalid login credentials.';
	}

	public static function block_xmlrpc_methods( array $methods ): array {
		unset( $methods );
		return [];
	}

	public static function block_user_enumeration(): void {
		if ( isset( $_GET['author'] ) && ! is_admin() ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		add_filter(
			'rest_endpoints',
			static function ( array $endpoints ): array {
				if ( ! current_user_can( 'manage_options' ) ) {
					unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
				}
				return $endpoints;
			}
		);
	}

	public static function add_security_headers(): void {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}

	public static function check_file_permissions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$config = ABSPATH . 'wp-config.php';
		if ( ! file_exists( $config ) ) {
			return;
		}

		$perms = decoct( fileperms( $config ) & 0777 );
		if ( '400' !== $perms && '440' !== $perms && '600' !== $perms ) {
			echo '<div class="notice notice-warning"><p>'
				. esc_html( "Bảo mật: wp-config.php nên chmod 400 trên production (hiện tại: {$perms})." )
				. '</p></div>';
		}
	}

	public static function log_admin_action(): void {
		self::log_security_event(
			'admin_action',
			[
				'action'  => current_action(),
				'user_id' => get_current_user_id(),
				'ip'      => self::get_client_ip(),
			]
		);
	}

	public static function log_profile_change( int $user_id, $old_user ): void {
		$new = get_userdata( $user_id );
		self::log_security_event(
			'profile_update',
			[
				'user_id'    => $user_id,
				'old_email'  => $old_user->user_email ?? '',
				'new_email'  => $new ? $new->user_email : '',
				'changed_by' => get_current_user_id(),
			]
		);
	}

	private static function log_security_event( string $event, array $data ): void {
		$log = get_option( 'pf_security_log', [] );
		if ( ! is_array( $log ) ) {
			$log = [];
		}
		array_unshift(
			$log,
			[
				'event' => $event,
				'data'  => $data,
				'time'  => current_time( 'mysql' ),
			]
		);
		update_option( 'pf_security_log', array_slice( $log, 0, 200 ), false );
	}

	public static function custom_login_url( string $login_url, string $redirect, bool $force_reauth ): string {
		unset( $force_reauth );
		$url = home_url( '/' . self::login_slug() . '/' );
		if ( $redirect ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );
		}
		return $url;
	}

	public static function custom_logout_url( string $logout_url, string $redirect ): string {
		unset( $logout_url );
		$url  = home_url( '/' . self::login_slug() . '/' );
		$args = [ 'action' => 'logout' ];
		if ( $redirect ) {
			$args['redirect_to'] = $redirect;
		}
		return wp_nonce_url( add_query_arg( $args, $url ), 'log-out' );
	}

	public static function maybe_serve_custom_login(): void {
		if ( is_admin() ) {
			return;
		}

		$path = trim( (string) parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
		$slug = self::login_slug();

		if ( $path !== $slug ) {
			return;
		}

		global $pagenow;
		$pagenow = 'wp-login.php';
		require_once ABSPATH . 'wp-login.php';
		exit;
	}

	public static function block_direct_wp_login(): void {
		if ( ! defined( 'PF_HIDE_DEFAULT_LOGIN' ) || ! PF_HIDE_DEFAULT_LOGIN ) {
			return;
		}

		$uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );
		if ( str_contains( $uri, 'wp-login.php' ) ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}
	}

	private static function get_client_ip(): string {
		$headers = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];
		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$ip = trim( explode( ',', (string) $_SERVER[ $header ] )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
		return '0.0.0.0';
	}
}
