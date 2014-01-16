jQuery( function( $ ) {

	$( '.mw_wp_form input[data-conv-half-alphanumeric="true"]' ).change( function() {
		var txt  = $( this ).val();
		var half = txt.replace( /[Ａ-Ｚａ-ｚ０-９]/g, function( s ) {
			return String.fromCharCode( s.charCodeAt( 0 ) - 0xFEE0 )
		} );
		$( this ).val( half );
	} );

} );

