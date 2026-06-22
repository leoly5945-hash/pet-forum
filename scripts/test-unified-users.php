<?php
/**
 * Verify pf-unified-users after migration.
 * Run: docker compose exec -T wpcli wp eval-file /scripts/test-unified-users.php --path=/var/www/html --allow-root
 */
defined( 'ABSPATH' ) || exit;

$passed = 0;
$failed = 0;

function pfu_test( $label, $condition, $detail = '' ) {
	global $passed, $failed;
	if ( $condition ) {
		$passed++;
		echo "  ✓ {$label}\n";
	} else {
		$failed++;
		echo "  ✗ {$label}" . ( $detail ? " — {$detail}" : '' ) . "\n";
	}
}

echo "\n=== PF Unified Users Migration Tests ===\n\n";

$classes = [
	'PF_Constants',
	'PF_User_Meta_V2',
	'PF_AntiSpam_V2',
	'PF_Register_V2',
	'PF_Roles_V2',
	'PF_Frontend_Mod_V2',
	'PF_Admin_V2',
];
foreach ( $classes as $class ) {
	pfu_test( "Class {$class} exists", class_exists( $class ) );
}

pfu_test( 'Plugin active', is_plugin_active( 'pf-unified-users/pf-unified-users.php' ) );
pfu_test( 'Old pf-member-shield inactive', ! is_plugin_active( 'pf-member-shield/pf-member-shield.php' ) );
pfu_test( 'Old pf-roles-manager inactive', ! is_plugin_active( 'pf-roles-manager/pf-roles-manager.php' ) );

pfu_test( 'Shortcode pf_register', shortcode_exists( 'pf_register' ) );
pfu_test( 'Shortcode pf_split_register (compat)', shortcode_exists( 'pf_split_register' ) );
pfu_test( 'Shortcode pf_user_profile', shortcode_exists( 'pf_user_profile' ) );

$page = get_page_by_path( 'register' );
pfu_test( 'Register page exists', (bool) $page );
if ( $page ) {
	pfu_test( 'Register uses pf_register shortcode', has_shortcode( $page->post_content, 'pf_register' ) || has_shortcode( $page->post_content, 'pf_split_register' ) );
}

foreach ( PF_Constants::ALL_PF_ROLES as $role ) {
	pfu_test( "Role {$role} exists", (bool) get_role( $role ) );
}

pfu_test( 'Constants TYPE_VERIFIED_VET', PF_Constants::TYPE_VERIFIED_VET === 'verified_vet' );
pfu_test( 'Vet group ID > 0', PF_Constants::get_vet_group_id() > 0, 'id=' . PF_Constants::get_vet_group_id() );

global $wpdb;
$meta_counts = $wpdb->get_results( "SELECT meta_key, COUNT(*) as cnt FROM {$wpdb->usermeta} WHERE meta_key LIKE 'pf_%' GROUP BY meta_key ORDER BY cnt DESC LIMIT 5" );
pfu_test( 'pf_* usermeta preserved', ! empty( $meta_counts ), 'top keys: ' . implode( ', ', array_column( $meta_counts, 'meta_key' ) ) );

pfu_test( 'CSS exists', file_exists( PFU_DIR . 'assets/css/pf-register.css' ) );
pfu_test( 'JS exists', file_exists( PFU_DIR . 'assets/js/pf-register.js' ) );

echo "\n--- Results: {$passed} passed, {$failed} failed ---\n\n";
exit( $failed > 0 ? 1 : 0 );
