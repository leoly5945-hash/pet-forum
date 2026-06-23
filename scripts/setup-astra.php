<?php
/**
 * Configure Astra theme for Pet Forum + wpForo.
 *
 * Run:
 *   docker compose exec -T wpcli wp eval-file /scripts/setup-astra.php
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	exit( 1 );
}

$astra_settings = array_merge(
	(array) get_option( 'astra-settings', array() ),
	array(
		'site-layout'                     => 'full-width',
		'container-layout'                => 'plain-container',
		'single-content-layout'           => 'plain-container',
		'body-font-family'                => 'Inter, sans-serif',
		'body-font-size'                  => array(
			'desktop' => 16,
			'tablet'  => 16,
			'mobile'  => 16,
			'desktop-unit' => 'px',
			'tablet-unit'  => 'px',
			'mobile-unit'  => 'px',
		),
		'headings-font-family'            => 'Inter, sans-serif',
		'header-main-bg-color'            => '#ffffff',
		'header-main-bg-color-responsive' => '#ffffff',
		'site-title-color'                => '#1b4332',
		'header-color-site-title'         => '#1b4332',
		'site-sidebar-layout'             => 'no-sidebar',
		'single-sidebar-layout'           => 'no-sidebar',
		'archive-sidebar-layout'          => 'no-sidebar',
		'page-sidebar-layout'             => 'no-sidebar',
		'footer-bg-color'                 => '#1b4332',
		'footer-color'                    => '#d8f3dc',
		'footer-copyright-color'          => '#95d5b2',
		'button-bg-color'                 => '#40916c',
		'button-bg-h-color'               => '#2d6a4f',
		'button-color'                    => '#ffffff',
		'button-radius'                   => 6,
	)
);

update_option( 'astra-settings', $astra_settings );
WP_CLI::success( 'Astra settings updated.' );

update_option( 'blogname', 'Pet Forum' );
update_option( 'blogdescription', 'Cộng đồng thú cưng — Dogs, Cats, Birds & More' );
WP_CLI::success( 'Site title and tagline updated.' );

$pages = get_pages();
foreach ( $pages as $page ) {
	update_post_meta( $page->ID, 'site-sidebar-layout', 'no-sidebar' );
	update_post_meta( $page->ID, 'site-content-layout', 'plain-container' );
	update_post_meta( $page->ID, 'ast-site-content-layout', 'full-width-container' );
}
WP_CLI::success( 'No-sidebar layout applied to all pages.' );

foreach ( array( 'sidebar-1', 'sidebar-2', 'wpforo_2_sidebar', 'wpforo_3_sidebar' ) as $sidebar_id ) {
	WP_CLI::runcommand(
		"widget reset {$sidebar_id}",
		array(
			'return'     => false,
			'exit_error' => false,
		)
	);
}
WP_CLI::success( 'Theme and wpForo sidebars cleared (Archives/Categories widgets removed).' );

$custom_css = <<<'CSS'
/* wpForo + Astra integration */
.wpforo-wrap {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
}
.wpforo-wrap .wpft-topic-head,
.wpforo-wrap .wpf-forum-head {
	background: #2d6a4f !important;
	color: #ffffff !important;
	border-radius: 8px 8px 0 0;
}
.wpforo-wrap .wpf-forum-title a,
.wpforo-wrap .wpforo-forum-title a {
	color: #40916c !important;
	font-weight: 600;
}
.wpforo-wrap .wpf-forum-title a:hover {
	color: #2d6a4f !important;
}
.wpforo-wrap .wpforo-button,
.wpforo-wrap .wpf-btn {
	background: #40916c !important;
	border-color: #40916c !important;
	border-radius: 6px !important;
	color: #fff !important;
}
.wpforo-wrap .wpforo-button:hover,
.wpforo-wrap .wpf-btn:hover {
	background: #2d6a4f !important;
}
.wpforo-wrap .wpf-breadcrumb {
	background: #f0f7ee;
	padding: 8px 16px;
	border-radius: 6px;
	margin-bottom: 16px;
}
.wpforo-wrap .wpf-singleforum-wrap:hover {
	box-shadow: 0 4px 12px rgba(64, 145, 108, 0.15);
	transition: box-shadow 0.2s ease;
}
#secondary,
.widget-area {
	display: none !important;
}
.ast-separate-container #primary,
.ast-plain-container #primary {
	width: 100% !important;
}
CSS;

$result = wp_update_custom_css_post(
	$custom_css,
	array(
		'stylesheet' => 'astra',
	)
);

if ( is_wp_error( $result ) ) {
	WP_CLI::warning( 'Custom CSS: ' . $result->get_error_message() );
} else {
	WP_CLI::success( 'Custom CSS added for Astra.' );
}

$blog_page = get_page_by_path( 'blog', OBJECT, 'page' );
if ( $blog_page ) {
	update_post_meta( $blog_page->ID, '_wp_page_template', 'page-blog.php' );
	WP_CLI::success( 'Unified blog template assigned to /blog/ (page #' . $blog_page->ID . ').' );
}

WP_CLI::log( '' );
WP_CLI::log( 'Theme setup complete.' );
WP_CLI::log( 'http://localhost:8080/blog/' );
WP_CLI::log( 'http://localhost:8080/english-section/' );
WP_CLI::log( 'http://localhost:8080/muc-tieng-viet/' );
