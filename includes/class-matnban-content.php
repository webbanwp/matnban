<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MATNBAN_Content {
	public function __construct() {
		add_filter( 'wp_insert_post_data', array( $this, 'on_save' ), 20, 2 );

		if ( Matnban::get( 'fix_on_display' ) ) {
			add_filter( 'the_content', array( $this, 'on_display' ), 5 );
			add_filter( 'the_title', array( $this, 'on_display_title' ), 5, 2 );
			add_filter( 'the_excerpt', array( $this, 'on_display' ), 5 );
			add_filter( 'widget_text_content', array( $this, 'on_display' ), 5 );
		}
		if ( Matnban::get( 'fix_comments' ) ) {
			add_filter( 'comment_text', array( $this, 'on_display' ), 5 );
		}

		if ( self::align_mode() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'align_css' ), 999 );
			if ( Matnban::get( 'align_editor' ) ) {
				add_filter( 'block_editor_settings_all', array( $this, 'align_block_editor' ), 20 );
				add_filter( 'tiny_mce_before_init', array( $this, 'align_tinymce' ) );
			}
		}
	}

	public static function align_mode() {
		$m = (string) Matnban::get( 'text_align', '' );
		return in_array( $m, array( 'justify', 'right', 'center', 'left' ), true ) ? $m : '';
	}

	public static function keep_selector( $mode ) {
		if ( Matnban::get( 'align_force' ) ) {
			return '';
		}
		$keep = array( 'center', 'left' );
		$keep = array_diff( $keep, array( $mode ) );
		$out  = '';
		foreach ( $keep as $k ) {
			$out .= ':not(.has-text-align-' . $k . '):not([style*="text-align: ' . $k . '"]):not([style*="text-align:' . $k . '"])';
		}
		return $out;
	}

	public static function align_containers() {
		if ( 'all' === Matnban::get( 'align_scope' ) ) {
			$c = array( 'body' );
		} else {
			$c = array(
				'.entry-content',
				'.post-content',
				'.wp-block-post-content',
				'.wp-block-post-excerpt',
				'.type-post',
				'.type-page',
				'.hentry',
				'.elementor-widget-theme-post-content',
				'.elementor-widget-text-editor',
				'.woocommerce-product-details__short-description',
				'.woocommerce-Tabs-panel--description',
				'.comment-content',
			);
		}
		return array_merge( $c, self::custom_selectors() );
	}

	public static function custom_selectors() {
		$custom = trim( (string) Matnban::get( 'justify_selector', '' ) );
		$out    = array();
		if ( '' !== $custom ) {
			foreach ( preg_split( '/\s*,\s*/', $custom ) as $x ) {
				$x = trim( wp_strip_all_tags( $x ) );
				if ( '' !== $x && ! preg_match( '/[{};<>]/', $x ) ) {
					$out[] = $x;
				}
			}
		}
		return $out;
	}

	public static function align_rules( $mode, $prefix = '' ) {
		$mode = in_array( $mode, array( 'justify', 'right', 'center', 'left' ), true ) ? $mode : 'justify';

		$tags = array( 'p', 'ul:not(.wp-block-post-template) > li', 'ol:not(.wp-block-post-template) > li', 'blockquote', 'dd', 'figcaption' );
		$not  = self::keep_selector( $mode );
		$sel  = array();
		foreach ( self::align_containers() as $c ) {
			foreach ( $tags as $t ) {
				$sel[] = trim( $prefix . ' ' . $c . ' ' . $t . $not );
			}
		}

		foreach ( self::custom_selectors() as $c ) {
			$sel[] = trim( $prefix . ' ' . $c . $not );
		}
		$extra = 'justify' === $mode ? "\n\ttext-justify: inter-word;\n\t-webkit-hyphens: none;\n\thyphens: none;\n\tword-spacing: normal;" : '';
		$css   = implode( ",\n", $sel ) . " {\n\ttext-align: {$mode} !important;{$extra}\n}\n";

		$reset = array();
		foreach ( self::align_containers() as $c ) {
			foreach ( array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '.entry-title', '.wp-block-post-title', '.wp-block-post-terms', '.wp-block-post-date', '.wp-block-post-author', 'pre', 'code', 'kbd', 'table', '.wp-block-code', '.wp-block-preformatted', '.wp-block-table', '.wp-block-buttons', 'nav', '.wp-block-navigation', '.wp-block-post-template > li', '.wp-block-post-template > li > *:not(p)' ) as $t ) {
				$reset[] = trim( $prefix . ' ' . $c . ' ' . $t );
			}
		}
		$keep_h = ':not(.has-text-align-center):not(.has-text-align-left):not(.has-text-align-right):not([style*="text-align"])';
		$reset  = array_map( function ( $x ) use ( $keep_h ) {
			return preg_match( '/h[1-6]$|-title$/', $x ) ? $x . $keep_h : $x;
		}, $reset );
		$css .= implode( ",\n", $reset ) . " {\n\ttext-align: start !important;\n}\n";
		return $css;
	}

	public function align_css() {
		/* استایل از مسیر استاندارد وردپرس — نه echo مستقیم */
		wp_register_style( 'matnban-align', false, array(), MATNBAN_VERSION );
		wp_enqueue_style( 'matnban-align' );
		wp_add_inline_style( 'matnban-align', self::align_rules( self::align_mode() ) );
	}

	public static function editor_css( $mode ) {
		$mode  = in_array( $mode, array( 'justify', 'right', 'center', 'left' ), true ) ? $mode : 'justify';
		$not   = self::keep_selector( $mode );
		$extra = 'justify' === $mode ? ' text-justify: inter-word; hyphens: none;' : '';
		return ".editor-styles-wrapper p{$not}, .editor-styles-wrapper ul > li{$not}, .editor-styles-wrapper ol > li{$not}, .editor-styles-wrapper blockquote{$not}, .editor-styles-wrapper figcaption{$not} { text-align: {$mode} !important;{$extra} }\n"
			. ".editor-styles-wrapper h1, .editor-styles-wrapper h2, .editor-styles-wrapper h3, .editor-styles-wrapper h4, .editor-styles-wrapper h5, .editor-styles-wrapper h6, .editor-styles-wrapper pre, .editor-styles-wrapper code, .editor-styles-wrapper table, .editor-styles-wrapper .wp-block-code { text-align: start !important; }";
	}

	public function align_block_editor( $settings ) {
		if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
			$settings['styles'] = array();
		}
		$settings['styles'][] = array( 'css' => self::editor_css( self::align_mode() ), '__unstableType' => 'plugin', 'source' => 'matnban' );
		return $settings;
	}

	public function align_tinymce( $init ) {
		$mode  = self::align_mode();
		$extra = 'justify' === $mode ? ' text-justify: inter-word;' : '';
		$not   = Matnban::get( 'align_force' ) ? '' : ":not([style*='text-align: center']):not([style*='text-align:center']):not([style*='text-align: left']):not([style*='text-align:left'])";
		$css   = "body.mce-content-body p{$not}, body.mce-content-body li{$not}, body.mce-content-body blockquote{$not} { text-align: {$mode} !important;{$extra} } body.mce-content-body pre, body.mce-content-body code, body.mce-content-body table { text-align: start !important; }";
		$init['content_style'] = ( isset( $init['content_style'] ) ? $init['content_style'] . ' ' : '' ) . $css;
		return $init;
	}

	public static function type_enabled( $post_type ) {
		return in_array( $post_type, (array) Matnban::get( 'post_types', array() ), true );
	}

	public function on_save( $data, $postarr ) {
		if ( empty( $data['post_type'] ) || ! self::type_enabled( $data['post_type'] ) ) {
			return $data;
		}
		if ( 'auto-draft' === $data['post_status'] || wp_is_post_revision( isset( $postarr['ID'] ) ? $postarr['ID'] : 0 ) ) {
			return $data;
		}

		if ( Matnban::get( 'auto_fix_on_save' ) ) {
			$walker = Matnban::walker();

			$content = wp_unslash( $data['post_content'] );
			$fixed   = $walker->process( $content );
			if ( $fixed !== $content ) {
				$data['post_content'] = wp_slash( $fixed );
			}
			if ( Matnban::get( 'auto_fix_title' ) ) {
				$normalizer = Matnban::normalizer();
				$title      = wp_unslash( $data['post_title'] );
				$data['post_title'] = wp_slash( $normalizer->normalize( $title ) );
				$excerpt    = wp_unslash( $data['post_excerpt'] );
				$data['post_excerpt'] = wp_slash( $walker->process( $excerpt ) );
			}
		}

		return $data;
	}

	public function on_display( $content ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $content;
		}
		$post = get_post();
		if ( $post && ! self::type_enabled( $post->post_type ) && current_filter() !== 'comment_text' && current_filter() !== 'widget_text_content' ) {
			return $content;
		}
		return Matnban::walker()->process( $content );
	}

	public function on_display_title( $title, $post_id = 0 ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $title;
		}
		$post = $post_id ? get_post( $post_id ) : get_post();
		if ( ! $post_id && ! $post ) {
			return $title;
		}
		if ( $post ) {
			$type = $post->post_type;
			if ( $type && ! self::type_enabled( $type ) ) {
				return $title;
			}
		}
		return Matnban::normalizer()->normalize( $title );
	}
}
