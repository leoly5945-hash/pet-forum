<?php
/**
 * Pet Forum — production wp-config extras.
 * Add to wp-config.php (before "That's all, stop editing"):
 *   require_once __DIR__ . '/wp-config-extra.php';
 *
 * LOCAL DEV: do not require this file (keeps /wp-login.php accessible).
 */

@ini_set( 'session.cookie_httponly', '1' );
@ini_set( 'session.cookie_secure', '1' );
@ini_set( 'session.use_only_cookies', '1' );
@ini_set( 'session.cookie_samesite', 'Strict' );

if (
	isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] )
	&& 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']
) {
	$_SERVER['HTTPS'] = 'on';
}

if ( ! empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) {
	$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

define( 'PF_LOGIN_SLUG', 'dang-nhap-pet' );
define( 'PF_HIDE_DEFAULT_LOGIN', true );
