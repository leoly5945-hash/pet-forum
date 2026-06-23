<?php
defined( 'ABSPATH' ) || exit;

class PF_Image_Processor {

	public static function init(): void {
		add_filter( 'wp_handle_upload', [ __CLASS__, 'process_after_upload' ], 10, 2 );
		add_filter( 'wp_generate_attachment_metadata', [ __CLASS__, 'strip_exif_metadata' ], 10, 2 );
	}

	public static function process_after_upload( array $upload, string $context ): array {
		if ( empty( $upload['type'] ) || 0 !== strpos( $upload['type'], 'image/' ) ) {
			return $upload;
		}

		if ( empty( $upload['file'] ) || ! file_exists( $upload['file'] ) ) {
			return $upload;
		}

		$file_path = $upload['file'];

		try {
			self::resize_image( $file_path, $upload['type'] );
			self::compress_image( $file_path, $upload['type'] );

			if ( 'image/png' === $upload['type'] ) {
				$webp_path = self::maybe_convert_to_webp( $file_path );
				if ( $webp_path ) {
					$upload['file'] = $webp_path;
					$upload['url']  = str_replace( basename( $file_path ), basename( $webp_path ), $upload['url'] );
					$upload['type'] = 'image/webp';
				}
			}
		} catch ( Exception $e ) {
			error_log( 'PF Image Processor error: ' . $e->getMessage() );
		}

		return $upload;
	}

	private static function resize_image( string $path, string $mime ): void {
		$size = @getimagesize( $path );
		if ( ! $size ) {
			return;
		}

		[ $width, $height ] = $size;
		$max_w               = PF_Constants::IMAGE_MAX_WIDTH;
		$max_h               = PF_Constants::IMAGE_MAX_HEIGHT;

		if ( $width <= $max_w && $height <= $max_h ) {
			return;
		}

		$ratio = min( $max_w / $width, $max_h / $height );
		$new_w = (int) round( $width * $ratio );
		$new_h = (int) round( $height * $ratio );

		$src = self::create_image_resource( $path, $mime );
		if ( ! $src ) {
			return;
		}

		$dst = imagecreatetruecolor( $new_w, $new_h );
		if ( ! $dst ) {
			imagedestroy( $src );
			return;
		}

		if ( in_array( $mime, [ 'image/png', 'image/gif' ], true ) ) {
			imagealphablending( $dst, false );
			imagesavealpha( $dst, true );
			$transparent = imagecolorallocatealpha( $dst, 255, 255, 255, 127 );
			imagefill( $dst, 0, 0, $transparent );
		}

		imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height );
		self::save_image( $dst, $path, $mime );

		imagedestroy( $src );
		imagedestroy( $dst );
	}

	private static function compress_image( string $path, string $mime ): void {
		$img = self::create_image_resource( $path, $mime );
		if ( ! $img ) {
			return;
		}
		self::save_image( $img, $path, $mime );
		imagedestroy( $img );
	}

	private static function maybe_convert_to_webp( string $png_path ): ?string {
		if ( ! function_exists( 'imagewebp' ) ) {
			return null;
		}

		$filesize = filesize( $png_path );
		if ( false === $filesize || $filesize < 100 * 1024 ) {
			return null;
		}

		$img = imagecreatefrompng( $png_path );
		if ( ! $img ) {
			return null;
		}

		imagealphablending( $img, false );
		imagesavealpha( $img, true );

		$webp_path = preg_replace( '/\.png$/i', '.webp', $png_path );
		$ok        = imagewebp( $img, $webp_path, PF_Constants::IMAGE_QUALITY_WEBP );
		imagedestroy( $img );

		if ( ! $ok || ! file_exists( $webp_path ) ) {
			return null;
		}

		if ( filesize( $webp_path ) >= $filesize ) {
			wp_delete_file( $webp_path );
			return null;
		}

		wp_delete_file( $png_path );
		return $webp_path;
	}

	public static function strip_exif_metadata( array $metadata, int $attachment_id ): array {
		$path = get_attached_file( $attachment_id );
		$mime = get_post_mime_type( $attachment_id );

		if ( ! $path || ! in_array( $mime, [ 'image/jpeg', 'image/jpg' ], true ) ) {
			return $metadata;
		}

		$img = imagecreatefromjpeg( $path );
		if ( $img ) {
			imagejpeg( $img, $path, PF_Constants::IMAGE_QUALITY_JPEG );
			imagedestroy( $img );
		}

		return $metadata;
	}

	private static function create_image_resource( string $path, string $mime ) {
		switch ( $mime ) {
			case 'image/jpeg':
			case 'image/jpg':
				return imagecreatefromjpeg( $path );
			case 'image/png':
				return imagecreatefrompng( $path );
			case 'image/gif':
				return imagecreatefromgif( $path );
			case 'image/webp':
				return function_exists( 'imagecreatefromwebp' ) ? imagecreatefromwebp( $path ) : false;
			default:
				return false;
		}
	}

	private static function save_image( $img, string $path, string $mime ): void {
		switch ( $mime ) {
			case 'image/jpeg':
			case 'image/jpg':
				imagejpeg( $img, $path, PF_Constants::IMAGE_QUALITY_JPEG );
				break;
			case 'image/png':
				imagepng( $img, $path, PF_Constants::IMAGE_QUALITY_PNG );
				break;
			case 'image/gif':
				imagegif( $img, $path );
				break;
			case 'image/webp':
				if ( function_exists( 'imagewebp' ) ) {
					imagewebp( $img, $path, PF_Constants::IMAGE_QUALITY_WEBP );
				}
				break;
		}
	}
}
