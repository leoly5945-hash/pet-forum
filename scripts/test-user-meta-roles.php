<?php
/**
 * Test user meta sync + pf-roles-manager permissions.
 * Run: docker compose exec -T wpcli wp eval-file /scripts/test-user-meta-roles.php --path=/var/www/html --allow-root
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$pass = 0;
$fail = 0;

function pf_test( $name, $cond, $detail = '' ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ✅ $name\n";
	} else {
		$GLOBALS['fail']++;
		echo "  ❌ $name" . ( $detail ? " — $detail" : '' ) . "\n";
	}
}

echo "\n=== PF USER META + ROLES TEST ===\n\n";

// ── Setup test users ──────────────────────────────────────────────
$ts = time();
$users = [
	'pf_test_member' => [ 'role' => 'subscriber', 'label' => 'Member' ],
	'pf_test_catmod' => [ 'role' => 'pf_cat_admin', 'label' => 'Cat Mod' ],
	'pf_test_global' => [ 'role' => 'pf_global_admin', 'label' => 'Global Mod' ],
];

foreach ( $users as $login => $cfg ) {
	$u = get_user_by( 'login', $login );
	if ( ! $u ) {
		$id = wp_create_user( $login, 'Test@1234!', "{$login}@petforum.test" );
		if ( ! is_wp_error( $id ) ) {
			$u = new WP_User( $id );
			$u->set_role( $cfg['role'] );
		}
	} else {
		$u->set_role( $cfg['role'] );
	}
}

$member_id = get_user_by( 'login', 'pf_test_member' )->ID ?? 0;
$catmod_id = get_user_by( 'login', 'pf_test_catmod' )->ID ?? 0;
$global_id = get_user_by( 'login', 'pf_test_global' )->ID ?? 0;

echo "Test users: member=$member_id catmod=$catmod_id global=$global_id\n\n";

// ── 1. USER META: sync wpForo → wp_usermeta ───────────────────────
echo "1. User meta save & sync\n";

if ( function_exists( 'WPF' ) ) {
	WPF()->init();
}

$data = [
	'data' => [
		'pf_country' => 'VN',
		'pf_phone'   => '+84901234567',
		'pf_city'    => 'Hà Nội',
	],
];
PF_User_Meta::sync_fields_to_usermeta( $member_id, $data );
update_user_meta( $member_id, 'pf_phone_public', '0' );
update_user_meta( $member_id, 'pf_city_public', '1' );

pf_test( 'pf_country saved', get_user_meta( $member_id, 'pf_country', true ) === 'VN' );
pf_test( 'pf_phone saved', get_user_meta( $member_id, 'pf_phone', true ) === '+84901234567' );
pf_test( 'pf_city saved', get_user_meta( $member_id, 'pf_city', true ) === 'Hà Nội' );
pf_test( 'pf_phone_public = 0', get_user_meta( $member_id, 'pf_phone_public', true ) === '0' );

PF_User_Meta::sync_usermeta_to_wpforo( $member_id );
$cf = function_exists( 'WPF' ) ? WPF()->member->get_custom_fields( $member_id ) : [];
pf_test( 'wpForo fields JSON synced', ( $cf['pf_country'] ?? '' ) === 'VN' && ( $cf['pf_phone'] ?? '' ) === '+84901234567' );

// Privacy helpers
pf_test(
	'can_view_field phone: owner sees',
	PF_User_Meta::can_view_field( $member_id, 'phone', $member_id )
);
pf_test(
	'can_view_field phone: guest blocked when private',
	! PF_User_Meta::can_view_field( $member_id, 'phone', 0 )
);
pf_test(
	'can_view_field city: guest allowed when public',
	PF_User_Meta::can_view_field( $member_id, 'city', 0 )
);
pf_test(
	'get_country_display VN',
	PF_User_Meta::get_country_display( 'VN' ) === '🇻🇳 Việt Nam'
);

// WP Admin save round-trip (Super Admin path)
wp_set_current_user( 1 );
$_POST['pf_country']        = 'TH';
$_POST['pf_phone']          = '+66812345678';
$_POST['pf_city']           = 'Bangkok';
$_POST['pf_phone_public']   = '1';
$_POST['pf_city_public']    = '0';
$_POST['pf_preferred_lang'] = 'en';
$_POST['pf_profile_nonce']  = wp_create_nonce( 'pf_save_profile_' . $member_id );
PF_User_Meta::save_profile_fields( $member_id );

$country_after = get_user_meta( $member_id, 'pf_country', true );
pf_test( 'WP Admin save → pf_country', $country_after === 'TH', "got: $country_after" );
pf_test( 'WP Admin save → pf_phone_public', get_user_meta( $member_id, 'pf_phone_public', true ) === '1' );
$cf2 = function_exists( 'WPF' ) ? WPF()->member->get_custom_fields( $member_id ) : [];
pf_test( 'WP Admin save → wpForo sync', ( $cf2['pf_country'] ?? '' ) === 'TH', 'json: ' . json_encode( $cf2 ) );

// ── 2. ROLES & PERMISSIONS ────────────────────────────────────────
echo "\n2. Roles & permissions\n";

foreach ( [ 'pf_global_admin', 'pf_cat_admin', 'pf_dog_admin' ] as $r ) {
	pf_test( "role exists: $r", (bool) get_role( $r ) );
}

PF_WpForo_Perms::assign_moderator( $catmod_id, 'pf_cat_admin' );
PF_WpForo_Perms::assign_moderator( $global_id, 'pf_global_admin' );

pf_test( 'get_pf_role catmod', PF_Roles::get_pf_role( $catmod_id ) === 'pf_cat_admin' );
pf_test( 'get_pf_role global', PF_Roles::get_pf_role( $global_id ) === 'pf_global_admin' );

PF_Roles::flush_forum_map_cache();
$cat_forums = PF_Roles::get_allowed_forum_ids( 'pf_cat_admin' );
pf_test( 'cat admin forum map not empty', ! empty( $cat_forums ), 'got: ' . json_encode( $cat_forums ) );

$cat_forum_id = (int) ( $cat_forums[0] ?? 3 );
$other_forum  = 2;

pf_test(
	'cat mod CAN mod cat forum',
	PF_Roles::user_can_moderate_forum( $catmod_id, $cat_forum_id )
);
pf_test(
	'cat mod CANNOT mod other forum (id=2)',
	! PF_Roles::user_can_moderate_forum( $catmod_id, $other_forum )
);
pf_test(
	'global mod CAN mod any forum',
	PF_Roles::user_can_moderate_forum( $global_id, $other_forum )
);
pf_test(
	'member CANNOT mod',
	! PF_Roles::user_can_moderate_forum( $member_id, $cat_forum_id )
);

// wpForo forum_can filter
wp_set_current_user( $catmod_id );
WPF()->init();
$can_au_cat   = WPF()->perm->forum_can( 'au', $cat_forum_id );
$can_au_other = WPF()->perm->forum_can( 'au', $other_forum );
pf_test( 'wpForo forum_can au on cat forum', (bool) $can_au_cat );
pf_test( 'wpForo forum_can au blocked on other', ! (bool) $can_au_other );

wp_set_current_user( $global_id );
pf_test( 'wpForo forum_can au global on any', (bool) WPF()->perm->forum_can( 'au', $other_forum ) );

// Admin lockdown caps
wp_set_current_user( $catmod_id );
$cat_caps = user_can( $catmod_id, 'manage_options' );
wp_set_current_user( $global_id );
$global_caps       = user_can( $global_id, 'activate_plugins' );
$global_edit_users = user_can( $global_id, 'edit_users' );
pf_test( 'section admin: no manage_options', ! $cat_caps );
pf_test( 'global admin: no activate_plugins', ! $global_caps );
pf_test( 'global admin: has edit_users', $global_edit_users );

$editable = PF_Admin_Lockdown::filter_editable_roles( get_editable_roles() );
pf_test(
	'global admin editable_roles excludes administrator',
	! isset( $editable['administrator'] )
);

// Mod log table
global $wpdb;
$log_table = $wpdb->prefix . 'pf_mod_log';
pf_test( 'pf_mod_log table exists', (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $log_table ) ) );

// ── Summary ───────────────────────────────────────────────────────
echo "\n=== RESULT: {$GLOBALS['pass']} passed, {$GLOBALS['fail']} failed ===\n\n";
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
