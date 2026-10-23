/* Members Plugin – Klassen-Inspector (nur für Admins) */
( function () {
	'use strict';

	var L = window.mpInspector || {};
	var active = false;
	var pinned = null;
	var onlyUm = true;
	var box, tag, panel;
	var STORE = 'mpInspectorActive';

	function isUmClass( c ) {
		return /^(um|uimob|select2|picker|tipsy|cropper|upload)/.test( c );
	}

	function el( tagName, cls, html ) {
		var n = document.createElement( tagName );
		if ( cls ) {
			n.className = cls;
		}
		if ( html !== undefined ) {
			n.textContent = html;
		}
		return n;
	}

	function classesOf( node ) {
		var c = node.getAttribute && node.getAttribute( 'class' );
		return c ? c.split( /\s+/ ).filter( function ( x ) {
			return x && x.indexOf( 'mp-insp' ) !== 0;
		} ) : [];
	}

	function isOwnUi( node ) {
		return node.closest && ( node.closest( '.mp-insp-panel' ) || node.closest( '#wpadminbar' ) );
	}

	function umTarget( node ) {
		if ( ! onlyUm ) {
			return node;
		}
		while ( node && node !== document.body ) {
			if ( classesOf( node ).some( isUmClass ) ) {
				return node;
			}
			node = node.parentElement;
		}
		return null;
	}

	function setup() {
		box = el( 'div', 'mp-insp-box' );
		tag = el( 'div', 'mp-insp-tag' );
		panel = el( 'div', 'mp-insp-panel' );
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', L.title || 'Inspector' );
		document.body.appendChild( box );
		document.body.appendChild( tag );
		document.body.appendChild( panel );
	}

	function copy( text, btn ) {
		var ok = function () {
			if ( ! btn ) {
				return;
			}
			var old = btn.textContent;
			btn.classList.add( 'is-copied' );
			btn.textContent = L.copied || 'Kopiert!';
			setTimeout( function () {
				btn.textContent = old;
				btn.classList.remove( 'is-copied' );
			}, 900 );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( ok, function () {} );
		} else {
			var ta = el( 'textarea' );
			ta.value = text;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			try {
				document.execCommand( 'copy' );
				ok();
			} catch ( e ) {}
			document.body.removeChild( ta );
		}
	}

	function position( node ) {
		var r = node.getBoundingClientRect();
		box.style.display = 'block';
		box.style.left = r.left + 'px';
		box.style.top = r.top + 'px';
		box.style.width = r.width + 'px';
		box.style.height = r.height + 'px';
		return r;
	}

	function showTag( node, r ) {
		tag.textContent = '';
		tag.appendChild( el( 'i', '', node.tagName.toLowerCase() ) );
		classesOf( node ).forEach( function ( c ) {
			var n = el( isUmClass( c ) ? 'b' : 'span', '', '.' + c );
			tag.appendChild( n );
		} );
		tag.style.display = 'block';
		var top = r.top - tag.offsetHeight - 4;
		if ( top < 34 ) {
			top = r.bottom + 4;
		}
		tag.style.top = Math.min( top, window.innerHeight - tag.offsetHeight - 4 ) + 'px';
		tag.style.left = Math.max( 4, Math.min( r.left, window.innerWidth - tag.offsetWidth - 4 ) ) + 'px';
	}

	/* Empfohlener Selektor: bis zu 3 Vorfahren mit Plugin-Klassen + Element. */
	function suggest( node ) {
		var parts = [];
		var n = node;
		while ( n && n !== document.body && parts.length < 4 ) {
			var um = classesOf( n ).filter( isUmClass ).filter( function ( c ) {
				return ! /^um-\d+$/.test( c ) && ! /^_um_row/.test( c );
			} );
			if ( um.length ) {
				parts.unshift( '.' + um.slice( 0, 2 ).join( '.' ) );
			}
			if ( n.classList && n.classList.contains( 'um' ) && parts.length ) {
				break;
			}
			n = n.parentElement;
		}
		if ( parts.length && parts[ 0 ].split( '.' )[ 1 ] !== 'um' ) {
			parts.unshift( '.um' );
		}
		return parts.join( ' ' ) || node.tagName.toLowerCase();
	}

	function chips( container, list ) {
		list.forEach( function ( c ) {
			var b = el( 'button', 'mp-insp-chip' + ( isUmClass( c ) ? '' : ' is-other' ), '.' + c );
			b.type = 'button';
			b.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				copy( '.' + c, b );
			} );
			container.appendChild( b );
		} );
	}

	function colorRow( grid, label, value ) {
		grid.appendChild( el( 'span', '', label ) );
		var v = el( 'span' );
		if ( /rgb|#/.test( value ) && ! /none/.test( value ) ) {
			var sw = el( 'i', 'mp-insp-sw' );
			sw.style.background = value;
			v.appendChild( sw );
		}
		v.appendChild( document.createTextNode( value ) );
		grid.appendChild( v );
	}

	function renderPanel( node ) {
		panel.textContent = '';
		var head = el( 'div', 'mp-insp-head' );
		head.appendChild( el( 'strong', '', L.title || 'CSS-Klassen-Inspector' ) );
		var lbl = el( 'label' );
		var cb = el( 'input' );
		cb.type = 'checkbox';
		cb.checked = onlyUm;
		cb.addEventListener( 'change', function () {
			onlyUm = cb.checked;
		} );
		lbl.appendChild( cb );
		lbl.appendChild( document.createTextNode( L.onlyUm || 'Nur Plugin-Elemente' ) );
		head.appendChild( lbl );
		var close = el( 'button', 'mp-insp-btn is-ghost', '×' );
		close.type = 'button';
		close.setAttribute( 'aria-label', L.close || 'Schließen' );
		close.addEventListener( 'click', function () {
			toggle( false );
		} );
		head.appendChild( close );
		panel.appendChild( head );

		var body = el( 'div', 'mp-insp-body' );
		if ( ! node ) {
			body.appendChild( el( 'p', 'mp-insp-hint', L.hint || '' ) );
		} else {
			body.appendChild( el( 'h4', '', L.selector || 'Selektor' ) );
			var row = el( 'div', 'mp-insp-sel' );
			var sel = suggest( node );
			row.appendChild( el( 'code', '', sel + ' { }' ) );
			var cbtn = el( 'button', 'mp-insp-btn', L.copy || 'Kopieren' );
			cbtn.type = 'button';
			cbtn.addEventListener( 'click', function () {
				copy( sel + ' {\n\t\n}', cbtn );
			} );
			row.appendChild( cbtn );
			body.appendChild( row );

			body.appendChild( el( 'h4', '', L.path || 'Pfad' ) );
			var ul = el( 'ul', 'mp-insp-path' );
			var chain = [];
			var n = node;
			while ( n && n !== document.body && chain.length < 8 ) {
				chain.unshift( n );
				if ( n.classList && n.classList.contains( 'um' ) ) {
					break;
				}
				n = n.parentElement;
			}
			chain.forEach( function ( item ) {
				var li = el( 'li' );
				li.appendChild( el( 'span', '', item.tagName.toLowerCase() ) );
				chips( li, classesOf( item ) );
				ul.appendChild( li );
			} );
			body.appendChild( ul );

			body.appendChild( el( 'h4', '', L.styles || 'Berechnete Werte' ) );
			var cs = window.getComputedStyle( node );
			var grid = el( 'div', 'mp-insp-styles' );
			colorRow( grid, 'color', cs.color );
			colorRow( grid, 'background', cs.backgroundColor );
			colorRow( grid, 'border', cs.borderTopWidth + ' ' + cs.borderTopStyle + ' ' + cs.borderTopColor );
			colorRow( grid, 'font', cs.fontSize + ' / ' + cs.fontWeight + ' ' + cs.fontFamily.split( ',' )[ 0 ] );
			colorRow( grid, 'padding', cs.padding );
			colorRow( grid, 'radius', cs.borderRadius );
			body.appendChild( grid );
		}
		panel.appendChild( body );

		if ( L.settings ) {
			var foot = el( 'div', 'mp-insp-foot' );
			var a = el( 'a', '', L.editCss || 'Eigenes CSS bearbeiten' );
			a.href = L.settings;
			foot.appendChild( a );
			panel.appendChild( foot );
		}
		panel.classList.add( 'is-open' );
	}

	function onMove( e ) {
		if ( ! active || pinned ) {
			return;
		}
		if ( isOwnUi( e.target ) ) {
			return;
		}
		var t = umTarget( e.target );
		if ( ! t ) {
			box.style.display = 'none';
			tag.style.display = 'none';
			return;
		}
		showTag( t, position( t ) );
	}

	function onClick( e ) {
		if ( ! active || isOwnUi( e.target ) ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		var t = umTarget( e.target );
		if ( pinned && ( ! t || t === pinned ) ) {
			pinned = null;
			box.classList.remove( 'is-pinned' );
			renderPanel( null );
			return;
		}
		if ( t ) {
			pinned = t;
			box.classList.add( 'is-pinned' );
			showTag( t, position( t ) );
			renderPanel( t );
		}
	}

	function onScroll() {
		if ( pinned ) {
			showTag( pinned, position( pinned ) );
		}
	}

	function toggle( on ) {
		active = on;
		pinned = null;
		document.documentElement.classList.toggle( 'mp-inspecting', on );
		try {
			if ( on ) {
				window.sessionStorage.setItem( STORE, '1' );
			} else {
				window.sessionStorage.removeItem( STORE );
			}
		} catch ( err ) {}
		if ( on ) {
			renderPanel( null );
		} else {
			box.style.display = 'none';
			tag.style.display = 'none';
			box.classList.remove( 'is-pinned' );
			panel.classList.remove( 'is-open' );
		}
	}

	function init() {
		setup();
		document.addEventListener( 'mousemove', onMove, true );
		document.addEventListener( 'click', onClick, true );
		window.addEventListener( 'scroll', onScroll, true );
		document.addEventListener( 'keydown', function ( e ) {
			if ( active && e.key === 'Escape' ) {
				toggle( false );
			}
		} );
		var node = document.querySelector( '#wp-admin-bar-mp-inspector > .ab-item' );
		if ( node ) {
			node.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				toggle( ! active );
			} );
		}
		try {
			if ( window.sessionStorage.getItem( STORE ) === '1' ) {
				toggle( true );
			}
		} catch ( err ) {}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
