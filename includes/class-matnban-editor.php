<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Editor {
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_ajax_matnban_fix_text', array( $this, 'ajax_fix' ) );
		add_action( 'wp_ajax_matnban_analyze', array( $this, 'ajax_analyze' ) );
	}

	private function enabled_for_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'post' !== $screen->base ) {
			return false;
		}
		return MATNBAN_Content::type_enabled( $screen->post_type );
	}

	public function metabox( $post_type ) {
		if ( ! Matnban::get( 'readability_panel' ) ) {
			return;
		}

		if ( self::is_block_editor() ) {
			return;
		}
		foreach ( (array) Matnban::get( 'post_types', array() ) as $pt ) {
			add_meta_box(
				'matnban-readability',
				'متن‌بان — تحلیل خوانایی فارسی',
				array( $this, 'render_metabox' ),
				$pt,
				'normal',
				'high'
			);
		}
	}

	public function render_metabox( $post ) {
		?>
		<div class="matnban-box" dir="rtl">
			<div class="matnban-box-head">
				<div class="matnban-score-wrap">
					<div class="matnban-score" id="matnban-score" data-grade="none"><span>—</span></div>
					<div class="matnban-score-meta">
						<strong id="matnban-grade-label">هنوز تحلیل نشده</strong>
						<small id="matnban-stats"></small>
					</div>
				</div>
				<div class="matnban-actions">
					<button type="button" class="button button-primary" id="matnban-btn-fix">✎ اصلاح نگارش</button>
					<button type="button" class="button" id="matnban-btn-analyze">↻ تحلیل مجدد</button>
					<span class="spinner"></span>
				</div>
			</div>
			<div class="matnban-kw-row">
				<label>کلمهٔ کلیدی هدف: <input type="text" id="matnban-keyword" value="<?php echo esc_attr( self::detect_keyword( $post->ID ) ); ?>" placeholder="خودکار از Yoast/Rank Math خوانده می‌شود"></label>
				<span class="matnban-slug-row" style="opacity:.6">نامک هوشمند: ویژگی <a href="https://webbanwp.ir/fileban/matnban/" target="_blank" rel="noopener">نسخهٔ کامل</a></span>
			</div>
			<ul class="matnban-checks" id="matnban-checks"><li class="matnban-muted">برای شروع روی «تحلیل مجدد» بزنید. تحلیل هنگام تایپ نیز خودکار به‌روز می‌شود.</li></ul>
			<div class="matnban-fix-report" id="matnban-fix-report" style="display:none"></div>
			<div class="matnban-ai-box" style="opacity:.65"><div class="matnban-ai-head"><strong>دستیار هوش مصنوعی</strong><small>بازنویسی، خلاصه‌سازی و غلط‌یابی هوشمند در <a href="https://webbanwp.ir/fileban/matnban/" target="_blank" rel="noopener">نسخهٔ کامل متن‌بان</a> فعال می‌شود.</small></div></div>
		</div>
		<?php
	}

	public static function detect_keyword( $post_id ) {
		$kw = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
		if ( '' === $kw ) {
			$kw = get_post_meta( $post_id, 'rank_math_focus_keyword', true );
			if ( $kw && false !== strpos( $kw, ',' ) ) {
				$kw = trim( explode( ',', $kw )[0] );
			}
		}
		return (string) $kw;
	}

	public static function detect_meta_desc( $post_id ) {
		$d = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( '' === $d ) {
			$d = get_post_meta( $post_id, 'rank_math_description', true );
		}
		return (string) $d;
	}

	public static function is_block_editor() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor();
	}

	public function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $this->enabled_for_screen() ) {
			return;
		}
		if ( ! Matnban::get( 'readability_panel' ) ) {
			return;
		}
		$post_id = get_the_ID();
		wp_enqueue_style( 'matnban-editor', MATNBAN_URL . 'assets/css/editor.css', array(), MATNBAN_VERSION );
		wp_enqueue_script( 'matnban-editor', MATNBAN_URL . 'assets/js/editor.js', array( 'jquery' ), MATNBAN_VERSION, true );

		if ( self::is_block_editor() ) {
			wp_enqueue_script(
				'matnban-sidebar',
				MATNBAN_URL . 'assets/js/sidebar.js',
				array( 'matnban-editor', 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-dom-ready' ),
				MATNBAN_VERSION,
				true
			);
		}

		wp_localize_script( 'matnban-editor', 'MATNBAN_EDITOR', array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'matnban_editor' ),
			'postId'  => $post_id,
			'keyword' => $post_id ? self::detect_keyword( $post_id ) : '',
			'i18n'   => array(
				'fixed'    => 'اصلاح شد',
				'nochange' => 'متن از نظر نگارشی سالم است. چیزی برای اصلاح نبود.',
				'error'    => 'خطا در ارتباط با سرور.',
				'fixes'    => 'اصلاح انجام شد',
				'aiSelect' => 'برای ساده‌نویسی، بخشی از متن را انتخاب کنید (یا کل متن استفاده می‌شود).',
				'replace'  => 'جایگزین کن',
				'insert'   => 'درج در متن',
				'copy'     => 'کپی',
				'useTitle' => 'استفاده به‌عنوان عنوان',
				'useMeta'  => 'کپی برای توضیحات متا',
				'copied'   => 'کپی شد',
			),
		) );
	}

	public function ajax_fix() {
		check_ajax_referer( 'matnban_editor', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'دسترسی ندارید.' );
		}
		$content = isset( $_POST['content'] ) ? wp_unslash( (string) $_POST['content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- kses applied below, capability-aware (mirrors core save behavior)
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$content = wp_kses_post( $content ); // mirror of core save behavior
		}
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['title'] ) ) : '';

		$normalizer = Matnban::normalizer();
		$normalizer->reset_stats();
		$fixed_content = Matnban::walker()->process( $content );
		$fixed_title   = $normalizer->normalize( $title );
		$stats         = $normalizer->get_stats();
		$labels        = MATNBAN_Normalizer::rule_labels();
		$report        = array();
		foreach ( $stats['rules'] as $rule => $n ) {
			$report[] = array( 'label' => isset( $labels[ $rule ] ) ? $labels[ $rule ] : $rule, 'count' => $n );
		}

		wp_send_json_success( array(
			'content' => $fixed_content,
			'title'   => $fixed_title,
			'total'   => $stats['total'],
			'report'  => $report,
		) );
	}

	public function ajax_analyze() {
		check_ajax_referer( 'matnban_editor', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'دسترسی ندارید.' );
		}
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( (string) $_POST['content'] ) ) : '';
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$meta    = $post_id ? self::detect_meta_desc( $post_id ) : '';

		if ( function_exists( 'do_blocks' ) && false !== strpos( $content, '<!-- wp:' ) ) {
			$content = do_blocks( $content );
		}

		$r = new MATNBAN_Readability( Matnban::normalizer() );
		wp_send_json_success( $r->analyze( $content, $title, $keyword, $meta ) );
	}

}
