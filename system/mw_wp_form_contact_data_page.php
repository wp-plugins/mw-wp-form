<?php
/**
 * Name: MW WP Form Contact Data Page
 * URI: http://2inc.org
 * Description: DB保存データを扱うクラス
 * Version: 1.0.1
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : October 10, 2013
 * Modified: October 23, 2013
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
class MW_WP_Form_Contact_Data_Page {

	private $POST_DATA_NAME;
	private $postdata;
	private $form_post_type = array();	// DB登録使用時のカスタム投稿タイプ名

	/**
	 * __construct
	 */
	public function __construct() {
		$this->POST_DATA_NAME = '_' . MWF_Config::NAME . '_data';
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_style' ) );
		add_action( 'admin_head', array( $this, 'cpt_public_false' ) );
		add_action( 'admin_head', array( $this, 'add_forms_columns' ) );
		add_action( 'admin_head', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'in_admin_footer', array( $this, 'add_csv_download_button' ) );
		add_action( 'wp_loaded', array( $this, 'csv_download' ) );
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
	 * admin_style
	 * CSS適用
	 */
	public function admin_style() {
		$post_type = get_post_type();
		if ( in_array( $post_type, $this->form_post_type ) ) {
			$url = plugin_dir_url( __FILE__ );
			wp_register_style( MWF_Config::DOMAIN.'-admin', $url.'../css/admin.css' );
			wp_enqueue_style( MWF_Config::DOMAIN.'-admin' );
		}
	}

	/**
	 * register_post_type
	 * メインクラスから呼ばれる
	 */
	public function register_post_type() {
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
	 * add_csv_download_button
	 * CSVダウンロードボタンを表示
	 */
	public function add_csv_download_button() {
		$post_type = get_post_type();
		if ( true !== apply_filters( 'mwform_csv_button_' . $post_type, true ) )
			return;
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
								$columns[$key] = $key;
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
				}
				$csv = mb_convert_encoding( $csv, 'sjis-win', get_option( 'blog_charset' ) );

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
	 * add_meta_box
	 */
	public function add_meta_box() {
		$post_type = get_post_type();
		if ( in_array( $post_type, $this->form_post_type ) ) {
			global $post;
			$this->postdata = get_post_meta( $post->ID, $this->POST_DATA_NAME, true );
			add_meta_box(
				substr( $this->POST_DATA_NAME, 1 ) . '_custom_fields',
				__( 'Custom Fields', MWF_Config::DOMAIN ),
				array( $this, 'custom_fields' ),
				$post_type
			);
		}
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

	/**
	 * custom_fields
	 * DB登録データの詳細画面にカスタムフィールドを表示
	 */
	public function custom_fields() {
		global $post;
		$post_custom = get_post_custom( $post->ID );
		// 前のバージョンでは MWF_Config::UPLOAD_FILE_KEYS を配列で保持していなかったので分岐させる
		$_upload_file_keys = get_post_meta( $post->ID, '_' . MWF_Config::UPLOAD_FILE_KEYS, true );
		if ( is_array( $_upload_file_keys ) ) {
			$upload_file_keys = $_upload_file_keys;
		} else {
			$upload_file_keys = get_post_custom_values( '_' . MWF_Config::UPLOAD_FILE_KEYS, $post->ID );
		}
		if ( ! empty( $post_custom ) && is_array( $post_custom ) ) {
			?>
			<table border="0" cellpadding="0" cellspacing="0">
				<?php
				foreach ( $post_custom as $key => $value ) :
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
									$src = wp_get_attachment_image_src( $value[0], 'medium' );
									echo '<img src="' . esc_url( $src[0] ) .'" alt="" />';
								}
								// 画像以外
								else {
									$src = wp_get_attachment_image_src( $value[0], 'none', true );
									echo '<a href="' . esc_url( wp_get_attachment_url( $value[0] ) ) .'" target="_blank">';
									echo '<img src="' . esc_url( $src[0] ) .'" alt="" />';
									echo '</a>';
								}
							}
						} else {
							echo nl2br( esc_html( $value[0] ) );
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
				<tr>
					<th><?php _e( 'Memo', MWF_Config::DOMAIN ); ?></th>
					<td><textarea name="<?php echo $this->POST_DATA_NAME; ?>[memo]" cols="50" rows="5"><?php echo $this->get_post_data( 'memo' ); ?></textarea></td>
				</tr>
			</table>
			<?php
		}
	}

	/**
	 * save_post
	 * @param	$post_ID
	 */
	public function save_post( $post_ID ) {
		if ( !( isset( $_POST['post_type'] ) && in_array( $_POST['post_type'], $this->form_post_type ) ) )
			return $post_ID;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_ID;
		if ( !current_user_can( 'manage_options', $post_ID ) )
			return $post_ID;

		// 保存可能なキー
		$permit_keys = array( 'memo' );
		$data = array();
		foreach ( $permit_keys as $key ) {
			if ( isset( $_POST[$this->POST_DATA_NAME][$key] ) )
				$data[$key] = $_POST[$this->POST_DATA_NAME][$key];
		}
		update_post_meta( $post_ID, $this->POST_DATA_NAME, $data, $this->postdata );
	}
}