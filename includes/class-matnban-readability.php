<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Readability {
	const MAX_SENTENCE_WORDS = 25;

	const MAX_PARAGRAPH_WORDS = 150;

	const WORDS_PER_HEADING = 300;

	const WPM = 200;

	const MIN_WORDS = 50;

	protected $normalizer;

	public function __construct( ?MATNBAN_Normalizer $normalizer = null ) {
		$this->normalizer = $normalizer ? $normalizer : new MATNBAN_Normalizer();
	}

	public static function transition_words() {
		return array(
			'بنابراین', 'در نتیجه', 'همچنین', 'علاوه بر این', 'علاوه‌بر این', 'از سوی دیگر', 'از طرف دیگر', 'با این حال',
			'با این‌حال', 'اما', 'ولی', 'زیرا', 'چون', 'چراکه', 'چرا که', 'به همین دلیل', 'به عبارت دیگر', 'به‌عبارت دیگر',
			'برای مثال', 'به عنوان مثال', 'به‌عنوان مثال', 'مثلاً', 'مثلا', 'در واقع', 'درواقع', 'در حقیقت', 'به طور کلی',
			'به‌طور کلی', 'در نهایت', 'سرانجام', 'در ادامه', 'پیش از این', 'پس از آن', 'سپس', 'در ابتدا', 'نخست', 'اول',
			'دوم', 'سوم', 'در مقابل', 'برعکس', 'به‌ویژه', 'به ویژه', 'خصوصاً', 'مخصوصاً', 'البته', 'اگرچه', 'هرچند',
			'با وجود این', 'در عین حال', 'به این ترتیب', 'بدین ترتیب', 'به طور خلاصه', 'خلاصه اینکه', 'یعنی', 'به بیان دیگر',
			'از این رو', 'ازاین‌رو', 'لذا', 'در صورتی که', 'درصورتی‌که', 'به شرطی که', 'همان‌طور که', 'همانطور که', 'همچنان‌که',
		);
	}

	public static function passive_markers() {
		return array( 'شد', 'شده', 'شده است', 'شده‌است', 'شده بود', 'شده‌بود', 'می‌شود', 'می شود', 'شدند', 'شده‌اند', 'شده اند', 'خواهد شد', 'گردید', 'گردیده', 'می‌گردد' );
	}

	public static function sentences( $text ) {
		/* ؛ هم پایان جمله حساب می‌شود — اصلاح ۱.۰.۲ */
		$parts = preg_split( '/(?<=[.!?؟؛…])\s+|\n+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			$parts = array();
		}
		$out   = array();
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( mb_strlen( $p ) > 1 && preg_match( '/[\x{0600}-\x{06FF}A-Za-z]/u', $p ) ) {
				$out[] = $p;
			}
		}
		return $out;
	}

	public static function words( $text ) {
		$words = preg_split( '/[\s]+/u', trim( (string) $text ), -1, PREG_SPLIT_NO_EMPTY );
		return is_array( $words ) ? $words : array();
	}

	public static function word_count( $text ) {
		return count( self::words( $text ) );
	}

	public static function stem( $word ) {
		$w = mb_strtolower( trim( $word ) );
		$w = str_replace( array( 'ي', 'ك', "\u{200C}" ), array( 'ی', 'ک', '' ), $w );
		$w = preg_replace( '/[«»"\'،؛؟!?,.;:()\[\]]+/u', '', $w );

		$w = preg_replace( '/^(می|نمی|بی)(?=.{3,})/u', '', $w );

		$w = preg_replace( '/(هایمان|هایتان|هایشان|هایی|های|ها|ترین|تر|ام|ات|اش|مان|تان|شان|ای|ی)$/u', '', $w );
		return $w;
	}

	public static function contains_keyword( $text, $keyword ) {
		return self::count_keyword( $text, $keyword ) > 0;
	}

	protected static function match_tokens( $text ) {
		$text = str_replace( "\u{200C}", ' ', (string) $text );
		$tok  = array_map( array( __CLASS__, 'stem' ), self::words( $text ) );
		return array_values( array_filter( $tok, 'strlen' ) );
	}

	public static function count_keyword( $text, $keyword ) {
		$kw = self::match_tokens( $keyword );
		if ( empty( $kw ) ) {
			return 0;
		}
		$tw = self::match_tokens( $text );
		$n  = count( $kw );
		$c  = 0;
		$len = count( $tw );
		for ( $i = 0; $i + $n <= $len; $i++ ) {
			$ok = true;
			for ( $j = 0; $j < $n; $j++ ) {
				if ( $tw[ $i + $j ] !== $kw[ $j ] ) {
					$ok = false;
					break;
				}
			}
			if ( $ok ) {
				$c++;
				$i += $n - 1;
			}
		}
		return $c;
	}

	public function analyze( $html, $title = '', $keyword = '', $meta_desc = '' ) {
		$ex    = MATNBAN_Html_Walker::extract( $html );
		$text  = $ex['text'];
		$words = self::words( $text );
		$wc    = count( $words );
		$sents = self::sentences( $text );
		$sc    = max( 1, count( $sents ) );

		$checks = array();
		$score  = 0;
		$max    = 0;

		$add = function ( $id, $status, $label, $detail, $weight ) use ( &$checks, &$score, &$max ) {
			$checks[] = array(
				'id'     => $id,
				'status' => $status,
				'label'  => $label,
				'detail' => $detail,
			);
			$max += $weight;
			if ( 'good' === $status ) {
				$score += $weight;
			} elseif ( 'warn' === $status ) {
				$score += $weight / 2;
			}
		};

		if ( $wc < 300 ) {
			$add( 'length', $wc < 150 ? 'bad' : 'warn', 'طول متن', "متن {$wc} کلمه دارد. برای رقابت در گوگل حداقل ۳۰۰ کلمه توصیه می‌شود.", 10 );
		} else {
			$add( 'length', 'good', 'طول متن', "متن {$wc} کلمه دارد. عالی!", 10 );
		}

		$long = 0;
		foreach ( $sents as $s ) {
			if ( self::word_count( $s ) > self::MAX_SENTENCE_WORDS ) {
				$long++;
			}
		}
		$long_pct = $wc ? round( $long / $sc * 100 ) : 0;
		if ( $long_pct <= 20 ) {
			$add( 'sentences', 'good', 'طول جمله‌ها', "{$long_pct}٪ جمله‌ها بیش از " . self::MAX_SENTENCE_WORDS . " کلمه دارند. عالی!", 15 );
		} elseif ( $long_pct <= 35 ) {
			$add( 'sentences', 'warn', 'طول جمله‌ها', "{$long_pct}٪ جمله‌ها بلندند ({$long} جمله). سعی کنید زیر ۲۰٪ باشد.", 15 );
		} else {
			$add( 'sentences', 'bad', 'طول جمله‌ها', "{$long_pct}٪ جمله‌ها بیش از " . self::MAX_SENTENCE_WORDS . " کلمه دارند ({$long} جمله). جمله‌های بلند را بشکنید.", 15 );
		}

		$long_p = 0;
		foreach ( $ex['paragraphs'] as $p ) {
			if ( self::word_count( $p ) > self::MAX_PARAGRAPH_WORDS ) {
				$long_p++;
			}
		}
		if ( 0 === $long_p ) {
			$add( 'paragraphs', 'good', 'طول پاراگراف‌ها', 'هیچ پاراگرافی بیش از ' . self::MAX_PARAGRAPH_WORDS . ' کلمه ندارد. عالی!', 10 );
		} else {
			$add( 'paragraphs', $long_p > 2 ? 'bad' : 'warn', 'طول پاراگراف‌ها', "{$long_p} پاراگراف بیش از " . self::MAX_PARAGRAPH_WORDS . ' کلمه دارد. پاراگراف‌های کوتاه‌تر در موبایل خواناترند.', 10 );
		}

		$tw_count = 0;
		foreach ( $sents as $s ) {
			foreach ( self::transition_words() as $t ) {
				if ( preg_match( '/(?<![\x{0600}-\x{06FF}])' . preg_quote( $t, '/' ) . '(?![\x{0600}-\x{06FF}])/u', $s ) ) {
					$tw_count++;
					break;
				}
			}
		}
		$tw_pct = round( $tw_count / $sc * 100 );
		if ( $tw_pct >= 25 ) {
			$add( 'transitions', 'good', 'کلمات ربط', "{$tw_pct}٪ جمله‌ها کلمهٔ ربط دارند (بنابراین، همچنین، اما…). عالی!", 10 );
		} elseif ( $tw_pct >= 15 ) {
			$add( 'transitions', 'warn', 'کلمات ربط', "{$tw_pct}٪ جمله‌ها کلمهٔ ربط دارند. برای پیوستگی بهتر به ۲۵٪ برسانید.", 10 );
		} else {
			$add( 'transitions', 'bad', 'کلمات ربط', "فقط {$tw_pct}٪ جمله‌ها کلمهٔ ربط دارند. از «بنابراین»، «همچنین»، «از سوی دیگر» و… بیشتر استفاده کنید.", 10 );
		}

		$passive = 0;
		foreach ( $sents as $s ) {
			foreach ( self::passive_markers() as $pm ) {
				if ( preg_match( '/(?<![\x{0600}-\x{06FF}])' . preg_quote( $pm, '/' ) . '(?![\x{0600}-\x{06FF}])/u', $s ) ) {
					$passive++;
					break;
				}
			}
		}
		$p_pct = round( $passive / $sc * 100 );
		if ( $p_pct <= 15 ) {
			$add( 'passive', 'good', 'جملات مجهول', "{$p_pct}٪ جمله‌ها مجهول‌اند. عالی!", 10 );
		} elseif ( $p_pct <= 25 ) {
			$add( 'passive', 'warn', 'جملات مجهول', "{$p_pct}٪ جمله‌ها مجهول‌اند («شد»، «می‌شود»…). جملات معلوم رساترند.", 10 );
		} else {
			$add( 'passive', 'bad', 'جملات مجهول', "{$p_pct}٪ جمله‌ها مجهول‌اند. بیشتر جمله‌ها را معلوم بنویسید.", 10 );
		}

		if ( $wc >= self::WORDS_PER_HEADING ) {
			$need = (int) floor( $wc / self::WORDS_PER_HEADING );
			if ( $ex['headings'] >= $need ) {
				$add( 'headings', 'good', 'زیرعنوان‌ها', "{$ex['headings']} زیرعنوان برای {$wc} کلمه. عالی!", 10 );
			} elseif ( $ex['headings'] > 0 ) {
				$add( 'headings', 'warn', 'زیرعنوان‌ها', "{$ex['headings']} زیرعنوان دارید؛ هر ۳۰۰ کلمه یک زیرعنوان (H2/H3) توصیه می‌شود.", 10 );
			} else {
				$add( 'headings', 'bad', 'زیرعنوان‌ها', 'متن هیچ زیرعنوانی ندارد. متن بلند بدون H2/H3 خسته‌کننده است.', 10 );
			}
		} else {
			$add( 'headings', 'good', 'زیرعنوان‌ها', 'متن کوتاه است و نیازی به زیرعنوان اضافی ندارد.', 10 );
		}

		$rep  = 0;
		$prev = null;
		$run  = 1;
		foreach ( $sents as $s ) {
			$first = self::stem( self::words( $s )[0] );
			if ( $first === $prev && mb_strlen( $first ) > 1 ) {
				$run++;
				if ( 3 === $run ) {
					$rep++;
				}
			} else {
				$run = 1;
			}
			$prev = $first;
		}
		if ( 0 === $rep ) {
			$add( 'repetition', 'good', 'تنوع آغاز جمله‌ها', 'هیچ سه جملهٔ پشت‌سرهم با کلمهٔ یکسان شروع نشده‌اند. عالی!', 5 );
		} else {
			$add( 'repetition', 'warn', 'تنوع آغاز جمله‌ها', "{$rep} مورد سه جملهٔ پشت‌سرهم با یک کلمه شروع شده‌اند. تنوع ایجاد کنید.", 5 );
		}

		$issues = $this->normalizer->count_issues( $text );
		if ( 0 === $issues ) {
			$add( 'typography', 'good', 'نگارش فارسی', 'هیچ خطای نگارشی قابل اصلاح پیدا نشد. عالی!', 15 );
		} elseif ( $issues <= 5 ) {
			$add( 'typography', 'warn', 'نگارش فارسی', "{$issues} خطای نگارشی (نیم‌فاصله، حروف عربی، علائم) پیدا شد. با دکمهٔ «اصلاح نگارش» یک‌جا برطرف کنید.", 15 );
		} else {
			$add( 'typography', 'bad', 'نگارش فارسی', "{$issues} خطای نگارشی پیدا شد. با دکمهٔ «اصلاح نگارش» یک‌جا برطرف کنید.", 15 );
		}

		$no_alt = count( array_filter( $ex['images'], function ( $i ) { return '' === $i['alt']; } ) );
		if ( empty( $ex['images'] ) ) {
			$add( 'images', 'warn', 'تصاویر', 'مطلب هیچ تصویری ندارد. تصاویر، خوانایی و سئو را بهبود می‌دهند.', 5 );
		} elseif ( 0 === $no_alt ) {
			$add( 'images', 'good', 'تصاویر', count( $ex['images'] ) . ' تصویر، همه با متن جایگزین. عالی!', 5 );
		} else {
			$add( 'images', 'bad', 'تصاویر', "{$no_alt} تصویر از " . count( $ex['images'] ) . ' تصویر متن جایگزین (alt) ندارند.', 5 );
		}

		$kw_data = null;
		if ( '' !== trim( $keyword ) ) {
			$k_count   = self::count_keyword( $text, $keyword );

			$density   = $wc ? round( $k_count / $wc * 100, 2 ) : 0;
			$in_title  = self::contains_keyword( $title, $keyword );
			$first_p   = isset( $ex['paragraphs'][0] ) ? $ex['paragraphs'][0] : '';
			$in_first  = self::contains_keyword( $first_p, $keyword );
			$in_meta   = '' === $meta_desc ? null : self::contains_keyword( $meta_desc, $keyword );
			$in_head   = false;
			if ( preg_match_all( '~<h[2-4]\b[^>]*>(.*?)</h[2-4]>~isu', $html, $hm ) ) {
				foreach ( $hm[1] as $h ) {
					if ( self::contains_keyword( wp_strip_all_tags( $h ), $keyword ) ) {
						$in_head = true;
						break;
					}
				}
			}
			$in_alt = false;
			foreach ( $ex['images'] as $img ) {
				if ( '' !== $img['alt'] && self::contains_keyword( $img['alt'], $keyword ) ) {
					$in_alt = true;
					break;
				}
			}

			$add( 'kw_title', $in_title ? 'good' : 'bad', 'کلمهٔ کلیدی در عنوان', $in_title ? 'کلمهٔ کلیدی در عنوان آمده است.' : 'کلمهٔ کلیدی در عنوان نیست.', 10 );
			$add( 'kw_first', $in_first ? 'good' : 'bad', 'کلمهٔ کلیدی در پاراگراف اول', $in_first ? 'کلمهٔ کلیدی در پاراگراف اول آمده است.' : 'کلمهٔ کلیدی را در پاراگراف اول بیاورید.', 10 );
			if ( $density >= 0.5 && $density <= 3 ) {
				$add( 'kw_density', 'good', 'تراکم کلمهٔ کلیدی', "{$k_count} بار تکرار، تراکم {$density}٪ (با احتساب صرف‌های فارسی). عالی!", 10 );
			} elseif ( $density > 3 ) {
				$add( 'kw_density', 'bad', 'تراکم کلمهٔ کلیدی', "تراکم {$density}٪ زیاد است (بیش از ۳٪). گوگل این را «کیورد استافینگ» می‌داند.", 10 );
			} else {
				$add( 'kw_density', $k_count ? 'warn' : 'bad', 'تراکم کلمهٔ کلیدی', "{$k_count} بار تکرار، تراکم {$density}٪. آن را به ۰.۵ تا ۳٪ برسانید.", 10 );
			}
			$add( 'kw_heading', $in_head ? 'good' : 'warn', 'کلمهٔ کلیدی در زیرعنوان', $in_head ? 'کلمهٔ کلیدی در یکی از زیرعنوان‌ها آمده است.' : 'کلمهٔ کلیدی را در یک زیرعنوان (H2/H3) بیاورید.', 5 );
			if ( ! empty( $ex['images'] ) ) {
				$add( 'kw_alt', $in_alt ? 'good' : 'warn', 'کلمهٔ کلیدی در alt تصاویر', $in_alt ? 'کلمهٔ کلیدی در متن جایگزین تصاویر آمده است.' : 'کلمهٔ کلیدی را در متن جایگزین حداقل یک تصویر بیاورید.', 5 );
			}
			if ( null !== $in_meta ) {
				$add( 'kw_meta', $in_meta ? 'good' : 'warn', 'کلمهٔ کلیدی در توضیحات متا', $in_meta ? 'کلمهٔ کلیدی در توضیحات متا آمده است.' : 'کلمهٔ کلیدی را در توضیحات متا بیاورید.', 5 );
			}

			$kw_data = array(
				'keyword'  => $keyword,
				'count'    => $k_count,
				'density'  => $density,
				'in_title' => $in_title,
				'in_first' => $in_first,
				'in_head'  => $in_head,
				'in_alt'   => $in_alt,
				'in_meta'  => $in_meta,
			);
		}

		$final = $max ? (int) round( $score / $max * 100 ) : 0;
		$grade = $final >= 80 ? 'good' : ( $final >= 55 ? 'warn' : 'bad' );
		$label = $final >= 80 ? 'عالی' : ( $final >= 55 ? 'قابل قبول' : 'نیاز به بهبود' );

		if ( $wc < self::MIN_WORDS ) {
			$final = (int) round( $final * $wc / self::MIN_WORDS );
			$grade = 'bad';
			$label = $wc ? 'متن خیلی کوتاه است' : 'متنی برای تحلیل نیست';
		}

		return array(
			'score'        => $final,
			'grade'        => $grade,
			'grade_label'  => $label,
			'word_count'   => $wc,
			'sentence_count' => count( $sents ),
			'paragraph_count' => count( $ex['paragraphs'] ),
			'reading_time' => $wc ? max( 1, (int) ceil( $wc / self::WPM ) ) : 0,
			'typo_issues'  => $issues,
			'checks'       => $checks,
			'keyword'      => $kw_data,
		);
	}
}
