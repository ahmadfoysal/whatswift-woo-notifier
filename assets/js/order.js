/* SwiftNotify – order screen box */
( function () {
	'use strict';

	var box = document.querySelector( '.snfw-orderbox' );
	if ( ! box || ! window.snfwOrder ) {
		return;
	}

	var button = box.querySelector( '[data-snfw-send]' );
	var field = box.querySelector( '[data-snfw-message]' );
	var result = box.querySelector( '[data-snfw-result]' );
	var list = box.querySelector( '[data-snfw-list]' );
	if ( ! button || ! field ) {
		return;
	}

	button.addEventListener( 'click', function () {
		var text = field.value.trim();
		if ( ! text ) {
			field.focus();
			return;
		}

		var label = button.textContent;
		button.disabled = true;
		button.textContent = snfwOrder.sending;
		result.textContent = '';
		result.className = 'snfw-orderbox-result';

		var body = new URLSearchParams();
		body.append( 'action', 'snfw_order_send' );
		body.append( 'nonce', snfwOrder.nonce );
		body.append( 'order_id', box.getAttribute( 'data-order' ) );
		body.append( 'message', text );

		fetch( snfwOrder.ajax, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				var data = json && json.data ? json.data : {};
				result.textContent = data.message || snfwOrder.error;
				result.classList.add( json && json.success ? 'is-ok' : 'is-bad' );
				if ( data.row ) {
					var empty = list.querySelector( '[data-snfw-empty]' );
					if ( empty ) {
						empty.remove();
					}
					list.insertAdjacentHTML( 'afterbegin', data.row );
				}
				if ( json && json.success ) {
					field.value = '';
				}
			} )
			.catch( function () {
				result.textContent = snfwOrder.error;
				result.classList.add( 'is-bad' );
			} )
			.then( function () {
				button.disabled = false;
				button.textContent = label;
			} );
	} );
} )();
