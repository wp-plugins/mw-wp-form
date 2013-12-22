<?php
require_once('../../../../wp-load.php');
require_once('../../../../wp-admin/includes/admin.php');
do_action( 'admin_init' );

if ( !is_user_logged_in() ) {
	exit( 'You must be logged in to access this script.' );
}
header( 'Content-Type: text/javascript' );
?>
( function() {
	tinymce.create( 'tinymce.plugins.<?php echo MW_WP_Form_Admin_Page::SHORTCODE_BUTTON_NAME; ?>', {

		init: function( ed, url ) {
		},

		createControl: function( n, cm ) {
			if ( n == '<?php echo MW_WP_Form_Admin_Page::SHORTCODE_BUTTON_NAME; ?>' ) {
				var mlb = cm.createListBox( '<?php echo MW_WP_Form_Admin_Page::SHORTCODE_BUTTON_NAME; ?>List', {
					title: '<?php _e( 'Shortcodes' ); ?>',
					onselect: function( v ) {
						tinyMCE.activeEditor.selection.setContent( '[' + v + ']' );
					 }
				});

				// Add some values to the list box
				<?php
				global $wp_filter;
				foreach ( $wp_filter['mwform_add_shortcode'][10] as $functions ) {
					foreach ( $functions as $function ) {
						if ( method_exists( $function[0], 'get_qtags' ) ) {
							$qtags = $function[0]->get_qtags();
							?>
							mlb.add(
								'<?php echo $qtags['display']; ?>',
								'<?php echo $qtags['arg1'] . $qtags['arg2']; ?>'
							);
							<?
						}
					}
				}
				?>
				return mlb;
			}
			return null;
		},

		getInfo: function() {
			return {
				longname : 'MW WP Form Shortcode Selector',
				author: 'Takashi Kitajima',
				authorurl : 'http://2inc.org',
				infourl: 'http://plugins.2inc.org/mw-wp-form/',
				version: "1.0.0"
			};
		}
	});

	tinymce.PluginManager.add(
		'<?php echo MW_WP_Form_Admin_Page::SHORTCODE_BUTTON_NAME; ?>',
		tinymce.plugins.<?php echo MW_WP_Form_Admin_Page::SHORTCODE_BUTTON_NAME; ?>
	);
} )();