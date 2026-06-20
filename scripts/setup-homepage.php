<?php
/**
 * Setup hybrid homepage — activate plugin, assign template, configure front page.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/setup-homepage.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( 1 );
}

// Activate plugin.
$plugin = 'pf-news-aggregator/pf-news-aggregator.php';
if ( ! is_plugin_active( $plugin ) ) {
	activate_plugin( $plugin );
	WP_CLI::success( 'Activated pf-news-aggregator plugin.' );
} else {
	WP_CLI::log( 'Plugin already active.' );
}

// Assign PF Home template to page ID 50.
$home_id = 50;
update_post_meta( $home_id, '_wp_page_template', 'page-home.php' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
WP_CLI::success( 'Homepage set to page ID 50 with PF Home template.' );

// Sidebar layout for homepage.
update_post_meta( $home_id, 'site-sidebar-layout', 'no-sidebar' );
update_post_meta( $home_id, 'site-content-layout', 'plain-container' );
update_post_meta( $home_id, 'ast-site-content-layout', 'full-width-container' );

// Flush rewrites for pet_news CPT.
flush_rewrite_rules();
WP_CLI::success( 'Rewrite rules flushed.' );

// Optionally fetch RSS (may take a while).
WP_CLI::log( 'Fetching RSS feeds (Google Translate fallback)...' );
if ( class_exists( 'PF_RSS_Fetcher' ) ) {
	$fetcher = PF_News_Aggregator::instance()->fetcher;
	$created = $fetcher->run();
	WP_CLI::success( "RSS fetch complete — {$created} new posts created." );

	// Mark first 3 posts as featured for hero.
	$posts = get_posts(
		array(
			'post_type'      => 'pet_news',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);
	foreach ( $posts as $pid ) {
		update_post_meta( $pid, '_pf_featured', '1' );
	}
	if ( $posts ) {
		WP_CLI::success( 'Marked ' . count( $posts ) . ' posts as featured.' );
	}
} else {
	WP_CLI::warning( 'PF_RSS_Fetcher not loaded — run wp pf-news fetch manually.' );
}

$count = (int) wp_count_posts( 'pet_news' )->publish;
WP_CLI::success( "Total pet_news posts: {$count}" );
WP_CLI::log( 'Visit: http://localhost:8080/' );
