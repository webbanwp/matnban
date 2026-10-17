<?php
/**
 * English, readable filenames for Persian media titles.
 *
 * @package Matnban
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Filename {
	protected $max_words;

	protected $custom_dict;

	public function __construct( $max_words = 8, array $custom_dict = array() ) {
		$this->max_words   = max( 2, (int) $max_words );
		$this->custom_dict = $custom_dict;
	}

	public static function stop_words() {
		return array(
			'و', 'در', 'به', 'از', 'که', 'را', 'با', 'این', 'آن', 'برای', 'تا', 'یا', 'هم', 'یک',
			'است', 'بود', 'شد', 'می', 'های', 'ها', 'ای', 'اش', 'اند', 'هر', 'چه', 'چرا', 'چگونه',
			'اگر', 'ولی', 'اما', 'پس', 'نیز', 'همه', 'خود', 'روی', 'بر', 'بین', 'مثل', 'مانند',
			'درباره', 'دربارهٔ', 'راجع', 'طبق', 'طی', 'ضمن', 'جز', 'بدون', 'هنوز', 'دیگر', 'باید', 'نباید',

			'ی', 'ء', 'من', 'ما', 'تو', 'شما', 'او', 'آنها', 'آن‌ها', 'ایشان', 'اینها', 'این‌ها',

			'نمی', 'کنیم', 'کنید', 'کنم', 'کند', 'کنند', 'کردن', 'کرد', 'کرده', 'شود', 'شوید', 'شویم', 'شوند', 'شدن', 'شده',
			'دهیم', 'دهید', 'دهد', 'دهند', 'دادن', 'داده', 'باشد', 'باشید', 'باشیم', 'باشند', 'بودن', 'بوده',
			'هست', 'هستند', 'هستم', 'هستید', 'هستیم', 'نیست', 'نیستند', 'دارد', 'دارید', 'داریم', 'دارند', 'داشتن', 'داشته',
			'توان', 'توانید', 'توانیم', 'تواند', 'توانند', 'بتوانیم', 'بتوانید', 'خواهد', 'خواهید', 'خواهیم', 'خواهند', 'خواهم', 'خواهی',
			'گیرد', 'گیرند', 'گیریم', 'گیرید', 'گرفتن', 'گرفته', 'رود', 'روند', 'رفتن', 'رفته', 'آید', 'آیند', 'آمدن', 'آمده', 'گردد', 'گردند',
			'چیزی', 'کسی', 'کس', 'چند', 'چندین', 'برخی', 'بعضی', 'همین', 'همان', 'چنین', 'چنان', 'فقط', 'حتی', 'البته',
			'یعنی', 'زیرا', 'چون', 'وقتی', 'هنگام', 'هنگامی', 'سپس', 'آیا', 'مگر', 'کاش', 'شاید', 'حتماً', 'حتما', 'اصلاً', 'اصلا', 'کی',
			'the', 'a', 'an', 'of', 'in', 'on', 'for', 'to', 'and', 'or', 'is', 'are', 'with',
		);
	}

	public static function dictionary() {
		static $dict = null;
		if ( null === $dict ) {
			$file = __DIR__ . '/matnban-dictionary.php';
			$dict = file_exists( $file ) ? (array) include $file : array();
		}
		return $dict;
	}

	public static function normalize_key( $word ) {
		$word = str_replace( array( 'ي', 'ك', 'ۀ', 'ة', "\u{200C}" ), array( 'ی', 'ک', 'ه', 'ه', ' ' ), (string) $word );
		$word = preg_replace( '/[\x{064B}-\x{065F}\x{0670}]/u', '', $word );
		return trim( preg_replace( '/\s+/u', ' ', $word ) );
	}

	public static function lookup( $word, array $dict ) {
		$w = self::normalize_key( $word );
		if ( '' === $w ) {
			return null;
		}
		$hit = self::lookup_with_suffix( $w, $dict );
		if ( null !== $hit ) {
			return $hit;
		}

		if ( false !== mb_strpos( $w, ' ' ) ) {
			return self::lookup_with_suffix( str_replace( ' ', '', $w ), $dict );
		}

		$prefixes = array( 'بی' => 'no-', 'نا' => 'non-', 'غیر' => 'non-', 'هم' => 'co-', 'با' => 'with-', 'پر' => 'high-', 'کم' => 'low-' );
		foreach ( $prefixes as $pre => $en_pre ) {
			if ( mb_strlen( $w ) > mb_strlen( $pre ) + 2 && mb_substr( $w, 0, mb_strlen( $pre ) ) === $pre ) {
				$root = mb_substr( $w, mb_strlen( $pre ) );
				$hit  = self::lookup_with_suffix( $root, $dict );
				if ( null !== $hit ) {
					return $en_pre . $hit;
				}
			}
		}
		return null;
	}

	protected static function lookup_with_suffix( $w, array $dict ) {
		if ( isset( $dict[ $w ] ) ) {
			return $dict[ $w ];
		}

		$suffixes = array(
			'هایمان' => 2, 'هایتان' => 2, 'هایشان' => 2, 'هایی' => 2, 'های' => 2, 'ها' => 2,
			'ترین' => 2, 'تر' => 3, 'گان' => 2, 'یان' => 2, 'ان' => 3,
			'یی' => 2, 'ای' => 2, 'ی' => 2, 'ات' => 3, 'مان' => 3, 'تان' => 3, 'شان' => 3,
		);
		$len = mb_strlen( $w );
		foreach ( $suffixes as $suf => $min_root ) {
			$sl = mb_strlen( $suf );
			if ( $len - $sl < $min_root || mb_substr( $w, -$sl ) !== $suf ) {
				continue;
			}
			$root       = rtrim( mb_substr( $w, 0, $len - $sl ) );
			$candidates = array( $root );
			if ( 'گان' === $suf ) {
				$candidates[] = $root . 'ه';
			}
			if ( 'یان' === $suf ) {
				$candidates[] = $root . 'ی';
			}
			if ( 'ای' === $suf || 'ی' === $suf ) {
				$candidates[] = $root . 'ه';
			}
			foreach ( $candidates as $c ) {
				if ( isset( $dict[ $c ] ) ) {
					return $dict[ $c ];
				}
			}
		}
		return null;
	}

	protected static function char_map() {
		return array(
			'ا' => 'a', 'آ' => 'a', 'أ' => 'a', 'إ' => 'e', 'ب' => 'b', 'پ' => 'p', 'ت' => 't', 'ث' => 's',
			'ج' => 'j', 'چ' => 'ch', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'z', 'ر' => 'r', 'ز' => 'z',
			'ژ' => 'zh', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'z', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a',
			'غ' => 'gh', 'ف' => 'f', 'ق' => 'gh', 'ک' => 'k', 'ك' => 'k', 'گ' => 'g', 'ل' => 'l', 'م' => 'm',
			'ن' => 'n', 'و' => 'o', 'ه' => 'h', 'ة' => 'h', 'ی' => 'i', 'ي' => 'i', 'ى' => 'a', 'ئ' => 'i', 'ؤ' => 'o', 'ء' => '',
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		);
	}

	public static function transliterate_word( $word ) {
		$w = $word;

		$w = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{200C}]/u', '', $w );

		$w = preg_replace( '/^و/u', 'v', $w );

		$w = preg_replace( '/خو(?=[ا])/u', 'خ', $w );

		$w = preg_replace( '/^ی/u', 'y', $w );

		$w = preg_replace( '/(?<=[^\s])ه$/u', 'e', $w );

		$w = str_replace( 'او', 'oo', $w );
		$w = str_replace( 'وی', 'ooi', $w );

		$w = preg_replace( '/^ای/u', 'i', $w );

		$w = strtr( $w, self::char_map() );
		$w = preg_replace( '/[^a-z0-9]+/i', '', $w );
		return strtolower( $w );
	}

	protected function push( array &$out, &$count, $piece ) {
		$piece = trim( (string) $piece, '-' );
		if ( '' === $piece ) {
			return false;
		}
		$k = substr_count( $piece, '-' ) + 1;
		if ( $count > 0 && $count + $k > $this->max_words ) {
			return false;
		}
		$out[] = $piece;
		$count += $k;
		return true;
	}

	public function make( $title ) {
		$title = trim( (string) $title );
		if ( '' === $title ) {
			return '';
		}

		if ( ! preg_match( '/[\x{0600}-\x{06FF}]/u', $title ) ) {
			return '';
		}

		$custom = array();
		foreach ( $this->custom_dict as $fa => $en ) {
			$k = self::normalize_key( mb_strtolower( $fa ) );
			if ( '' !== $k && '' !== $en ) {
				$custom[ $k ] = $en;
			}
		}
		$dict = $custom + self::dictionary();

		$text = mb_strtolower( $title );
		$text = str_replace( array( 'ي', 'ك', 'ۀ', 'ة' ), array( 'ی', 'ک', 'ه', 'ه' ), $text );
		$text = preg_replace( '/[\x{064B}-\x{065F}\x{0670}]/u', '', $text );

		$text = preg_replace( '/[«»"\'“”‘’،؛؟!?,.;:()\[\]{}\-–—\/\\\\|+*]+/u', ' ', $text );
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

		$words = array();
		$glued = array();
		foreach ( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) as $tok ) {
			$parts = preg_split( '/\x{200C}+/u', $tok, -1, PREG_SPLIT_NO_EMPTY );
			$cnt   = count( $parts );
			foreach ( $parts as $k => $part ) {
				$words[] = $part;
				$glued[] = ( $k < $cnt - 1 );
			}
		}
		$stop  = array_flip( self::stop_words() );
		$out   = array();
		$count = 0;
		$n     = count( $words );
		$i     = 0;

		while ( $i < $n && $count < $this->max_words ) {
			$matched = false;
			for ( $len = min( 4, $n - $i ); $len >= 2; $len-- ) {
				$slice  = array_slice( $words, $i, $len );
				$phrase = implode( ' ', $slice );
				$hit    = self::lookup( $phrase, $dict );
				if ( null === $hit ) {
					$all_glued = true;
					for ( $g = $i; $g < $i + $len - 1; $g++ ) {
						if ( empty( $glued[ $g ] ) ) {
							$all_glued = false;
							break;
						}
					}
					if ( $all_glued ) {
						$hit = self::lookup( implode( '', $slice ), $dict );
					}
				}
				if ( null === $hit ) {
					$core = array_values( array_diff( $slice, array( 'ها', 'های', 'هایی', 'ی' ) ) );
					if ( count( $core ) >= 2 && count( $core ) < count( $slice ) ) {
						$phrase2 = implode( ' ', $core );
						$hit     = isset( $dict[ $phrase2 ] ) ? $dict[ $phrase2 ] : null;
					}
				}
				if ( null !== $hit && $this->push( $out, $count, $hit ) ) {
					$i      += $len;
					$matched = true;
					break;
				}
			}
			if ( $matched ) {
				continue;
			}

			$w = $words[ $i ];
			$i++;

			if ( preg_match( '/^[a-z0-9\-]+$/i', $w ) ) {
				if ( ! isset( $stop[ strtolower( $w ) ] ) ) {
					$this->push( $out, $count, strtolower( $w ) );
				}
				continue;
			}

			if ( isset( $stop[ $w ] ) ) {
				continue;
			}

			$hit = self::lookup( $w, $dict );
			if ( null !== $hit ) {
				$this->push( $out, $count, $hit );
				continue;
			}

			$t = self::transliterate_word( $w );
			if ( '' !== $t ) {
				$this->push( $out, $count, $t );
			}
		}

		$slug = implode( '-', $out );
		$slug = preg_replace( '/-{2,}/', '-', $slug );
		return trim( $slug, '-' );
	}

	/**
	 * نام فایل انگلیسی خوانا از عنوان فارسی (برای رسانه).
	 */
	public static function for_filename( $title ) {
		$gen = new self( 6, array() );
		return $gen->make( $title );
	}
}
