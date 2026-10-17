<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Media {
	private $original_names = array();

	public function __construct() {
		if ( Matnban::get( 'media_rename' ) ) {
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'rename' ) );
		}
		if ( Matnban::get( 'media_alt' ) ) {
			add_action( 'add_attachment', array( $this, 'set_alt' ) );
		}
	}

	public function rename( $file ) {
		if ( empty( $file['name'] ) ) {
			return $file;
		}
		$name = $file['name'];
		if ( ! preg_match( '/[\x{0600}-\x{06FF}]/u', $name ) ) {
			return $file;
		}
		$info = pathinfo( $name );
		$base = isset( $info['filename'] ) ? $info['filename'] : $name;
		$ext  = isset( $info['extension'] ) ? '.' . strtolower( $info['extension'] ) : '';

		$title = str_replace( array( '-', '_' ), ' ', $base );
		$slug  = MATNBAN_Filename::for_filename( $title );
		if ( '' === $slug ) {
			$slug = 'image-' . wp_generate_password( 6, false, false );
		}
		$new_name = $slug . $ext;

		$this->original_names[ $new_name ] = $base;
		set_transient( 'matnban_orig_' . md5( $new_name ), $base, 10 * MINUTE_IN_SECONDS );

		$file['name'] = $new_name;
		return $file;
	}

	public function set_alt( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}
		$existing_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( '' !== trim( (string) $existing_alt ) ) {
			return;
		}

		$file     = get_attached_file( $attachment_id );
		$filename = $file ? wp_basename( $file ) : '';
		$orig     = $filename ? get_transient( 'matnban_orig_' . md5( $filename ) ) : false;

		if ( ! $orig && $filename && preg_match( '/^(.+)-(\d+)(\.[a-z0-9]+)$/i', $filename, $m ) ) {
			$orig = get_transient( 'matnban_orig_' . md5( $m[1] . $m[3] ) );
			if ( $orig ) {
				$filename = $m[1] . $m[3];
			}
		}

		$post  = get_post( $attachment_id );
		$title = $orig ? $orig : ( $post ? $post->post_title : '' );

		$title = str_replace( array( '-', '_' ), ' ', $title );
		$title = preg_replace( '/\s+/u', ' ', trim( $title ) );

		$title = preg_replace( '/\b(scaled|copy|final|new)\b/i', '', $title );
		$title = trim( $title );

		if ( '' === $title ) {
			return;
		}
		$title = Matnban::normalizer()->normalize( $title );

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $title );

		if ( $post && MATNBAN_Normalizer::has_persian( $title ) && ! MATNBAN_Normalizer::has_persian( $post->post_title ) ) {
			wp_update_post( wp_slash( array(
				'ID'         => $attachment_id,
				'post_title' => $title,
			) ) );
		}
		if ( $filename ) {
			delete_transient( 'matnban_orig_' . md5( $filename ) );
		}
	}
}
