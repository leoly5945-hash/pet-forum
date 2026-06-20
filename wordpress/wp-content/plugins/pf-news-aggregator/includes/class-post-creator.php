<?php
/**
 * Create pet_news posts from translated RSS items.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_Post_Creator {

	/** @var PF_AI_Translator */
	private $translator;

	public function __construct() {
		$this->translator = new PF_AI_Translator();
	}

	/**
	 * @param array $source RSS source config.
	 * @param array $item   Parsed feed item.
	 * @return int|false Post ID.
	 */
	public function create_from_item( $source, $item ) {
		$original_url = $item['url'] ?? '';
		if ( ! $original_url ) {
			return false;
		}

		$existing = get_posts(
			array(
				'post_type'   => 'pet_news',
				'meta_key'    => '_pf_source_url',
				'meta_value'  => $original_url,
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return false;
		}

		$translated = $this->translator->translate_and_rewrite(
			$item['title'] ?? '',
			$item['content'] ?? ''
		);

		if ( ! $translated || empty( $translated['title_vi'] ) ) {
			return false;
		}

		$term    = get_term_by( 'name', $source['cat'] ?? '', 'news_category' );
		$term_id = $term ? (int) $term->term_id : 0;

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'pet_news',
				'post_title'   => sanitize_text_field( $translated['title_vi'] ),
				'post_content' => $this->build_bilingual_content( $translated, $original_url ),
				'post_excerpt' => sanitize_text_field( $translated['excerpt_vi'] ?? '' ),
				'post_status'  => 'publish',
				'post_author'  => 1,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		if ( $term_id ) {
			wp_set_object_terms( $post_id, array( $term_id ), 'news_category' );
		}

		update_post_meta( $post_id, '_pf_source_url', $original_url );
		update_post_meta( $post_id, '_pf_source_name', $source['name'] ?? '' );
		update_post_meta( $post_id, '_pf_title_en', $translated['title_en'] ?? '' );
		update_post_meta( $post_id, '_pf_content_en', $translated['content_en'] ?? '' );
		update_post_meta( $post_id, '_pf_title_vi', $translated['title_vi'] ?? '' );
		update_post_meta( $post_id, '_pf_content_vi', $translated['content_vi'] ?? '' );
		update_post_meta( $post_id, '_pf_featured', '0' );

		if ( ! empty( $item['image_url'] ) ) {
			$this->sideload_image( $item['image_url'], $post_id );
		}

		return (int) $post_id;
	}

	/**
	 * @param array  $data       Translated fields.
	 * @param string $source_url Original URL.
	 * @return string
	 */
	public function build_bilingual_content( $data, $source_url ) {
		$source_url = esc_url( $source_url );

		return '
<div class="pf-news-vi">
  <div class="pf-lang-badge vi">🇻🇳 Tiếng Việt</div>
  <div class="pf-news-body">' . wpautop( esc_html( $data['content_vi'] ?? '' ) ) . '</div>
</div>
<div class="pf-news-en">
  <div class="pf-lang-badge en">🇺🇸 English</div>
  <div class="pf-news-body">' . wpautop( esc_html( $data['content_en'] ?? '' ) ) . '</div>
</div>
<div class="pf-source-credit">
  📡 Nguồn: <a href="' . $source_url . '" target="_blank" rel="noopener">bài gốc</a>
</div>';
	}

	/**
	 * @param string $image_url Remote image URL.
	 * @param int    $post_id   Post ID.
	 */
	public function sideload_image( $image_url, $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attach_id = media_sideload_image( $image_url, $post_id, null, 'id' );
		if ( ! is_wp_error( $attach_id ) ) {
			set_post_thumbnail( $post_id, $attach_id );
		}
	}
}
