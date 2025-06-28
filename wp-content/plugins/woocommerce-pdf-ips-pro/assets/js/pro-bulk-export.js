jQuery( document ).ready( function( $ ) {
	var options = {
		dateFormat: 'yy-mm-dd'
	};

	$( '#date-from' ).datepicker( options );
	$( '#date-to' ).datepicker( options );

	$( function() {
		$( '.checkall' ).on( 'click', function () {
			$( this ).closest( 'fieldset' ).find( ':checkbox' ).prop( 'checked', this.checked );
		} );
	} );

	// replace Order date with Refund date when Credit Note is the selected
	$( '#document_type' ).on( 'change', function () {
		if ( $( '#document_type option:selected' ).val() === 'credit-note' )  {
			$( '#date_type option[value=order_date]' ).text( woocommerce_pdf_pro_bulk.refund_date );
		} else {
			$( '#date_type option[value=order_date]' ).text( woocommerce_pdf_pro_bulk.order_date );
		}
	} ).trigger( 'change' );

	// disable status filters for exporting credit notes by document date
	// & export by document date = always "only existing"
	$( '#wcpdf-pro-bulk-export #date_type, #wcpdf-pro-bulk-export #document_type' ).on( 'change', function( event ) {
		if ( $( '#wcpdf-pro-bulk-export #date_type' ).val() == 'document_date' ) {
			$( '#wcpdf-pro-bulk-export #only_existing' ).prop( 'disabled', true ).prop( 'checked', true );
			if ( $( '#wcpdf-pro-bulk-export #document_type' ).val() == 'credit-note' ) {
				$( '#wcpdf-pro-bulk-export .status-filters:input, .checkall' ).prop( 'disabled', true );
			} else {
				$( '#wcpdf-pro-bulk-export .status-filters:input, .checkall' ).prop( 'disabled', false );
			}
		} else {
			$( '#wcpdf-pro-bulk-export #only_existing' ).prop( 'disabled', false );
			$( '#wcpdf-pro-bulk-export .status-filters:input, .checkall' ).prop( 'disabled', false );
		}
	} ).trigger( 'change' );

	// change button text on summary selection
	$( '#wcpdf-pro-bulk-export #document_type' ).on( 'change', function ( event ) {
		if ( $( this ).val() == 'summary' ) {
			$( '.button.wpo_wcpdf_zip_bulk_export ').text( woocommerce_pdf_pro_bulk.download_pdf );
			$( '.button.wpo_wcpdf_cloud_service_bulk_export ').hide();
		} else {
			$( '.button.wpo_wcpdf_zip_bulk_export ').text( woocommerce_pdf_pro_bulk.download_zip );
			$( '.button.wpo_wcpdf_cloud_service_bulk_export ').show();
		}
	} ).trigger( 'change' );

	$( '.button.bulk-export' ).on( 'click', function( event ) {
		event.preventDefault();
		if ( $( this ).hasClass( 'disabled' ) ) {
			return;
		}

		var export_mode = ( $( this ).hasClass( 'wpo_wcpdf_cloud_service_bulk_export' ) ) ? 'upload' : 'download';

		$( '.button.bulk-export' ).addClass( 'disabled' );
		$( '#wcpdf-pro-bulk-export :input' ).prop( 'disabled', true );
		$( '.bulk-export-waiting' ).show();

		var status_filters = [];
		$( '#wcpdf-pro-bulk-export .status-filters' ).each( function() {
			if ( $( this ).is( ':checked' ) ) {
				status_filters.push( $( this ).val() );
			}
		} );

		if ( 0 === status_filters.length ) {
			$( '#bulk_export_status_error' ).show();
			$( '.button.bulk-export' ).removeClass( 'disabled' );
			$( '#wcpdf-pro-bulk-export :input' ).prop( 'disabled', false );
			$( '.bulk-export-waiting' ).hide();
			return;
		}
		
		let document_type = $( '#wcpdf-pro-bulk-export #document_type' ).val();
		let output_format = 'pdf';
			
		// is UBL output
		if ( document_type.indexOf( 'ubl' ) != -1 ) {
			document_type = document_type.replace( '_ubl', '' );
			output_format = 'ubl';
		}

		var data = {
			action:        'wpo_wcpdf_export_get_order_ids',
			security:      woocommerce_pdf_pro_bulk.nonce,
			date_from:     $( '#wcpdf-pro-bulk-export #date-from' ).val(),
			hour_from:     $( '#wcpdf-pro-bulk-export #hour-from' ).val(),
			minute_from:   $( '#wcpdf-pro-bulk-export #minute-from' ).val(),
			date_to:       $( '#wcpdf-pro-bulk-export #date-to' ).val(),
			hour_to:       $( '#wcpdf-pro-bulk-export #hour-to' ).val(),
			minute_to:     $( '#wcpdf-pro-bulk-export #minute-to' ).val(),
			date_type:     $( '#wcpdf-pro-bulk-export #date_type' ).val(),
			document_type: document_type,
			output_format: output_format,
			status_filter: status_filters,
			users_filter:  $( '#wcpdf-pro-bulk-export #users_filter' ).val() || [],
			only_existing: $( '#wcpdf-pro-bulk-export #only_existing' ).is( ':checked' ),
			skip_free:	   $( '#wcpdf-pro-bulk-export #skip_free' ).is( ':checked' )
		};

		// Allow 3rd parties to alter the arguments sent with the Ajax request
		// @author Aelia
		$( document ).trigger( 'wpo_wcpdf_export_get_order_ids_args', data );

		$.post( woocommerce_pdf_pro_bulk.ajax_url, data, function( response ) {
			response = $.parseJSON( response );
			if ( response !== null && typeof response === 'object' && 'error' in response ) {
				wpo_wcpdf_bulk_admin_notice( response.error, 'error' );
				$( '.button.bulk-export' ).removeClass( 'disabled' );
				$( '#wcpdf-pro-bulk-export :input' ).prop( 'disabled', false );
				$( '.bulk-export-waiting' ).hide();
			} else if ( response !== null && typeof response === 'object' ) {
				// we have order_ids!
				woocommerce_pdf_pro_bulk.saved_files = [];
				wpo_wcpdf_save_bulk( response, Number( woocommerce_pdf_pro_bulk.chunk_size ), 0, export_mode, data.document_type, data.output_format );
			}
		} );

		function wpo_wcpdf_save_bulk( order_ids, chunk_size, offset, export_mode, document_type, output_format ) {
			let order_ids_chunk;
			if ( 'summary' === document_type ) {
				order_ids_chunk = order_ids;
			} else {
				order_ids_chunk = order_ids.slice( offset, offset + chunk_size );
			}

			let data = {
				action:        'wpo_wcpdf_export_bulk',
				security:      woocommerce_pdf_pro_bulk.nonce,
				document_type: document_type,
				output_format: output_format,
				skip_free:     $( '#wcpdf-pro-bulk-export #skip_free' ).is( ':checked' ),
				only_existing: $( '#wcpdf-pro-bulk-export #only_existing' ).is( ':checked' ),
				users_filter:  $( '#wcpdf-pro-bulk-export #users_filter' ).val() || [],
				order_ids:     order_ids_chunk,
				export_mode:   export_mode,
			};

			$.ajax( {
				async:    true,
				url:      woocommerce_pdf_pro_bulk.ajax_url,
				data:     data,
				type:     'POST',
				success:  function( response ) {
					response = $.parseJSON( response );
					if ( response !== null && typeof response === 'object' && response.error.length > 0 ) {
						// error!
						$.each( response.error, function ( key, error ) {
							wpo_wcpdf_bulk_admin_notice( error, 'error' );
						} );
					} else if ( response !== null && typeof response === 'object' && response.success.length > 0 ) {
						// success!
						$.each( response.success, function ( key, filename ) {
							woocommerce_pdf_pro_bulk.saved_files.push( filename );
						} );

						let message = response.success.length + ' ' + response.output_format.toUpperCase() + ' ' + woocommerce_pdf_pro_bulk.documents_saved;
						wpo_wcpdf_bulk_admin_notice( message, 'success', 'replace' );
					}

					// calc make new offset
					offset = offset + chunk_size;
					// continue if we have order_ids left
					if ( offset < order_ids.length && response.document_type != 'summary' ) {
						wpo_wcpdf_save_bulk( order_ids, chunk_size, offset, export_mode, response.document_type, response.output_format );
					} else {
						let action = 'wpo_wcpdf_';
						switch ( export_mode ) {
							case 'download':
								$( '#wcpdf-pro-bulk-export :input' ).prop( 'disabled', false );
								$( '.button.bulk-export' ).removeClass( 'disabled' );
								$( '.bulk-export-waiting' ).hide();

								action += response.document_type == 'summary' ? 'download_file' : 'zip_bulk';
								let url;
								if ( woocommerce_pdf_pro_bulk.ajax_url.indexOf( "?" ) != -1 ) {
									url = woocommerce_pdf_pro_bulk.ajax_url+'&action='+action+'&security='+woocommerce_pdf_pro_bulk.nonce+'&cache='+response.cache+'&document_type='+response.document_type+'&output_format='+response.output_format;
								} else {
									url = woocommerce_pdf_pro_bulk.ajax_url+'?action='+action+'&security='+woocommerce_pdf_pro_bulk.nonce+'&cache='+response.cache+'&document_type='+response.document_type+'&output_format='+response.output_format;
								}

								let a      = document.createElement( 'A' );
								a.download = response.filename;
								a.href     = url;
								document.body.appendChild( a );
								a.click();
								document.body.removeChild( a );
								break;
							case 'upload':
								wpo_wcpdf_bulk_admin_notice( woocommerce_pdf_pro_bulk.uploading, 'success', 'replace' );

								action    += 'cloud_upload';
								let data   = {
									action:        action,
									security:      woocommerce_pdf_pro_bulk.nonce,
									document_type: response.document_type,
									cache:         response.cache,
								}
								$.ajax( {
									async:   true,
									url:     woocommerce_pdf_pro_bulk.ajax_url,
									data:    data,
									type:    'POST',
									success: function( response ) {
										$( '#wcpdf-pro-bulk-export :input' ).prop( 'disabled', false );
										$( '.button.bulk-export' ).removeClass( 'disabled' );
										$( '.bulk-export-waiting' ).hide();

										if ( response !== null && typeof response === 'object' ) {
											// error!
											if ( response.error !== undefined ) {
												wpo_wcpdf_bulk_admin_notice( response.error, 'error' );
											}
											// success!
											if ( response.success !== undefined ) {
												wpo_wcpdf_bulk_admin_notice( response.success, 'success', 'replace' );
											}
										}				
									}
								} );
								break;
							default:
								return;
						} 
					}
				}
			} );
		}

		function wpo_wcpdf_bulk_admin_notice( message, type, replace ) {
			var notice = '<div class="wpo_wcpdf_bulk_notice notice notice-'+type+'"><p>'+message+'</p></div>';

			$prev_notices = $( '.wpo_wcpdf_bulk_notice.notice-'+type );
			if ( typeof replace === 'undefined' || $prev_notices.length == 0 ) {
				$main_header = $( '#wpbody-content > .wrap > h2:first' );
				$main_header.after( notice );
				$( 'html, body' ).animate( { scrollTop: 0 }, 'slow' );
			} else {
				$( '.wpo_wcpdf_bulk_notice.notice-'+type ).first().replaceWith( notice );
			}
		}

	});

	var select2_args = {
		allowClear: 		true,
		placeholder: 		$( ':input#users_filter' ).data( 'placeholder' ),
		minimumInputLength: 2,
		escapeMarkup: 		function( m ) {
			return m;
		},
		ajax: 				{
			url:			woocommerce_pdf_pro_bulk.ajax_url,
			dataType:		'json',
			delay:			250,
			data:			function( params ) {
				return {
					term:		params.term,
					action:		'wpo_wcpdf_search_users',
					security:	woocommerce_pdf_pro_bulk.nonce,
				};
			},
			processResults: function( data ) {
				var terms = [];
				if ( data ) {
					$.each( data, function( id, text ) {
						terms.push( { id: id, text: text } );
					} );
				}
				return {
					results: terms
				};
			},
			cache: true
		}
	};

	$( ':input#users_filter' ).select2( select2_args );

	// Toggle status filters selection error message.
	$( '.status-filters, .checkall' ).change( function() {
		let checkedStatusFilters = $( '.status-filters:checked' ).length;
		$( '#bulk_export_status_error' ).toggle( 0 === checkedStatusFilters );
	} );
	
});
