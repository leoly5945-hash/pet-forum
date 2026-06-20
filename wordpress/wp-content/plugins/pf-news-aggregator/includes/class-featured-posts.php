<?php
/**
 * Featured post meta box and admin list column for Hero Section.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_Featured_Posts {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post_pet_news', array( __CLASS__, 'save_metabox' ) );
		add_filter( 'manage_pet_news_posts_columns', array( __CLASS__, 'add_list_column' ) );
		add_action( 'manage_pet_news_posts_custom_column', array( __CLASS__, 'render_list_column' ), 10, 2 );
	}

	public static function register_metabox() {
		add_meta_box(
			'pf_featured_box',
			'⭐ Bài Nổi Bật (Hero Section)',
			array( __CLASS__, 'render_metabox' ),
			'pet_news',
			'side',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		$featured = get_post_meta( $post->ID, '_pf_featured', true );
		wp_nonce_field( 'pf_featured_nonce', 'pf_featured_nonce_field' );
		?>
		<label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer">
			<input type="checkbox" name="pf_featured" value="1" <?php checked( $featured, '1' ); ?> style="width:18px;height:18px">
			<span>Hiển thị ở Hero Section trang chủ</span>
		</label>
		<p style="color:#888;font-size:12px;margin:8px 0 0">
			Chọn tối đa 3 bài. Bài đầu tiên = khung lớn, 2 bài tiếp = khung nhỏ bên phải.
		</p>
		<?php
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function save_metabox( $post_id ) {
		if ( ! isset( $_POST['pf_featured_nonce_field'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pf_featured_nonce_field'] ) ), 'pf_featured_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST['pf_featured'] ) ? '1' : '0';
		update_post_meta( $post_id, '_pf_featured', $value );
	}

	/**
	 * @param array $columns List columns.
	 * @return array
	 */
	public static function add_list_column( $columns ) {
		$columns['pf_featured'] = '⭐ Nổi Bật';
		return $columns;
	}

	/**
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_list_column( $column, $post_id ) {
		if ( 'pf_featured' !== $column ) {
			return;
		}
		$featured = get_post_meta( $post_id, '_pf_featured', true );
		echo '1' === $featured ? '⭐' : '—';
	}

	/**
	 * Clear all featured flags.
	 */
	public static function clear_all_featured() {
		$old = get_posts(
			array(
				'post_type'   => 'pet_news',
				'meta_key'    => '_pf_featured',
				'meta_value'  => '1',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $old as $id ) {
			update_post_meta( (int) $id, '_pf_featured', '0' );
		}
	}

	/**
	 * @param int[] $post_ids Post IDs to feature.
	 */
	public static function set_featured( $post_ids ) {
		self::clear_all_featured();
		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id > 0 && 'pet_news' === get_post_type( $post_id ) ) {
				update_post_meta( $post_id, '_pf_featured', '1' );
			}
		}
	}
}
