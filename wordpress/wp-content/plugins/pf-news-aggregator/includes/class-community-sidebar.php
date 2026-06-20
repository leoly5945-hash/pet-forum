<?php
/**
 * Cached wpForo recent discussions for homepage sidebar.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_Community_Sidebar {

	const CACHE_KEY = 'pf_recent_discussions';

	/**
	 * @param int $limit Max topics.
	 * @return array
	 */
	public function get_recent_discussions( $limit = 10 ) {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$results = $this->query_all_boards( $limit );
		set_transient( self::CACHE_KEY, $results, 5 * MINUTE_IN_SECONDS );

		return $results;
	}

	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * @param int $limit Max topics.
	 * @return array
	 */
	private function query_all_boards( $limit ) {
		global $wpdb;

		$boards = $wpdb->get_results(
			"SELECT boardid, slug, title FROM {$wpdb->prefix}wpforo_boards WHERE status = 1",
			ARRAY_A
		);

		if ( empty( $boards ) ) {
			return $this->query_default_tables( $limit );
		}

		$all = array();

		foreach ( $boards as $board ) {
			$boardid = (int) $board['boardid'];
			$prefix  = $wpdb->prefix . 'wpforo_' . $boardid . '_';
			$topics  = $wpdb->prefix . 'wpforo_' . $boardid . '_topics';
			$forums  = $wpdb->prefix . 'wpforo_' . $boardid . '_forums';

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $topics ) ) !== $topics ) {
				continue;
			}

			$sql = "
				SELECT
					t.topicid, t.title, t.slug, t.posts AS reply_count,
					t.modified AS last_posted, t.userid,
					u.display_name AS author_name,
					f.title AS forum_name, f.slug AS forum_slug
				FROM {$topics} t
				JOIN {$wpdb->users} u ON t.userid = u.ID
				JOIN {$forums} f ON t.forumid = f.forumid
				WHERE t.status = 0
				ORDER BY t.modified DESC
				LIMIT %d
			";

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A );
			foreach ( $rows as $row ) {
				$row['board_slug']  = $board['slug'];
				$row['board_title'] = $board['title'];
				$row['topic_url']   = $this->build_topic_url( $row );
				$all[]              = $row;
			}
		}

		usort(
			$all,
			function ( $a, $b ) {
				return (int) $b['last_posted'] <=> (int) $a['last_posted'];
			}
		);

		return array_slice( $all, 0, $limit );
	}

	/**
	 * Fallback for single-board installs.
	 *
	 * @param int $limit Max topics.
	 * @return array
	 */
	private function query_default_tables( $limit ) {
		global $wpdb;

		$prefix = $wpdb->prefix . 'wpforo_';

		$sql = "
			SELECT
				t.topicid, t.title, t.slug, t.posts AS reply_count,
				t.modified AS last_posted, t.userid,
				u.display_name AS author_name,
				f.title AS forum_name, f.slug AS forum_slug
			FROM {$prefix}topics t
			JOIN {$wpdb->users} u ON t.userid = u.ID
			JOIN {$prefix}forums f ON t.forumid = f.forumid
			WHERE t.status = 0
			ORDER BY t.modified DESC
			LIMIT %d
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A );
		foreach ( $rows as &$row ) {
			$row['board_slug'] = '';
			$row['topic_url']  = home_url( '/community/' . $row['forum_slug'] . '/' . $row['slug'] . '/' );
		}

		return $rows;
	}

	/**
	 * @param array $row Topic row.
	 * @return string
	 */
	public function build_topic_url( $row ) {
		if ( function_exists( 'WPF' ) && ! empty( $row['topicid'] ) ) {
			$boardid = 0;
			global $wpdb;
			$boards = $wpdb->get_results(
				"SELECT boardid, slug FROM {$wpdb->prefix}wpforo_boards WHERE status = 1",
				ARRAY_A
			);
			foreach ( $boards as $board ) {
				if ( ( $row['board_slug'] ?? '' ) === $board['slug'] ) {
					$boardid = (int) $board['boardid'];
					break;
				}
			}
			if ( $boardid ) {
				WPF()->change_board( $boardid );
				$topic = WPF()->topic->get_topic( (int) $row['topicid'], false );
				$forum = WPF()->forum->get_forum( $topic['forumid'] ?? 0 );
				if ( $topic ) {
					return WPF()->topic->get_url( $topic, $forum );
				}
			}
		}

		$board_slug = $row['board_slug'] ?? '';
		$path       = trim( $board_slug . '/' . ( $row['forum_slug'] ?? '' ) . '/' . ( $row['slug'] ?? '' ), '/' );

		return home_url( '/' . $path . '/' );
	}

	/**
	 * @return array{topics:int,members:int}
	 */
	public function get_stats() {
		global $wpdb;

		$members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
		$topics  = 0;

		$boards = $wpdb->get_results(
			"SELECT boardid FROM {$wpdb->prefix}wpforo_boards WHERE status = 1",
			ARRAY_A
		);

		if ( ! empty( $boards ) ) {
			foreach ( $boards as $board ) {
				$table = $wpdb->prefix . 'wpforo_' . (int) $board['boardid'] . '_topics';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
					$topics += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 0" );
				}
			}
		} else {
			$topics = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpforo_topics WHERE status = 0" );
		}

		return array(
			'topics'  => $topics,
			'members' => $members,
		);
	}
}
