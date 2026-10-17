( function ( $ ) {
	'use strict';

	var isGutenberg = !! ( window.wp && wp.data && wp.data.select( 'core/editor' ) && wp.data.select( 'core/editor' ).getEditedPostAttribute );
	var timer       = null;
	var lastHash    = '';

	function getContent() {
		if ( isGutenberg ) {
			return wp.data.select( 'core/editor' ).getEditedPostContent() || '';
		}
		if ( window.tinymce && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
			return tinymce.get( 'content' ).getContent();
		}
		return $( '#content' ).val() || '';
	}
	function setContent( html ) {
		if ( isGutenberg ) {
			var blocks = wp.blocks.parse( html );
			wp.data.dispatch( 'core/block-editor' ).resetBlocks( blocks );
			return;
		}
		if ( window.tinymce && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
			tinymce.get( 'content' ).setContent( html );
			tinymce.get( 'content' ).save();
			return;
		}
		$( '#content' ).val( html );
	}
	function getTitle() {
		if ( isGutenberg ) {
			return wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '';
		}
		return $( '#title' ).val() || '';
	}
	function setTitle( t ) {
		if ( isGutenberg ) {
			wp.data.dispatch( 'core/editor' ).editPost( { title: t } );
			return;
		}
		$( '#title' ).val( t );
	}
	
	function getKeyword() {
		var v = $( '#matnban-keyword' ).val();
		if ( v ) { return v; }
		var y = $( '#focus-keyword-input-metabox, #yoast-google-preview-focus-keyword' ).val();
		if ( y ) { return y; }
		if ( window.rankMath && rankMath.focusKeyword ) { return rankMath.focusKeyword.split( ',' )[0]; }
		return '';
	}

	function icon( status ) {
		return { good: '🟢', warn: '🟠', bad: '🔴' }[ status ] || '⚪';
	}
	function render( r ) {
		var $score = $( '#matnban-score' );
		$score.attr( 'data-grade', r.grade ).find( 'span' ).text( r.score );
		$( '#matnban-grade-label' ).text( r.grade_label );
		$( '#matnban-stats' ).text( r.word_count + ' کلمه · ' + r.sentence_count + ' جمله · ' + r.reading_time + ' دقیقه مطالعه · ' + r.typo_issues + ' خطای نگارشی' );

		var $ul = $( '#matnban-checks' ).empty();
		$.each( r.checks, function ( i, c ) {
			$ul.append( $( '<li>' ).addClass( 'matnban-' + c.status ).html( '<span class="matnban-ic">' + icon( c.status ) + '</span><strong></strong> <span></span>' ).find( 'strong' ).text( c.label ).end().find( 'span:last' ).text( c.detail ).end() );
		} );
	}

	function analyze( force ) {
		var content = getContent(), title = getTitle(), kw = getKeyword();
		var hash = title + '|' + kw + '|' + content.length + '|' + content.slice( 0, 200 ) + content.slice( -200 );
		if ( ! force && hash === lastHash ) { return; }
		lastHash = hash;
		$( '.matnban-box .spinner' ).addClass( 'is-active' );
		$.post( MATNBAN_EDITOR.ajax, {
			action: 'matnban_analyze', nonce: MATNBAN_EDITOR.nonce, post_id: MATNBAN_EDITOR.postId,
			content: content, title: title, keyword: kw
		} ).done( function ( res ) {
			if ( res && res.success ) { render( res.data ); }
		} ).always( function () {
			$( '.matnban-box .spinner' ).removeClass( 'is-active' );
		} );
	}

	function fix() {
		var content = getContent(), title = getTitle();
		$( '.matnban-box .spinner' ).addClass( 'is-active' );
		$( '#matnban-btn-fix' ).prop( 'disabled', true );
		$.post( MATNBAN_EDITOR.ajax, {
			action: 'matnban_fix_text', nonce: MATNBAN_EDITOR.nonce, content: content, title: title
		} ).done( function ( res ) {
			var $rep = $( '#matnban-fix-report' ).show();
			if ( ! res || ! res.success ) { $rep.html( '<span class="matnban-bad">' + MATNBAN_EDITOR.i18n.error + '</span>' ); return; }
			var d = res.data;
			if ( d.total === 0 ) {
				$rep.html( '<span class="matnban-good">✓ ' + MATNBAN_EDITOR.i18n.nochange + '</span>' );
			} else {
				if ( d.content !== content ) { setContent( d.content ); }
				if ( d.title !== title ) { setTitle( d.title ); }
				var items = $.map( d.report, function ( x ) { return '<li>' + $( '<b>' ).text( x.count ).prop( 'outerHTML' ) + ' × ' + $( '<span>' ).text( x.label ).prop( 'outerHTML' ) + '</li>'; } );
				$rep.html( '<span class="matnban-good">✓ ' + d.total + ' ' + MATNBAN_EDITOR.i18n.fixes + '</span><ul>' + items.join( '' ) + '</ul>' );
				setTimeout( function () { analyze( true ); }, 300 );
			}
		} ).fail( function () {
			$( '#matnban-fix-report' ).show().html( '<span class="matnban-bad">' + MATNBAN_EDITOR.i18n.error + '</span>' );
		} ).always( function () {
			$( '.matnban-box .spinner' ).removeClass( 'is-active' );
			$( '#matnban-btn-fix' ).prop( 'disabled', false );
		} );
	}

	function getSelectedText() {
		if ( isGutenberg ) {
			try {
				var be  = wp.data.select( 'core/block-editor' );
				var sel = window.getSelection ? String( window.getSelection() ) : '';
				if ( ! sel ) {
					var frame = document.querySelector( 'iframe[name="editor-canvas"]' );
					if ( frame && frame.contentWindow && frame.contentWindow.getSelection ) { sel = String( frame.contentWindow.getSelection() ); }
				}
				if ( sel && sel.trim().length > 3 ) { return sel; }
				var ids = be.getSelectedBlockClientIds ? be.getSelectedBlockClientIds() : [];
				if ( ids.length ) {
					return $.map( ids, function ( id ) { var b = be.getBlock( id ); return b ? $( '<div>' ).html( wp.blocks.getBlockContent( b ) ).text() : ''; } ).join( '\n\n' );
				}
			} catch ( e ) {}
			return '';
		}
		if ( window.tinymce && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
			return tinymce.get( 'content' ).selection.getContent( { format: 'text' } ) || '';
		}
		var ta = document.getElementById( 'content' );
		return ta ? ta.value.substring( ta.selectionStart, ta.selectionEnd ) : '';
	}
	function plainContent() {
		var html = getContent();
		if ( isGutenberg ) { html = html.replace( /<!--[\s\S]*?-->/g, '' ); }
		return $( '<div>' ).html( html ).text();
	}
	function replaceSelection( text ) {
		if ( isGutenberg ) {
			try {
				var be = wp.data.select( 'core/block-editor' ), dispatch = wp.data.dispatch( 'core/block-editor' );
				var ids = be.getSelectedBlockClientIds ? be.getSelectedBlockClientIds() : [];
				var paras = text.split( /\n{2,}/ ).map( function ( p ) { return wp.blocks.createBlock( 'core/paragraph', { content: p.trim() } ); } );
				if ( ids.length ) { dispatch.replaceBlocks( ids, paras ); return true; }
				dispatch.insertBlocks( paras );
				return true;
			} catch ( e ) { return false; }
		}
		if ( window.tinymce && tinymce.get( 'content' ) && ! tinymce.get( 'content' ).isHidden() ) {
			var ed = tinymce.get( 'content' );
			var html = text.split( /\n{2,}/ ).map( function ( p ) { return '<p>' + $( '<div>' ).text( p.trim() ).html() + '</p>'; } ).join( '' );
			if ( ed.selection.getContent() ) { ed.selection.setContent( html ); } else { ed.insertContent( html ); }
			ed.save();
			return true;
		}
		var ta = document.getElementById( 'content' );
		if ( ! ta ) { return false; }
		var v = ta.value, a = ta.selectionStart, b = ta.selectionEnd;
		ta.value = v.substring( 0, a ) + text + v.substring( b );
		return true;
	}
	
	function copyText( t ) {
		if ( navigator.clipboard ) { navigator.clipboard.writeText( t ); return; }
		var ta = $( '<textarea>' ).val( t ).appendTo( 'body' );
		ta[0].select();
		try { document.execCommand( 'copy' ); } catch ( e ) {}
		ta.remove();
	}
	

	window.MATNBAN = {
		setTitle: setTitle,
		replaceSelection: replaceSelection,
		copy: copyText,
		analyze: function ( kw ) {
			var content = getContent(), title = getTitle();
			return $.post( MATNBAN_EDITOR.ajax, {
				action: 'matnban_analyze', nonce: MATNBAN_EDITOR.nonce, post_id: MATNBAN_EDITOR.postId,
				content: content, title: title, keyword: kw || getKeyword()
			} ).then( function ( res ) { return ( res && res.success ) ? res.data : null; } );
		},
		fix: function () {
			var content = getContent(), title = getTitle();
			return $.post( MATNBAN_EDITOR.ajax, {
				action: 'matnban_fix_text', nonce: MATNBAN_EDITOR.nonce, content: content, title: title
			} ).then( function ( res ) {
				if ( ! res || ! res.success ) { return $.Deferred().reject().promise(); }
				var d = res.data;
				if ( d.total > 0 ) {
					if ( d.content !== content ) { setContent( d.content ); }
					if ( d.title !== title ) { setTitle( d.title ); }
				}
				return d;
			} );
		}
	};

	$( function () {
		if ( ! $( '#matnban-readability' ).length || isGutenberg ) { return; }

		$( '#matnban-btn-analyze' ).on( 'click', function () { analyze( true ); } );
		$( '#matnban-btn-fix' ).on( 'click', fix );
		$( '#matnban-keyword' ).on( 'change keyup', function () { clearTimeout( timer ); timer = setTimeout( function () { analyze( true ); }, 800 ); } );

		function schedule() { clearTimeout( timer ); timer = setTimeout( function () { analyze( false ); }, 2500 ); }
		if ( isGutenberg ) {
			wp.data.subscribe( schedule );
		} else {
			$( document ).on( 'input keyup', '#title, #content', schedule );
			$( document ).on( 'tinymce-editor-init', function ( e, ed ) { ed.on( 'keyup change', schedule ); } );
		}

		

		setTimeout( function () { analyze( true ); }, 1200 );
	} );
} )( jQuery );
