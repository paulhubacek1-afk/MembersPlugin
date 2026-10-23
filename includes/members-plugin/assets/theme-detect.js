/**
 * Members Plugin – Live-Erkennung der Theme-Farben im Browser.
 *
 * Ergänzt die serverseitige Erkennung für Themes ohne theme.json/Palette
 * (klassische Themes): liest die tatsächlich dargestellten Farben (Hintergrund,
 * Text, Theme-Buttons, Links) aus und setzt nur die CSS-Variablen, die der
 * Server noch nicht kennt. Manuelle Werte aus den Einstellungen bleiben unberührt.
 */
( function () {
	'use strict';

	var cfg = window.mpThemeDetect || {};
	var have = cfg.have || [];

	function need( key ) {
		return have.indexOf( key ) === -1;
	}

	function parse( c ) {
		var m = /rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)(?:[\s,/]+([\d.]+%?))?/.exec( c || '' );
		if ( ! m ) {
			return null;
		}
		var a = m[ 4 ] === undefined ? 1 : ( m[ 4 ].indexOf( '%' ) > -1 ? parseFloat( m[ 4 ] ) / 100 : parseFloat( m[ 4 ] ) );
		return [ +m[ 1 ], +m[ 2 ], +m[ 3 ], a ];
	}

	function hex( c ) {
		return '#' + c.slice( 0, 3 ).map( function ( v ) {
			var h = Math.round( v ).toString( 16 );
			return h.length === 1 ? '0' + h : h;
		} ).join( '' );
	}

	function lum( c ) {
		var v = c.slice( 0, 3 ).map( function ( x ) {
			x /= 255;
			return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * v[ 0 ] + 0.7152 * v[ 1 ] + 0.0722 * v[ 2 ];
	}

	function contrast( a, b ) {
		var la = lum( a ), lb = lum( b );
		return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
	}

	function mix( a, b, w ) {
		return [ 0, 1, 2 ].map( function ( i ) {
			return a[ i ] * w + b[ i ] * ( 1 - w );
		} ).concat( 1 );
	}

	function sat( c ) {
		var max = Math.max( c[ 0 ], c[ 1 ], c[ 2 ] ) / 255, min = Math.min( c[ 0 ], c[ 1 ], c[ 2 ] ) / 255;
		if ( max === min ) {
			return 0;
		}
		var l = ( max + min ) / 2, d = max - min;
		return l > 0.5 ? d / ( 2 - max - min ) : d / ( max + min );
	}

	function bgOf( el ) {
		while ( el && el.nodeType === 1 ) {
			var c = parse( getComputedStyle( el ).backgroundColor );
			if ( c && c[ 3 ] > 0.5 ) {
				return c;
			}
			el = el.parentElement;
		}
		return [ 255, 255, 255, 1 ];
	}

	function isOwn( el ) {
		return el.closest( '.um, .um-modal, #wpadminbar, .mp-insp-panel, .select2-container' );
	}

	function visible( el ) {
		var r = el.getBoundingClientRect();
		return r.width > 20 && r.height > 12;
	}

	function run() {
		var root = document.documentElement;
		var um = document.querySelector( '.um' );
		var ctx = um && um.parentElement ? um.parentElement : document.body;
		var bg = bgOf( ctx );
		var text = parse( getComputedStyle( ctx ).color ) || [ 51, 51, 51, 1 ];
		var set = {};

		// Theme-Button finden (außerhalb des Plugins).
		var primary = null, onPrimary = null;
		var buttons = document.querySelectorAll( '.wp-element-button, .wp-block-button__link, .button, button, input[type=submit], .btn' );
		for ( var i = 0; i < buttons.length && ! primary; i++ ) {
			var b = buttons[ i ];
			if ( isOwn( b ) || ! visible( b ) ) {
				continue;
			}
			var cs = getComputedStyle( b );
			var bb = parse( cs.backgroundColor );
			if ( bb && bb[ 3 ] > 0.8 && contrast( bb, bg ) > 1.3 ) {
				primary = bb;
				onPrimary = parse( cs.color );
			}
		}
		// Sonst: farbige Links.
		if ( ! primary ) {
			var links = document.querySelectorAll( 'main a, article a, .entry-content a, .site-content a, #content a, a' );
			for ( var j = 0; j < links.length && j < 200; j++ ) {
				if ( isOwn( links[ j ] ) || ! visible( links[ j ] ) ) {
					continue;
				}
				var lc = parse( getComputedStyle( links[ j ] ).color );
				if ( lc && sat( lc ) > 0.3 && contrast( lc, bg ) > 2 ) {
					primary = lc;
					break;
				}
			}
		}

		// Monochromes Theme ohne Akzentfarbe: Buttons in der Textfarbe (wie Theme-Buttons üblicherweise).
		if ( ! primary && contrast( text, bg ) >= 4.5 ) {
			primary = text;
		}

		if ( need( 'bg' ) || need( 'text' ) ) {
			set.bg = hex( bg );
			set[ 'text-strong' ] = hex( text );
			set.text = hex( mix( text, bg, 0.85 ) );
			set[ 'text-muted' ] = hex( mix( text, bg, 0.55 ) );
			set[ 'text-light' ] = hex( mix( text, bg, 0.42 ) );
			set[ 'bg-alt' ] = hex( mix( text, bg, 0.05 ) );
			set.border = hex( mix( text, bg, 0.15 ) );
			set[ 'border-strong' ] = hex( mix( text, bg, 0.3 ) );
			set.secondary = hex( mix( text, bg, 0.08 ) );
			set[ 'secondary-hover' ] = hex( mix( text, bg, 0.14 ) );
			set[ 'on-secondary' ] = hex( mix( text, bg, 0.85 ) );
			set[ 'tooltip-bg' ] = hex( text );
			var nav = lum( bg ) < 0.2 ? mix( text, bg, 0.18 ) : mix( text, bg, 0.88 );
			set[ 'nav-bg' ] = hex( nav );
			set[ 'nav-hover' ] = hex( mix( nav, lum( nav ) < 0.18 ? [ 255, 255, 255 ] : [ 0, 0, 0 ], 0.9 ) );
			set[ 'on-nav' ] = contrast( nav, [ 255, 255, 255 ] ) >= contrast( nav, [ 17, 17, 17 ] ) ? '#ffffff' : '#111111';
		}

		if ( primary && need( 'primary' ) ) {
			if ( ! onPrimary || contrast( onPrimary, primary ) < 3 ) {
				onPrimary = contrast( primary, [ 255, 255, 255 ] ) >= contrast( primary, [ 17, 17, 17 ] ) ? [ 255, 255, 255, 1 ] : [ 17, 17, 17, 1 ];
			}
			var toward = lum( primary ) < 0.18 ? [ 255, 255, 255 ] : [ 0, 0, 0 ];
			set.primary = hex( primary );
			set[ 'primary-hover' ] = hex( mix( primary, toward, 0.86 ) );
			set[ 'primary-soft' ] = hex( mix( primary, bg, 0.15 ) );
			set[ 'on-primary' ] = hex( onPrimary );
		}

		Object.keys( set ).forEach( function ( k ) {
			if ( need( k ) ) {
				root.style.setProperty( '--um-' + k, set[ k ] );
			}
		} );
		if ( lum( bg ) < 0.2 ) {
			document.body.classList.add( 'um-theme-dark' );
		}
		root.setAttribute( 'data-mp-detected', Object.keys( set ).join( ' ' ) );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}
}() );
