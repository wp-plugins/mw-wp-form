<?php
/**
 * Name: MW WP Form Admin Page
 * URI: http://2inc.org
 * Description: 管理画面クラス
 * Version: 1.2.1
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: February 21, 2013
 * Modified: May 13, 2013
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
	const DBDATA = 'mwf_';
	private $postdata;
	private $form_post_type = array();	// DB登録使用時のカスタム投稿タイプ名

	/**
	 * __construct
	 */
	public function __construct() {
		add_action( 'admin_print_styles', array( $this, 'admin_style' ) );
		add_action( 'admin_print_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_head', array( $this, 'add_forms_columns' ) );
		add_action( 'admin_head', array( $this, 'cpt_public_false' ) );
		add_action( 'admin_head', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_filter( 'user_can_richedit', array( $this, 'disable_visual_editor' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktag' ) );
	}

	/**
	 * get_post_data
	 * フォームの設定データを返す
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

		$_posts = get_posts( array(
			'post_type' => self::NAME,
			'posts_per_page' => -1
		) );
		foreach ( $_posts as $_post ) {
			$post_meta = get_post_meta( $_post->ID, self::NAME, true );
			if ( empty( $post_meta['usedb'] ) )
				continue;

			$post_type = self::DBDATA . $_post->ID;
			register_post_type( $post_type, array(
				'label' => $_post->post_title,
				'labels' => array(
					'name' => $_post->post_title,
					'singular_name' => $_post->post_title,
					'edit_item' => __( 'Edit ', self::DOMAIN ) . ':' . $_post->post_title,
					'view_item' => __( 'View', self::DOMAIN ) . ':' . $_post->post_title,
					'search_items' => __( 'Search', self::DOMAIN ) . ':' . $_post->post_title,
					'not_found' => __( 'No data found', self::DOMAIN ),
					'not_found_in_trash' => __( 'No data found in Trash', self::DOMAIN ),
				),
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => 'edit.php?post_type=' . self::NAME,
				'supports' => array( 'title' ),
			) );
			$this->form_post_type[] = $post_type;
		}
	}

	/**
	 * cpt_public_false
	 * DB登録データの一覧、詳細画面で新規追加のリンクを消す
	 */
	public function cpt_public_false() {
		if ( in_array( get_post_type(), $this->form_post_type ) ) : ?>
		<style type="text/css">
		h2 a.add-new-h2 {
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
		$post_type = get_post_type();
		if ( self::NAME == $post_type ) {
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
			// 自動返信メール設定
			add_meta_box(
				self::NAME.'_mail',
				__( 'Automatic Reply Email Options', self::DOMAIN ),
				array( $this, 'add_mail_options' ),
				self::NAME, 'side'
			);
			// 管理者メール設定
			add_meta_box(
				self::NAME.'_admin_mail',
				__( 'Admin Email Options', self::DOMAIN ),
				array( $this, 'add_admin_mail_options' ),
				self::NAME, 'side'
			);
			// 設定
			add_meta_box(
				self::NAME.'_settings',
				__( 'settings', self::DOMAIN ),
				array( $this, 'settings' ),
				self::NAME, 'side'
			);
		} elseif ( in_array( $post_type, $this->form_post_type ) ) {
			add_meta_box(
				self::NAME.'_custom_fields',
				__( 'Custom Fields', self::DOMAIN ),
				array( $this, 'custom_fields' ),
				$post_type
			);
		}
	}

	/**
	 * custom_fields
	 * DB登録データの詳細画面にカスタムフィールドを表示
	 */
	public function custom_fields() {
		global $post;
		$post_custom = get_post_custom( $post->ID );
		if ( ! empty( $post_custom ) && is_array( $post_custom ) ) {
			?>
			<table border="0" cellpadding="0" cellspacing="0">
			<?php
			foreach ( $post_custom as $key => $val ) {
				if ( $key == '_edit_lock' )
					continue;
				?>
					<tr>
						<th><?php echo esc_html( $key ); ?></th>
						<td><?php echo nl2br( esc_html( $val[0] ) ); ?></td>
					</tr>
				<?php
			}
			?>
			</table>
			<?php
		}
	}

	/**
	 * add_quicktag
	 * HTMLエディタにクイックタグを追加
	 */
	public function add_quicktag() {
		if ( self::NAME == get_post_type() ) : ?>
		<script type="text/javascript">
		if ( typeof( QTags ) !== 'undefined' ) {
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
		}
		</script>
		<?php
		endif;
	}

	/**
	 * admin_style
	 * CSS適用
	 */
	public function admin_style() {
		$post_type = get_post_type();
		if ( self::NAME == $post_type || in_array( $post_type, $this->form_post_type ) ) {
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
			<span id="formkey_field">[mwform_formkey key="<?php the_ID(); ?>"]</span>
		</p>
		<?php
	}

	/**
	 * settings
	 * $post を取得するための引数を有効にするフィールドを表示
	 */
	public function settings() {
		global $post;
		?>
		<p>
			<label><input type="checkbox" name="<?php echo esc_attr( self::NAME ); ?>[querystring]" value="1" <?php checked( $this->get_post_data( 'querystring' ), 1 ); ?> /> <?php _e( 'Activate Query string of post', self::DOMAIN ); ?></label><br />
			<?php _e( 'If this field is active, MW WP Form get the post as query string "post_id" and you can use $post\'s property in editor', self::DOMAIN ); ?><br />
			<?php _e( 'Example: {ID}, {post_title}, {post_meta} etc...', self::DOMAIN ); ?>
		</p>
		<p>
			<input type="checkbox" name="<?php echo esc_attr( self::NAME ); ?>[usedb]" value="1" <?php checked( $this->get_post_data( 'usedb' ), 1 ); ?> /> <?php _e( 'Saving contact data in database', self::DOMAIN ); ?></label>
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
	 * 自動返信メール設定フォームを表示
	 */
	public function add_mail_options() {
		global $post;
		?>
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
	 * add_admin_mail_options
	 * 管理者宛メール設定フォームを表示
	 */
	public function add_admin_mail_options() {
		global $post;
		?>
		<p>
			<?php _e( 'If Admin Email Options is a blank, Automatic Replay Email Options is used as Admin Email Options.', self::DOMAIN ); ?>
		</p>
		<p>
			<b><?php _e( 'To ( E-mail address )', self::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[mail_to]" value="<?php echo esc_attr( $this->get_post_data( 'mail_to' ) ); ?>" /><br />
			<?php _e( 'If empty: Using admin E-mail address.', self::DOMAIN ); ?>
		</p>
		<p>
			<b><?php _e( 'Subject', self::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( self::NAME ); ?>[admin_mail_subject]" value="<?php echo esc_attr( $this->get_post_data( 'admin_mail_subject' ) ); ?>" />
		</p>
		<p>
			<b><?php _e( 'Ccontent', self::DOMAIN ); ?></b><br />
			<textarea name="<?php echo esc_attr( self::NAME ); ?>[admin_mail_content]" cols="30" rows="10"><?php echo esc_attr( $this->get_post_data( 'admin_mail_content' ) ); ?></textarea><br />
			<?php _e( '{key} is converted form data.', self::DOMAIN ); ?>
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

	/**
	 * add_form_columns_name
	 * DB登録使用時に問い合わせデータ一覧にカラムを追加
	 */
	public function add_forms_columns() {
		global $wp_query;
		$post_type = get_post_type();
		if ( ! is_admin() )
			return;
		if ( ! in_array( $post_type, $this->form_post_type ) )
			return;
		add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_form_columns_name' ) );
		add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'add_form_columns' ), 10, 2 );
	}
	public function add_form_columns_name( $columns ) {
		global $posts;
		unset( $columns['date'] );
		$columns['post_date'] = __( 'Registed Date', self::DOMAIN );
		foreach ( $posts as $post ) {
			$post_custom_keys = get_post_custom_keys( $post->ID );
			if ( ! empty( $post_custom_keys ) && is_array( $post_custom_keys ) ) {
				foreach ( $post_custom_keys as $key ) {
					if ( $key == '_edit_lock' )
						continue;
					$columns[$key] = $key;
				}
			}
		}
		return $columns;
	}
	public function add_form_columns( $column, $post_id ) {
		$post_custom_keys = get_post_custom_keys( $post_id );
		if ( $column == 'post_date' ) {
			$post = get_post( $post_id );
			echo esc_html( $post->post_date );
		}
		elseif ( !empty( $post_custom_keys ) && is_array( $post_custom_keys ) && in_array( $column, $post_custom_keys ) ) {
			$post_meta = get_post_meta( $post_id, $column, true );
			if ( $post_meta ) {
				echo esc_html( $post_meta );
			} else {
				echo '&nbsp;';
			}
		} else {
			echo '&nbsp;';
		}
	}
}
?>