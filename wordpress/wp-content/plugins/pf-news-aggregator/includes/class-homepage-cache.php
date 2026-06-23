<?php
/**
 * Homepage news query cache (transients).
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_Homepage_Cache {

	const TTL = 300; // 5 minutes (safety net; busted on every pet_news change).

	public static function init() {
		add_action( 'save_post_pet_news', array( __CLASS__, 'clear_all' ) );
		add_action( 'trashed_post', array( __CLASS__, 'maybe_clear_on_delete' ) );
		add_action( 'deleted_post', array( __CLASS__, 'maybe_clear_on_delete' ) );
		add_action( 'pf_rss_fetch_complete', array( __CLASS__, 'on_fetch_complete' ), 10, 1 );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function maybe_clear_on_delete( $post_id ) {
		if ( 'pet_news' === get_post_type( $post_id ) ) {
			self::clear_all();
		}
	}

	/**
	 * @param int $created Number of posts created in this fetch run.
	 */
	public static function on_fetch_complete( $created ) {
		if ( $created > 0 ) {
			$latest = get_posts(
				array(
					'post_type'      => 'pet_news',
					'posts_per_page' => 3,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'post_status'    => 'publish',
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $latest ) ) {
				PF_Featured_Posts::set_featured( $latest );
			}
		}

		self::clear_all();
	}

	public static function clear_all() {
		$gen = (int) get_option( 'pf_home_news_gen', 0 );
		update_option( 'pf_home_news_gen', $gen + 1, false );

		foreach ( array( 'featured', 'latest', 'trending' ) as $key ) {
			delete_transient( self::legacy_cache_key( $key ) );
			delete_transient( self::cache_key( $key ) );
		}
	}

	/**
	 * @param string $key Cache bucket.
	 * @return string
	 */
	private static function cache_key( $key ) {
		$gen = (int) get_option( 'pf_home_news_gen', 0 );

		return 'pf_home_news_' . $key . '_v' . $gen;
	}

	/**
	 * Pre-version transient names (cleanup on bust).
	 *
	 * @param string $key Cache bucket.
	 * @return string
	 */
	private static function legacy_cache_key( $key ) {
		return 'pf_home_news_' . $key;
	}

	/**
	 * Run WP_Query with transient cache of post IDs.
	 *
	 * @param string $key  Cache bucket key.
	 * @param array  $args WP_Query args.
	 * @return WP_Query
	 */
	public static function query( $key, $args ) {
		$cache_key = self::cache_key( $key );
		$ids       = get_transient( $cache_key );

		if ( is_array( $ids ) ) {
			if ( empty( $ids ) ) {
				return new WP_Query( array( 'post__in' => array( 0 ) ) );
			}

			return new WP_Query(
				array(
					'post_type'      => 'pet_news',
					'post__in'       => $ids,
					'orderby'        => 'post__in',
					'posts_per_page' => count( $ids ),
					'post_status'    => 'publish',
				)
			);
		}

		$query = new WP_Query( $args );
		$ids   = wp_list_pluck( $query->posts, 'ID' );

		if ( ! empty( $ids ) ) {
			set_transient( $cache_key, $ids, self::TTL );
		}

		return $query;
	}

	/**
	 * Featured hero posts (falls back to latest if none marked featured).
	 *
	 * @return WP_Query
	 */
	public static function featured_query() {
		$featured = self::query(
			'featured',
			array(
				'post_type'      => 'pet_news',
				'posts_per_page' => 3,
				'meta_query'     => array(
					array(
						'key'   => '_pf_featured',
						'value' => '1',
					),
				),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			)
		);

		if ( $featured->have_posts() ) {
			return $featured;
		}

		delete_transient( self::legacy_cache_key( 'featured' ) );
		delete_transient( self::cache_key( 'featured' ) );

		return self::query(
			'featured',
			array(
				'post_type'      => 'pet_news',
				'posts_per_page' => 3,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			)
		);
	}
}
