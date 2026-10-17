( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.element || ! wp.components || ! wp.data ) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var el             = wp.element.createElement;
	var useState       = wp.element.useState;
	var useEffect      = wp.element.useEffect;
	var useRef         = wp.element.useRef;
	var Fragment       = wp.element.Fragment;
	var Button         = wp.components.Button;
	var TextControl    = wp.components.TextControl;
	var Spinner        = wp.components.Spinner;
	var PanelBody      = wp.components.PanelBody;
	var useSelect      = wp.data.useSelect;

	var PluginSidebar         = ( wp.editor && wp.editor.PluginSidebar ) || ( wp.editPost && wp.editPost.PluginSidebar );
	var PluginSidebarMoreMenu = ( wp.editor && wp.editor.PluginSidebarMoreMenuItem ) || ( wp.editPost && wp.editPost.PluginSidebarMoreMenuItem );
	var PluginPrePublishPanel = ( wp.editor && wp.editor.PluginPrePublishPanel ) || ( wp.editPost && wp.editPost.PluginPrePublishPanel );

	if ( ! PluginSidebar ) {
		return;
	}

	var SIDEBAR_NAME = 'matnban-sidebar';

	var Icon = el( 'svg', { width: 24, height: 24, viewBox: '0 0 24 24', fill: 'none', xmlns: 'http://www.w3.org/2000/svg' },
		el( 'circle', { cx: 12, cy: 12, r: 10, stroke: 'currentColor', strokeWidth: 1.8 } ),
		el( 'path', { d: 'M7.5 12.5l3 3 6-7', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' } )
	);

	var STATUS_ICON = { good: '🟢', warn: '🟠', bad: '🔴' };

	function Panel( props ) {
		var compact = !! props.compact;
		var api     = window.MATNBAN;
		var _s      = useState( null ),   result   = _s[0], setResult   = _s[1];
		var _b      = useState( false ),  busy     = _b[0], setBusy     = _b[1];
		var _r      = useState( null ),   report   = _r[0], setReport   = _r[1];
		var _k      = useState( MATNBAN_EDITOR.keyword || '' ), keyword = _k[0], setKeyword = _k[1];
		var timer   = useRef( null );
		var lastSig = useRef( '' );

		var sig = useSelect( function ( select ) {
			var e = select( 'core/editor' );
			var c = e.getEditedPostContent() || '';
			return ( e.getEditedPostAttribute( 'title' ) || '' ) + '|' + c.length + '|' + c.slice( 0, 120 ) + c.slice( -120 );
		}, [] );

		function runAnalyze( force ) {
			if ( ! api ) { return; }
			var s = sig + '|' + keyword;
			if ( ! force && s === lastSig.current ) { return; }
			lastSig.current = s;
			setBusy( true );
			api.analyze( keyword ).then( function ( r ) { if ( r ) { setResult( r ); } } ).always( function () { setBusy( false ); } );
		}
		function runFix() {
			if ( ! api ) { return; }
			setBusy( true ); setReport( null );
			api.fix().then( function ( d ) {
				setReport( d );
				setTimeout( function () { runAnalyze( true ); }, 300 );
			} ).always( function () { setBusy( false ); } );
		}
		
		

		var r = result;
		return el( 'div', { className: 'matnban-sidebar' + ( compact ? ' matnban-sidebar--compact' : '' ), dir: 'rtl' },

			el( 'div', { className: 'matnban-sb-head' },
				el( 'div', { className: 'matnban-score', 'data-grade': r ? r.grade : 'none' }, el( 'span', null, r ? r.score : '—' ) ),
				el( 'div', { className: 'matnban-score-meta' },
					el( 'strong', null, r ? r.grade_label : 'هنوز تحلیل نشده' ),
					r && el( 'small', null, r.word_count + ' کلمه · ' + r.sentence_count + ' جمله · ' + r.reading_time + ' دقیقه · ' + r.typo_issues + ' خطای نگارشی' )
				)
			),

			el( 'div', { className: 'matnban-sb-actions' },
				el( Button, { variant: 'primary', isPrimary: true, onClick: runFix, disabled: busy }, '✎ اصلاح نگارش' ),
				el( Button, { variant: 'secondary', isSecondary: true, onClick: function () { runAnalyze( true ); }, disabled: busy }, '↻ تحلیل' ),
				busy && el( Spinner, null )
			),

			report && el( 'div', { className: 'matnban-fix-report' },
				report.total === 0
					? el( 'span', { className: 'matnban-good' }, '✓ ' + MATNBAN_EDITOR.i18n.nochange )
					: el( Fragment, null,
						el( 'span', { className: 'matnban-good' }, '✓ ' + report.total + ' ' + MATNBAN_EDITOR.i18n.fixes ),
						el( 'ul', null, report.report.map( function ( x, i ) { return el( 'li', { key: i }, el( 'b', null, x.count ), ' × ', x.label ); } ) )
					)
			),

			! compact && el( Fragment, null,
				el( 'div', { className: 'matnban-sb-kw' },
					el( TextControl, {
						label: 'کلمهٔ کلیدی هدف',
						value: keyword,
						onChange: setKeyword,
						placeholder: 'خودکار از Yoast / Rank Math',
						help: MATNBAN_EDITOR.keyword ? 'خوانده‌شده از افزونهٔ سئو. می‌توانید تغییر دهید.' : 'اگر Yoast یا Rank Math ندارید، دستی وارد کنید.',
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true
					} )
				),

			),

			
			r && el( 'ul', { className: 'matnban-checks' },
				r.checks.map( function ( c ) {
					return el( 'li', { key: c.id, className: 'matnban-' + c.status },
						el( 'span', { className: 'matnban-ic' }, STATUS_ICON[ c.status ] || '⚪' ),
						el( 'strong', null, c.label ),
						el( 'span', null, c.detail )
					);
				} )
			),

			! r && ! busy && el( 'p', { className: 'matnban-muted' }, 'تحلیل هنگام تایپ خودکار انجام می‌شود.' )
		);
	}

	registerPlugin( 'matnban', {
		icon: Icon,
		render: function () {
			return el( Fragment, null,
				PluginSidebarMoreMenu && el( PluginSidebarMoreMenu, { target: SIDEBAR_NAME, icon: Icon }, 'متن‌بان' ),
				el( PluginSidebar, { name: SIDEBAR_NAME, title: 'متن‌بان — خوانایی فارسی', icon: Icon, isPinnable: true },
					el( Panel, null )
				),
				PluginPrePublishPanel && el( PluginPrePublishPanel, { title: 'متن‌بان', initialOpen: true, icon: Icon },
					el( Panel, { compact: true } )
				)
			);
		}
	} );

	wp.domReady( function () {
		try {
			var iface = wp.data.dispatch( 'core/interface' );
			var key   = 'matnban_sidebar_seen';
			if ( iface && ! window.localStorage.getItem( key ) ) {
				iface.enableComplementaryArea( 'core', 'matnban/' + SIDEBAR_NAME );
				window.localStorage.setItem( key, '1' );
			}
		} catch ( e ) {  }
	} );
} )( window.wp );
