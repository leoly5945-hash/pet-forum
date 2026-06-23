<?php
defined( 'ABSPATH' ) || exit;

/**
 * Translate primary nav labels by current UI language (vi|en).
 */
class PF_Nav_Menu {

	public static function init(): void {
		add_filter( 'wp_nav_menu_objects', [ __CLASS__, 'translate_items' ], 20, 2 );
	}

	/**
	 * @param WP_Post[] $items Menu items.
	 * @param object    $args  Menu args.
	 * @return WP_Post[]
	 */
	public static function translate_items( array $items, $args ): array {
		if ( empty( $items ) ) {
			return $items;
		}

		$theme_location = is_object( $args ) && isset( $args->theme_location ) ? (string) $args->theme_location : '';
		if ( $theme_location && ! in_array( $theme_location, [ 'primary', 'mobile_menu' ], true ) ) {
			return $items;
		}

		$lang = class_exists( 'PF_I18n' ) ? PF_I18n::current_lang() : 'vi';

		foreach ( $items as $item ) {
			$key = self::resolve_item_key( $item );
			if ( $key ) {
				$item->title = PF_I18n::get( $key, $lang );
			}
		}

		return $items;
	}

	/**
	 * @param WP_Post $item Menu item.
	 */
	private static function resolve_item_key( $item ): ?string {
		$path = wp_parse_url( $item->url ?? '', PHP_URL_PATH );
		$path = is_string( $path ) ? untrailingslashit( $path ) : '';

		$map = [
			''                    => 'nav_home',
			'/community'            => 'nav_community',
			'/english-section'      => 'nav_english',
			'/muc-tieng-viet'       => 'nav_vietnamese',
			'/community/members'    => 'nav_members',
			'/blog'                 => 'nav_blog',
			'/world-languages'      => 'nav_world',
			'/terms'                => 'nav_terms',
			'/gop-y'                => 'nav_feedback',
		];

		if ( isset( $map[ $path ] ) ) {
			return $map[ $path ];
		}

		$title = strtolower( wp_strip_all_tags( $item->title ?? '' ) );

		if ( false !== strpos( $title, 'home' ) || false !== strpos( $title, 'trang chủ' ) ) {
			return 'nav_home';
		}
		if ( false !== strpos( $title, 'community' ) || false !== strpos( $title, 'diễn đàn' ) ) {
			return 'nav_community';
		}
		if ( false !== strpos( $title, 'english' ) || false !== strpos( $title, 'diễn đàn anh' ) ) {
			return 'nav_english';
		}
		if ( false !== strpos( $title, 'tiếng việt' ) || false !== strpos( $title, 'vietnamese' ) ) {
			return 'nav_vietnamese';
		}
		if ( false !== strpos( $title, 'thành viên' ) || false !== strpos( $title, 'members' ) ) {
			return 'nav_members';
		}
		if ( false !== strpos( $title, 'blog' ) ) {
			return 'nav_blog';
		}
		if ( false !== strpos( $title, 'world' ) || false !== strpos( $title, 'đa ngôn ngữ' ) ) {
			return 'nav_world';
		}
		if ( false !== strpos( $title, 'terms' ) || false !== strpos( $title, 'điều khoản' ) ) {
			return 'nav_terms';
		}
		if ( false !== strpos( $title, 'góp ý' ) || false !== strpos( $title, 'feedback' ) ) {
			return 'nav_feedback';
		}

		return null;
	}
}
