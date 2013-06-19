jQuery( function( $ ) {

	var cnt = $( '.validation-box' ).length;

	/**
	 * 開閉ボタン
	 */
	$( '.validation-btn b' ).on( 'click', function() {
		$( this ).parent().siblings( '.validation-content' ).slideToggle( 'high' );
	} );

	/**
	 * 削除ボタン
	 */
	$( '.validation-remove b' ).on( 'click', function() {
		cnt++;
		$( this ).closest( '.validation-box' ).fadeOut( function() {
			$( this ).remove();
		} );
	} );

	/**
	 * 追加ボタン
	 */
	$( '#mw-wp-form_validation b' ).click( function() {
		cnt++;
		var clone = $( this ).parent().find( '.validation-box:first' ).clone( true );
		clone.find( 'input' ).each( function() {
			$( this ).attr( 'name', $( this ).attr( 'name' ).replace( /\[\d+\]/, '[' + cnt + ']' ) );
		} );
		clone.hide();
		$( this ).siblings( '.validation-box:first' ).after( clone.fadeIn() );
	} );

	/**
	 * ターゲット名をラベルとして表示
	 */
	$( '.targetKey' ).on( 'keyup', function() {
		var val = $( this ).val();
		console.log( val );
		$( this ).parent().parent().find( '.validation-btn span' ).text( val );
	} );

	/**
	 * 完了ページの入力エリアからオリジナルボタンを消去
	 */
	$( window ).on( 'load', function() {
		$( '#mw-wp-form_complete_message_metabox input[id^="qt_mw-wp-form_complete_message_mwform_"]' ).remove();
	} );

} );