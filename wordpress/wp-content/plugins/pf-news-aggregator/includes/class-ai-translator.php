<?php
/**
 * AI / Google Translate pipeline.
 *
 * @package PF_News_Aggregator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PF_AI_Translator {

	/**
	 * @param string $title   Original title.
	 * @param string $content Original content.
	 * @return array|null
	 */
	public function translate_and_rewrite( $title, $content ) {
		$api_key = defined( 'ANTHROPIC_API_KEY' ) ? ANTHROPIC_API_KEY : get_option( 'pf_anthropic_api_key', '' );

		if ( $api_key ) {
			$result = $this->claude_translate( $title, $content, $api_key );
			if ( $result ) {
				return $result;
			}
		}

		return $this->google_translate_fallback( $title, $content );
	}

	/**
	 * @param string $title   Original title.
	 * @param string $content Original content.
	 * @param string $api_key API key.
	 * @return array|null
	 */
	private function claude_translate( $title, $content, $api_key ) {
		$content_trimmed = mb_substr( wp_strip_all_tags( $content ), 0, 800 );

		$prompt = <<<PROMPT
Bạn là biên tập viên nội dung thú cưng. Từ bài gốc tiếng Anh dưới đây, hãy:
1. Viết lại tiêu đề tiếng Anh (ngắn gọn, hấp dẫn, dưới 70 ký tự)
2. Dịch tiêu đề sang tiếng Việt (tự nhiên, không cứng nhắc)
3. Tóm tắt nội dung tiếng Anh (2-3 câu, giữ điểm chính)
4. Dịch nội dung sang tiếng Việt (tự nhiên như người Việt viết)
5. Viết excerpt tiếng Việt (1 câu ngắn)

Trả về JSON với format:
{
  "title_en": "...",
  "title_vi": "...",
  "content_en": "...",
  "content_vi": "...",
  "excerpt_vi": "..."
}

BÀI GỐC:
Tiêu đề: {$title}
Nội dung: {$content_trimmed}
PROMPT;

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => 'claude-haiku-4-5-20251001',
						'max_tokens' => 1000,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['content'][0]['text'] ?? '';

		if ( preg_match( '/\{[\s\S]*\}/', $text, $matches ) ) {
			$data = json_decode( $matches[0], true );
			if ( is_array( $data ) && ! empty( $data['title_vi'] ) ) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * @param string $title   Original title.
	 * @param string $content Original content.
	 * @return array
	 */
	public function google_translate_fallback( $title, $content ) {
		$translate = function ( $text, $target ) {
			$url  = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='
				. rawurlencode( $target ) . '&dt=t&q=' . rawurlencode( mb_substr( $text, 0, 500 ) );
			$res  = wp_remote_get( $url, array( 'timeout' => 15 ) );
			if ( is_wp_error( $res ) ) {
				return $text;
			}
			$data       = json_decode( wp_remote_retrieve_body( $res ), true );
			$translated = '';
			foreach ( ( $data[0] ?? array() ) as $chunk ) {
				$translated .= $chunk[0] ?? '';
			}

			return $translated ?: $text;
		};

		$plain      = wp_strip_all_tags( $content );
		$title_vi   = $translate( $title, 'vi' );
		$content_vi = $translate( $plain, 'vi' );

		return array(
			'title_en'   => $title,
			'title_vi'   => $title_vi,
			'content_en' => mb_substr( $plain, 0, 600 ),
			'content_vi' => $content_vi,
			'excerpt_vi' => mb_substr( $content_vi, 0, 120 ) . '...',
		);
	}
}
