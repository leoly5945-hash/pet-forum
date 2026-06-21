<?php
defined( 'ABSPATH' ) || exit;

class PF_WpForo_Perms {

	private static $mod_caps = [
		'vf', 'enf', 'ct', 'vt', 'ent', 'et', 'dt',
		'cr', 'ocr', 'vr', 'er', 'dr', 'tag',
		'eot', 'eor', 'dot', 'dor', 'sb', 'l', 'r', 's',
		'au', 'p', 'op', 'vp', 'sv', 'osv', 'v', 'vop', 'vlp',
		'a', 'va', 'at', 'oat', 'aot', 'cot', 'mt',
	];

	public static function init() {
		add_filter( 'wpforo_permissions_forum_can', [ __CLASS__, 'filter_forum_can' ], 10, 4 );
		add_filter( 'wpforo_current_user_is', [ __CLASS__, 'filter_current_user_is' ], 10, 2 );
		add_filter( 'wpforo_user_is', [ __CLASS__, 'filter_user_is' ], 10, 3 );
	}

	public static function assign_moderator( $user_id, $pf_role ) {
		$user_id = (int) $user_id;
		if ( ! $user_id || ! in_array( $pf_role, PF_Roles::$pf_role_slugs, true ) ) {
			return;
		}

		update_user_meta( $user_id, 'pf_wpforo_role', $pf_role );
		PF_Roles::flush_forum_map_cache();
		$forum_ids = PF_Roles::get_allowed_forum_ids( $pf_role );
		update_user_meta( $user_id, 'pf_mod_forum_ids', $forum_ids );

		if ( $pf_role === 'pf_global_admin' && function_exists( 'WPF' ) ) {
			$member = WPF()->member->get_member( $user_id );
			if ( $member && (int) ( $member['groupid'] ?? 0 ) === 3 ) {
				WPF()->member->update_profile_fields( $user_id, [ 'groupid' => 2 ], false );
			}
		}
	}

	public static function revoke_moderator( $user_id ) {
		$user_id = (int) $user_id;
		delete_user_meta( $user_id, 'pf_wpforo_role' );
		delete_user_meta( $user_id, 'pf_mod_forum_ids' );
	}

	public static function filter_forum_can( $result, $do, $forumid, $groupids ) {
		if ( ! is_user_logged_in() || ! function_exists( 'WPF' ) ) {
			return $result;
		}

		$user_id = get_current_user_id();
		$pf_role = PF_Roles::get_pf_role( $user_id );
		if ( ! $pf_role ) {
			return $result;
		}

		$forum_id = (int) ( is_array( $forumid ) ? ( $forumid['forumid'] ?? 0 ) : $forumid );
		if ( ! $forum_id ) {
			$forum_id = (int) wpfval( WPF()->current_object, 'forum', 'forumid' );
		}

		if ( $pf_role === 'pf_global_admin' ) {
			if ( in_array( $do, self::$mod_caps, true ) ) {
				return 1;
			}
			return $result;
		}

		if ( ! PF_Roles::user_can_moderate_forum( $user_id, $forum_id ) ) {
			return $result;
		}

		if ( in_array( $do, self::$mod_caps, true ) ) {
			return 1;
		}

		return $result;
	}

	public static function filter_current_user_is( $result, $role ) {
		if ( ! is_null( $result ) ) {
			return $result;
		}

		if ( $role !== 'moderator' ) {
			return $result;
		}

		$pf_role = PF_Roles::get_pf_role();
		if ( $pf_role === 'pf_global_admin' ) {
			return true;
		}

		return $result;
	}

	public static function filter_user_is( $result, $userid, $role ) {
		if ( ! is_null( $result ) ) {
			return $result;
		}

		if ( $role !== 'moderator' ) {
			return $result;
		}

		$pf_role = PF_Roles::get_pf_role( $userid );
		if ( $pf_role === 'pf_global_admin' ) {
			return true;
		}

		return $result;
	}
}
