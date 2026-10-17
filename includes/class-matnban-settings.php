<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Settings {
	const PAGE = 'matnban';

	/** فیلتر فعلی جدول گزارش (issues|score|alt|slug|all) */
	protected $filter = 'issues';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_ajax_matnban_quick_test', array( $this, 'ajax_quick_test' ) );
		add_action( 'wp_ajax_matnban_tab', array( $this, 'ajax_tab' ) );
		add_action( 'wp_ajax_matnban_save_settings', array( $this, 'ajax_save_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MATNBAN_FILE ), array( $this, 'action_links' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );
	}

	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'تنظیمات', 'matnban' ) . '</a>' );
		return $links;
	}

	public static function icon_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M3 4h14v2H3zm0 4h9v2H3zm0 4h14v2H3zm0 4h9v2H3zm12-5 2 2-4 4h-2v-2z"/></svg>';
	}

	public function menu() {
		add_menu_page(
			__( 'متن‌بان', 'matnban' ),
			__( 'متن‌بان', 'matnban' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' ),
			'data:image/svg+xml;base64,' . base64_encode( self::icon_svg() ),
			81
		);
		$subs = array();
		foreach ( self::tabs() as $_k => $_t ) {
			$subs[ $_k ] = $_t[0];
		}
		/* همهٔ زیرمنوها همان صفحهٔ اصلی را رندر می‌کنند تا بازکردن مستقیم لینک (یا F5 روی تب) صفحهٔ خالی ندهد */
		foreach ( $subs as $slug => $label ) {
			add_submenu_page( self::PAGE, 'متن‌بان — ' . $label, $label, 'manage_options', self::PAGE . ( 'dashboard' === $slug ? '' : '&tab=' . $slug ), array( $this, 'render' ) );
		}
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'matnban-admin', MATNBAN_URL . 'assets/css/admin.css', array(), MATNBAN_VERSION );
		wp_enqueue_script( 'matnban-admin', MATNBAN_URL . 'assets/js/admin.js', array( 'jquery' ), MATNBAN_VERSION, true );
		// بارگذاری مطمئن (نسخهٔ ۱.۰.۱): برخی هاست‌ها فایل‌های استایل/اسکریپت افزونه را ناقص تحویل می‌دهند؛
		// محتوا علاوه بر فایل، داخل خود صفحه هم تزریق می‌شود تا پنل همیشه درست نمایش داده شود.
		$matnban_css = file_get_contents( MATNBAN_DIR . 'assets/css/admin.css' );
		if ( is_string( $matnban_css ) && '' !== $matnban_css ) {
			wp_add_inline_style( 'matnban-admin', $matnban_css );
		}
		wp_localize_script( 'matnban-admin', 'MATNBAN_ADMIN', array(
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'matnban_quick_test' ),
			'reportNonce' => wp_create_nonce( 'matnban_report' ),
			'tabNonce'    => wp_create_nonce( 'matnban_admin_tab' ),
			'pageUrl'     => admin_url( 'admin.php?page=' . self::PAGE ),
			'editBase'    => admin_url( 'post.php?action=edit&post=' ),
			'labels'      => MATNBAN_Normalizer::rule_labels(),
			'i18n'        => array(
				'scanning'  => 'در حال بررسی…',
				'scanned'   => 'بررسی شد',
				'of'        => 'از',
				'done'      => 'بررسی کامل شد.',
				'fixing'    => 'در حال اصلاح…',
				'fixed'     => 'اصلاح شد',
				'confirm'   => 'متن این مطالب طبق قواعد فعال اصلاح و ذخیره می‌شود (نسخهٔ قبلی در «بازنگری‌ها» می‌ماند). ادامه می‌دهید؟',
				'error'     => 'خطا در ارتباط با سرور.',
				'noPosts'   => 'مطلبی برای بررسی پیدا نشد.',
				'testing'   => 'در حال آزمایش اتصال…',
				'connected' => 'اتصال برقرار است',
				'saved'     => 'تنظیمات ذخیره شد.',
				'saving'    => 'در حال ذخیره…',
				'loading'   => 'در حال بارگذاری…',
			),
		) );
		$matnban_js = file_get_contents( MATNBAN_DIR . 'assets/js/admin.js' );
		if ( is_string( $matnban_js ) && '' !== $matnban_js ) {
			// گارد «یک‌بار اجرا» داخل خود فایل است؛ نسخهٔ دوم (فایل یا inline) بی‌اثر می‌شود.
			wp_add_inline_script( 'matnban-admin', $matnban_js );
		}
	}

	public function register() {
		register_setting( 'matnban_settings_group', MATNBAN_OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize' ),
		) );
	}

	public function sanitize( $input ) {
		$defaults = Matnban::default_settings();
		$current  = Matnban::get_settings();
		$out      = $current;
		$section  = isset( $input['_section'] ) ? sanitize_key( $input['_section'] ) : 'all';

		if ( in_array( $section, array( 'all', 'rules' ), true ) ) {
			$out['rules'] = array();
			foreach ( array_keys( $defaults['rules'] ) as $rule ) {
				$out['rules'][ $rule ] = ! empty( $input['rules'][ $rule ] ) ? 1 : 0;
			}
		}

		if ( in_array( $section, array( 'all', 'auto' ), true ) ) {
			$out['post_types'] = array();
			if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
				$out['post_types'] = array_values( array_map( 'sanitize_key', $input['post_types'] ) );
			}
			foreach ( array( 'auto_fix_on_save', 'auto_fix_title', 'fix_on_display', 'fix_comments', 'readability_panel', 'align_editor', 'align_force' ) as $k ) {
				$out[ $k ] = ! empty( $input[ $k ] ) ? 1 : 0;
			}
			$out['justify_selector'] = isset( $input['justify_selector'] ) ? sanitize_text_field( $input['justify_selector'] ) : '';
			$ta = isset( $input['text_align'] ) ? sanitize_key( $input['text_align'] ) : '';
			$out['text_align']  = in_array( $ta, array( 'justify', 'right', 'center', 'left' ), true ) ? $ta : '';
			$out['align_scope'] = ( isset( $input['align_scope'] ) && 'all' === $input['align_scope'] ) ? 'all' : 'content';
		}

		if ( in_array( $section, array( 'all', 'slug' ), true ) ) {
			foreach ( array( 'media_rename', 'media_alt' ) as $k ) {
				$out[ $k ] = ! empty( $input[ $k ] ) ? 1 : 0;
			}
		}

		if ( in_array( $section, array( 'all', 'report' ), true ) ) {
			$sch = isset( $input['report_schedule'] ) ? sanitize_key( $input['report_schedule'] ) : 'weekly';
			$out['report_schedule'] = in_array( $sch, array( 'off', 'daily', 'weekly' ), true ) ? $sch : 'weekly';
			$out['report_email']    = ! empty( $input['report_email'] ) ? 1 : 0;
		}

		add_settings_error( 'matnban_messages', 'matnban_saved', __( 'تنظیمات ذخیره شد.', 'matnban' ), 'updated' );
		return $out;
	}

	public function ajax_quick_test() {
		check_ajax_referer( 'matnban_quick_test', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'دسترسی ندارید.', 'matnban' ) );
		}
		$text = isset( $_POST['text'] ) ? wp_kses_post( wp_unslash( (string) $_POST['text'] ) ) : '';

		$normalizer = Matnban::normalizer();
		$normalizer->reset_stats();
		$fixed  = Matnban::walker()->process( $text );
		$stats  = $normalizer->get_stats();
		$labels = MATNBAN_Normalizer::rule_labels();
		$report = array();
		foreach ( $stats['rules'] as $rule => $n ) {
			$report[] = array( 'label' => isset( $labels[ $rule ] ) ? $labels[ $rule ] : $rule, 'count' => $n );
		}
		$r = new MATNBAN_Readability( $normalizer );
		$a = $r->analyze( '<p>' . $fixed . '</p>' );
		wp_send_json_success( array(
			'fixed'  => $fixed,
			'total'  => $stats['total'],
			'report' => $report,
			'score'  => $a['score'],
			'grade'  => $a['grade_label'],
		) );
	}

	public function dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'matnban_dashboard_widget', 'متن‌بان — سلامت نگارش سایت', array( $this, 'render_dashboard_widget' ) );
	}

	public function render_dashboard_widget() {
		$s = MATNBAN_Report::summary();
		if ( ! $s || empty( $s['scanned'] ) ) {
			echo '<p>هنوز گزارشی ساخته نشده است. <a href="' . esc_url( admin_url( 'admin.php?page=matnban&tab=report' ) ) . '">بررسی مطالب سایت</a></p>';
			return;
		}
		$pct = $s['scanned'] ? round( $s['with_issues'] / $s['scanned'] * 100 ) : 0;
		echo '<div dir="rtl" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">';
		echo '<div><strong style="font-size:22px;display:block">' . esc_html( self::fa( $pct ) ) . '٪</strong><span>مطالب دارای خطای نگارشی</span></div>';
		echo '<div><strong style="font-size:22px;display:block">' . esc_html( self::fa( $s['avg_score'] ) ) . '</strong><span>میانگین خوانایی</span></div>';
		echo '<div><strong style="font-size:22px;display:block">' . esc_html( self::fa( $s['no_alt'] ) ) . '</strong><span>تصویر بدون alt</span></div>';
		echo '</div><p style="margin-top:10px"><a class="button" href="' . esc_url( admin_url( 'admin.php?page=matnban&tab=report' ) ) . '">گزارش کامل</a> <span class="description">آخرین بررسی: ' . esc_html( self::ago( $s['time'] ) ) . '</span></p>';
	}

	public static function fa( $n ) {
		return strtr( (string) $n, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹', ',' => '٬' ) );
	}

	public static function ago( $ts ) {
		return self::fa( human_time_diff( (int) $ts, time() ) ) . ' پیش';
	}

	public static function tabs() {
		$tabs = array(
			'dashboard' => array( 'داشبورد', 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z' ),
			'test'      => array( 'آزمایش سریع', 'M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3' ),
			'report'    => array( 'گزارش سایت', 'M4 20V10M10 20V4M16 20v-7M22 20H2' ),
			'rules'     => array( 'قواعد نگارش', 'M4 6h16M4 12h10M4 18h16M17 10l3 3-3 3' ),
			'auto'      => array( 'اصلاح خودکار', 'M13 2L4 14h7l-1 8 9-12h-7z' ),
			'slug'      => array( 'نامک و تصاویر', 'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1' ),
		);
		return $tabs;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation, validated against tabs() below
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'dashboard';
		}
		$this->filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'issues'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filtering
		if ( ! in_array( $this->filter, array( 'issues', 'score', 'alt', 'slug', 'all' ), true ) ) {
			$this->filter = 'issues';
		}
		$summary      = MATNBAN_Report::summary();
		?>
		<div class="wrap matnban-app" id="matnban-app" dir="rtl" data-tab="<?php echo esc_attr( $tab ); ?>">
			<header class="matnban-header">
				<div class="matnban-brand">
					<span class="matnban-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 11h10M4 16h16M4 21h10"/><path d="M17 9l3 3-6 6h-3v-3z"/></svg></span>
					<div>
						<h1>متن‌بان <span class="matnban-ver">نسخهٔ <?php echo esc_html( self::fa( MATNBAN_VERSION ) ); ?></span></h1>
						<p>دستیار سئوی محتوای فارسی — مکمل Yoast و Rank Math</p>
					</div>
				</div>
				<div class="matnban-header-status" id="matnban-header-status"><?php echo $this->status_html( $summary ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</header>

			<?php settings_errors( 'matnban_messages' ); ?>

			<div class="matnban-layout">
				<nav class="matnban-nav" id="matnban-nav" aria-label="بخش‌های متن‌بان">
					<?php foreach ( $tabs as $slug => $t ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . ( 'dashboard' === $slug ? '' : '&tab=' . $slug ) ) ); ?>" data-tab="<?php echo esc_attr( $slug ); ?>" class="matnban-nav-item <?php echo $tab === $slug ? 'is-active' : ''; ?>" <?php echo $tab === $slug ? 'aria-current="page"' : ''; ?>>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo esc_attr( $t[1] ); ?>"/></svg>
							<span><?php echo esc_html( $t[0] ); ?></span>
						</a>
					<?php endforeach; ?>
					<div class="matnban-nav-foot">
						<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">نوشتهٔ جدید با متن‌بان ←</a>
					</div>
				</nav>

				<main class="matnban-main" id="matnban-main" data-tab="<?php echo esc_attr( $tab ); ?>" data-filter="<?php echo esc_attr( $this->filter ); ?>">
								<div class="notice notice-info" style="margin:0 0 14px"><p><strong>اصلاح یکجای مطالب</strong> (گروهی) ویژگی نسخهٔ کامل است — <a href="https://webbanwp.ir/fileban/matnban/" target="_blank" rel="noopener">نسخهٔ کامل متن‌بان در وب‌بان</a>. در نسخهٔ رایگان، هر مطلب را در ویرایشگر خودش با دکمهٔ «اصلاح نگارش» درست کنید.</p></div>
									<?php echo $this->tab_html( $tab ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</main>
			</div>
		</div>
		<?php
	}

	/** پیل‌های وضعیت بالای صفحه (در پاسخ AJAX هم برمی‌گردد تا پس از ذخیره تازه شود) */
	protected function status_html( $summary ) {
		$out = '';
		if ( $summary && ! empty( $summary['scanned'] ) ) {
			$out .= '<span class="matnban-pill ' . ( $summary['with_issues'] ? 'is-warn' : 'is-good' ) . '">' . esc_html( self::fa( $summary['with_issues'] ) ) . ' مطلب نیازمند اصلاح</span>';
		} else {
			$out .= '<span class="matnban-pill">هنوز بررسی نشده</span>';
		}
		$out .= '<span class="matnban-pill">نسخهٔ رایگان</span>';
		return $out;
	}

	/** محتوای یک تب را (بدون پوسته) رندر می‌کند */
	protected function tab_html( $tab ) {
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'dashboard';
		}
		$s       = Matnban::get_settings();
		$summary = MATNBAN_Report::summary();
		$method  = 'tab_' . $tab;
		ob_start();
		$this->$method( $s, $summary );
		return (string) ob_get_clean();
	}

	/** بستهٔ پاسخ AJAX برای جابه‌جایی تب / ذخیرهٔ تنظیمات */
	protected function payload( $tab ) {
		$tabs = self::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'dashboard';
		}
		$title = 'dashboard' === $tab ? 'متن‌بان' : 'متن‌بان — ' . $tabs[ $tab ][0];
		return array(
			'tab'    => $tab,
			'filter' => $this->filter,
			'html'   => $this->tab_html( $tab ),
			'status' => $this->status_html( MATNBAN_Report::summary() ),
			'title'  => $title . ' ‹ ' . wp_strip_all_tags( get_bloginfo( 'name' ) ) . ' — وردپرس',
		);
	}

	/** AJAX: بارگذاری تب بدون تازه‌سازی صفحه */
	public function ajax_tab() {
		check_ajax_referer( 'matnban_admin_tab', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی ندارید.', 403 );
		}
		$tab          = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'dashboard';
		$this->filter = isset( $_POST['filter'] ) && '' !== $_POST['filter'] ? sanitize_key( wp_unslash( $_POST['filter'] ) ) : 'issues';
		if ( ! in_array( $this->filter, array( 'issues', 'score', 'alt', 'slug', 'all' ), true ) ) {
			$this->filter = 'issues';
		}
		wp_send_json_success( $this->payload( $tab ) );
	}

	/** AJAX: ذخیرهٔ فرم تنظیمات همان تب (همان پاک‌سازی فرم عادی، بدون تازه‌سازی صفحه) */
	public function ajax_save_settings() {
		check_ajax_referer( 'matnban_admin_tab', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'دسترسی ندارید.', 403 );
		}
		$input = isset( $_POST[ MATNBAN_OPTION ] ) ? wp_unslash( $_POST[ MATNBAN_OPTION ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitize() پاک‌سازی می‌کند
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$clean = $this->sanitize( $input );
		update_option( MATNBAN_OPTION, $clean );
		$tab          = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'dashboard';
		$this->filter = isset( $_POST['filter'] ) && '' !== $_POST['filter'] ? sanitize_key( wp_unslash( $_POST['filter'] ) ) : 'issues';
		$data         = $this->payload( $tab );
		$data['message'] = 'تنظیمات ذخیره شد.';
		wp_send_json_success( $data );
	}

	protected function toggle( $name, $checked, $label, $desc = '', $value = '1' ) {
		?>
		<label class="matnban-toggle">
			<input type="checkbox" name="<?php echo esc_attr( MATNBAN_OPTION . '[' . $name . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $checked ); ?>>
			<span class="matnban-toggle-track" aria-hidden="true"></span>
			<span class="matnban-toggle-text"><strong><?php echo esc_html( $label ); ?></strong><?php if ( $desc ) : ?><small><?php echo wp_kses_post( $desc ); ?></small><?php endif; ?></span>
		</label>
		<?php
	}

	protected function form_open( $section ) {
		echo '<form method="post" action="options.php" class="matnban-form">';
		settings_fields( 'matnban_settings_group' );
		echo '<input type="hidden" name="' . esc_attr( MATNBAN_OPTION ) . '[_section]" value="' . esc_attr( $section ) . '">';
	}

	protected function form_close() {
		echo '<div class="matnban-savebar"><button type="submit" class="button button-primary button-hero">ذخیرهٔ تنظیمات</button><span class="matnban-savebar-note">تغییرات فقط برای همین بخش ذخیره می‌شود.</span></div></form>';
	}

	protected function card_open( $title, $desc = '', $extra_class = '' ) {
		echo '<section class="matnban-card ' . esc_attr( $extra_class ) . '"><header class="matnban-card-head"><h2>' . esc_html( $title ) . '</h2>';
		if ( $desc ) {
			echo '<p>' . wp_kses_post( $desc ) . '</p>';
		}
		echo '</header><div class="matnban-card-body">';
	}

	protected function card_close() {
		echo '</div></section>';
	}

	protected function tab_dashboard( $s, $summary ) {
		$active_rules = count( array_filter( (array) $s['rules'] ) );
		$features     = array(
			array( 'قواعد نگارش', self::fa( $active_rules ) . ' قاعده فعال', 'rules', true ),
			array( 'اصلاح هنگام ذخیره', $s['auto_fix_on_save'] ? 'روشن' : 'خاموش', 'auto', (bool) $s['auto_fix_on_save'] ),
			array( 'اصلاح هنگام نمایش', $s['fix_on_display'] ? 'روشن' : 'خاموش', 'auto', (bool) $s['fix_on_display'] ),
			array( 'چینش متن', array( '' => 'پیش‌فرض قالب', 'justify' => 'تراز دوطرفه', 'right' => 'راست‌چین', 'center' => 'وسط‌چین', 'left' => 'چپ‌چین' )[ $s['text_align'] ], 'auto', '' !== $s['text_align'] ),
			array( 'سئوی تصاویر', ( $s['media_rename'] || $s['media_alt'] ) ? 'روشن' : 'خاموش', 'slug', (bool) ( $s['media_rename'] || $s['media_alt'] ) ),
			array( 'پنل خوانایی', $s['readability_panel'] ? 'روشن' : 'خاموش', 'auto', (bool) $s['readability_panel'] ),
		);
		?>
		<div class="matnban-stats-grid">
			<?php if ( $summary && ! empty( $summary['scanned'] ) ) :
				$pct = round( $summary['with_issues'] / $summary['scanned'] * 100 );
				?>
				<div class="matnban-stat <?php echo $pct > 30 ? 'is-bad' : ( $pct > 0 ? 'is-warn' : 'is-good' ); ?>">
					<span class="matnban-stat-v"><?php echo esc_html( self::fa( $pct ) ); ?>٪</span>
					<span class="matnban-stat-k">مطالب دارای خطای نگارشی</span>
					<small><?php echo esc_html( self::fa( $summary['with_issues'] ) ); ?> از <?php echo esc_html( self::fa( $summary['scanned'] ) ); ?> مطلب</small>
				</div>
				<div class="matnban-stat <?php echo $summary['avg_score'] >= 80 ? 'is-good' : ( $summary['avg_score'] >= 55 ? 'is-warn' : 'is-bad' ); ?>">
					<span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['avg_score'] ) ); ?></span>
					<span class="matnban-stat-k">میانگین امتیاز خوانایی</span>
					<small>از ۱۰۰</small>
				</div>
				<div class="matnban-stat <?php echo $summary['no_alt'] ? 'is-warn' : 'is-good'; ?>">
					<span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['no_alt'] ) ); ?></span>
					<span class="matnban-stat-k">تصویر بدون متن جایگزین</span>
					<small>در متن مطالب</small>
				</div>
				<div class="matnban-stat <?php echo $summary['slug_fa'] ? 'is-warn' : 'is-good'; ?>">
					<span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['slug_fa'] ) ); ?></span>
					<span class="matnban-stat-k">مطلب با نامک فارسی</span>
					<small>آدرس‌های غیرانگلیسی</small>
				</div>
			<?php else : ?>
				<div class="matnban-stat matnban-stat-cta">
					<span class="matnban-stat-k">هنوز مطالب سایت بررسی نشده‌اند</span>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=matnban&tab=report' ) ); ?>">شروع بررسی سایت</a>
					<small>در چند ثانیه می‌فهمید چند درصد مطالب قدیمی مشکل نیم‌فاصله و نگارش دارند.</small>
				</div>
			<?php endif; ?>
		</div>

		<div class="matnban-grid-2">
			<?php $this->card_open( 'وضعیت قابلیت‌ها', 'یک نگاه به تنظیمات فعلی. برای تغییر روی هر مورد بزنید.' ); ?>
			<ul class="matnban-feature-list">
				<?php foreach ( $features as $f ) : ?>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=matnban&tab=' . $f[2] ) ); ?>"><span class="matnban-dot <?php echo $f[3] ? 'is-on' : ''; ?>"></span><span><?php echo esc_html( $f[0] ); ?></span><b><?php echo esc_html( $f[1] ); ?></b></a></li>
				<?php endforeach; ?>
			</ul>
			<?php $this->card_close(); ?>

			<?php $this->card_open( 'شروع سریع', 'سه قدم برای بیشترین نتیجه در کمترین زمان' ); ?>
			<ol class="matnban-steps">
				<li><strong>بررسی سایت</strong><span>از بخش «گزارش سایت» همهٔ مطالب را یک‌بار بررسی کنید تا بدانید کجا ایستاده‌اید.</span></li>
				<li><strong>اصلاح گروهی</strong><span>ویژگی نسخهٔ کامل است؛ در نسخهٔ رایگان هر مطلب را در ویرایشگر خودش اصلاح کنید.</span></li>
				<li><strong>خودکار کنید</strong><span>«اصلاح هنگام ذخیره» را روشن کنید تا مطالب جدید از همان ابتدا تمیز ذخیره شوند.</span></li>
			</ol>
			<?php $this->card_close(); ?>
		</div>

		<?php if ( $summary && ! empty( $summary['rules'] ) ) : ?>
			<?php $this->card_open( 'پرتکرارترین خطاهای نگارشی سایت', 'بر اساس آخرین بررسی — ' . self::ago( $summary['time'] ) ); ?>
			<?php $this->bars( $summary['rules'] ); ?>
			<?php $this->card_close(); ?>
		<?php endif; ?>
		<?php
	}

	protected function bars( $rules, $limit = 8 ) {
		$labels = MATNBAN_Normalizer::rule_labels();
		$rules  = array_slice( $rules, 0, $limit, true );
		$max    = max( 1, max( $rules ) );
		echo '<div class="matnban-bars">';
		foreach ( $rules as $rule => $n ) {
			$w = max( 2, round( $n / $max * 100 ) );
			echo '<div class="matnban-bar"><span class="matnban-bar-label">' . esc_html( isset( $labels[ $rule ] ) ? $labels[ $rule ] : $rule ) . '</span><span class="matnban-bar-track"><i style="width:' . esc_attr( $w ) . '%"></i></span><b>' . esc_html( self::fa( $n ) ) . '</b></div>';
		}
		echo '</div>';
	}

	protected function tab_report( $s, $summary ) {
		$total = MATNBAN_Report::total_posts();
		$csv   = wp_nonce_url( admin_url( 'admin-ajax.php?action=matnban_report_csv' ), 'matnban_report_csv' );
		?>
		<?php $this->card_open( 'بررسی مطالب سایت', 'همهٔ مطالب انواع انتخاب‌شده (' . esc_html( implode( '، ', MATNBAN_Report::post_types() ) ) . ') از نظر خطاهای نگارشی، خوانایی، نامک و تصاویر بررسی می‌شوند. هیچ تغییری در مطالب داده نمی‌شود.', 'matnban-card-scan' ); ?>
		<div class="matnban-scan-row">
			<button type="button" class="button button-primary button-hero" id="matnban-scan-start" data-total="<?php echo esc_attr( $total ); ?>"><?php echo $summary ? 'بررسی مجدد' : 'شروع بررسی'; ?> (<?php echo esc_html( self::fa( $total ) ); ?> مطلب)</button>
			<div class="matnban-progress" id="matnban-scan-progress" hidden><div class="matnban-progress-bar"><i></i></div><span class="matnban-progress-text"></span></div>
			<?php if ( $summary && ! empty( $summary['scanned'] ) ) : ?>
				<span class="description">آخرین بررسی: <?php echo esc_html( self::ago( $summary['time'] ) ); ?> — <?php echo esc_html( self::fa( $summary['scanned'] ) ); ?> مطلب</span>
				<a class="button" href="<?php echo esc_url( $csv ); ?>">خروجی CSV</a>
			<?php endif; ?>
		</div>
		<?php $this->card_close(); ?>

		<?php if ( $summary && ! empty( $summary['scanned'] ) ) :
			$pct = round( $summary['with_issues'] / $summary['scanned'] * 100 );
			?>
			<div class="matnban-stats-grid" id="matnban-report-stats">
				<div class="matnban-stat <?php echo $pct > 30 ? 'is-bad' : ( $pct > 0 ? 'is-warn' : 'is-good' ); ?>"><span class="matnban-stat-v"><?php echo esc_html( self::fa( $pct ) ); ?>٪</span><span class="matnban-stat-k">مطالب دارای خطای نگارشی</span><small><?php echo esc_html( self::fa( $summary['issues'] ) ); ?> خطا در <?php echo esc_html( self::fa( $summary['with_issues'] ) ); ?> مطلب</small></div>
				<div class="matnban-stat"><span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['score_good'] ) ); ?> / <?php echo esc_html( self::fa( $summary['score_warn'] ) ); ?> / <?php echo esc_html( self::fa( $summary['score_bad'] ) ); ?></span><span class="matnban-stat-k">خوانایی: عالی / قابل قبول / ضعیف</span><small>میانگین <?php echo esc_html( self::fa( $summary['avg_score'] ) ); ?> از ۱۰۰</small></div>
				<div class="matnban-stat <?php echo $summary['no_alt'] ? 'is-warn' : 'is-good'; ?>"><span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['no_alt'] ) ); ?></span><span class="matnban-stat-k">تصویر بدون alt</span><small>در متن مطالب</small></div>
				<div class="matnban-stat <?php echo $summary['no_keyword'] ? 'is-warn' : 'is-good'; ?>"><span class="matnban-stat-v"><?php echo esc_html( self::fa( $summary['no_keyword'] ) ); ?></span><span class="matnban-stat-k">مطلب بدون کلمهٔ کلیدی</span><small>در Yoast / Rank Math</small></div>
			</div>

			<div class="matnban-grid-2">
				<?php $this->card_open( 'خطاهای نگارشی به تفکیک نوع', 'مجموع موارد در کل سایت' ); ?>
				<?php empty( $summary['rules'] ) ? print( '<p class="matnban-empty">هیچ خطای نگارشی پیدا نشد. آفرین!</p>' ) : $this->bars( $summary['rules'], 16 ); ?>
				<?php $this->card_close(); ?>

				<?php $this->card_open( 'بررسی خودکار', 'گزارش به‌صورت دوره‌ای تازه شود' ); ?>
				<?php $this->form_open( 'report' ); ?>
				<div class="matnban-radio-list">
					<?php foreach ( array( 'weekly' => 'هفتگی', 'daily' => 'روزانه', 'off' => 'خاموش' ) as $v => $l ) : ?>
						<label><input type="radio" name="<?php echo esc_attr( MATNBAN_OPTION ); ?>[report_schedule]" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s['report_schedule'], $v ); ?>> <?php echo esc_html( $l ); ?></label>
					<?php endforeach; ?>
				</div>
				<?php $this->toggle( 'report_email', $s['report_email'], 'ایمیل خلاصهٔ گزارش به مدیر', 'به ' . esc_html( get_option( 'admin_email' ) ) . ' — فقط وقتی بررسی خودکار روشن باشد.' ); ?>
				<p class="description">هر مطلب هنگام ذخیره هم به‌صورت خودکار دوباره بررسی می‌شود.</p>
				<?php $this->form_close(); ?>
				<?php $this->card_close(); ?>
			</div>

			<?php
			$filters = array( 'issues' => 'خطای نگارشی', 'score' => 'خوانایی ضعیف', 'alt' => 'تصویر بدون alt', 'slug' => 'نامک فارسی', 'all' => 'همه' );
			$filter  = isset( $filters[ $this->filter ] ) ? $this->filter : 'issues';
			$rows    = MATNBAN_Report::problem_posts( array( 'filter' => $filter ) );
			$this->card_open( 'مطالب نیازمند توجه', 'روی عنوان بزنید تا مطلب در ویرایشگر خودش باز شود و با دکمهٔ «اصلاح نگارش» درست شود.' );
			?>
			<div class="matnban-filters">
				<?php foreach ( $filters as $k => $l ) : ?>
					<a class="matnban-chip <?php echo $filter === $k ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=matnban&tab=report&filter=' . $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php if ( empty( $rows ) ) : ?>
				<p class="matnban-empty">موردی در این دسته نیست.</p>
			<?php else : ?>
				<table class="widefat striped matnban-table">
					<thead><tr>
						<th>عنوان</th><th>نوع</th><th>خطای نگارشی</th><th>خوانایی</th><th>تصویر بدون alt</th><th>نامک</th><th>کلمهٔ کلیدی</th><th></th>
					</tr></thead>
					<tbody>
					<?php foreach ( array_slice( $rows, 0, 100 ) as $r ) :
						$p = get_post( $r['id'] );
						if ( ! $p ) {
							continue;
						}
						$pt = get_post_type_object( $p->post_type );
						?>
						<tr data-id="<?php echo esc_attr( $r['id'] ); ?>">
							<td><a href="<?php echo esc_url( get_edit_post_link( $r['id'] ) ); ?>"><strong><?php echo esc_html( $p->post_title ?: '(بدون عنوان)' ); ?></strong></a><br><small class="description"><?php echo esc_html( self::fa( $r['words'] ) ); ?> کلمه · <?php echo 'publish' === $p->post_status ? 'منتشرشده' : 'پیش‌نویس'; ?></small></td>
							<td><?php echo esc_html( $pt ? $pt->labels->singular_name : $p->post_type ); ?></td>
							<td class="matnban-td-issues"><?php echo $r['issues'] ? '<span class="matnban-badge is-bad">' . esc_html( self::fa( $r['issues'] ) ) . '</span>' : '<span class="matnban-badge is-good">۰</span>'; ?></td>
							<td><span class="matnban-badge <?php echo $r['score'] >= 80 ? 'is-good' : ( $r['score'] >= 55 ? 'is-warn' : 'is-bad' ); ?>"><?php echo esc_html( self::fa( $r['score'] ) ); ?></span></td>
							<td><?php echo $r['no_alt'] ? '<span class="matnban-badge is-warn">' . esc_html( self::fa( $r['no_alt'] ) ) . ' از ' . esc_html( self::fa( $r['images'] ) ) . '</span>' : ( $r['images'] ? '<span class="matnban-badge is-good">✓</span>' : '—' ); ?></td>
							<td><?php echo $r['slug_fa'] ? '<span class="matnban-badge is-warn">فارسی</span>' : '<span class="matnban-badge is-good">انگلیسی</span>'; ?></td>
							<td><?php echo $r['keyword'] ? '<span class="matnban-badge is-good">دارد</span>' : '<span class="matnban-badge">ندارد</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( count( $rows ) > 100 ) : ?><p class="description">۱۰۰ مورد اول نمایش داده شد. فهرست کامل را از «خروجی CSV» بگیرید.</p><?php endif; ?>
			<?php endif; ?>
			<?php $this->card_close(); ?>
		<?php else : ?>
			<?php $this->card_open( 'گزارش چه چیزهایی را نشان می‌دهد؟' ); ?>
			<ul class="matnban-bullets">
				<li>چند درصد مطالب قدیمی هنوز خطای نیم‌فاصله، حروف عربی یا علائم نگارشی دارند — به تفکیک نوع خطا</li>
				<li>امتیاز خوانایی هر مطلب و میانگین کل سایت</li>
				<li>تصاویری که متن جایگزین ندارند و مطالبی که نامک فارسی دارند</li>
				<li>مطالبی که در Yoast / Rank Math کلمهٔ کلیدی ندارند</li>
				<li>اصلاح تکی یا گروهی مطالب مشکل‌دار، خروجی CSV برای مدیر سئو و بررسی خودکار هفتگی</li>
			</ul>
			<?php $this->card_close(); ?>
		<?php endif; ?>
		<?php
	}

	protected function tab_rules( $s ) {
		$labels = MATNBAN_Normalizer::rule_labels();
		$groups = array(
			'نیم‌فاصله'      => array( 'prefix_mi', 'prefix_bi', 'suffix_ha', 'suffix_tar', 'suffix_verb', 'ezafe_ye', 'zwnj_cleanup' ),
			'حروف و ارقام'   => array( 'arabic_chars', 'arabic_digits', 'latin_digits', 'kashida', 'diacritics' ),
			'علائم و فاصله'  => array( 'punctuation', 'quotes', 'percent', 'spaces', 'direction_marks' ),
		);
		$this->form_open( 'rules' );
		foreach ( $groups as $title => $keys ) {
			$this->card_open( $title );
			echo '<div class="matnban-toggle-grid">';
			foreach ( $keys as $k ) {
				if ( isset( $labels[ $k ] ) ) {
					$this->toggle( 'rules][' . $k, ! empty( $s['rules'][ $k ] ), $labels[ $k ] );
				}
			}
			echo '</div>';
			$this->card_close();
		}
		echo '<p class="description">این قواعد در دکمهٔ «اصلاح نگارش» ویرایشگر، اصلاح خودکار و آزمایش سریع استفاده می‌شوند. «تبدیل ارقام انگلیسی» و «حذف اعراب» به‌صورت پیش‌فرض خاموش‌اند چون همیشه مطلوب نیستند.</p>';
		$this->form_close();
	}

	protected function tab_auto( $s ) {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		$this->form_open( 'auto' );

		$this->card_open( 'اصلاح خودکار', 'انتخاب کنید متن‌بان چه زمانی خودش دست‌به‌کار شود.' );
		echo '<div class="matnban-toggle-list">';
		$this->toggle( 'auto_fix_on_save', $s['auto_fix_on_save'], 'اصلاح هنگام ذخیره', 'متن مطلب هنگام ذخیره/انتشار خودکار اصلاح و در دیتابیس ذخیره می‌شود. کدها، شورت‌کدها و بلوک‌های گوتنبرگ دست نمی‌خورند.' );
		$this->toggle( 'auto_fix_title', $s['auto_fix_title'], 'عنوان و خلاصه هم اصلاح شوند' );
		$this->toggle( 'fix_on_display', $s['fix_on_display'], 'اصلاح هنگام نمایش', 'متن هنگام نمایش به بازدیدکننده اصلاح می‌شود، بدون هیچ تغییری در دیتابیس. خروجی شورت‌کدها و بلوک‌های زندهٔ افزونه‌های دیگر را شامل نمی‌شود؛ با افزونه‌های کش سازگار است.' );
		$this->toggle( 'fix_comments', $s['fix_comments'], 'اصلاح دیدگاه‌های کاربران هنگام نمایش', 'مستقل از کلید بالا کار می‌کند؛ فقط متن دیدگاه‌ها هنگام نمایش به بازدیدکننده تمیز می‌شود، بدون هیچ تغییری در دیتابیس.' );
		$this->toggle( 'readability_panel', $s['readability_panel'], 'پنل «خوانایی فارسی» در ویرایشگر', 'در گوتنبرگ به‌صورت نوار کناری و در ویرایشگر کلاسیک به‌صورت جعبه زیر متن. خاموش‌کردن آن، دکمهٔ «اصلاح نگارش» را هم از ویرایشگر برمی‌دارد.' );
		echo '</div>';
		$this->card_close();

		$this->card_open( 'نوع مطالب', 'متن‌بان روی کدام نوع محتوا کار کند؟' );
		echo '<div class="matnban-toggle-grid">';
		foreach ( $types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			$this->toggle( 'post_types][', in_array( $pt->name, (array) $s['post_types'], true ), $pt->labels->name, '', $pt->name );
		}
		echo '</div>';
		$this->card_close();

		$this->card_open( 'چینش متن مطالب', 'فقط ظاهر تغییر می‌کند؛ محتوا دست نمی‌خورد. عنوان‌ها، جدول‌ها و کدها مستثنا هستند.' );
		$aligns = array(
			''        => array( 'پیش‌فرض قالب', 'تغییری داده نشود', 'M4 6h16M4 12h16M4 18h16' ),
			'justify' => array( 'تراز دوطرفه', 'مثل Word؛ لبهٔ چپ سطرها هم صاف می‌شود', 'M4 6h16M4 10h16M4 14h16M4 18h16' ),
			'right'   => array( 'راست‌چین', 'همهٔ پاراگراف‌ها از راست', 'M4 6h16M8 10h12M4 14h16M10 18h10' ),
			'center'  => array( 'وسط‌چین', 'مناسب متن‌های کوتاه', 'M4 6h16M7 10h10M4 14h16M8 18h8' ),
			'left'    => array( 'چپ‌چین', 'برای سایت‌های دوزبانه', 'M4 6h16M4 10h12M4 14h16M4 18h10' ),
		);
		echo '<div class="matnban-align-grid">';
		foreach ( $aligns as $v => $a ) {
			?>
			<label class="matnban-align-card">
				<input type="radio" name="<?php echo esc_attr( MATNBAN_OPTION ); ?>[text_align]" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s['text_align'], $v ); ?>>
				<span class="matnban-align-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="<?php echo esc_attr( $a[2] ); ?>"/></svg><strong><?php echo esc_html( $a[0] ); ?></strong><small><?php echo esc_html( $a[1] ); ?></small></span>
			</label>
			<?php
		}
		echo '</div><div class="matnban-toggle-list" style="margin-top:14px">';
		$this->toggle( 'align_editor', $s['align_editor'], 'همین چینش داخل ویرایشگر هم نمایش داده شود' );
		$this->toggle( 'align_force', $s['align_force'], 'اعمال اجباری', 'پاراگراف‌های وسط‌چین و چپ‌چین دستی هم همین چینش را بگیرند.' );
		$this->toggle( 'align_scope', 'all' === $s['align_scope'], 'روی همهٔ متن‌های سایت', 'نه فقط متن مطالب — اگر قالب شما خاص است و چینش اعمال نشد، این را بزنید.', 'all' );
		echo '</div>';
		echo '<p class="matnban-field"><label for="matnban-justify-selector">انتخابگر CSS اختصاصی برای قالب‌های خاص (اختیاری)</label><input type="text" id="matnban-justify-selector" name="' . esc_attr( MATNBAN_OPTION ) . '[justify_selector]" value="' . esc_attr( $s['justify_selector'] ) . '" class="regular-text code" dir="ltr" placeholder=".my-theme-content"></p>';
		$this->card_close();

		$this->form_close();
	}

	protected function tab_slug( $s ) {
		$this->form_open( 'slug' );

		$this->card_open( 'سئوی تصاویر فارسی' );
		echo '<div class="matnban-toggle-list">';
		$this->toggle( 'media_rename', $s['media_rename'], 'تبدیل نام فایل‌های فارسی به انگلیسی خوانا هنگام آپلود', 'مثال: «معرفی-افزونه.jpg» ← <code>intro-plugin.jpg</code>. نام فارسی روی برخی هاست‌ها خراب می‌شود و برای سئو مناسب نیست.' );
		$this->toggle( 'media_alt', $s['media_alt'], 'پر کردن خودکار متن جایگزین (alt) و عنوان تصویر', 'از روی نام اصلی فایل، به فارسی تمیز.' );
		echo '</div>';
		$this->card_close();
		$this->form_close();
	}


	protected function tab_test( $s ) {
		$this->card_open( 'آزمایش سریع', 'متنی را اینجا بچسبانید تا ببینید متن‌بان با تنظیمات فعلی چه تغییراتی می‌دهد. هیچ چیزی ذخیره نمی‌شود.' );
		?>
		<textarea id="matnban-test-input" rows="7" class="large-text" placeholder="مثال: من می روم و كتاب ها را می خوانم , باشه ?"></textarea>
		<p class="matnban-inline-actions"><button class="button button-primary" id="matnban-test-run">اصلاح کن</button> <button class="button" id="matnban-test-sample" type="button">نمونه‌متن</button> <span class="spinner"></span></p>
		<div id="matnban-test-result" hidden>
			<div class="matnban-test-grid">
				<div>
					<h3>متن اصلاح‌شده <span class="matnban-badge is-good" id="matnban-test-total"></span></h3>
					<textarea id="matnban-test-output" rows="7" class="large-text" readonly></textarea>
					<p><button type="button" class="button button-small" id="matnban-test-copy">کپی</button></p>
				</div>
				<div>
					<h3>جزئیات</h3>
					<ul id="matnban-test-report" class="matnban-report-list"></ul>
					<p><strong>خوانایی:</strong> <span id="matnban-test-score"></span></p>
				</div>
			</div>
		</div>
		<?php
		$this->card_close();
	}
}
