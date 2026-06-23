<?php
defined( 'ABSPATH' ) || exit;

class PF_Ads {

	const ADSENSE_CLIENT  = 'ca-pub-XXXXXXXXXX';
	const SLOT_HEADER     = 'XXXXXXXXXX';
	const SLOT_SIDEBAR    = 'XXXXXXXXXX';
	const SLOT_IN_CONTENT = 'XXXXXXXXXX';
	const SLOT_MOBILE     = 'XXXXXXXXXX';

	public static function init(): void {
		add_action( 'wp_head', [ __CLASS__, 'inject_adsense_script' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'astra_header_after', [ __CLASS__, 'maybe_header_ad' ] );
		add_filter( 'the_content', [ __CLASS__, 'inject_in_content_ad' ] );
		add_action( 'wpforo_post_content_footer', [ __CLASS__, 'inject_forum_post_affiliate' ], 10, 4 );
		add_action( 'widgets_init', [ __CLASS__, 'register_sidebar_widget_area' ] );
		add_action( 'widgets_init', [ __CLASS__, 'register_widgets' ] );
		add_action( 'wp_footer', [ __CLASS__, 'maybe_sticky_mobile_ad' ] );
	}

	public static function get_client_id(): string {
		$saved = get_option( 'pf_adsense_client', '' );

		return $saved ?: self::ADSENSE_CLIENT;
	}

	public static function get_slot( string $key ): string {
		$saved = get_option( "pf_adsense_slot_{$key}", '' );
		$const = [
			'header'     => self::SLOT_HEADER,
			'sidebar'    => self::SLOT_SIDEBAR,
			'in_content' => self::SLOT_IN_CONTENT,
			'mobile'     => self::SLOT_MOBILE,
		];

		return $saved ?: ( $const[ $key ] ?? '' );
	}

	public static function inject_adsense_script(): void {
		if ( ! self::ads_enabled() || ! self::has_valid_client() ) {
			return;
		}
		$client = esc_attr( self::get_client_id() );
		?>
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo $client; ?>"
			crossorigin="anonymous"></script>
		<?php
	}

	public static function enqueue_assets(): void {
		if ( ! self::ads_enabled() ) {
			return;
		}

		wp_enqueue_style( 'pf-ads', PFU_URL . 'assets/css/pf-ads.css', [], PFU_VERSION );

		if ( self::has_valid_client() ) {
			wp_enqueue_script( 'pf-ads', PFU_URL . 'assets/js/pf-ads.js', [], PFU_VERSION, true );
		}
	}

	public static function maybe_header_ad(): void {
		if ( ! self::slot_enabled( 'header' ) || ! self::has_valid_client() ) {
			return;
		}

		echo self::render_adsense_unit( self::get_slot( 'header' ), '728x90', 'horizontal', 'pf-ad-header' );
	}

	public static function inject_in_content_ad( string $content ): string {
		if ( ! self::slot_enabled( 'in_content' ) || ! self::has_valid_client() ) {
			return $content;
		}

		if ( ! is_singular( 'post' ) && ! is_singular( 'pet_news' ) ) {
			return $content;
		}

		if ( false === strpos( $content, '</p>' ) ) {
			return $content;
		}

		$ad_html = self::render_adsense_unit(
			self::get_slot( 'in_content' ),
			'336x280',
			'rectangle',
			'pf-ad-in-content'
		);

		$parts = preg_split( '/<\/p>/i', $content, 3 );
		if ( count( $parts ) < 3 ) {
			return $content;
		}

		return $parts[0] . '</p>' . $parts[1] . '</p>' . $ad_html . '<p>' . $parts[2];
	}

	public static function register_sidebar_widget_area(): void {
		register_sidebar(
			[
				'name'          => '🐾 Pet Forum — Sidebar Ads',
				'id'            => 'pf-sidebar-ads',
				'description'   => 'Quảng cáo sidebar 300x250 (AdSense hoặc Affiliate)',
				'before_widget' => '<div class="pf-sidebar-ad-wrap">',
				'after_widget'  => '</div>',
				'before_title'  => '',
				'after_title'   => '',
			]
		);
	}

	public static function register_widgets(): void {
		register_widget( 'PF_Ads_Sidebar_Widget' );
	}

	public static function render_sidebar_ad(): string {
		if ( ! self::slot_enabled( 'sidebar' ) || ! self::has_valid_client() ) {
			return '';
		}

		return self::render_adsense_unit(
			self::get_slot( 'sidebar' ),
			'300x250',
			'rectangle',
			'pf-ad-sidebar'
		);
	}

	public static function render_sidebar_affiliate(): string {
		if ( ! self::slot_enabled( 'affiliate' ) ) {
			return '';
		}

		$forum_id = self::get_current_forum_id();
		$section  = self::detect_section( $forum_id );
		$products = self::get_affiliate_products( $section );

		if ( empty( $products ) ) {
			return '';
		}

		return self::render_affiliate_block( array_slice( $products, 0, 2 ), $section, 'pf-affiliate-sidebar' );
	}

	public static function render_sidebar_block(): string {
		ob_start();

		if ( is_active_sidebar( 'pf-sidebar-ads' ) ) {
			dynamic_sidebar( 'pf-sidebar-ads' );
		} else {
			echo self::render_sidebar_ad();
		}

		echo self::render_sidebar_affiliate();

		return ob_get_clean();
	}

	public static function inject_forum_post_affiliate( $post, $topic = null, $forum = null, $layout = null ): void {
		unset( $topic, $forum, $layout );

		if ( ! self::slot_enabled( 'affiliate' ) || empty( $post['is_first_post'] ) ) {
			return;
		}

		$forum_id = (int) ( $post['forumid'] ?? 0 );
		$section  = self::detect_section( $forum_id );
		$products = self::get_affiliate_products( $section );

		if ( empty( $products ) ) {
			return;
		}

		echo self::render_affiliate_block( $products, $section );
	}

	public static function maybe_sticky_mobile_ad(): void {
		if ( ! self::slot_enabled( 'mobile_sticky' ) || ! self::has_valid_client() ) {
			return;
		}

		$client = esc_attr( self::get_client_id() );
		$slot   = esc_attr( self::get_slot( 'mobile' ) ?: self::get_slot( 'header' ) );
		?>
		<div class="pf-ad-mobile-sticky" id="pf-mobile-sticky-ad">
			<button type="button" class="pf-ad-close" onclick="this.parentElement.remove()" aria-label="Đóng">✕</button>
			<ins class="adsbygoogle pf-ad-lazy-unit"
				style="display:inline-block;width:320px;height:50px"
				data-ad-client="<?php echo $client; ?>"
				data-ad-slot="<?php echo $slot; ?>"
				data-ad-format="horizontal"></ins>
		</div>
		<?php
	}

	private static function render_adsense_unit(
		string $slot,
		string $size,
		string $format,
		string $class
	): string {
		if ( ! $slot || false !== strpos( $slot, 'XXXX' ) ) {
			return '';
		}

		[ $w, $h ] = array_pad( explode( 'x', $size ), 2, '90' );
		$client    = esc_attr( self::get_client_id() );

		return sprintf(
			'<div class="pf-ad-wrap %s">
				<ins class="adsbygoogle pf-ad-lazy-unit"
					style="display:block;min-height:%spx"
					data-ad-client="%s"
					data-ad-slot="%s"
					data-ad-format="%s"
					data-full-width-responsive="true"></ins>
			</div>',
			esc_attr( $class ),
			(int) $h,
			$client,
			esc_attr( $slot ),
			esc_attr( $format )
		);
	}

	private static function render_affiliate_block( array $products, string $section, string $extra_class = '' ): string {
		$section_names = [
			'dog'     => '🐕 Sản phẩm cho Chó',
			'cat'     => '🐈 Sản phẩm cho Mèo',
			'bird'    => '🐦 Sản phẩm cho Chim',
			'market'  => '🛒 Sản phẩm nổi bật',
			'default' => '🐾 Sản phẩm gợi ý',
		];

		ob_start();
		?>
		<div class="pf-affiliate-block <?php echo esc_attr( $extra_class ); ?>">
			<div class="pf-affiliate-label"><?php echo esc_html( $section_names[ $section ] ?? $section_names['default'] ); ?></div>
			<div class="pf-affiliate-products">
			<?php foreach ( $products as $p ) : ?>
				<a href="<?php echo esc_url( $p['url'] ); ?>"
					target="_blank" rel="nofollow sponsored noopener"
					class="pf-affiliate-card">
					<img src="<?php echo esc_url( $p['img'] ); ?>"
						alt="<?php echo esc_attr( $p['name'] ); ?>"
						loading="lazy" width="80" height="80">
					<div class="pf-affiliate-info">
						<span class="pf-affiliate-name"><?php echo esc_html( $p['name'] ); ?></span>
						<span class="pf-affiliate-price"><?php echo esc_html( $p['price'] ); ?></span>
						<span class="pf-affiliate-cta">Xem trên Shopee →</span>
					</div>
				</a>
			<?php endforeach; ?>
			</div>
			<p class="pf-affiliate-disclaimer">* Liên kết có thể là affiliate. Giá &amp; ưu đãi có thể thay đổi.</p>
		</div>
		<?php

		return ob_get_clean();
	}

	private static function get_affiliate_products( string $section ): array {
		$custom = get_option( 'pf_ads_affiliate_products', [] );
		if ( ! empty( $custom[ $section ] ) && is_array( $custom[ $section ] ) ) {
			return $custom[ $section ];
		}

		$db = [
			'dog' => [
				[
					'name'  => 'Pedigree Thức Ăn Cho Chó Trưởng Thành',
					'price' => '145,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Dog+Food',
					'url'   => 'https://shopee.vn',
				],
				[
					'name'  => 'Vòng cổ chống ve chó Seresto',
					'price' => '520,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Collar',
					'url'   => 'https://shopee.vn',
				],
				[
					'name'  => 'Royal Canin Mini Adult',
					'price' => '230,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Royal+Canin',
					'url'   => 'https://shopee.vn',
				],
			],
			'cat' => [
				[
					'name'  => 'Whiskas Thức Ăn Mèo Trưởng Thành',
					'price' => '89,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Whiskas',
					'url'   => 'https://shopee.vn',
				],
				[
					'name'  => 'Cát vệ sinh OkiCat than hoạt tính',
					'price' => '110,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Litter',
					'url'   => 'https://shopee.vn',
				],
				[
					'name'  => 'Đồ chơi cần câu mèo tự động',
					'price' => '75,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Cat+Toy',
					'url'   => 'https://shopee.vn',
				],
			],
			'bird' => [
				[
					'name'  => 'Hạt hướng dương Versele-Laga cho Vẹt',
					'price' => '95,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Bird+Food',
					'url'   => 'https://shopee.vn',
				],
			],
			'default' => [
				[
					'name'  => 'Vitamin tổng hợp cho thú cưng',
					'price' => '180,000₫',
					'img'   => 'https://via.placeholder.com/80x80?text=Vitamins',
					'url'   => 'https://shopee.vn',
				],
			],
		];

		return $db[ $section ] ?? $db['default'];
	}

	private static function detect_section( int $forum_id ): string {
		static $map = null;

		if ( null === $map ) {
			$map = get_option( 'pf_ads_forum_section_map', [] );

			if ( empty( $map ) ) {
				$role_sections = [
					PF_Constants::ROLE_DOG_MOD    => 'dog',
					PF_Constants::ROLE_CAT_MOD    => 'cat',
					PF_Constants::ROLE_BIRD_MOD   => 'bird',
					PF_Constants::ROLE_MARKET_MOD => 'market',
				];

				foreach ( $role_sections as $role => $section ) {
					foreach ( PF_Roles_V2::get_allowed_forum_ids( $role ) as $fid ) {
						$map[ (int) $fid ] = $section;
					}
				}

				update_option( 'pf_ads_forum_section_map', $map, false );
			}
		}

		return $map[ $forum_id ] ?? 'default';
	}

	private static function get_current_forum_id(): int {
		if ( function_exists( 'WPF' ) ) {
			$forum_id = (int) wpfval( WPF()->current_object, 'forum', 'forumid' );
			if ( $forum_id ) {
				return $forum_id;
			}

			$topic_id = (int) wpfval( WPF()->current_object, 'topic', 'topicid' );
			if ( $topic_id && isset( WPF()->current_object['topic']['forumid'] ) ) {
				return (int) WPF()->current_object['topic']['forumid'];
			}
		}

		return 0;
	}

	public static function ads_enabled(): bool {
		return (bool) get_option( 'pf_ads_enabled', false );
	}

	private static function slot_enabled( string $slot ): bool {
		if ( ! self::ads_enabled() ) {
			return false;
		}

		return (bool) get_option( "pf_ads_slot_{$slot}", false );
	}

	private static function has_valid_client(): bool {
		$client = self::get_client_id();

		return $client && false === strpos( $client, 'XXXX' );
	}
}

class PF_Ads_Sidebar_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'pf_ads_sidebar',
			'🐾 PF Sidebar Ad',
			[ 'description' => 'AdSense 300×250 cho sidebar Pet Forum' ]
		);
	}

	public function widget( $args, $instance ): void {
		unset( $instance );

		if ( ! class_exists( 'PF_Ads' ) ) {
			return;
		}

		$html = PF_Ads::render_sidebar_ad();
		if ( ! $html ) {
			return;
		}

		echo $args['before_widget'] . $html . $args['after_widget'];
	}
}
