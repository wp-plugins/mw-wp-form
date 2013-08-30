<?php
/**
 * Name: MW WP Form Admin Page
 * URI: http://2inc.org
 * Description: 管理画面クラス
 * Version: 1.5
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: February 21, 2013
 * Modified: August 26, 2013
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
		// add_filter( 'user_can_richedit', array( $this, 'disable_visual_editor' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'add_quicktag' ) );

		add_action( 'in_admin_footer', array( $this, 'add_csv_download_button' ) );
		add_action( 'wp_loaded', array( $this, 'csv_download' ) );
	}

	/**
	 * add_csv_download_button
	 * CSVダウンロードボタンを表示
	 */
	public function add_csv_download_button() {
		$post_type = get_post_type();
		$page = ( basename( $_SERVER['PHP_SELF'] ) );
		if ( in_array( $post_type, $this->form_post_type ) && $page == 'edit.php' ) {
			$action = $_SERVER['REQUEST_URI'];
			?>
			<form id="mw-wp-form_csv" method="post" action="<?php echo esc_url( $action ); ?>">
				<input type="hidden" name="test" value="hoge" />
				<input type="submit" value="<?php _e( 'CSV Download', MWF_Config::DOMAIN ); ?>" class="button-primary" />
				<?php wp_nonce_field( MWF_Config::NAME ); ?>
			</form>
			<?php
		}
	}

	/**
	 * csv_download
	 * CSVを生成、出力
	 */
	public function csv_download() {
		if ( isset( $_GET['post_type'] ) ) {
			$post_type = $_GET['post_type'];
			if ( in_array( $post_type, $this->form_post_type ) && !empty( $_POST ) ) {
				check_admin_referer( MWF_Config::NAME );

				$posts_mwf = get_posts( array(
					'post_type' => $post_type,
					'pre_get_posts' => -1,
					'post_status' => 'any',
				) );
				$csv = '';

				// 見出しを追加
				$rows[] = array( 'ID', 'post_date', 'post_modified', 'post_title' );
				foreach ( $posts_mwf as $post ) {
					setup_postdata( $post );
					$columns = array();
					foreach ( $posts_mwf as $post ) {
						$post_custom_keys = get_post_custom_keys( $post->ID );
						if ( ! empty( $post_custom_keys ) && is_array( $post_custom_keys ) ) {
							foreach ( $post_custom_keys as $key ) {
								if ( preg_match( '/^_/', $key ) )
									continue;
								$columns[] = $key;
							}
						}
					}
					$rows[0] = array_merge( $rows[0], $columns );
				}
				wp_reset_postdata();

				// 各データを追加
				foreach ( $posts_mwf as $post ) {
					setup_postdata( $post );
					$column = array();
					foreach ( $rows[0] as $key => $value ) {
						if ( isset( $post->$value ) ) {
							$column[$key] = $this->escape_double_quote( $post->$value );
						} else {
							$post_meta = get_post_meta( $post->ID, $value, true );
							$column[$key] = ( $post_meta ) ? $this->escape_double_quote( $post_meta ) : '';
						}
					}
					$rows[] = $column;
				}
				wp_reset_postdata();

				// エンコード
				foreach ( $rows as $row ) {
					$csv .= implode( ',', $row ) . "\r\n";
					$csv = mb_convert_encoding( $csv, 'SJIS-win', get_option( 'blog_charset' ) );
				}

				$file_name = 'mw_wp_form_' . date( 'YmdHis' ) . '.csv';
				header( 'Content-Type: application/octet-stream' );
				header( 'Content-Disposition: attachment; filename=' . $file_name );
				echo $csv;
				exit;
			}
		}
	}
	private function escape_double_quote( $value ) {
		$value = str_replace( '"', '""', $value );
		return '"' . $value . '"';
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
		register_post_type( MWF_Config::NAME, array(
			'label' => 'MW WP Form',
			'labels' => array(
				'name' => 'MW WP Form',
				'singular_name' => 'MW WP Form',
				'add_new_item' => __( 'Add New Form', MWF_Config::DOMAIN ),
				'edit_item' => __( 'Edit Form', MWF_Config::DOMAIN ),
				'new_item' => __( 'New Form', MWF_Config::DOMAIN ),
				'view_item' => __( 'View Form', MWF_Config::DOMAIN ),
				'search_items' => __( 'Search Forms', MWF_Config::DOMAIN ),
				'not_found' => __( 'No Forms found', MWF_Config::DOMAIN ),
				'not_found_in_trash' => __( 'No Forms found in Trash', MWF_Config::DOMAIN ),
			),
			'public'  => false,
			'show_ui' => true,
		) );

		$_posts = get_posts( array(
			'post_type' => MWF_Config::NAME,
			'posts_per_page' => -1
		) );
		foreach ( $_posts as $_post ) {
			$post_meta = get_post_meta( $_post->ID, MWF_Config::NAME, true );
			if ( empty( $post_meta['usedb'] ) )
				continue;

			$post_type = MWF_Config::DBDATA . $_post->ID;
			register_post_type( $post_type, array(
				'label' => $_post->post_title,
				'labels' => array(
					'name' => $_post->post_title,
					'singular_name' => $_post->post_title,
					'edit_item' => __( 'Edit ', MWF_Config::DOMAIN ) . ':' . $_post->post_title,
					'view_item' => __( 'View', MWF_Config::DOMAIN ) . ':' . $_post->post_title,
					'search_items' => __( 'Search', MWF_Config::DOMAIN ) . ':' . $_post->post_title,
					'not_found' => __( 'No data found', MWF_Config::DOMAIN ),
					'not_found_in_trash' => __( 'No data found in Trash', MWF_Config::DOMAIN ),
				),
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => 'edit.php?post_type=' . MWF_Config::NAME,
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
		if ( MWF_Config::NAME == $post_type ) {
			global $post;
			// 設定データ取得
			$this->postdata = get_post_meta( $post->ID, MWF_Config::NAME, true );
			// 完了画面内容
			add_meta_box(
				MWF_Config::NAME.'_complete_message_metabox',
				__( 'Complete Message', MWF_Config::DOMAIN ),
				array( $this, 'add_complete_message' ),
				MWF_Config::NAME, 'normal'
			);
			// 入力画面URL
			add_meta_box(
				MWF_Config::NAME.'_url',
				__( 'URL Options', MWF_Config::DOMAIN ),
				array( $this, 'add_url' ),
				MWF_Config::NAME, 'normal'
			);
			// バリデーション
			add_meta_box(
				MWF_Config::NAME.'_validation',
				__( 'Validation Rule', MWF_Config::DOMAIN ),
				array( $this, 'add_validation_rule' ),
				MWF_Config::NAME, 'normal'
			);
			// フォーム識別子
			add_meta_box(
				MWF_Config::NAME.'_formkey',
				__( 'Form Key', MWF_Config::DOMAIN ),
				array( $this, 'display_form_key' ),
				MWF_Config::NAME, 'side'
			);
			// 自動返信メール設定
			add_meta_box(
				MWF_Config::NAME.'_mail',
				__( 'Automatic Reply Email Options', MWF_Config::DOMAIN ),
				array( $this, 'add_mail_options' ),
				MWF_Config::NAME, 'side'
			);
			// 管理者メール設定
			add_meta_box(
				MWF_Config::NAME.'_admin_mail',
				__( 'Admin Email Options', MWF_Config::DOMAIN ),
				array( $this, 'add_admin_mail_options' ),
				MWF_Config::NAME, 'side'
			);
			// 設定
			add_meta_box(
				MWF_Config::NAME.'_settings',
				__( 'settings', MWF_Config::DOMAIN ),
				array( $this, 'settings' ),
				MWF_Config::NAME, 'side'
			);
		} elseif ( in_array( $post_type, $this->form_post_type ) ) {
			add_meta_box(
				MWF_Config::NAME.'_custom_fields',
				__( 'Custom Fields', MWF_Config::DOMAIN ),
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
		$upload_file_keys = get_post_custom_values( '_' . MWF_Config::UPLOAD_FILE_KEYS, $post->ID );
		if ( ! empty( $post_custom ) && is_array( $post_custom ) ) {
			?>
			<table border="0" cellpadding="0" cellspacing="0">
			<?php
			foreach ( $post_custom as $key => $value ) {
				if ( preg_match( '/^_/', $key ) ) continue;
				?>
				<tr>
					<th><?php echo esc_html( $key ); ?></th>
					<td>
						<?php
						if ( is_array( $upload_file_keys ) && in_array( $key, $upload_file_keys ) ) {
							$mimetype = get_post_mime_type( $value[0] );
							if ( $mimetype ) {
								// 画像だったら
								if ( preg_match( '/^image\/.+?$/', $mimetype ) ) {
									$src = wp_get_attachment_image_src( $value[0], 'midium' );
									echo '<img src="' . esc_url( $src[0] ) .'" alt="" />';
								}
								// 画像以外
								else {
									$src = wp_get_attachment_image_src( $value[0], 'none', true );
									echo '<img src="' . esc_url( $src[0] ) .'" alt="" />';
									echo '<a href="' . esc_url( wp_get_attachment_url( $value[0] ) ) .'" target="_blank">' . esc_url( wp_get_attachment_url( $value[0] ) ) .'</a>';
								}
							}
						} else {
							echo nl2br( esc_html( $value[0] ) );
						}
						?>
					</td>
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
		if ( MWF_Config::NAME == get_post_type() ) : ?>
		<script type="text/javascript">
		if ( typeof( QTags ) !== 'undefined' ) {
			<?php do_action( 'mwform_add_qtags' ); ?>
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
		if ( MWF_Config::NAME == $post_type || in_array( $post_type, $this->form_post_type ) ) {
			$url = plugin_dir_url( __FILE__ );
			wp_register_style( MWF_Config::DOMAIN.'-admin', $url.'../css/admin.css' );
			wp_enqueue_style( MWF_Config::DOMAIN.'-admin' );
		}
	}

	/**
	 * admin_scripts
	 * JavaScript適用
	 */
	public function admin_scripts() {
		if ( MWF_Config::NAME == get_post_type() ) {
			$url = plugin_dir_url( __FILE__ );
			wp_register_script( MWF_Config::DOMAIN.'-admin', $url.'../js/admin.js' );
			wp_enqueue_script( MWF_Config::DOMAIN.'-admin' );
		}
	}

	/**
	 * save_post
	 * @param	$post_ID
	 */
	public function save_post( $post_ID ) {
		if ( ! isset( $_POST[MWF_Config::NAME.'_nonce'] ) )
			return $post_ID;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_ID;
		if ( !wp_verify_nonce( $_POST[MWF_Config::NAME.'_nonce'], MWF_Config::NAME ) )
			return $post_ID;
		if ( !current_user_can( 'manage_options', $post_ID ) )
			return $post_ID;

		$data = $_POST[MWF_Config::NAME];
		if ( !empty( $data['validation'] ) && is_array( $data['validation'] ) ) {
			$validation = array();
			foreach ( $data['validation'] as $_validation ) {
				if ( empty( $_validation['target'] ) ) continue;
				foreach ( $_validation as $key => $value ) {
					// between min, max
					if ( $key == 'between' ) {
						if ( !MWF_Functions::is_numeric( $value['min'] ) ) {
							unset( $_validation[$key]['min'] );
						}
						if ( !MWF_Functions::is_numeric( $value['max'] ) ) {
							unset( $_validation[$key]['max'] );
						}
					}
					// minlength min
					elseif ( $key == 'minlength' && !MWF_Functions::is_numeric( $value['min'] ) ) {
						unset( $_validation[$key] );
					}
					// fileType types
					elseif ( $key == 'fileType' && isset( $value['types'] ) && !preg_match( '/^[0-9A-Za-z,]+$/', $value['types'] ) ) {
						unset( $_validation[$key] );
					}
					// fileSize bytes
					elseif ( $key == 'fileSize' && !MWF_Functions::is_numeric( $value['bytes'] ) ) {
						unset( $_validation[$key] );
					}

					// 要素が空のときは削除
					// 単一項目のとき
					if ( empty( $value ) ) {
						unset( $_validation[$key] );
					}
					// 配列のとき
					elseif ( is_array( $value ) && !array_diff( $value, array( '' ) ) ) {
						unset( $_validation[$key] );
					}
				}
				$validation[] = $_validation;
			}
			$data['validation'] = $validation;
		}
		$old_data = get_post_meta( $post_ID, MWF_Config::NAME, true );
		update_post_meta( $post_ID, MWF_Config::NAME, $data, $old_data );
	}

	/**
	 * display_form_key
	 * formkeyのテキストフィールドを表示
	 */
	public function display_form_key() {
		global $post;
		?>
		<p>
			<span id="formkey_field">[mwform_formkey key="<?php the_ID(); ?>"]</span>
			<span class="mwf_note">
				<?php _e( 'Copy and Paste this shortcode.', MWF_Config::DOMAIN ); ?><br />
				<?php _e( 'The key to use with hook is ', MWF_Config::DOMAIN ); ?><?php echo MWF_Config::NAME; ?>-<?php echo $post->ID; ?>
			</span>
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
			<label><input type="checkbox" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[querystring]" value="1" <?php checked( $this->get_post_data( 'querystring' ), 1 ); ?> /> <?php _e( 'Activate Query string of post', MWF_Config::DOMAIN ); ?></label><br />
			<span class="mwf_note"><?php _e( 'If this field is active, MW WP Form get the post as query string "post_id" and you can use $post\'s property in editor', MWF_Config::DOMAIN ); ?><br />
			<?php _e( 'Example: {ID}, {post_title}, {post_meta} etc...', MWF_Config::DOMAIN ); ?></span>
		</p>
		<p>
			<label><input type="checkbox" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[usedb]" value="1" <?php checked( $this->get_post_data( 'usedb' ), 1 ); ?> /> <?php _e( 'Saving contact data in database', MWF_Config::DOMAIN ); ?></label>
		</p>
		<table border="0" cellpadding="0" cellspacing="0" class="akismet">
			<tr>
				<th colspan="2"><?php _e( 'Akismet Setting', MWF_Config::DOMAIN ); ?></th>
			</tr>
			<tr>
				<td>author</td>
				<td><input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[akismet_author]" value="<?php echo esc_attr( $this->get_post_data( 'akismet_author' ) ); ?>" /></td>
			</tr>
			<tr>
				<td>email</td>
				<td><input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[author_email]" value="<?php echo esc_attr( $this->get_post_data( 'akismet_author_email' ) ); ?>" /></td>
			</tr>
			<tr>
				<td>url</td>
				<td><input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[author_url]" value="<?php echo esc_attr( $this->get_post_data( 'akismet_author_url' ) ); ?>" /></td>
			</tr>
		</table>
		<span class="mwf_note"><?php _e( 'Input the key to use Akismet.', MWF_Config::DOMAIN ); ?></span>
		<?php
	}

	/**
	 * add_complete_message
	 * 完了画面内容の入力画面を表示
	 */
	public function add_complete_message() {
		global $post;
		$content = $this->get_post_data( 'complete_message' );
		wp_editor( $content, MWF_Config::NAME.'_complete_message', array(
			'textarea_name' => MWF_Config::NAME.'[complete_message]',
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
			<b><?php _e( 'Subject', MWF_Config::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[mail_subject]" value="<?php echo esc_attr( $this->get_post_data( 'mail_subject' ) ); ?>" />
		</p>
		<p>
			<b><?php _e( 'Ccontent', MWF_Config::DOMAIN ); ?></b><br />
			<textarea name="<?php echo esc_attr( MWF_Config::NAME ); ?>[mail_content]" cols="30" rows="10"><?php echo esc_attr( $this->get_post_data( 'mail_content' ) ); ?></textarea><br />
			<span class="mwf_note"><?php _e( '{key} is converted form data.', MWF_Config::DOMAIN ); ?></span>
		</p>
		<p>
			<b><?php _e( 'Automatic reply email', MWF_Config::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[automatic_reply_email]" value="<?php echo esc_attr( $this->get_post_data( 'automatic_reply_email') ); ?>" /><br />
			<span class="mwf_note"><?php _e( 'Input the key to use as transmission to automatic reply email.', MWF_Config::DOMAIN ); ?></span>
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
			<?php _e( 'If Admin Email Options is a blank, Automatic Replay Email Options is used as Admin Email Options.', MWF_Config::DOMAIN ); ?>
		</p>
		<p>
			<b><?php _e( 'To ( E-mail address )', MWF_Config::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[mail_to]" value="<?php echo esc_attr( $this->get_post_data( 'mail_to' ) ); ?>" /><br />
			<span class="mwf_note"><?php _e( 'If empty: Using admin E-mail address.', MWF_Config::DOMAIN ); ?></span>
		</p>
		<p>
			<b><?php _e( 'Subject', MWF_Config::DOMAIN ); ?></b><br />
			<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[admin_mail_subject]" value="<?php echo esc_attr( $this->get_post_data( 'admin_mail_subject' ) ); ?>" />
		</p>
		<p>
			<b><?php _e( 'Ccontent', MWF_Config::DOMAIN ); ?></b><br />
			<textarea name="<?php echo esc_attr( MWF_Config::NAME ); ?>[admin_mail_content]" cols="30" rows="10"><?php echo esc_attr( $this->get_post_data( 'admin_mail_content' ) ); ?></textarea><br />
			<span class="mwf_note"><?php _e( '{key} is converted form data.', MWF_Config::DOMAIN ); ?></span>
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
		<input type="hidden" name="<?php echo esc_attr( MWF_Config::NAME ); ?>_nonce" value="<?php echo wp_create_nonce( MWF_Config::NAME ); ?>" />
		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<th><?php _e( 'Input Page URL', MWF_Config::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[input_url]" value="<?php echo esc_attr( $this->get_post_data( 'input_url' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Confirmation Page URL', MWF_Config::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[confirmation_url]" value="<?php echo esc_attr( $this->get_post_data( 'confirmation_url' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Complete Page URL', MWF_Config::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[complete_url]" value="<?php echo esc_attr( $this->get_post_data( 'complete_url' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Validation Error Page URL', MWF_Config::DOMAIN ); ?></th>
				<td>
					<input type="text" name="<?php echo esc_attr( MWF_Config::NAME ); ?>[validation_error_url]" value="<?php echo esc_attr( $this->get_post_data( 'validation_error_url' ) ); ?>" />
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
			'fileType'     => array(),
			'fileSize'     => array(),
		);
		// 空の隠れバリデーションフィールド（コピー元）を挿入
		array_unshift( $postdata, $validation_keys );
		?>
		<b id="add-validation-btn"><?php _e( 'Add Validation rule', MWF_Config::DOMAIN ); ?></b>
		<?php foreach ( $postdata as $key => $value ) : $value = array_merge( $validation_keys, $value ); ?>
		<div class="validation-box"<?php if ( $key === 0 ) : ?> style="display:none"<?php endif; ?>>
			<div class="validation-remove"><b>×</b></div>
			<div class="validation-btn"><span><?php echo esc_attr( $value['target'] ); ?></span><b>▼</b></div>
			<div class="validation-content">
				<?php _e( 'The key which applies validation', MWF_Config::DOMAIN ); ?>：<input type="text" class="targetKey" value="<?php echo esc_attr( $value['target'] ); ?>" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][target]" />
				<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td colspan="2">
							<label><input type="checkbox" <?php checked( $value['noempty'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][noempty]" value="1" /><?php _e( 'No empty', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['required'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][required]" value="1" /><?php _e( 'No empty( with checkbox )', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['numeric'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][numeric]" value="1" /><?php _e( 'Numeric', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['alpha'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][alpha]" value="1" /><?php _e( 'Alphabet', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['alphanumeric'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][alphanumeric]" value="1" /><?php _e( 'Alphabet and Numeric', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['zip'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][zip]" value="1" /><?php _e( 'Zip Code', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['tel'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][tel]" value="1" /><?php _e( 'Tel', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['mail'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][mail]" value="1" /><?php _e( 'E-mail', MWF_Config::DOMAIN ); ?></label>
							<label><input type="checkbox" <?php checked( $value['date'], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][date]" value="1" /><?php _e( 'Date', MWF_Config::DOMAIN ); ?></label>
						</td>
					</tr>
					<tr>
						<td style="width:20%"><?php _e( 'The key at same value', MWF_Config::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$value['eq']['target'] ); ?>" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][eq][target]" /></td>
					</tr>
					<tr>
						<td><?php _e( 'The range of the number of characters', MWF_Config::DOMAIN ); ?></td>
						<td>
							<input type="text" value="<?php echo esc_attr( @$value['between']['min'] ); ?>" size="3" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][between][min]" />
							〜
							<input type="text" value="<?php echo esc_attr( @$value['between']['max'] ); ?>" size="3" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][between][max]" />
						</td>
					</tr>
					<tr>
						<td><?php _e( 'The number of the minimum characters', MWF_Config::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$value['minlength']['min'] ); ?>" size="3" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][minlength][min]" /></td>
					</tr>
					<tr>
						<td><?php _e( 'Permitted Extension', MWF_Config::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$value['fileType']['types'] ); ?>" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][fileType][types]" /> <span class="mwf_note"><?php _e( 'Example:jpg or jpg,txt,…', MWF_Config::DOMAIN ); ?></span></td>
					</tr>
					<tr>
						<td><?php _e( 'Permitted file size', MWF_Config::DOMAIN ); ?></td>
						<td><input type="text" value="<?php echo esc_attr( @$value['fileSize']['bytes'] ); ?>" name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][fileSize][bytes]" /> <span class="mwf_note"><?php _e( 'bytes', MWF_Config::DOMAIN ); ?></span></td>
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
		if ( MWF_Config::NAME == get_post_type() ) {
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
		$columns['post_date'] = __( 'Registed Date', MWF_Config::DOMAIN );
		foreach ( $posts as $post ) {
			$post_custom_keys = get_post_custom_keys( $post->ID );
			if ( ! empty( $post_custom_keys ) && is_array( $post_custom_keys ) ) {
				foreach ( $post_custom_keys as $key ) {
					if ( preg_match( '/^_/', $key ) )
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