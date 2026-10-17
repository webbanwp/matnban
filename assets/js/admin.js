( function ( $ ) {
	'use strict';
	/* یک‌بار اجرا شود: اگر فایل یا نسخهٔ داخل صفحه هر دو موجود باشند، فقط اولی می‌بندد */
	if ( window.MATNBAN_ADMIN_LOADED ) { return; }
	window.MATNBAN_ADMIN_LOADED = true;

	var fa = function ( n ) {
		return String( n ).replace( /\d/g, function ( d ) { return '۰۱۲۳۴۵۶۷۸۹'[ d ]; } );
	};

	var $app, $main, $nav, $status;
	var loading = null;

	/* ---------- ابزارهای مسیریابی ---------- */
	function parseHref( href ) {
		var a = document.createElement( 'a' );
		a.href = href;
		if ( a.search.indexOf( 'page=matnban' ) === -1 && a.pathname.indexOf( 'admin.php' ) === -1 ) { return null; }
		var q = {};
		a.search.replace( /^\?/, '' ).split( '&' ).forEach( function ( kv ) {
			var p = kv.split( '=' );
			if ( p[ 0 ] ) { q[ decodeURIComponent( p[ 0 ] ) ] = decodeURIComponent( p[ 1 ] || '' ); }
		} );
		if ( q.page !== 'matnban' ) { return null; }
		return { tab: q.tab || 'dashboard', filter: q.filter || '' };
	}

	function urlFor( tab, filter ) {
		var u = MATNBAN_ADMIN.pageUrl;
		if ( tab && tab !== 'dashboard' ) { u += '&tab=' + encodeURIComponent( tab ); }
		if ( filter && filter !== 'issues' ) { u += '&filter=' + encodeURIComponent( filter ); }
		return u;
	}

	function setActive( tab ) {
		$nav.find( '.matnban-nav-item' ).each( function () {
			var on = $( this ).data( 'tab' ) === tab;
			$( this ).toggleClass( 'is-active', on );
			if ( on ) { $( this ).attr( 'aria-current', 'page' ); } else { $( this ).removeAttr( 'aria-current' ); }
		} );
		/* هماهنگ کردن زیرمنوی وردپرس در ستون کناری */
		var $items = $( '#adminmenu a[href*="page=matnban"]' );
		$items.each( function () {
			var r = parseHref( this.href );
			var on = r && r.tab === tab;
			$( this ).toggleClass( 'current', !! on ).parent( 'li' ).toggleClass( 'current', !! on );
		} );
	}

	function applyPayload( d ) {
		$main.attr( 'data-tab', d.tab ).attr( 'data-filter', d.filter || 'issues' ).html( d.html );
		$app.attr( 'data-tab', d.tab );
		$status.html( d.status );
		if ( d.title ) { document.title = d.title; }
		setActive( d.tab );
		initTab();
	}

	function toast( msg, bad ) {
		var $t = $( '<div class="matnban-toast" role="status">' ).toggleClass( 'is-bad', !! bad ).text( msg ).appendTo( $app );
		window.setTimeout( function () { $t.addClass( 'is-show' ); }, 10 );
		window.setTimeout( function () { $t.removeClass( 'is-show' ); window.setTimeout( function () { $t.remove(); }, 300 ); }, 2600 );
	}

	/* بارگذاری یک تب با AJAX (بدون تازه‌سازی صفحه) */
	function loadTab( tab, filter, push ) {
		if ( loading ) { loading.abort(); }
		$main.addClass( 'is-loading' ).attr( 'aria-busy', 'true' );
		loading = $.post( MATNBAN_ADMIN.ajax, { action: 'matnban_tab', nonce: MATNBAN_ADMIN.tabNonce, tab: tab, filter: filter || '' } ).done( function ( res ) {
			if ( ! res || ! res.success ) { window.location.href = urlFor( tab, filter ); return; }
			applyPayload( res.data );
			if ( push ) { window.history.pushState( { tab: res.data.tab, filter: res.data.filter }, '', urlFor( res.data.tab, res.data.filter ) ); }
			var top = $app.offset().top - 40;
			if ( window.pageYOffset > top ) { window.scrollTo( { top: top, behavior: 'smooth' } ); }
		} ).fail( function ( xhr, st ) {
			if ( st !== 'abort' ) { window.location.href = urlFor( tab, filter ); }
		} ).always( function () {
			loading = null;
			$main.removeClass( 'is-loading' ).removeAttr( 'aria-busy' );
		} );
	}

	function router() {
		if ( ! MATNBAN_ADMIN.tabNonce ) { return; }
		/* لینک‌های داخل پنل + زیرمنوی وردپرس */
		$( document ).on( 'click.matnban', '#matnban-app a[href*="page=matnban"], #adminmenu a[href*="page=matnban"]', function ( e ) {
			if ( e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2 || this.target === '_blank' ) { return; }
			var r = parseHref( this.href );
			if ( ! r ) { return; }
			e.preventDefault();
			loadTab( r.tab, r.filter, true );
		} );
		window.addEventListener( 'popstate', function ( ev ) {
			var st = ev.state || parseHref( window.location.href ) || { tab: 'dashboard', filter: '' };
			loadTab( st.tab, st.filter, false );
		} );
		window.history.replaceState( { tab: $main.data( 'tab' ), filter: $main.data( 'filter' ) }, '', window.location.href );
		setActive( $main.data( 'tab' ) );
	}

	/* ذخیرهٔ فرم تنظیمات هر تب با AJAX */
	function forms() {
		$( document ).on( 'submit.matnban', '#matnban-app form.matnban-form', function ( e ) {
			if ( ! MATNBAN_ADMIN.tabNonce ) { return; }
			e.preventDefault();
			var $f = $( this ), $btn = $f.find( '.matnban-savebar button[type="submit"]' ), $note = $f.find( '.matnban-savebar-note' );
			var data = $f.serialize() + '&action=matnban_save_settings&nonce=' + encodeURIComponent( MATNBAN_ADMIN.tabNonce ) + '&tab=' + encodeURIComponent( $main.attr( 'data-tab' ) ) + '&filter=' + encodeURIComponent( $main.attr( 'data-filter' ) || '' );
			$btn.prop( 'disabled', true );
			$note.text( MATNBAN_ADMIN.i18n.saving || 'در حال ذخیره…' );
			$.post( MATNBAN_ADMIN.ajax, data ).done( function ( res ) {
				if ( ! res || ! res.success ) { $note.text( MATNBAN_ADMIN.i18n.error ); toast( MATNBAN_ADMIN.i18n.error, true ); return; }
				applyPayload( res.data );
				toast( res.data.message || MATNBAN_ADMIN.i18n.saved );
			} ).fail( function () {
				$note.text( MATNBAN_ADMIN.i18n.error );
				toast( MATNBAN_ADMIN.i18n.error, true );
			} ).always( function () { $btn.prop( 'disabled', false ); } );
		} );
	}

	function reloadCurrent() {
		loadTab( $main.attr( 'data-tab' ), $main.attr( 'data-filter' ), false );
	}

	/* ---------- ماژول‌های هر تب (پس از هر بارگذاری دوباره وصل می‌شوند) ---------- */
	function quickTest() {
		var $in = $( '#matnban-test-input' );
		if ( ! $in.length ) { return; }
		$( '#matnban-test-sample' ).on( 'click', function () {
			$in.val( 'من می روم و كتاب ها ي فارسي را مي خوانم , باشه ? گفت "سلام" و ٥٠ % كار ها را انجام داده ام .' ).trigger( 'input' );
		} );
		$( '#matnban-test-copy' ).on( 'click', function () {
			var el = document.getElementById( 'matnban-test-output' );
			el.select();
			try { document.execCommand( 'copy' ); } catch ( e ) {}
			if ( navigator.clipboard ) { navigator.clipboard.writeText( el.value ); }
		} );
		$( '#matnban-test-run' ).on( 'click', function ( e ) {
			e.preventDefault();
			var text = $in.val();
			if ( ! text.trim() ) { return; }
			$( '.matnban-inline-actions .spinner' ).addClass( 'is-active' );
			$.post( MATNBAN_ADMIN.ajax, { action: 'matnban_quick_test', nonce: MATNBAN_ADMIN.nonce, text: text } ).done( function ( res ) {
				if ( ! res || ! res.success ) { return; }
				var d = res.data;
				$( '#matnban-test-result' ).prop( 'hidden', false );
				$( '#matnban-test-output' ).val( d.fixed );
				$( '#matnban-test-total' ).text( d.total ? fa( d.total ) + ' اصلاح' : 'بدون تغییر' ).toggleClass( 'is-good', d.total > 0 );
				var $ul = $( '#matnban-test-report' ).empty();
				if ( ! d.report.length ) {
					$ul.append( '<li class="is-none">متن از نظر نگارشی سالم است.</li>' );
				}
				$.each( d.report, function ( i, r ) {
					$ul.append( $( '<li>' ).append( $( '<b>' ).text( fa( r.count ) ) ).append( $( '<span>' ).text( r.label ) ) );
				} );
				$( '#matnban-test-score' ).text( fa( d.score ) + ' از ۱۰۰ — ' + d.grade );
			} ).always( function () {
				$( '.matnban-inline-actions .spinner' ).removeClass( 'is-active' );
			} );
		} );
	}

	function scan() {
		var $btn = $( '#matnban-scan-start' );
		if ( ! $btn.length ) { return; }
		var $prog = $( '#matnban-scan-progress' ), $bar = $prog.find( 'i' ), $txt = $prog.find( '.matnban-progress-text' );

		function batch( offset, total ) {
			$.post( MATNBAN_ADMIN.ajax, { action: 'matnban_scan_batch', nonce: MATNBAN_ADMIN.reportNonce, offset: offset } ).done( function ( res ) {
				if ( ! res || ! res.success ) { $txt.text( MATNBAN_ADMIN.i18n.error ); $btn.prop( 'disabled', false ); return; }
				var d = res.data, t = d.total || total || 1;
				$bar.css( 'width', Math.min( 100, Math.round( d.offset / t * 100 ) ) + '%' );
				$txt.text( fa( Math.min( d.offset, t ) ) + ' ' + MATNBAN_ADMIN.i18n.of + ' ' + fa( t ) + ' ' + MATNBAN_ADMIN.i18n.scanned );
				if ( d.finished ) {
					$txt.text( MATNBAN_ADMIN.i18n.done );
					window.setTimeout( reloadCurrent, 600 );
				} else {
					batch( d.offset, t );
				}
			} ).fail( function () { $txt.text( MATNBAN_ADMIN.i18n.error ); $btn.prop( 'disabled', false ); } );
		}

		$btn.on( 'click', function () {
			var total = parseInt( $btn.data( 'total' ), 10 ) || 0;
			if ( ! total ) { window.alert( MATNBAN_ADMIN.i18n.noPosts ); return; }
			$btn.prop( 'disabled', true );
			$prog.prop( 'hidden', false );
			$bar.css( 'width', '0%' );
			$txt.text( MATNBAN_ADMIN.i18n.scanning );
			$.post( MATNBAN_ADMIN.ajax, { action: 'matnban_scan_reset', nonce: MATNBAN_ADMIN.reportNonce } ).always( function () { batch( 0, total ); } );
		} );
	}

	function initTab() {
		quickTest();
		scan();
	}

	$( function () {
		$app = $( '#matnban-app' );
		if ( ! $app.length ) { return; }
		$main = $( '#matnban-main' );
		$nav = $( '#matnban-nav' );
		$status = $( '#matnban-header-status' );
		/* رویدادهای واگذارشده — فقط یک‌بار */
		router();
		forms();
		initTab();
	} );
} )( jQuery );
