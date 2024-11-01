(function( $ ) {
	$( document ).ready( function() {
		/**
		 * show loader on backup page
		 */
		$( '.zndskhc_submit_button' ).click( function() {
			$( this ).parent().find( '.zndskhc_loader' ).css( 'display', 'inline-block' );
			if ( $( this ).hasClass( 'zndskhc_export' ) ) {
				$( this ).parent().find( '.zndskhc_loader' ).fadeOut( 800 );
			}
		});
	});
})( jQuery );