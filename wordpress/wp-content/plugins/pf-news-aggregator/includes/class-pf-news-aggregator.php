<?php
/**
 * Main plugin bootstrap.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_News_Aggregator {

	/** @var PF_News_Aggregator|null */
	private static $instance = null;

	/** @var PF_RSS_Fetcher */
	public $fetcher;

	/** @var PF_Community_Sidebar */
	public $sidebar;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->fetcher = new PF_RSS_Fetcher();
		$this->sidebar = new PF_Community_Sidebar();

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'maybe_seed_terms' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ) );
		add_action( 'pf_fetch_rss_event', array( $this->fetcher, 'run' ) );

		add_action( 'wp_ajax_pf_load_more_news', array( $this, 'ajax_load_more_news' ) );
		add_action( 'wp_ajax_nopriv_pf_load_more_news', array( $this, 'ajax_load_more_news' ) );
		add_action( 'wp_ajax_pf_load_sidebar', array( $this, 'ajax_load_sidebar' ) );
		add_action( 'wp_ajax_nopriv_pf_load_sidebar', array( $this, 'ajax_load_sidebar' ) );

		add_action( 'wpforo_add_topic', array( $this->sidebar, 'clear_cache' ) );
		add_action( 'wpforo_add_post', array( $this->sidebar, 'clear_cache' ) );

		if ( is_admin() ) {
			require_once PF_NEWS_PLUGIN_DIR . 'admin/settings-page.php';
			PF_News_Admin::init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register_cli_commands();
		}
	}

	public function register_post_type() {
		register_post_type(
			'pet_news',
			array(
				'labels'       => array(
					'name'          => 'Pet News',
					'singular_name' => 'News',
				),
				'public'       => true,
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'news' ),
				'menu_icon'    => 'dashicons-rss',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'show_in_rest' => true,
			)
		);
	}

	public function register_taxonomy() {
		register_taxonomy(
			'news_category',
			'pet_news',
			array(
				'labels'       => array( 'name' => 'News Categories' ),
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => 'news-category' ),
				'show_in_rest' => true,
			)
		);
	}

	public function maybe_seed_terms() {
		if ( get_option( 'pf_news_terms_seeded' ) ) {
			return;
		}

		$terms = array(
			'Dog News',
			'Cat News',
			'Exotic Pets',
			'Vet & Health',
			'Products & Trends',
			'Rescue & Adoption',
		);

		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, 'news_category' ) ) {
				wp_insert_term( $term, 'news_category' );
			}
		}

		update_option( 'pf_news_terms_seeded', 1 );
	}

	public function add_cron_schedules( $schedules ) {
		$schedules['every_6_hours'] = array(
			'interval' => 21600,
			'display'  => 'Every 6 Hours',
		);

		return $schedules;
	}

	public function activate() {
		$this->register_post_type();
		$this->register_taxonomy();
		$this->maybe_seed_terms();
		flush_rewrite_rules();

		if ( ! wp_next_scheduled( 'pf_fetch_rss_event' ) ) {
			wp_schedule_event( time() + 300, 'every_6_hours', 'pf_fetch_rss_event' );
		}
	}

	public function deactivate() {
		wp_clear_scheduled_hook( 'pf_fetch_rss_event' );
	}

	public function ajax_load_more_news() {
		check_ajax_referer( 'pf_load_more', 'nonce' );

		$page = max( 1, intval( $_POST['page'] ?? 1 ) );

		$query = new WP_Query(
			array(
				'post_type'      => 'pet_news',
				'posts_per_page' => 12,
				'paged'          => $page,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'partials/news', 'card' );
			}
		}
		wp_reset_postdata();

		wp_send_json_success(
			array(
				'html'     => ob_get_clean(),
				'has_more' => $page < (int) $query->max_num_pages,
			)
		);
	}

	public function ajax_load_sidebar() {
		check_ajax_referer( 'pf_load_more', 'nonce' );

		ob_start();
		get_template_part( 'partials/community', 'sidebar' );
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	private function register_cli_commands() {
		WP_CLI::add_command(
			'pf-news fetch',
			function () {
				WP_CLI::log( 'Fetching RSS feeds...' );
				$this->fetcher->run();
				update_option( 'pf_news_last_fetch', current_time( 'mysql' ) );
				WP_CLI::success( 'Done!' );
			}
		);

		WP_CLI::add_command(
			'pf-news count',
			function () {
				$count = (int) wp_count_posts( 'pet_news' )->publish;
				WP_CLI::success( "Total pet_news posts: {$count}" );
			}
		);
	}
}
