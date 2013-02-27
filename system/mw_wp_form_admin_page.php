<?php
/**
 * Name: MW WP Form Admin Page
 * URI: http://2inc.org
 * Description: 管理画面クラス
 * Version: 1.1
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: February 21, 2013
 * Modified: February 27, 2013
 * License: GPL2
 *
 * Copyright 2013 Takashi Kitajima (email : inc@2inc.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
class MW_WP_Form_Admin_Page {

	const NAME = 'mw-wp-form';
	const DOMAIN = 'mw-wp-form';
	private $postmeta;

	/**
	 * __construct
	 */
	public function __construct() {
		add_action( 'admin_head', array( $this, 'cpt_public_false' ) );
		add_action( 'admin_head', array( $this, 'add_meta_box' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktag' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_style' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'user_can_richedit', array( $this, 'disable_visual_editor' ) );
	}

	/**
	 * get_post_data
	 */
	protected function get_post_data( $key ) {
		if ( isset( $this->postdata[$key] ) ) {
			return $this->postdata[$key];
		}
	}

	/**
	 * register_post_type
	 */
	public function register_post_type() {
		register_post_type( self::NAME, array(
			'label' => 'MW WP Form',
			'labels' => array(
				'name' => 'MW WP Form',
				'singular_name' => 'MW WP Form',
				'add_new_item' => __( 'Add New Form', self::DOMAIN ),
				'edit_item' => __( 'Edit Form', self::DOMAIN ),
				'new_item' => __( 'New Form', self::DOMAIN ),
				'view_item' => __( 'View Form', self::DOMAIN ),
				'search_items' => __( 'Search Forms', self::DOMAIN ),
				'not_found' => __( 'No Forms found', self::DOMAIN ),
				'not_found_in_trash' => __( 'No Forms found in Trash', self::DOMAIN ),
			),
			'public'  => false,
			'show_ui' => true,
		) );
	}

	/**
	 * cpt_public_false
	 */
	public function cpt_public_false() {
		if ( self::NAME == get_post_type() ) : ?>
		<style type="text/css">
		#misc-publishing-actions {
			display: none;
		}
		.post-php #message a {
			display: none;
		}
		.wp-list-table .post-title span.hide-if-no-js {
			display: none;
		}
		</style>
		<?php
		endif;
	}

	/**
	 * add_meta_box
	 */
	public function add_meta_box() {
		if ( self::NAME == get_post_type() ) {
			global $post;
			// 設定データ取得
			$this->postdata = get_post_meta( $post->ID, self::NAME, true );
			// 完了画面内容
			add_meta_box(
				self::NAME.'_complete_message_metabox',
				__( 'Complete Message', self::DOMAIN ),
				array( $this, 'add_complete_message' ),
				self::NAME, 'normal'
			);
			// 入力画面URL
			add_meta_box(
				self::NAME.'_url',
				__( 'URL Options', self::DOMAIN ),
				array( $this, 'add_url' ),
				self::NAME, 'normal'
			);
			// バリデーション
			add_meta_box(
				self::NAME.'_validation',
				__( 'Validation Rule', self::DOMAIN ),
				array( $this, 'add_validation_rule' ),
				self::NAME, 'normal'
			);
			// フォーム識別子
			add_meta_box(
				self::NAME.'_formkey',
				__( 'Form Key', self::DOMAIN ),
				array( $this, 'display_form_key' ),
				self::NAME, 'side'
			);
			// メール設定
			add_meta_box(
				self::NAME.'_mail',
				__( 'Mail Options', self::DOMAIN ),
				array( $this, 'add_mail_options' ),
				self::NAME, 'side'
			);
		}
	}

	/**
	 * add_quicktag
	 * HTMLエディタにクイックタグを追加
	 */
	public function add_quicktag() {
		if ( self::NAME == get_post_type() ) : ?>
		<script type="text/javascript">
		QTags.addButton(
			'<?php echo self::NAME; ?>_text',
			'<?php _e( 'Text', self::DOMAIN ); ?>',
			'[mwform_text name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_textarea',
			'<?php _e( 'Textarea', self::DOMAIN ); ?>',
			'[mwform_textarea name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_zip',
			'<?php _e( 'Zip Code', self::DOMAIN ); ?>',
			'[mwform_zip name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_tel',
			'<?php _e( 'Tel', self::DOMAIN ); ?>',
			'[mwform_tel name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_select',
			'<?php _e( 'Select', self::DOMAIN ); ?>',
			'[mwform_select name="" children=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_radio',
			'<?php _e( 'Radio', self::DOMAIN ); ?>',
			'[mwform_radio name="" children=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_checkbox',
			'<?php _e( 'Checkbox', self::DOMAIN ); ?>',
			'[mwform_checkbox name="" children=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_datepicker',
			'<?php _e( 'Datepicker', self::DOMAIN ); ?>',
			'[mwform_datepicker name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_password',
			'<?php _e( 'Password', self::DOMAIN ); ?>',
			'[mwform_password name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_backButton',
			'<?php _e( 'Back', self::DOMAIN ); ?>',
			'[mwform_backButton]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_submitButton',
			'<?php _e( 'Confirm &amp; Submit', self::DOMAIN ); ?>',
			'[mwform_submitButton]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_submit',
			'<?php _e( 'Submit', self::DOMAIN ); ?>',
			'[mwform_submit name="submit"]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_button',
			'<?php _e( 'Button', self::DOMAIN ); ?>',
			'[mwform_button name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_hidden',
			'<?php _e( 'Hidden', self::DOMAIN ); ?>',
			'[mwform_hidden name=""]',
			''
		);
		QTags.addButton(
			'<?php echo self::NAME; ?>_error',
			'<?php _e( 'Error Message', self::DOMAIN ); ?>',
			'[mwform_error keys=""]',
			''
		);
		</script>
		<?php
		endif;
	}

	/**
	 * admin_style
	 * CSS適用
	 */
	public function admin_style() {
		if ( self::NAME == get_post_type() ) {
			$url = plugin_dir_url( __FILE__ );
			wp_register_style( self::DOMAIN.'-admin', $url.'../css/admin.css' );
			wp_enqueue_style( self::DOMAIN.'-admin' );
		}
	}

	/**
	 * admin_scripts
	 * JavaScript適用
	 */
	public function admin_scripts() {
		if ( self::NAME == get_post_type() ) {
			$url = plugin_dir_url( __FILE__ );
			wp_register_script( self::DOMAIN.'-admin', $url.'../js/admin.js' );
			wp_enqueue_script( self::DOMAIN.'-admin' );
		}
	}

	/**
	 * save_post
	 * @param	$post_ID
	 */
	public function save_post( $post_ID ) {
		if ( ! isset( $_POST[self::NAME.'_nonce'] ) )
			return $post_ID;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_ID;
		if ( !wp_verify_nonce( $_POST[self::NAME.'_nonce'], self::NAME ) )
			return $post_ID;
		if ( !current_user_can( 'manage_options', $post_ID ) )
			return $post_ID;

		$data = $_POST[self::NAME];
		if ( !empty( $data['validation'] ) && is_array( $data['validation'] ) ) {
			$validation = array();
			foreach ( $data['validation'] as $_validation ) {
				if ( empty( $_validation['target'] ) ) continue;
				foreach ( $_validation as $key => $val ) {
					// 単一項目のとき
					if ( empty( $val ) ) {
						unset( $_validation[$key] );
					}
					// 配列のとき
					elseif ( is_array( $val ) && !array_diff( $val, array( '' ) ) ) {
						unset( $_validation[$key] );
					}
				}
				$validation[] = $_validation;
			}
			$data['validation'] = $validation;
		}
		$old_data = get_post_meta( $post_ID, self::NAME, true );
		update_post_meta( $post_ID, self::NAME, $data, $old_data );
	}

	/**
	 * display_form_key
	 * formkeyのテキストフィールドを表示
	 */
	public function display_form_key() {
		global $post;
		?>
		<p>
			<?php _e( 'Copy and Paste this shortcode.', self::DOMAIN ); ?><br />
			<input type="text" disabled="disabled" value='[mwform_formkey key="<?php the_ID(); ?>"]' size="30" />
		</p>
		<?php
	}

	/**
	 * add_complete_message
	 * 完了画面内容の入力画面を表示
	 */
	public function add_complete_message() {
		global $post;
		$content = $this->get_post_data( 'complete_message' );
		wp_editor( $content, self::NAME.'_complete_message', array(
			'textarea_name' => self::NAME.'[complete_message]',
			'textarea_rows' => 7,
		) );
	}

	/**
	 * add_mail_options
	 * メール設定フォームを表示
	 */
	public function add_mail_options() {
		global $post;
		?>
		<p>
			<b><?php _e( 'To ( E-mail address )', self::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[mail_to]" value="<?php echo esc_attr( $this->get_post_data( 'mail_to' ) ); ?>" /><br />
			<?php _e( 'If empty: Using admin E-mail address.', self::DOMAIN ); ?>
		</p>
		<p>
			<b><?php _e( 'Subject', self::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[mail_subject]" value="<?php echo esc_attr( $this->get_post_data( 'mail_subject' ) ); ?>" />
		</p>
		<p>
			<b><?php _e( 'Ccontent', self::DOMAIN ); ?></b><br />
			<textarea name="<?php echo esc_attr( self::NAME ); ?>[mail_content]" cols="30" rows="10"><?php echo esc_attr( $this->get_post_data( 'mail_content' ) ); ?></textarea><br />
			<?php _e( '{key} is converted form data.', self::DOMAIN ); ?>
		</p>
		<p>
			<b><?php _e( 'Automatic reply email', self::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[automatic_reply_email]" value="<?php echo esc_attr( $this->get_post_data( 'automatic_reply_email') ); ?>" /><br />
			<?php _e( 'Please input the key to use as transmission to automatic reply email.', self::DOMAIN ); ?>
		</p>
		<?php
	}

	/**
	 * add_url
	 * URL設定フォームを表示
	 */
	public function add_url() {
		global $post;
		?>
		<input type="hidden" name="<?php echo esc_attr( self::NAME ); ?>_nonce" value="<?php echo wp_create_nonce( self::NAME ); ?>" />
		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<th><?php _e( 'Input Page URL', self::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[input_url]" value="<?php echo esc_attr( $this->get_post_data( 'input_url' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Confirmation Page URL', self::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[confirmation_url]" value="<?php echo esc_attr( $this->get_post_data( 'confirmation_url' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Complete Page URL', self::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[complete_url]" value="<?php echo esc_attr( $this->get_post_data( 'complete_url' ) ); ?>" />
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * add_validation_rule
	 * バリデーションルール設定フォームを表示
	 */
	public function add_validation_rule() {
		global $post;
		if ( ! $postdata = $this->get_post_data( 'validation' ) )
			$postdata = array();
		$validation_keys = array(
			'target'       => '',
			'noempty'      => '',
			'required'     => '',
			'numeric'      => '',
			'alpha'        => '',
			'alphanumeric' => '',
			'zip'          => '',
			'tel'          => '',
			'mail'         => '',
			'date'         => '',
			'eq'           => array(),
			'between'      => array(),
			'minlength'    => array(),
		);
		// 空の隠れバリデーションフィールド（コピー元）を挿入
		array_unshift( $postdata, $validation_keys );
		?>
		<b id="add-validation-btn"><?php _e( 'Add Validation rule', self::DOMAIN ); ?></b>
		<?php foreach ( $postdata as $key => $val ) : $val = array_merge( $validation_keys, $val ); ?>
		<div class="validation-box"<?php if ( $key === 0 ) : ?> style="display:none"<?php endif; ?>>
			<div class="validation-remove"><b>×</b></div>
			<div class="validation-btn"><span><?php echo esc_attr( $val['target'] ); ?></span><b>▼</b></div>
			<div class="validation-content">
				<?php _e( 'The key which applies validation', self::DOMAIN ); ?>：<input type="text" class="targetKey" value="<?php echo esc_attr( $val['target'] ); ?>" name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][target]" />
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td colspan="2">
							<label><input type="checkbox" <?php checked( $val['noempty'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][noempty]" value="1" /><?php _e( 'No empty', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['required'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][required]" value="1" /><?php _e( 'No empty( with checkbox )', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['numeric'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][numeric]" value="1" /><?php _e( 'Numeric', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['alpha'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][alpha]" value="1" /><?php _e( 'Alphabet', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['alphanumeric'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][alphanumeric]" value="1" /><?php _e( 'Alphabet and Numeric', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['zip'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][zip]" value="1" /><?php _e( 'Zip code', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['tel'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][tel]" value="1" /><?php _e( 'Tel', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['mail'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][mail]" value="1" /><?php _e( 'E-mail', self::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $val['date'], 1 ); ?> name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][date]" value="1" /><?php _e( 'Date', self::DOMAIN ); ?></label>
						</td>
					</tr>
					<tr>
						<td style="width:20%"><?php _e( 'The key at same value', self::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$val['eq']['target'] ); ?>" name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][eq][target]" /></td>
					</tr>
					<tr>
						<td><?php _e( 'The range of the number of characters', self::DOMAIN ); ?></td>
						<td>
							<input type="text" value="<?php echo esc_attr( @$val['between']['min'] ); ?>" size="3" name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][between][min]" />
							〜
							<input type="text" value="<?php echo esc_attr( @$val['between']['max'] ); ?>" size="3" name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][between][max]" />
						</td>
					</tr>
					<tr>
						<td><?php _e( 'The number of the minimum characters', self::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$val['minlength']['min'] ); ?>" size="3" name="<?php echo self::NAME; ?>[validation][<?php echo $key; ?>][minlength][min]" /></td>
					</tr>
				</table>
			<!-- end .validation-content --></div>
		<!-- end .validatioin-box --></div>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * disable_visual_editor
	 * ビジュアルエディタを無効に
	 */
	public function disable_visual_editor() {
		if ( self::NAME == get_post_type() ) {
			return false;
		}
		return true;
	}
}
?>