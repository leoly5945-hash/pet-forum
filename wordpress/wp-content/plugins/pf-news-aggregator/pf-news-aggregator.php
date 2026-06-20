<?php
/**
 * Plugin Name: PF News Aggregator
 * Description: RSS fetch + AI translate + auto-post pet news
 * Version: 1.0.0
 * Author: PetForum
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PF_NEWS_VERSION', '1.0.0' );
define( 'PF_NEWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PF_NEWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define(
	'PF_RSS_SOURCES',
	array(
		array(
			'url'  => 'https://www.thedodo.com/rss.xml',
			'name' => 'The Dodo',
			'cat'  => 'Dog News',
		),
		array(
			'url'  => 'https://www.akc.org/rss/news/',
			'name' => 'AKC',
			'cat'  => 'Dog News',
		),
		array(
			'url'  => 'https://cattime.com/feed',
			'name' => 'CatTime',
			'cat'  => 'Cat News',
		),
		array(
			'url'  => 'https://www.petmd.com/rss.xml',
			'name' => 'PetMD',
			'cat'  => 'Vet & Health',
		),
		array(
			'url'  => 'https://www.aspca.org/rss.xml',
			'name' => 'ASPCA',
			'cat'  => 'Rescue & Adoption',
		),
		array(
			'url'  => 'https://moderndogmagazine.com/feed',
			'name' => 'Modern Dog',
			'cat'  => 'Dog News',
		),
		array(
			'url'  => 'https://www.animalhealthfoundation.net/feed/',
			'name' => 'AHF',
			'cat'  => 'Vet & Health',
		),
		array(
			'url'  => 'https://pets.webmd.com/rss.xml',
			'name' => 'WebMD Pets',
			'cat'  => 'Vet & Health',
		),
	)
);

require_once PF_NEWS_PLUGIN_DIR . 'includes/class-ai-translator.php';
require_once PF_NEWS_PLUGIN_DIR . 'includes/class-post-creator.php';
require_once PF_NEWS_PLUGIN_DIR . 'includes/class-rss-fetcher.php';
require_once PF_NEWS_PLUGIN_DIR . 'includes/class-community-sidebar.php';
require_once PF_NEWS_PLUGIN_DIR . 'includes/class-featured-posts.php';
require_once PF_NEWS_PLUGIN_DIR . 'includes/class-pf-news-aggregator.php';

register_activation_hook(
	__FILE__,
	static function () {
		PF_News_Aggregator::instance()->activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		PF_News_Aggregator::instance()->deactivate();
	}
);

PF_News_Aggregator::instance();
