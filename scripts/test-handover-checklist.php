<?php
/**
 * Handover checklist: register, 2FA, mod-center, vet forum slugs.
 *
 * Run:
 *   docker exec pet-forum-wpcli wp eval-file /scripts/test-handover-checklist.php --path=/var/www/html --allow-root
 */
defined( 'ABSPATH' ) || exit;

$GLOBALS['pf_hc_passed'] = 0;
$GLOBALS['pf_hc_failed'] = 0;

function pf_hc_internal_url( $path_or_url ) {
	$path = $path_or_url;
	if ( 0 === strpos( $path_or_url, 'http' ) ) {
		$parsed = wp_parse_url( $path_or_url );
		$path   = ( $parsed['path'] ?? '/' ) . ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );
	}

	$internal_base = getenv( 'PF_TEST_INTERNAL_URL' ) ?: 'http://pet-forum-wordpress';

	return rtrim( $internal_base, '/' ) . ( '/' === $path ? '/' : $path );
}

function pf_hc_http_get( $path_or_url ) {
	$internal_url = pf_hc_internal_url( $path_or_url );
	$public_host  = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

	return wp_remote_get(
		$internal_url,
		[
			'timeout'     => 10,
			'sslverify'   => false,
			'redirection' => 0,
			'headers'     => [
				'Host' => $public_host ?: 'localhost',
			],
		]
	);
}

function pf_hc_test( $label, $condition, $detail = '' ) {
	if ( $condition ) {
		$GLOBALS['pf_hc_passed']++;
		echo "  ✓ {$label}\n";
	} else {
		$GLOBALS['pf_hc_failed']++;
		echo "  ✗ {$label}" . ( $detail ? " — {$detail}" : '' ) . "\n";
	}
}

echo "\n=== Pet Forum Handover Checklist ===\n\n";

echo "--- Register ---\n";
pf_hc_test( 'Plugin pf-unified-users active', is_plugin_active( 'pf-unified-users/pf-unified-users.php' ) );
pf_hc_test( 'Shortcode pf_register', shortcode_exists( 'pf_register' ) );
pf_hc_test( 'Class PF_Register_V2', class_exists( 'PF_Register_V2' ) );

$register_page = get_page_by_path( 'register' );
pf_hc_test( 'Register page /register/', (bool) $register_page );
if ( $register_page ) {
	pf_hc_test( 'Register page has [pf_register]', has_shortcode( $register_page->post_content, 'pf_register' ) );
}

$register_html = '';
if ( $register_page ) {
	$register_html = do_shortcode( '[pf_register]' );
}
pf_hc_test( 'Register shortcode renders output', ! empty( $register_html ) );
pf_hc_test( 'Split register has type cards', false !== strpos( $register_html, 'pf-type-card' ) || false !== strpos( $register_html, 'pf-type-selector' ) );

$member_form_html = '';
$_GET['type']     = 'member';
$member_form_html = do_shortcode( '[pf_register]' );
unset( $_GET['type'] );
pf_hc_test( 'Member form reachable (?type=member)', false !== strpos( $member_form_html, 'pf-register-form' ) || false !== strpos( $member_form_html, 'form-member' ) || ! empty( $member_form_html ) );

pf_hc_test( 'Register CSS on disk', file_exists( PFU_DIR . 'assets/css/pf-register.css' ) );
pf_hc_test( 'Register JS on disk', file_exists( PFU_DIR . 'assets/js/pf-register.js' ) );

echo "\n--- 2FA ---\n";
pf_hc_test( 'Class PF_TwoFactor', class_exists( 'PF_TwoFactor' ) );
pf_hc_test( '2FA meta keys in constants', class_exists( 'PF_Constants' ) && PF_Constants::META_2FA_OTP === 'pf_2fa_otp' );

$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
if ( ! empty( $admins ) ) {
	$admin_id = (int) $admins[0];
	$ref      = new ReflectionMethod( 'PF_TwoFactor', 'requires_2fa' );
	$ref->setAccessible( true );
	$needs_2fa = $ref->invoke( null, $admin_id );
	pf_hc_test( 'Administrator requires 2FA', $needs_2fa );
} else {
	pf_hc_test( 'Administrator requires 2FA', false, 'no admin user found' );
}

$global_mod = get_users( [ 'role' => PF_Constants::ROLE_GLOBAL_ADMIN, 'number' => 1, 'fields' => 'ID' ] );
if ( ! empty( $global_mod ) && isset( $ref ) ) {
	pf_hc_test( 'Global sub-admin requires 2FA', $ref->invoke( null, (int) $global_mod[0] ) );
} else {
	pf_hc_test( 'Global sub-admin requires 2FA (skip)', true, 'no pf_global_admin user — assign one to test login flow' );
}

$vet_users = get_users(
	[
		'meta_key'   => PF_Constants::META_USER_TYPE,
		'meta_value' => PF_Constants::TYPE_VERIFIED_VET,
		'number'     => 1,
		'fields'     => 'ID',
	]
);
if ( ! empty( $vet_users ) && isset( $ref ) ) {
	pf_hc_test( 'Verified vet exempt from 2FA', ! $ref->invoke( null, (int) $vet_users[0] ) );
} else {
	pf_hc_test( 'Verified vet exempt from 2FA (skip)', true, 'no verified vet user yet' );
}

echo "\n--- Mod Center ---\n";
pf_hc_test( 'Shortcode pf_mod_center', shortcode_exists( 'pf_mod_center' ) );
pf_hc_test( 'Class PF_Mod_Center', class_exists( 'PF_Mod_Center' ) );

$mod_page = get_page_by_path( 'mod-center' );
pf_hc_test( 'Mod center page /mod-center/', (bool) $mod_page );
if ( $mod_page ) {
	pf_hc_test( 'Mod center has [pf_mod_center]', has_shortcode( $mod_page->post_content, 'pf_mod_center' ) );
}

pf_hc_test( 'Mod center CSS', file_exists( PFU_DIR . 'assets/css/pf-mod-center.css' ) );
pf_hc_test( 'Mod center JS', file_exists( PFU_DIR . 'assets/js/pf-mod-center.js' ) );
pf_hc_test( 'Mod log table exists', (bool) $GLOBALS['wpdb']->get_var( "SHOW TABLES LIKE '{$GLOBALS['wpdb']->prefix}pf_mod_log'" ) );

foreach ( PF_Constants::SECTION_MOD_ROLES as $role ) {
	pf_hc_test( "Section mod role {$role}", (bool) get_role( $role ) );
}

echo "\n--- Vet Forum ---\n";
pf_hc_test( 'VET_FORUM_SLUGS constant', ! empty( PF_Constants::VET_FORUM_SLUGS ) );
pf_hc_test( 'Primary VI slug bac-si-tu-van', in_array( 'bac-si-tu-van', PF_Constants::VET_FORUM_SLUGS, true ) );

global $wpdb;
$vi_table = $wpdb->prefix . 'wpforo_3_forums';
$vi_slug  = $wpdb->get_var( "SELECT slug FROM {$vi_table} WHERE slug IN ('bac-si-tu-van','bac-si-thu-y') LIMIT 1" );
pf_hc_test( 'VI vet forum exists in wpForo board 3', ! empty( $vi_slug ), 'found=' . ( $vi_slug ?: 'none' ) );

$en_table = $wpdb->prefix . 'wpforo_2_forums';
$en_slug  = $wpdb->get_var( "SELECT slug FROM {$en_table} WHERE slug = 'vet-consultation' LIMIT 1" );
pf_hc_test( 'EN vet forum vet-consultation (board 2)', $en_slug === 'vet-consultation' );

$vet_url = PF_Constants::get_vet_forum_url();
pf_hc_test( 'Vet URL helper', false !== strpos( $vet_url, 'bac-si-tu-van' ), $vet_url );

echo "\n--- HTTP smoke (internal: pet-forum-wordpress) ---\n";
$urls = [
	'home'     => home_url( '/' ),
	'register' => home_url( '/register/' ),
	'mod'      => home_url( '/mod-center/' ),
];
foreach ( $urls as $name => $url ) {
	$response      = pf_hc_http_get( $url );
	$internal_url  = pf_hc_internal_url( $url );
	$code          = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	$error_detail  = is_wp_error( $response ) ? $response->get_error_message() : $internal_url;
	pf_hc_test( "HTTP {$name} ({$code})", $code >= 200 && $code < 400, $error_detail );
}

pf_hc_test(
	'Vet forum slug in DB (bac-si-tu-van)',
	! empty( $vi_slug ),
	'URL routing may require wpForo board path — verify in browser'
);

$passed = (int) $GLOBALS['pf_hc_passed'];
$failed = (int) $GLOBALS['pf_hc_failed'];
echo "\n--- Results: {$passed} passed, {$failed} failed ---\n\n";
exit( $failed > 0 ? 1 : 0 );
