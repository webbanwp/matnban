<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Html_Walker {
	const PROTECTED_TAGS = 'code|pre|script|style|textarea|kbd|samp|var|svg|math';

	protected $normalizer;

	public function __construct( MATNBAN_Normalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	public function process( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! MATNBAN_Normalizer::has_persian( $html ) && ! preg_match( '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $html ) ) {
			return $html;
		}

		$pattern = '~'
			. '(<!--.*?-->)'
			. '|(<(' . self::PROTECTED_TAGS . ')\b[^>]*>.*?</\3\s*>)'
			/* تگ معمولی: اول با آگاهی از کوتیشن (تا > داخل ویژگی نگذارد)، سپس حالت سنتی <[^>]+> — اصلاح ۱.۰.۲ */
			. '|(<(?:[^>"\']+|"[^"]*"|\'[^\']*\')*>|<[^>]+>)'
			. '|(\[\[.*?\]\])'
			. '|(\[/?[A-Za-z_][\w-]*(?:\s[^\]]*)?\])'
			. '|(&#?[A-Za-z0-9]+;)'
			. '~isu';

		$out    = '';
		$offset = 0;
		$len    = strlen( $html );

		while ( $offset < $len && preg_match( $pattern, $html, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
			$match_start = $m[0][1];
			$match_text  = $m[0][0];

			if ( $match_start > $offset ) {
				$out .= $this->normalizer->normalize( substr( $html, $offset, $match_start - $offset ) );
			}

			$out   .= $match_text;
			$offset = $match_start + strlen( $match_text );
		}

		if ( $offset < $len ) {
			$out .= $this->normalizer->normalize( substr( $html, $offset ) );
		}

		return $out;
	}

	public static function extract( $html ) {
		$headings = 0;
		$images   = array();

		if ( preg_match_all( '~<h([2-4])\b[^>]*>(.*?)</h\1>~isu', $html, $hm ) ) {
			$headings = count( $hm[0] );
		}

		if ( preg_match_all( '~<img\b[^>]*>~iu', $html, $im ) ) {
			foreach ( $im[0] as $img ) {
				$alt = '';
				if ( preg_match( '~\balt\s*=\s*(["\'])(.*?)\1~isu', $img, $am ) ) {
					$alt = trim( $am[2] );
				}
				$images[] = array( 'alt' => $alt );
			}
		}

		$text = $html;
		if ( ! is_string( $text ) || '' === $text || ! preg_match( '//u', $text ) ) {
			$text = '';
		}
		$text = preg_replace( '~<!--.*?-->~su', ' ', $text );
		$text = preg_replace( '~<(' . self::PROTECTED_TAGS . ')\b[^>]*>.*?</\1\s*>~isu', ' ', $text );
		$text = preg_replace( '~\[/?[A-Za-z_][\w-]*(?:\s[^\]]*)?\]~u', ' ', $text );

		$text = preg_replace( '~</(p|div|li|h[1-6]|blockquote|tr|section|article)\s*>|<br\s*/?>~iu', "\n\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\u{00A0}", ' ', $text );

		$paragraphs = preg_split( '~\n\s*\n~u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$paragraphs = array_values( array_filter( array_map( 'trim', $paragraphs ), 'strlen' ) );

		return array(
			'text'       => trim( preg_replace( '~\s+~u', ' ', $text ) ),
			'headings'   => $headings,
			'paragraphs' => $paragraphs,
			'images'     => $images,
		);
	}
}
