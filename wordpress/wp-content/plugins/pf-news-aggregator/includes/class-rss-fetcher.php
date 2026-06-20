<?php
/**
 * RSS fetch orchestration.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_RSS_Fetcher {

	/** @var PF_Post_Creator */
	private $creator;

	public function __construct() {
		$this->creator = new PF_Post_Creator();
	}

	public function run() {
		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}

		$sources = $this->get_active_sources();
		$created = 0;

		foreach ( $sources as $source ) {
			$created += $this->fetch_single_rss( $source );
		}

		update_option( 'pf_news_last_fetch', current_time( 'mysql' ) );
		update_option( 'pf_news_last_fetch_count', $created );

		return $created;
	}

	/**
	 * @return array
	 */
	public function get_active_sources() {
		$disabled = (array) get_option( 'pf_disabled_rss_sources', array() );
		$sources  = PF_RSS_SOURCES;

		return array_values(
			array_filter(
				$sources,
				function ( $source ) use ( $disabled ) {
					return ! in_array( $source['name'], $disabled, true );
				}
			)
		);
	}

	/**
	 * @param array $source Source config.
	 * @return int Number of posts created.
	 */
	public function fetch_single_rss( $source ) {
		$rss = fetch_feed( $source['url'] );
		if ( is_wp_error( $rss ) ) {
			return 0;
		}

		$items   = $rss->get_items( 0, 5 );
		$created = 0;

		foreach ( $items as $item ) {
			$enclosure = $item->get_enclosure();
			$image_url = null;

			if ( $enclosure ) {
				$image_url = $enclosure->get_link();
			}

			if ( ! $image_url ) {
				$image_url = $this->extract_image_from_content( $item->get_content() ?: $item->get_description() );
			}

			$parsed = array(
				'title'     => $item->get_title(),
				'content'   => wp_strip_all_tags( $item->get_description() ?: $item->get_content() ),
				'url'       => $item->get_permalink(),
				'image_url' => $image_url,
			);

			if ( $this->creator->create_from_item( $source, $parsed ) ) {
				++$created;
			}
		}

		return $created;
	}

	/**
	 * @param string $html HTML content.
	 * @return string|null
	 */
	private function extract_image_from_content( $html ) {
		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $html, $matches ) ) {
			return esc_url_raw( $matches[1] );
		}

		return null;
	}
}
