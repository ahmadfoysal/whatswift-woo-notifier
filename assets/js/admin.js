/* SwiftNotify – settings screens */
( function () {
	'use strict';

	var cfg = window.snfwAdmin || { sample: {}, i18n: {} };
	var preview = document.querySelector( '[data-snfw-preview]' );
	var active = null;

	function escapeHtml( text ) {
		return text.replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* WhatsApp-style rendering of a template with sample data. */
	function renderPreview( field ) {
		if ( ! preview ) {
			return;
		}
		var text = field ? field.value : '';
		if ( ! text.trim() ) {
			preview.classList.add( 'is-empty' );
			preview.textContent = cfg.i18n.emptyPreview || '';
			return;
		}
		if ( ! field.hasAttribute( 'data-snfw-plain' ) ) {
			Object.keys( cfg.sample ).forEach( function ( tag ) {
				text = text.split( tag ).join( cfg.sample[ tag ] );
			} );
		}
		var html = escapeHtml( text )
			.replace( /(^|[\s(])\*([^*\n]+)\*(?=$|[\s.,!?)])/gm, '$1<strong>$2</strong>' )
			.replace( /(^|[\s(])_([^_\n]+)_(?=$|[\s.,!?)])/gm, '$1<em>$2</em>' )
			.replace( /(^|[\s(])~([^~\n]+)~(?=$|[\s.,!?)])/gm, '$1<s>$2</s>' )
			.replace( /(https?:\/\/[^\s<]+)/g, '<span class="snfw-link">$1</span>' )
			.replace( /\n/g, '<br>' );
		preview.classList.remove( 'is-empty' );
		preview.innerHTML = html;
	}

	function updateCount( field ) {
		var wrap = field.closest( '.snfw-template-body, form' );
		var counter = wrap ? wrap.querySelector( '[data-snfw-count]' ) : null;
		if ( counter ) {
			counter.textContent = field.value.length + ' ' + ( cfg.i18n.chars || '' );
		}
	}

	function activate( field ) {
		active = field;
		renderPreview( field );
		updateCount( field );
	}

	var fields = Array.prototype.slice.call( document.querySelectorAll( '[data-snfw-template]' ) );
	fields.forEach( function ( field ) {
		updateCount( field );
		field.addEventListener( 'focus', function () {
			activate( field );
		} );
		field.addEventListener( 'input', function () {
			activate( field );
		} );
	} );
	if ( fields.length ) {
		var first = document.querySelector( '.snfw-template.is-open [data-snfw-template]' ) || fields[ 0 ];
		activate( first );
	}

	/* Collapsible template cards. */
	document.querySelectorAll( '.snfw-template-toggle' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var card = button.closest( '.snfw-template' );
			var open = card.classList.toggle( 'is-open' );
			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				var field = card.querySelector( '[data-snfw-template]' );
				if ( field ) {
					field.focus();
				}
			}
		} );
	} );

	/* Dim cards that are switched off. */
	document.querySelectorAll( '.snfw-template-head input[type="checkbox"]' ).forEach( function ( box ) {
		box.addEventListener( 'change', function () {
			box.closest( '.snfw-template' ).classList.toggle( 'is-off', ! box.checked );
		} );
	} );

	/* Placeholder chips insert at the cursor of the last used field. */
	document.querySelectorAll( '[data-snfw-chip]' ).forEach( function ( chip ) {
		chip.addEventListener( 'click', function () {
			if ( ! active ) {
				return;
			}
			var card = active.closest( '.snfw-template' );
			if ( card && ! card.classList.contains( 'is-open' ) ) {
				card.classList.add( 'is-open' );
			}
			var tag = chip.getAttribute( 'data-snfw-chip' );
			var start = active.selectionStart;
			var end = active.selectionEnd;
			active.value = active.value.slice( 0, start ) + tag + active.value.slice( end );
			active.focus();
			active.selectionStart = active.selectionEnd = start + tag.length;
			active.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		} );
	} );

	/* Starter template. */
	document.querySelectorAll( '[data-snfw-reset]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var field = button.closest( '.snfw-template-body' ).querySelector( '[data-snfw-template]' );
			if ( field.value.trim() && ! window.confirm( cfg.i18n.confirmReset ) ) {
				return;
			}
			field.value = field.getAttribute( 'data-default' ) || '';
			field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			field.focus();
		} );
	} );

	/* Show / hide the Auth Key. */
	document.querySelectorAll( '[data-snfw-reveal]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var input = document.getElementById( button.getAttribute( 'data-snfw-reveal' ) );
			var hidden = 'password' === input.type;
			input.type = hidden ? 'text' : 'password';
			button.textContent = hidden ? cfg.i18n.hide : cfg.i18n.show;
		} );
	} );

	/* Confirm before resending or clearing. */
	document.querySelectorAll( '[data-snfw-confirm]' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( link.getAttribute( 'data-snfw-confirm' ) ) ) {
				event.preventDefault();
			}
		} );
	} );

	/* Warn about unsaved changes. */
	var dirty = false;
	document.querySelectorAll( 'form[data-snfw-dirty]' ).forEach( function ( form ) {
		form.addEventListener( 'input', function () {
			dirty = true;
		} );
		form.addEventListener( 'change', function () {
			dirty = true;
		} );
		form.addEventListener( 'submit', function () {
			dirty = false;
		} );
	} );
	window.addEventListener( 'beforeunload', function ( event ) {
		if ( dirty ) {
			event.preventDefault();
			event.returnValue = cfg.i18n.unsaved || '';
		}
	} );
} )();
