jQuery( function( $ ) {

	$( '.mw_wp_form input[data-conv-half-alphanumeric="true"]' ).change( function() {
		var txt  = $( this ).val();
		var half = txt.replace( /[Ａ-Ｚａ-ｚ０-９]/g, function( s ) {
			return String.fromCharCode( s.charCodeAt( 0 ) - 0xFEE0 )
		} );
		$( this ).val( half );
	} );

	var file_delete = $( '.mw_wp_form .mwform-file-delete' );
	$( '.mw_wp_form input[type="file"]' ).change( function() {
		var name = $( this ).attr( 'name' );
		file_delete.closest( '[data-mwform-file-delete="' + name + '"]' ).show();
	} );
	file_delete.click( function() {
		$( this ).hide();
		var target = $( this ).data( 'mwform-file-delete' );
		var field = $( 'input[type="file"][name="' + target + '"]' );
		field.replaceWith( field.clone( true ) );
	} );

} );

