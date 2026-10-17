<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Normalizer {
	const ZWNJ = "\u{200C}";

	const AL = '\x{0621}-\x{063A}\x{0641}-\x{064A}\x{066E}\x{066F}\x{0671}-\x{06D3}\x{06D5}\x{06EE}\x{06EF}\x{06FA}-\x{06FC}\x{06FF}';

	const PD = '\x{06F0}-\x{06F9}';

	protected $rules;

	protected $stats = array();

	public static function default_rules() {
		return array(
			'arabic_chars' => true,
			'kashida'      => true,
			'zwnj_cleanup' => true,
			'direction_marks' => true,
			'prefix_mi'    => true,
			'prefix_bi'    => true,
			'suffix_ha'    => true,
			'suffix_tar'   => true,
			'suffix_verb'  => true,
			'ezafe_ye'     => true,
			'punctuation'  => true,
			'quotes'       => true,
			'arabic_digits'=> true,
			'latin_digits' => false,
			'percent'      => true,
			'spaces'       => true,
			'diacritics'   => false,
		);
	}

	public static function rule_labels() {
		/* \u{200E} (نشان چپ‌به‌راست) فقط داخل پرانتزهاست تا نمونه‌های انگلیسی در متن فارسی جابه‌جا دیده نشوند */
		return array(
			'arabic_chars' => 'تبدیل حروف عربی (ي، ك، ة) به فارسی',
			'kashida'      => 'حذف کشیده (ـــ)',
			'zwnj_cleanup' => 'پاک‌سازی نیم‌فاصله‌های اضافی',
			'direction_marks' => 'تبدیل نویسه‌های جهت‌دهی (RLM) به‌جای نیم‌فاصله به نیم‌فاصلهٔ استاندارد',
			'prefix_mi'    => 'نیم‌فاصلهٔ «می» و «نمی» (می رود ← می‌رود)',
			'prefix_bi'    => 'نیم‌فاصلهٔ پیشوند «بی» (بی نظیر ← بی‌نظیر)',
			'suffix_ha'    => 'نیم‌فاصلهٔ جمع «ها» (کتاب ها ← کتاب‌ها)',
			'suffix_tar'   => 'نیم‌فاصلهٔ «تر» و «ترین» (بزرگ تر ← بزرگ‌تر)',
			'suffix_verb'  => 'نیم‌فاصلهٔ شناسهٔ فعل (رفته ام ← رفته‌ام)',
			'ezafe_ye'     => 'نیم‌فاصلهٔ یای میانجی (خانه ی من ← خانه‌ی من)',
			'punctuation'  => "علائم نگارشی فارسی و فاصله‌گذاری (\u{200E}, ; ?\u{200E} ← ، ؛ ؟)",
			'quotes'       => 'گیومهٔ فارسی ("متن" ← «متن»)',
			'arabic_digits'=> 'تبدیل ارقام عربی (٤٥) به فارسی (۴۵)',
			'latin_digits' => 'تبدیل ارقام انگلیسی (45) به فارسی (۴۵)',
			'percent'      => "علامت درصد فارسی (۵۰ \u{200E}%\u{200E} ← ۵۰٪)",
			'spaces'       => 'حذف فاصله‌های تکراری و نامرئی',
			'diacritics'   => 'حذف اعراب (فتحه، کسره، تنوین)',
		);
	}

	public function __construct( array $rules = array() ) {
		$this->rules = array_merge( self::default_rules(), $rules );
	}

	public function get_stats() {
		$total = 0;
		foreach ( $this->stats as $n ) {
			$total += $n;
		}
		return array(
			'total' => $total,
			'rules' => $this->stats,
		);
	}

	public function reset_stats() {
		$this->stats = array();
	}

	protected function add_stat( $rule, $n ) {
		if ( $n > 0 ) {
			$this->stats[ $rule ] = ( isset( $this->stats[ $rule ] ) ? $this->stats[ $rule ] : 0 ) + $n;
		}
	}

	protected function on( $rule ) {
		return ! empty( $this->rules[ $rule ] );
	}

	public static function has_persian( $text ) {
		return (bool) preg_match( '/[' . self::AL . ']/u', $text );
	}

	public function normalize( $text ) {
		if ( '' === $text || ! is_string( $text ) ) {
			return $text;
		}

		if ( ! self::has_persian( $text ) && ! preg_match( '/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $text ) ) {
			return $text;
		}

		if ( ! preg_match( '//u', $text ) ) {
			return $text;
		}

		$text = $this->protect_urls( $text, $placeholders );

		/* نویسه‌های جهت‌دهی (RLM/LRM) و اتصالِ صفر (ZWJ) که به‌جای نیم‌فاصله میان حروف فارسی نشسته‌اند ← نیم‌فاصلهٔ استاندارد */
		if ( $this->on( 'direction_marks' ) ) {
			$n = preg_match_all( '/[' . self::AL . '\x{200C}][\x{200E}\x{200F}\x{200D}]+(?=[' . self::AL . '])/u', $text );
			if ( $n ) {
				$before = $text;
				$text   = preg_replace( '/(?<=[' . self::AL . '\x{200C}])[\x{200E}\x{200F}\x{200D}]+(?=[' . self::AL . '])/u', "\u{200C}", $text );
				$text   = preg_replace( '/\x{200C}{2,}/u', "\u{200C}", $text );
				if ( $before !== $text ) {
					$this->add_stat( 'direction_marks', $n );
				}
			}
		}

		$AL = self::AL;
		$PD = self::PD;
		$Z  = self::ZWNJ;

		if ( $this->on( 'arabic_chars' ) ) {
			$map = array(
				"\u{064A}" => 'ی',
				"\u{0643}" => 'ک',
				"\u{0649}" => 'ی',
				"\u{0629}" => 'ه',
				"\u{FEFB}" => 'لا',
				"\u{FEFC}" => 'لا',
				"\u{FDF2}" => 'الله',
			);
			$before = $text;
			$text   = strtr( $text, $map );
			if ( $before !== $text ) {
				$this->add_stat( 'arabic_chars', $this->count_diff_chars( $before, $text ) );
			}
		}

		if ( $this->on( 'arabic_digits' ) ) {
			$before = $text;
			$text   = strtr( $text, array(
				'٠' => '۰', '١' => '۱', '٢' => '۲', '٣' => '۳', '٤' => '۴',
				'٥' => '۵', '٦' => '۶', '٧' => '۷', '٨' => '۸', '٩' => '۹',
			) );
			if ( $before !== $text ) {
				$this->add_stat( 'arabic_digits', $this->count_diff_chars( $before, $text ) );
			}
		}

		if ( $this->on( 'latin_digits' ) ) {
			$text = preg_replace_callback(
				'/(?<![A-Za-z0-9])[0-9]+(?![A-Za-z0-9])/u',
				function ( $m ) {
					$this->add_stat( 'latin_digits', 1 );
					return strtr( $m[0], array(
						'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
						'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
					) );
				},
				$text
			);
		}

		if ( $this->on( 'kashida' ) ) {
			$text = preg_replace( '/\x{0640}+/u', '', $text, -1, $n );
			$this->add_stat( 'kashida', $n );
		}

		if ( $this->on( 'diacritics' ) ) {
			/* ءِ اضافهٔ فارسی (U+0654 در «کلمهٔ»، «افزونهٔ» و…) نگه داشته می‌شود — املای درست است، نه اعراب اضافه */
			$text = preg_replace( '/[\x{064B}-\x{0653}\x{0655}-\x{065F}\x{0670}]/u', '', $text, -1, $n );
			$this->add_stat( 'diacritics', $n );
		}

		if ( $this->on( 'zwnj_cleanup' ) ) {
			$text = preg_replace( '/\x{200C}{2,}/u', $Z, $text, -1, $n1 );

			$text = preg_replace( '/(?<![' . $AL . '])\x{200C}|\x{200C}(?![' . $AL . '])/u', '', $text, -1, $n2 );
			$this->add_stat( 'zwnj_cleanup', $n1 + $n2 );
		}

		$B  = '(?<![' . $AL . '\x{200C}])';
		$E  = '(?![' . $AL . '\x{200C}])';
		/* فاصله‌های یونیکد نامرئی (فاصلهٔ باریک، نیمه‌فاصلهٔ فرانسه، فاصلهٔ CJK و…) هم مثل فاصلهٔ معمولی و NBSP حساب می‌شوند */
		$SP = '[ \x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]+';

		if ( $this->on( 'prefix_mi' ) ) {
			/* «می» به‌عنوان اسم مستقل (ماهِ مِی، جامِ می، قدِ می…) به واژهٔ بعدی نچسبد */
			$MI_NOUN = '(?<!ماه )(?<!جام )(?<!قد )(?<!باده )(?<!پیمانه )';
			$text = preg_replace( '/' . $MI_NOUN . $B . '(ن?می)' . $SP . '(?=[' . $AL . ']{2,})/u', '$1' . $Z, $text, -1, $n );
			$this->add_stat( 'prefix_mi', $n );
		}

		if ( $this->on( 'prefix_bi' ) ) {
			$text = preg_replace( '/' . $B . '(بی)' . $SP . '(?=[' . $AL . ']{2,})/u', '$1' . $Z, $text, -1, $n );
			$this->add_stat( 'prefix_bi', $n );
		}

		if ( $this->on( 'suffix_ha' ) ) {
			$text = preg_replace( '/(?<=[' . $AL . '])' . $SP . '(ها|های|هایی|هایم|هایت|هایش|هایمان|هایتان|هایشان)' . $E . '/u', $Z . '$1', $text, -1, $n );
			$this->add_stat( 'suffix_ha', $n );
		}

		if ( $this->on( 'suffix_tar' ) ) {
			$text = preg_replace( '/(?<=[' . $AL . '])' . $SP . '(تر|ترین)' . $E . '/u', $Z . '$1', $text, -1, $n );
			$this->add_stat( 'suffix_tar', $n );
		}

		if ( $this->on( 'suffix_verb' ) ) {
			$text = preg_replace( '/(?<=ه)' . $SP . '(ام|ای|ایم|اید|اند)' . $E . '/u', $Z . '$1', $text, -1, $n );
			$this->add_stat( 'suffix_verb', $n );
		}

		if ( $this->on( 'ezafe_ye' ) ) {
			$text = preg_replace( '/(?<=ه)' . $SP . 'ی' . $E . '/u', $Z . 'ی', $text, -1, $n );
			$text = preg_replace( '/(?<=ها)' . $SP . 'ی' . $E . '/u', 'ی', $text, -1, $m );
			$this->add_stat( 'ezafe_ye', $n + $m );
		}

		if ( $this->on( 'punctuation' ) ) {
			$total = 0;

			$text = preg_replace( '/(?<=[' . $AL . $PD . '])[ \x{00A0}]*,(?![0-9' . $PD . '])/u', '،', $text, -1, $n ); $total += $n;
			$text = preg_replace( '/(?<=[' . $AL . $PD . '])[ \x{00A0}]*;/u', '؛', $text, -1, $n ); $total += $n;
			$text = preg_replace( '/(?<=[' . $AL . $PD . '])[ \x{00A0}]*\?/u', '؟', $text, -1, $n ); $total += $n;

			$text = preg_replace( '/(?<=[' . $AL . $PD . '])[ \x{00A0}]+(?=[،؛؟!:]|\.(?!\.))/u', '', $text, -1, $n ); $total += $n;

			$text = preg_replace( '/([،؛؟!])(?=[' . $AL . '])/u', '$1 ', $text, -1, $n ); $total += $n;

			$text = preg_replace( '/(?<!(?<![' . $AL . '\x{200C}])[' . $AL . '])([.:])(?=[' . $AL . '])/u', '$1 ', $text, -1, $n ); $total += $n;
			$this->add_stat( 'punctuation', $total );
		}

		if ( $this->on( 'quotes' ) ) {
			$text = preg_replace_callback(
				'/["“”]([ \x{00A0}]*)([^"“”\n]{1,300}?)([ \x{00A0}]*)["“”]/u',
				function ( $m ) use ( $AL ) {
					if ( preg_match( '/^[' . $AL . ']/u', $m[2] ) ) {
						$this->add_stat( 'quotes', 1 );
						return '«' . $m[2] . '»';
					}
					return $m[0];
				},
				$text
			);
		}

		if ( $this->on( 'percent' ) ) {
			$text = preg_replace( '/(?<=[' . $PD . '])[ \x{00A0}]*%/u', '٪', $text, -1, $n );
			$this->add_stat( 'percent', $n );
		}

		if ( $this->on( 'spaces' ) ) {
			/* فاصله‌های نامرئی یونیکد (مثل فاصلهٔ باریک U+2009 که در کپی از Word/PDF زیاد دیده می‌شود) به فاصلهٔ معمولی تبدیل می‌شوند */
			$text = preg_replace( '/[\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $text, -1, $n1 );
			$text = preg_replace( '/ {2,}/u', ' ', $text, -1, $n2 );
			$this->add_stat( 'spaces', $n1 + $n2 );
		}

		if ( $this->on( 'zwnj_cleanup' ) ) {
			$text = preg_replace( '/\x{200C}{2,}/u', $Z, $text );
			$text = preg_replace( '/(?<![' . $AL . '])\x{200C}|\x{200C}(?![' . $AL . '])/u', '', $text );
		}

		return $this->restore_urls( $text, $placeholders );
	}

	protected function protect_urls( $text, &$placeholders ) {
		$placeholders = array();
		$i            = 0;
		return preg_replace_callback(
			'~(https?://[^\s<>"«»]+|www\.[^\s<>"«»]+|[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})~u',
			function ( $m ) use ( &$placeholders, &$i ) {
				$key                  = "\u{E000}" . $i . "\u{E001}";
				$placeholders[ $key ] = $m[0];
				$i++;
				return $key;
			},
			$text
		);
	}

	protected function restore_urls( $text, $placeholders ) {
		if ( empty( $placeholders ) ) {
			return $text;
		}
		return strtr( $text, $placeholders );
	}

	protected function count_diff_chars( $a, $b ) {
		$a = preg_split( '//u', $a, -1, PREG_SPLIT_NO_EMPTY );
		$b = preg_split( '//u', $b, -1, PREG_SPLIT_NO_EMPTY );
		if ( count( $a ) !== count( $b ) ) {
			return 1;
		}
		$n = 0;
		foreach ( $a as $k => $ch ) {
			if ( $ch !== $b[ $k ] ) {
				$n++;
			}
		}
		return $n;
	}

	public function count_issues( $text ) {
		$this->reset_stats();
		$this->normalize( $text );
		$stats = $this->get_stats();
		$this->reset_stats();
		return $stats['total'];
	}
}
