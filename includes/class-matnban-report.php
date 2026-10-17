<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Report {
	const META    = '_matnban_scan';
	const META_IN = '_matnban_in_summary';
	const OPTION  = 'matnban_report';
	const CRON    = 'matnban_weekly_scan';
	const CRON_CONTINUE = 'matnban_scan_continue';
	const BATCH   = 20;

	public function __construct() {
		add_action( 'wp_ajax_matnban_scan_batch', array( $this, 'ajax_scan_batch' ) );
		add_action( 'wp_ajax_matnban_scan_reset', array( $this, 'ajax_scan_reset' ) );
		add_action( 'wp_ajax_matnban_report_csv', array( $this, 'export_csv' ) );
		add_action( self::CRON, array( $this, 'cron_scan' ) );
		add_action( self::CRON_CONTINUE, array( $this, 'cron_scan' ) );
		add_action( 'save_post', array( $this, 'on_save_post' ), 99, 2 );
		add_action( 'before_delete_post', array( $this, 'on_delete_post' ) );
		add_action( 'update_option_' . MATNBAN_OPTION, array( $this, 'settings_changed' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'maybe_schedule' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
	}

	/** زمان‌بندی «هفتگی» که هستهٔ وردپرس به‌صورت پیش‌فرض ندارد (ووکامرس هم می‌افزاید؛ این فقط در صورت نبود اضافه می‌کند) */
	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array( 'interval' => WEEK_IN_SECONDS, 'display' => 'یک‌بار در هفته' );
		}
		return $schedules;
	}

	public static function post_types() {
		$types = (array) Matnban::get( 'post_types', array( 'post', 'page' ) );
		return array_values( array_filter( $types, 'post_type_exists' ) );
	}

	public static function total_posts() {
		$types = self::post_types();
		if ( empty( $types ) ) {
			return 0;
		}
		$q = new WP_Query( array(
			'post_type'      => $types,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		) );
		return (int) $q->found_posts;
	}

	public static function scan_post( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return null;
		}
		$normalizer = Matnban::normalizer();
		$content    = (string) $post->post_content;
		$title      = (string) $post->post_title;

		$normalizer->reset_stats();
		Matnban::walker()->process( $content );
		$normalizer->normalize( $title );
		$stats = $normalizer->get_stats();
		$normalizer->reset_stats();

		$rules = array();
		foreach ( $stats['rules'] as $rule => $n ) {
			$rules[ $rule ] = (int) $n;
		}

		$rendered = $content;
		if ( function_exists( 'do_blocks' ) && false !== strpos( $rendered, '<!-- wp:' ) ) {
			$rendered = do_blocks( $rendered );
		}
		$r        = new MATNBAN_Readability( $normalizer );
		$analysis = $r->analyze( $rendered, $title, MATNBAN_Editor::detect_keyword( $post->ID ), MATNBAN_Editor::detect_meta_desc( $post->ID ) );
		$ex       = MATNBAN_Html_Walker::extract( $rendered );
		$no_alt   = 0;
		foreach ( $ex['images'] as $img ) {
			if ( '' === $img['alt'] ) {
				$no_alt++;
			}
		}

		$slug_fa = (bool) preg_match( '/%[0-9A-Fa-f]{2}|[\x{0600}-\x{06FF}]/u', (string) $post->post_name );

		$data = array(
			'time'     => time(),
			'issues'   => (int) $stats['total'],
			'rules'    => $rules,
			'score'    => (int) $analysis['score'],
			'words'    => (int) $analysis['word_count'],
			'images'   => count( $ex['images'] ),
			'no_alt'   => $no_alt,
			'slug_fa'  => $slug_fa ? 1 : 0,
			'keyword'  => empty( $analysis['keyword'] ) ? 0 : 1,
		);
		update_post_meta( $post->ID, self::META, $data );
		return $data;
	}

	public function on_save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		if ( ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		if ( false === get_option( self::OPTION ) ) {
			return;
		}
		$old = get_post_meta( $post_id, self::META, true );
		$old = is_array( $old ) ? $old : null;
		/* آیا سهم این مطلب الان داخل خلاصه است؟ (پرچم؛ برای داده‌های قدیمیِ بدون پرچم: اگر اسکن دارد، بوده است) */
		$flag = get_post_meta( $post_id, self::META_IN, true );
		$in   = '' === $flag ? ( null !== $old ) : (bool) $flag;
		$live = in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future', 'private' ), true );
		if ( ! $live ) {
			/* مطلب از دسترس گزارش خارج شده (مثلاً به زباله‌دان رفته) — فقط یک‌بار و فقط اگر داخل آمار است کم شود */
			if ( $old && $in ) {
				self::adjust_summary( $old, null );
				update_post_meta( $post_id, self::META_IN, 0 );
			}
			return;
		}
		$new = self::scan_post( $post );
		if ( ! $new ) {
			return;
		}
		if ( $in && $old ) {
			self::adjust_summary( $old, $new );
		} else {
			self::adjust_summary( null, $new );
		}
		update_post_meta( $post_id, self::META_IN, 1 );
	}

	/** حذف دائمی مطلب — سهمش پیش از پاک شدن متادیتا از خلاصه کم شود */
	public function on_delete_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		if ( false === get_option( self::OPTION ) ) {
			return;
		}
		$old = get_post_meta( $post_id, self::META, true );
		$flag = get_post_meta( $post_id, self::META_IN, true );
		$in   = '' === $flag ? ( is_array( $old ) ? true : false ) : (bool) $flag;
		if ( is_array( $old ) && $in ) {
			self::adjust_summary( $old, null );
		}
	}

	/** تغییر تنظیمات افزونه — زمان‌بندی تازه شود و اگر گزارشی وجود دارد، دامنهٔ آن (نوع مطالب و…) دوباره جمع شود */
	public function settings_changed() {
		$this->schedule();
		if ( false !== get_option( self::OPTION ) ) {
			self::rebuild_summary();
		}
	}

	/**
	 * به‌روزرسانی افزایشی خلاصهٔ گزارش پس از تغییر یک مطلب —
	 * به‌جای بازخوانی متادیتای همهٔ مطالب سایت (که در سایت‌های پرمطلب هر ذخیره را کند می‌کرد).
	 * $old/$new = دادهٔ اسکن قبلی/جدی همان مطلب (null = نبوده).
	 */
	public static function adjust_summary( $old, $new ) {
		$summary = self::summary();
		if ( ! is_array( $summary ) || empty( $summary['scanned'] ) ) {
			self::rebuild_summary();
			return;
		}
		if ( $old ) {
			$summary['scanned']     = max( 0, (int) $summary['scanned'] - 1 );
			$summary['issues']      = max( 0, (int) $summary['issues'] - (int) $old['issues'] );
			$summary['with_issues'] = max( 0, (int) $summary['with_issues'] - ( (int) $old['issues'] > 0 ? 1 : 0 ) );
			foreach ( (array) $old['rules'] as $rule => $n ) {
				if ( isset( $summary['rules'][ $rule ] ) ) {
					$summary['rules'][ $rule ] = (int) $summary['rules'][ $rule ] - (int) $n;
					if ( $summary['rules'][ $rule ] <= 0 ) {
						unset( $summary['rules'][ $rule ] );
					}
				}
			}
			$summary['score_sum'] = max( 0, (int) $summary['score_sum'] - (int) $old['score'] );
			$bucket               = (int) $old['score'] >= 80 ? 'score_good' : ( (int) $old['score'] >= 55 ? 'score_warn' : 'score_bad' );
			$summary[ $bucket ]   = max( 0, (int) $summary[ $bucket ] - 1 );
			$summary['slug_fa']    = max( 0, (int) $summary['slug_fa'] - (int) $old['slug_fa'] );
			$summary['no_alt']     = max( 0, (int) $summary['no_alt'] - (int) $old['no_alt'] );
			$summary['words']      = max( 0, (int) $summary['words'] - (int) $old['words'] );
			$summary['no_keyword'] = max( 0, (int) $summary['no_keyword'] - ( empty( $old['keyword'] ) ? 1 : 0 ) );
		}
		if ( $new ) {
			$summary['scanned']++;
			$summary['issues'] += (int) $new['issues'];
			if ( (int) $new['issues'] > 0 ) {
				$summary['with_issues']++;
			}
			foreach ( (array) $new['rules'] as $rule => $n ) {
				$summary['rules'][ $rule ] = ( isset( $summary['rules'][ $rule ] ) ? $summary['rules'][ $rule ] : 0 ) + (int) $n;
			}
			$summary['score_sum'] += (int) $new['score'];
			$bucket               = (int) $new['score'] >= 80 ? 'score_good' : ( (int) $new['score'] >= 55 ? 'score_warn' : 'score_bad' );
			$summary[ $bucket ]++;
			$summary['slug_fa']    += (int) $new['slug_fa'];
			$summary['no_alt']     += (int) $new['no_alt'];
			$summary['words']      += (int) $new['words'];
			$summary['no_keyword'] += empty( $new['keyword'] ) ? 1 : 0;
		}
		arsort( $summary['rules'] );
		$summary['avg_score'] = $summary['scanned'] ? (int) round( $summary['score_sum'] / $summary['scanned'] ) : 0;
		$summary['total']     = self::total_posts();
		/* «آخرین بررسی» عمداً دست نمی‌خورد؛ این فیلد مال آخرین بررسی کامل سایت است، نه آخرین ذخیرهٔ یک مطلب */
		update_option( self::OPTION, $summary, false );
		return $summary;
	}

	public static function rebuild_summary() {
		global $wpdb;
		$types = self::post_types();
		if ( empty( $types ) ) {
			return array();
		}
		$in   = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$rows = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off summary query
			"SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type IN ($in) AND p.post_status IN ('publish','draft','pending','future','private')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in holds only %s placeholders
			array_merge( array( self::META ), $types )
		) );

		$summary = array(
			'time'        => time(),
			'scanned'     => 0,
			'total'       => self::total_posts(),
			'with_issues' => 0,
			'issues'      => 0,
			'rules'       => array(),
			'score_sum'   => 0,
			'score_bad'   => 0,
			'score_warn'  => 0,
			'score_good'  => 0,
			'slug_fa'     => 0,
			'no_alt'      => 0,
			'no_keyword'  => 0,
			'words'       => 0,
		);
		foreach ( $rows as $raw ) {
			$d = maybe_unserialize( $raw );
			if ( ! is_array( $d ) ) {
				continue;
			}
			$summary['scanned']++;
			$summary['issues'] += (int) $d['issues'];
			if ( $d['issues'] > 0 ) {
				$summary['with_issues']++;
			}
			foreach ( (array) $d['rules'] as $rule => $n ) {
				$summary['rules'][ $rule ] = ( isset( $summary['rules'][ $rule ] ) ? $summary['rules'][ $rule ] : 0 ) + (int) $n;
			}
			$summary['score_sum'] += (int) $d['score'];
			if ( $d['score'] >= 80 ) {
				$summary['score_good']++;
			} elseif ( $d['score'] >= 55 ) {
				$summary['score_warn']++;
			} else {
				$summary['score_bad']++;
			}
			$summary['slug_fa']   += (int) $d['slug_fa'];
			$summary['no_alt']    += (int) $d['no_alt'];
			$summary['words']     += (int) $d['words'];
			$summary['no_keyword'] += empty( $d['keyword'] ) ? 1 : 0;
		}
		arsort( $summary['rules'] );
		$summary['avg_score'] = $summary['scanned'] ? (int) round( $summary['score_sum'] / $summary['scanned'] ) : 0;
		update_option( self::OPTION, $summary, false );
		return $summary;
	}

	public static function summary() {
		$s = get_option( self::OPTION );
		return is_array( $s ) ? $s : null;
	}

	public function ajax_scan_reset() {
		check_ajax_referer( 'matnban_report', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی ندارید.' );
		}
		update_option( 'matnban_scan_progress', array( 'offset' => 0, 'started' => time() ), false );
		wp_send_json_success( array( 'total' => self::total_posts() ) );
	}

	public function ajax_scan_batch() {
		check_ajax_referer( 'matnban_report', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی ندارید.' );
		}
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
		$types  = self::post_types();
		$q      = new WP_Query( array(
			'post_type'      => $types,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => self::BATCH,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		) );
		$done = 0;
		foreach ( $q->posts as $id ) {
			self::scan_post( $id );
			$done++;
		}
		$next     = $offset + $done;
		$finished = $done < self::BATCH || $next >= (int) $q->found_posts;
		$summary  = null;
		if ( $finished ) {
			$summary = self::rebuild_summary();
			delete_option( 'matnban_scan_progress' );
		}
		wp_send_json_success( array(
			'offset'   => $next,
			'total'    => (int) $q->found_posts,
			'finished' => $finished,
			'summary'  => $summary,
		) );
	}


	public static function problem_posts( $args = array() ) {
		global $wpdb;
		$args = wp_parse_args( $args, array(
			'per_page' => 20,
			'paged'    => 1,
			'orderby'  => 'issues',
			'filter'   => 'issues',
		) );
		$filter = $args['filter'];
		$types  = self::post_types();
		$rows   = array();
		if ( empty( $types ) ) {
			return $rows;
		}
		/* همهٔ مطالب، دستهٔ ۲۰۰تایی و با ترتیب مشخص — در سایت‌های بزرگ فقط ۲۰۰ مطلب اول خوانده نمی‌شود */
		$offset = 0;
		$batch  = 200;
		do {
			$q = new WP_Query( array(
				'post_type'      => $types,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => $batch,
				'offset'         => $offset,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			) );
			if ( empty( $q->posts ) ) {
				break;
			}
			$ids_ph = implode( ',', array_fill( 0, count( $q->posts ), '%d' ) );
			$metas  = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN ($ids_ph)", array_merge( array( self::META ), array_map( 'intval', $q->posts ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $ids_ph holds only %d placeholders; batched meta lookup
			$by_id  = array();
			foreach ( $metas as $mrow ) {
				$by_id[ (int) $mrow->post_id ] = maybe_unserialize( $mrow->meta_value );
			}
			foreach ( $q->posts as $id ) {
				$d = isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
				if ( ! is_array( $d ) ) {
					continue;
				}
				$keep = false;
				switch ( $filter ) {
					case 'issues':
						$keep = $d['issues'] > 0;
						break;
					case 'score':
						$keep = $d['score'] < 55;
						break;
					case 'alt':
						$keep = $d['no_alt'] > 0;
						break;
					case 'slug':
						$keep = ! empty( $d['slug_fa'] );
						break;
					default:
						$keep = true;
				}
				if ( $keep ) {
					$rows[] = array_merge( $d, array( 'id' => (int) $id ) );
				}
			}
			$offset += $batch;
		} while ( count( $q->posts ) === $batch );
		$key = in_array( $args['orderby'], array( 'issues', 'score', 'no_alt', 'words' ), true ) ? $args['orderby'] : 'issues';
		usort( $rows, function ( $a, $b ) use ( $key ) {
			if ( 'score' === $key ) {
				return $a['score'] - $b['score'];
			}
			return $b[ $key ] - $a[ $key ];
		} );
		return $rows;
	}

	public function export_csv() {
		check_admin_referer( 'matnban_report_csv' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$rows = self::problem_posts( array( 'filter' => 'all' ) );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=matnban-report-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streaming CSV export
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming CSV BOM
		matnban_csv_row( $out, array( 'شناسه', 'عنوان', 'نوع', 'وضعیت', 'خطاهای نگارشی', 'امتیاز خوانایی', 'کلمات', 'تصاویر', 'تصاویر بدون alt', 'نامک فارسی', 'کلمهٔ کلیدی', 'آدرس ویرایش' ) );
		foreach ( $rows as $r ) {
			$p = get_post( $r['id'] );
			if ( ! $p ) {
				continue;
			}
			matnban_csv_row( $out, array(
				$r['id'],
				$p->post_title,
				$p->post_type,
				$p->post_status,
				$r['issues'],
				$r['score'],
				$r['words'],
				$r['images'],
				$r['no_alt'],
				$r['slug_fa'] ? 'بله' : 'خیر',
				$r['keyword'] ? 'دارد' : 'ندارد',
				get_edit_post_link( $r['id'], 'raw' ),
			) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming CSV export
		exit;
	}

	public function maybe_schedule() {
		$this->schedule();
	}

	public function schedule() {
		$want = (string) Matnban::get( 'report_schedule', 'weekly' );
		$next = wp_next_scheduled( self::CRON );
		if ( 'off' === $want ) {
			if ( $next ) {
				wp_clear_scheduled_hook( self::CRON );
			}
			return;
		}
		/* اگر تناوب رویداد موجود با تنظیم تازه فرق دارد (مثلاً هفتگی ← روزانه)، زمان‌بندی اصلاح شود */
		$recurrence = 'daily' === $want ? 'daily' : 'weekly';
		$event      = $next ? wp_get_scheduled_event( self::CRON ) : false;
		if ( ! $event || $event->schedule !== $recurrence ) {
			wp_clear_scheduled_hook( self::CRON );
			wp_schedule_event( time() + 10 * MINUTE_IN_SECONDS, $recurrence, self::CRON );
		}
	}

	public function cron_scan() {
		$types = self::post_types();
		if ( empty( $types ) ) {
			return;
		}
		/* بررسی دسته‌دسته با بودجهٔ زمانی؛ اگر تمام نشد، ادامهٔ آن برای یک دقیقهٔ بعد زمان‌بندی می‌شود تا روی هاست اشتراکی به timeout نخورد */
		$progress = get_option( 'matnban_cron_progress' );
		if ( ! is_array( $progress ) || empty( $progress['started'] ) || ( time() - (int) $progress['started'] ) > 6 * HOUR_IN_SECONDS ) {
			$progress = array( 'offset' => 0, 'started' => time() );
		}
		$start = time();
		while ( time() - $start < 20 ) {
			$q = new WP_Query( array(
				'post_type'      => $types,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => 50,
				'offset'         => (int) $progress['offset'],
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			) );
			if ( empty( $q->posts ) ) {
				$this->finish_cron_scan();
				return;
			}
			foreach ( $q->posts as $id ) {
				self::scan_post( $id );
			}
			$progress['offset'] += count( $q->posts );
			if ( count( $q->posts ) < 50 ) {
				$this->finish_cron_scan();
				return;
			}
			update_option( 'matnban_cron_progress', $progress, false );
		}
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::CRON_CONTINUE );
	}

	protected function finish_cron_scan() {
		delete_option( 'matnban_cron_progress' );
		$summary = self::rebuild_summary();
		if ( Matnban::get( 'report_email' ) && ! empty( $summary['scanned'] ) ) {
			$this->send_email( $summary );
		}
	}

	protected function send_email( $summary ) {
		$to   = get_option( 'admin_email' );
		$name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$pct  = $summary['scanned'] ? round( $summary['with_issues'] / $summary['scanned'] * 100 ) : 0;
		$body = "گزارش متن‌بان برای سایت {$name}\n\n"
			. "مطالب بررسی‌شده: {$summary['scanned']}\n"
			. "مطالب دارای خطای نگارشی: {$summary['with_issues']} ({$pct}٪)\n"
			. "مجموع خطاهای نگارشی: {$summary['issues']}\n"
			. "میانگین امتیاز خوانایی: {$summary['avg_score']} از ۱۰۰\n"
			. "تصاویر بدون متن جایگزین: {$summary['no_alt']}\n"
			. "مطالب با نامک فارسی: {$summary['slug_fa']}\n\n"
			. 'گزارش کامل: ' . admin_url( 'admin.php?page=matnban&tab=report' ) . "\n";
		$freq = 'daily' === (string) Matnban::get( 'report_schedule', 'weekly' ) ? 'روزانه' : 'هفتگی';
		wp_mail( $to, "[{$name}] گزارش {$freq} متن‌بان", $body );
	}

}

/**
 * CSV row writer: explicit parameters (PHP 8.4+) and formula-injection guard.
 */
function matnban_csv_row( $out, $row ) {
	foreach ( $row as &$cell ) {
		if ( is_string( $cell ) && '' !== $cell && in_array( $cell[0], array( '=', '+', '-', '@' ), true ) ) {
			$cell = "'" . $cell;
		}
	}
	unset( $cell );
	fputcsv( $out, $row, ',', '"', '' );
}
