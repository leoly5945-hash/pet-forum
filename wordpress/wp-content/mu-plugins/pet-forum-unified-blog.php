<?php
/**
 * Route posts page to Unified Blog template when page_for_posts is set.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( is_home() && ! is_front_page() ) {
			$posts_page = (int) get_option( 'page_for_posts' );
			if ( $posts_page && 'page-blog.php' === get_page_template_slug( $posts_page ) ) {
				$classes[] = 'page-template-page-blog';
				$classes[] = 'unified-blog-page';
			}
		}
		return $classes;
	}
);

add_filter(
	'template_include',
	static function ( $template ) {
		if ( ! is_home() || is_front_page() ) {
			return $template;
		}

		$posts_page = (int) get_option( 'page_for_posts' );
		if ( ! $posts_page ) {
			return $template;
		}

		$page_template = get_page_template_slug( $posts_page );
		if ( 'page-blog.php' !== $page_template ) {
			return $template;
		}

		$custom = locate_template( 'page-blog.php' );
		return $custom ? $custom : $template;
	},
	99
);
