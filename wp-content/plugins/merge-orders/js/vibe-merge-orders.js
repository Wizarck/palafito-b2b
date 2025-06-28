let vibe_merge_orders = (function( $ ) {

	/**
	 * The line items div
	 */
	let $postbox;

	let $popup;

	let _init = function() {
		$postbox = $( '#woocommerce-order-items' );

		if ( $postbox.length > 0 ) {
			$postbox.on( 'click', 'button.merge-order', _begin_merge_order );
		}

		$( document.body ).on( 'wc_backbone_modal_response', _popup_confirmed );

		if ( parseInt( vibe_merge_orders_data.has_been_merged_into ) ) {
			_add_refund_notice();
		}
	};

	let _add_refund_notice = function() {
		$postbox.find( '.wc-order-refund-items > .wc-order-totals tbody' ).prepend(
			'<tr><td colspan="2">' + vibe_merge_orders_data.refund_notice + '</td></tr>'
		);
	};

	let _begin_merge_order = function( e ) {
		e.preventDefault();

		_show_popup();
	};

	let _block_el = function( $el ) {
		$el.block( {
			message: null, overlayCSS: {
				background: '#fff', opacity: 0.6
			}
		} );
	};

	let _unblock_el = function( $el ) {
		$el.unblock();
	};

	let _show_popup = function() {
		$( this ).WCBackboneModal( {
			template: 'wc-modal-merge-orders'
		} );

		_refresh_popup();
	};

	let _popup_confirmed = function( event, target ) {
		if ( 'wc-modal-merge-orders' === target ) {
			_merge_order();
		}
	};

	let _refresh_popup = function() {
		_block_el( $( '#modal-merge-orders-orders' ) );

		let data = {
			action:   'vibe_merge_orders_popup_orders',
			nonce:    vibe_merge_orders_data.popup_nonce,
			order_id: $( 'button.merge-order' ).data('id')
		};

		$.get( vibe_merge_orders_data.ajaxurl, data, _show_popup_response )
	};

	let _show_popup_response = function( response ) {
		let $modal_container = $( '#modal-merge-orders-orders' );
		_unblock_el( $modal_container );

		if ( response.success ) {
			$modal_container.html( response.html );

			_init_popup();
		}
	};

	let _init_popup = function() {
		$popup = $( '#merge-orders-popup' );

		if ( $popup.length > 0 ) {
			$popup.find( '#merge_order_select' ).selectWoo( {
				ajax: {
					url: vibe_merge_orders_data.ajaxurl,
					method: 'GET',
					dataType: 'json',
					data: function( params ) {
						return {
							action: 'vibe_merge_orders_get_orders',
							nonce: vibe_merge_orders_data.get_orders_nonce,
							search : params.term || '',
							page : params.page || 1,
							amount: 20,
							target_id: $popup.data( 'order-id' )
						}
					}
				}
			} );

			_set_submit_button_state();

			$popup.find( '#merge_order_select' ).on( 'change', _order_select_changed );
			$popup.find( '#merge_order_orders' ).on( 'change', _orders_changed );

			$popup.find( 'button.add-order-to-merge' ).on( 'click', _add_order_to_merge );
		}
	}

	let _orders_changed = function( e ) {
		_set_submit_button_state();
	};

	let _set_submit_button_state = function() {
		if ( $popup.find( '#merge_order_orders' ).val() ) {
			_enable_submit_button();
		} else {
			_disable_submit_button();
		}
	};

	let _disable_submit_button = function() {
		$( '#btn-ok' ).attr( 'disabled', 'disabled' );
	};

	let _enable_submit_button = function() {
		$( '#btn-ok' ).removeAttr( 'disabled' );
	};

	let _add_error = function ( message ) {
		$popup.prepend( '<div class="notice notice-error"><p>' + message + '</p></div>' );
	};

	let _clear_errors = function() {
		$popup.find( '.notice.notice-error' ).remove();
	};

	let _order_select_changed = function( e ) {
		_clear_errors();
	};

	let _add_order_to_merge = function( e ) {
		let $select = $popup.find( '#merge_order_select' );

		if ( ! $select.val() ) {
			_add_error( 'You must select an order to add' );
			return;
		}

		let data = $select.selectWoo('data').shift();

		if ( parseInt( data.id ) === parseInt( $popup.data( 'order-id' ) ) ) {
			_add_error( 'You cannot merge an order with itself' );
			return;
		}

		let $orders = $popup.find( '#merge_order_orders' );
		let orders = $orders.val();
		let orders_arr = orders ? orders.split( '|' ) : [];

		if ( orders_arr.includes( data.id ) ) {
			_add_error( 'You have already added this order to the merge' );
			return;
		}

		orders_arr.push( data.id );

		$orders.val( orders_arr.join( '|' ) );
		$orders.trigger( 'change' );

		$popup.find( 'tbody' ).append( data.row_content );

		_setup_remove_order_events();

		$select.val( null ).trigger( 'change' );
	};

	let _setup_remove_order_events = function() {
		$popup.find( '.remove-order-from-merge' ).off( 'click', _remove_order_from_merge );
		$popup.find( '.remove-order-from-merge' ).on( 'click', _remove_order_from_merge );
	};

	let _remove_order_from_merge= function(e) {
		e.preventDefault();
		e.stopImmediatePropagation();

		let $button = $( this );
		let $row = $button.closest( 'tr' );
		let id = $row.data( 'order-id' );
		let $orders = $popup.find( '#merge_order_orders' );
		let orders = $orders.val();
		let orders_arr = orders ? orders.split( '|' ) : [];

		let index = orders_arr.indexOf(id);

		if ( index ) {
			orders_arr.splice( index, 1 );

			$orders.val( orders_arr.join( '|' ) );
			$orders.trigger( 'change' );

			$row.remove();
		}
	}

	let _merge_order = function( e ) {
		_block_el( $postbox );

		let data = {
			action: 'vibe_merge_orders_merge_order',
			nonce: vibe_merge_orders_data.merging_nonce,
			order_ids: $popup.find( '#merge_order_orders' ).val(),
			target_id: $popup.data( 'order-id' )
		};

		$.post( vibe_merge_orders_data.ajaxurl, data, _merge_order_response );
	};

	let _merge_order_response = function( response ) {
		_unblock_el( $postbox );

		if ( response.success ) {
			window.location.reload();
		}
	};

	$( _init );

	return {};

})( jQuery );