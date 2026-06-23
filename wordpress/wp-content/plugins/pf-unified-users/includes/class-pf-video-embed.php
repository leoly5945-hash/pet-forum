<?php
defined( 'ABSPATH' ) || exit;

class PF_Video_Embed {

	public static function init(): void {
		add_filter( 'wpforo_post_content', [ __CLASS__, 'convert_links_to_embeds' ], 20 );
		add_filter( 'the_content', [ __CLASS__, 'convert_links_to_embeds' ], 20 );
		add_shortcode( 'pf_video', [ __CLASS__, 'shortcode_embed' ] );
		add_action( 'wp_ajax_pf_preview_video', [ __CLASS__, 'ajax_preview' ] );
		add_action( 'wp_ajax_nopriv_pf_preview_video', [ __CLASS__, 'ajax_preview' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	public static function enqueue(): void {
		if ( ! self::should_load_assets() ) {
			return;
		}

		wp_enqueue_style( 'pf-video-embed', PFU_URL . 'assets/css/pf-video-embed.css', [], PFU_VERSION );
		wp_enqueue_script( 'pf-video-embed', PFU_URL . 'assets/js/pf-video-embed.js', [ 'jquery' ], PFU_VERSION, true );
		wp_localize_script(
			'pf-video-embed',
			'pfVideo',
			[
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'pf_video' ),
				'allowedDomains' => PF_Constants::VIDEO_ALLOWED_DOMAINS,
				'strings'        => [
					'unsupported' => 'Link video không được hỗ trợ. Chỉ chấp nhận: YouTube, TikTok, Facebook, Vimeo.',
					'invalid'     => 'URL không hợp lệ.',
					'preview'     => 'Xem trước video',
				],
			]
		);
	}

	private static function should_load_assets(): bool {
		if ( is_page() || is_singular() ) {
			return true;
		}

		if ( function_exists( 'is_wpforo' ) && is_wpforo() ) {
			return true;
		}

		return (bool) apply_filters( 'pf_video_embed_load_assets', false );
	}

	public static function convert_links_to_embeds( string $content ): string {
		if ( empty( $content ) || false === strpos( $content, 'http' ) ) {
			return $content;
		}

		$domains = array_map( 'preg_quote', PF_Constants::VIDEO_ALLOWED_DOMAINS );
		$pattern = '/^(https?:\/\/(?:www\.)?(?:' . implode( '|', $domains ) . ')[^\s<>"]*)\s*$/m';

		return (string) preg_replace_callback(
			$pattern,
			static function ( array $matches ) {
				$url  = trim( $matches[1] );
				$html = self::get_embed_html( $url );
				return $html ?: $matches[0];
			},
			$content
		);
	}

	public static function get_embed_html( string $url ): ?string {
		$url = esc_url_raw( trim( $url ) );
		if ( ! $url ) {
			return null;
		}

		$yt_id = self::parse_youtube_id( $url );
		if ( $yt_id ) {
			return self::render_embed(
				"https://www.youtube-nocookie.com/embed/{$yt_id}?rel=0&modestbranding=1",
				'youtube',
				"https://img.youtube.com/vi/{$yt_id}/hqdefault.jpg",
				$url
			);
		}

		if ( false !== strpos( $url, 'tiktok.com' ) ) {
			$oembed = self::fetch_oembed( 'https://www.tiktok.com/oembed?url=' . rawurlencode( $url ) );
			if ( $oembed && ! empty( $oembed['html'] ) ) {
				return '<div class="pf-video-wrap pf-video-tiktok">' . wp_kses_post( $oembed['html'] ) . '</div>';
			}
		}

		if ( false !== strpos( $url, 'facebook.com' ) || false !== strpos( $url, 'fb.watch' ) ) {
			$encoded = rawurlencode( $url );
			return self::render_embed(
				"https://www.facebook.com/plugins/video.php?href={$encoded}&show_text=false&width=640",
				'facebook',
				null,
				$url
			);
		}

		if ( preg_match( '/vimeo\.com\/(\d+)/', $url, $m ) ) {
			return self::render_embed(
				'https://player.vimeo.com/video/' . $m[1] . '?dnt=1',
				'vimeo',
				null,
				$url
			);
		}

		return null;
	}

	private static function render_embed( string $embed_url, string $platform, ?string $thumb_url, string $orig_url ): string {
		$labels = [
			'youtube'  => 'YouTube',
			'facebook' => 'Facebook',
			'vimeo'    => 'Vimeo',
		];
		$label = $labels[ $platform ] ?? ucfirst( $platform );

		$thumb_html = $thumb_url
			? "<img src='" . esc_url( $thumb_url ) . "' alt='Video thumbnail' loading='lazy' class='pf-video-thumb'>"
			: "<div class='pf-video-placeholder'>▶ {$label}</div>";

		return "
<div class='pf-video-wrap pf-video-{$platform}'
     data-embed='" . esc_attr( $embed_url ) . "'
     data-platform='" . esc_attr( $platform ) . "'>
    <div class='pf-video-preview' onclick='pfPlayVideo(this)'>
        {$thumb_html}
        <div class='pf-video-play-btn'>▶</div>
        <div class='pf-video-badge'>{$label}</div>
    </div>
    <a href='" . esc_url( $orig_url ) . "' class='pf-video-fallback'
       target='_blank' rel='noopener noreferrer'>
        Xem video trên {$label} →
    </a>
</div>";
	}

	private static function parse_youtube_id( string $url ): ?string {
		$patterns = [
			'/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
			'/youtu\.be\/([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
			'/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
		];
		foreach ( $patterns as $p ) {
			if ( preg_match( $p, $url, $m ) ) {
				return $m[1];
			}
		}

		return null;
	}

	private static function fetch_oembed( string $api_url ): ?array {
		$resp = wp_remote_get( $api_url, [ 'timeout' => 5 ] );
		if ( is_wp_error( $resp ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $resp ), true );
		return is_array( $data ) ? $data : null;
	}

	public static function shortcode_embed( array $atts ): string {
		$atts = shortcode_atts( [ 'url' => '' ], $atts, 'pf_video' );
		if ( empty( $atts['url'] ) ) {
			return '';
		}

		if ( ! self::is_allowed_video_url( $atts['url'] ) ) {
			return '<div class="pf-video-error">Domain video không được hỗ trợ.</div>';
		}

		return self::get_embed_html( $atts['url'] )
			?: '<div class="pf-video-error">Không thể nhúng video này.</div>';
	}

	public static function ajax_preview(): void {
		check_ajax_referer( 'pf_video', 'nonce' );

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( [ 'message' => 'URL trống.' ] );
		}

		if ( ! self::is_allowed_video_url( $url ) ) {
			wp_send_json_error( [ 'message' => 'Domain không được hỗ trợ.' ] );
		}

		$html = self::get_embed_html( $url );
		if ( $html ) {
			wp_send_json_success( [ 'html' => $html ] );
		}

		wp_send_json_error( [ 'message' => 'Không parse được URL này.' ] );
	}

	private static function is_allowed_video_url( string $url ): bool {
		$host = parse_url( $url, PHP_URL_HOST );
		$host = ltrim( (string) $host, 'www.' );

		foreach ( PF_Constants::VIDEO_ALLOWED_DOMAINS as $domain ) {
			if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return true;
			}
		}

		return false;
	}
}
