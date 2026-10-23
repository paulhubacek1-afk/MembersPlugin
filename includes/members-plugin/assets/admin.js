/* Members Plugin – Admin "Design & CSS" */
( function ( $ ) {
	'use strict';

	var i18n = window.mpAdmin || { copied: 'Kopiert!', copy: 'Kopieren' };

	function copyText( text, el ) {
		var done = function () {
			if ( ! el ) {
				return;
			}
			var old = el.getAttribute( 'data-label' ) || el.textContent;
			el.setAttribute( 'data-label', old );
			el.classList.add( 'is-copied' );
			el.textContent = i18n.copied;
			setTimeout( function () {
				el.textContent = old;
				el.classList.remove( 'is-copied' );
			}, 900 );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( done, function () {} );
		} else {
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			try {
				document.execCommand( 'copy' );
				done();
			} catch ( e ) {}
			document.body.removeChild( ta );
		}
	}

	$( document ).on( 'click', '.mp-copy', function () {
		copyText( this.getAttribute( 'data-copy' ), this );
	} );

	/* ---------- Live-Vorschau ---------- */
	var $scope = $( '.mp-preview-scope' );

	function setVar( key, value ) {
		if ( ! $scope.length ) {
			return;
		}
		var el = $scope.get( 0 );
		if ( value ) {
			el.style.setProperty( '--um-' + key, value );
		} else {
			// Zurück auf den beim Laden berechneten Wert.
			var tmp = document.createElement( 'div' );
			tmp.setAttribute( 'style', $scope.attr( 'data-initial-style' ) || '' );
			var initial = tmp.style.getPropertyValue( '--um-' + key );
			if ( initial ) {
				el.style.setProperty( '--um-' + key, initial );
			} else {
				el.style.removeProperty( '--um-' + key );
			}
		}
	}

	$( '.mp-color' ).each( function () {
		var $input = $( this );
		$input.wpColorPicker( {
			change: function ( event, ui ) {
				setVar( $input.data( 'var' ), ui.color.toString() );
			},
			clear: function () {
				setVar( $input.data( 'var' ), '' );
			}
		} );
	} );

	$( '.mp-text-var' ).on( 'input', function () {
		var v = $.trim( this.value );
		if ( /^\d+$/.test( v ) ) {
			v += 'px';
		}
		setVar( $( this ).data( 'var' ), v );
	} );

	// Klassen in der Vorschau anzeigen / kopieren.
	var $info = $( '.mp-preview-classes' );
	$scope.on( 'mouseover', '[class]', function ( e ) {
		e.stopPropagation();
		var cls = ( this.getAttribute( 'class' ) || '' ).split( /\s+/ ).filter( Boolean );
		$info.text( cls.length ? '.' + cls.join( '.' ) : '' );
	} );
	$scope.on( 'click', '[class]', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var cls = ( this.getAttribute( 'class' ) || '' ).split( /\s+/ ).filter( function ( c ) {
			return /^(um|uimob)/.test( c );
		} );
		if ( cls.length ) {
			copyText( '.um .' + cls.join( '.' ) );
			$info.text( i18n.copied + ' .um .' + cls.join( '.' ) );
		}
	} );

	/* ---------- Klassen-Suche ---------- */
	var $rows = $( '.mp-class-table tbody tr' );
	function filterClasses() {
		var q = $.trim( $( '#mp-class-search' ).val() || '' ).toLowerCase().replace( /^\./, '' );
		var g = $( '#mp-class-group' ).val();
		var styled = $( '#mp-class-styled' ).is( ':checked' );
		var visible = 0;
		$rows.each( function () {
			var show = ( ! q || this.getAttribute( 'data-class' ).toLowerCase().indexOf( q ) !== -1 ) &&
				( ! g || this.getAttribute( 'data-group' ) === g ) &&
				( ! styled || this.getAttribute( 'data-styled' ) === '1' );
			this.hidden = ! show;
			visible += show ? 1 : 0;
		} );
		$( '.mp-empty' ).prop( 'hidden', visible > 0 );
	}
	$( '#mp-class-search' ).on( 'input', filterClasses );
	$( '#mp-class-group, #mp-class-styled' ).on( 'change', filterClasses );

	/* ---------- Code-Editor ---------- */
	if ( window.mpCodeEditor && window.wp && wp.codeEditor && document.getElementById( 'mp-custom-css' ) ) {
		wp.codeEditor.initialize( 'mp-custom-css', window.mpCodeEditor );
	}
}( jQuery ) );
