<?php
/**
 * Integration tests for PF_Split_Register.
 * Run: docker compose exec -T wpcli wp eval-file /var/www/scripts/test-split-register.php --path=/var/www/html --allow-root
 */

defined( 'ABSPATH' ) || exit;

$passed = 0;
$failed = 0;

function pf_test( $label, $condition, $detail = '' ) {
	global $passed, $failed;
	if ( $condition ) {
		$passed++;
		echo "  ✓ {$label}\n";
	} else {
		$failed++;
		echo "  ✗ {$label}" . ( $detail ? " — {$detail}" : '' ) . "\n";
	}
}

echo "\n=== PF Split Register Tests ===\n\n";

// 1. Class loaded
pf_test( 'PF_Split_Register class exists', class_exists( 'PF_Split_Register' ) );
pf_test( 'Shortcode registered', shortcode_exists( 'pf_split_register' ) );
pf_test( 'Vet role exists', (bool) get_role( 'pf_verified_vet' ) );

// 2. Register page
$page = get_page_by_path( 'register' );
pf_test( 'Register page exists', (bool) $page );
if ( $page ) {
	pf_test( 'Register page has pf_split_register shortcode', has_shortcode( $page->post_content, 'pf_split_register' ) );
}

// 3. Member registration
$member_email = 'pf_split_member_' . time() . '@example.com';
$member_id    = wp_create_user( 'pf_split_m_' . time(), 'Test@1234!', $member_email );
if ( ! is_wp_error( $member_id ) ) {
	update_user_meta( $member_id, 'pf_user_type', 'member' );
	update_user_meta( $member_id, 'pf_country', 'VN' );
	update_user_meta( $member_id, 'pf_account_active', '1' );
	pf_test( 'Member user created', true );
	pf_test( 'Member pf_user_type = member', get_user_meta( $member_id, 'pf_user_type', true ) === 'member' );
	wp_delete_user( $member_id );
} else {
	pf_test( 'Member user created', false, $member_id->get_error_message() );
}

// 4. Vet pending registration
$vet_email = 'pf_split_vet_' . time() . '@example.com';
$vet_id      = wp_create_user( 'pf_split_v_' . time(), 'Test@1234!', $vet_email );
if ( ! is_wp_error( $vet_id ) ) {
	update_user_meta( $vet_id, 'pf_user_type', 'vet_pending' );
	update_user_meta( $vet_id, 'pf_vet_status', 'pending' );
	update_user_meta( $vet_id, 'pf_account_active', '0' );
	update_user_meta( $vet_id, 'pf_phone', '+84901234567' );
	update_user_meta( $vet_id, 'pf_workplace', 'Test Clinic' );

	$user = get_user_by( 'id', $vet_id );
	$auth = apply_filters( 'authenticate', $user, $user->user_login, 'Test@1234!' );
	pf_test( 'Pending vet login blocked', is_wp_error( $auth ) && $auth->get_error_code() === 'pf_vet_pending' );

	// Approve flow
	update_user_meta( $vet_id, 'pf_vet_status', 'approved' );
	update_user_meta( $vet_id, 'pf_user_type', 'verified_vet' );
	update_user_meta( $vet_id, 'pf_account_active', '1' );
	$auth2 = apply_filters( 'authenticate', $user, $user->user_login, 'Test@1234!' );
	pf_test( 'Approved vet can authenticate', ! is_wp_error( $auth2 ) || $auth2 instanceof WP_User );

	$badge_name = apply_filters( 'wpforo_user_display_name', 'Dr Test', $user );
	pf_test( 'Verified badge added to display name', strpos( $badge_name, 'pf-verified-badge' ) !== false );

	wp_delete_user( $vet_id );
} else {
	pf_test( 'Vet user created', false, $vet_id->get_error_message() );
}

// 5. Verified Vet usergroup
$group_id = PF_Split_Register::get_vet_group_id();
pf_test( 'Verified Vet group ID configured', $group_id > 0, "group_id={$group_id}" );

// 6. Assets exist
pf_test( 'CSS file exists', file_exists( PF_SHIELD_DIR . 'assets/css/pf-split-register.css' ) );
pf_test( 'JS file exists', file_exists( PF_SHIELD_DIR . 'assets/js/pf-split-register.js' ) );

echo "\n--- Results: {$passed} passed, {$failed} failed ---\n\n";
exit( $failed > 0 ? 1 : 0 );
