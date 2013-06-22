<?php
/**
 * Plugin Name: MW WP Form
 * Plugin URI: http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/
 * Description: MW WP Form can create mail form with a confirmation screen.
 * Version: 0.9
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: September 25, 2012
 * Modified: June 21, 2013
 * Text Domain: mw-wp-form
 * Domain Path: /languages/
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
include_once( plugin_dir_path( __FILE__ ) . 'system/mwf_functions.php' );
include_once( plugin_dir_path( __FILE__ ) . 'system/mwf_config.php' );
$mw_wp_form = new mw_wp_form();
class mw_wp_form {

	protected $key;
	protected $input;
	protected $preview;
	protected $complete;
	protected $Data;
	protected $Form;
	protected $Validation;
	protected $Error;
	protected $viewFlg = 'input';
	protected $MW_WP_Form_Admin_Page;
	protected $options_by_formkey;
	protected $insert_id;

	/**
	 * __construct
	 */
	public function __construct() {
		load_plugin_textdomain( MWF_Config::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );

		// 有効化した時の処理
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		// アンインストールした時の処理
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		// 管理画面の実行
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_admin_page.php' );
		$this->MW_WP_Form_Admin_Page = new MW_WP_Form_Admin_Page();
		add_action( 'init', array( $this, 'register_post_type' ) );
		// フォームフィールドの読み込み、インスタンス化
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_form_field.php' );
		foreach ( glob( plugin_dir_path( __FILE__ ) . 'form_fields/*.php' ) as $form_field ) {
			include_once $form_field;
			$className = basename( $form_field, '.php' );
			if ( class_exists( $className ) ) {
				new $className();
			}
		}

		if ( is_admin() ) return;

		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_error.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_form.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_mail.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_session.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_validation.php' );
		add_action( 'wp', array( $this, 'init' ) );
		add_action( 'wp_print_styles', array( $this, 'original_style' ) );
	}

	/**
	 * activation
	 * 有効化した時の処理
	 */
	public static function activation() {
	}

	/**
	 * uninstall
	 * アンインストールした時の処理
	 */
	public static function uninstall() {
		$forms = get_posts( array(
			'post_type' => MWF_Config::NAME,
			'posts_per_page' => -1,
		) );
		if ( empty( $forms ) ) return;
		$data_post_ids[] = array();
		foreach ( $forms as $form ) {
			$data_post_ids[] = $form->ID;
			wp_delete_post( $form->ID, true );
		}

		if ( empty( $data_post_ids ) ) return;
		foreach ( $data_post_ids as $data_post_id ) {
			$data_posts = get_posts( array(
				'post_type' => MWF_Config::DBDATA . $data_post_id,
				'posts_per_page' => -1,
			) );
			if ( empty( $data_posts ) ) continue;
			foreach ( $data_posts as $data_post ) {
				wp_delete_post( $data_post->ID, true );
			}
		}
	}

	/**
	 * register_post_type
	 * 管理画面（カスタム投稿タイプ）の設定
	 */
	public function register_post_type() {
		$this->MW_WP_Form_Admin_Page->register_post_type();
	}

	/**
	 * original_style
	 * CSS適用
	 */
	public function original_style() {
		$url = plugin_dir_url( __FILE__ );
		wp_register_style( MWF_Config::DOMAIN, $url.'css/style.css' );
		wp_enqueue_style( MWF_Config::DOMAIN );
	}

	/**
	 * init
	 * 表示画面でのプラグインの初期化処理等。
	 */
	public function init() {
		global $post;
		if ( empty( $post->ID ) ) return;

		// URL設定を取得
		add_shortcode( 'mwform', array( $this, '_meta_mwform' ) );
		do_shortcode( $post->post_content );
		remove_shortcode( 'mwform' );

		// formkeyでのフォーム生成の場合はそれをもとに設定を取得
		add_shortcode( 'mwform_formkey', array( $this, '_meta_mwform_formkey' ) );
		do_shortcode( $post->post_content );
		remove_shortcode( 'mwform_formkey' );

		// フォームが定義されていない場合は終了
		if ( is_null( $this->key ) ||
			 is_null( $this->input ) ||
			 is_null( $this->preview ) ||
			 is_null( $this->complete ) )
			return;

		// セッション初期化
		$this->Session = MW_Session::start( $this->key );
		// $_POSTがあるときは$_POST、無いときは$this->Session->getValues()
		$_data = ( !empty( $_POST ) ) ? $_POST : $this->Session->getValues();
		$this->Data = new MW_WP_Form_Data( $this->key );
		$this->Data->setValues( $_data );

		// $_FILESがあるときは$this->dataに統合
		foreach ( $_FILES as $key => $file ) {
			if ( $this->Data->getValue( $key ) === null ) {
				$this->Data->setValue( $key, $file['name'] );
			}
		}
		// フォームオブジェクト生成
		$this->Form = new MW_Form( $this->Data->getValues(), $this->key );

		// バリデーションオブジェクト生成
		$this->Validation = new MW_Validation( $this->Data->getValues() );
		// バリデーション実行（Validation->dataに値がないと$Errorは返さない（true））
		$this->apply_filters_mwform_validation();

		// 入力画面（戻る）のとき
		if ( $this->Form->isInput() ) {
			$this->redirect( $this->input );
		}
		// 確認画面のとき
		elseif ( $this->Form->isPreview() ) {
			if ( $this->Validation->check() ) {
				$this->viewFlg = 'preview';
				$this->fileUpload();
				$this->redirect( $this->preview );
			} else {
				$this->redirect( $this->input );
			}
		}
		// 完了画面のとき
		elseif ( $this->Form->isComplete() ) {
			if ( $this->Validation->check() ) {
				$this->viewFlg = 'complete';
				$this->fileUpload();

				// 管理画面作成・個別URL・現在画面と完了画面が同じとき以外はメール送信
				$REQUEST_URI = $this->parse_url( $_SERVER['REQUEST_URI'] );
				if ( ! ( $this->is_management_different_url() && $REQUEST_URI == $this->complete ) )
					$this->apply_filters_mwform_mail();

				// 管理画面作成・個別URLのとき以外はクリア
				if ( ! $this->is_management_different_url() )
					$this->Form->clearToken();

				$this->redirect( $this->complete );
				$this->Form->clearToken();
			} else {
				$this->redirect( $this->input );
			}
		}
		$this->Session->clearValues();

		add_shortcode( 'mwform_formkey', array( $this, '_mwform_formkey' ) );
		add_shortcode( 'mwform', array( $this, '_mwform' ) );
		add_shortcode( 'mwform_complete_message', array( $this, '_mwform_complete_message' ) );
	}

	/**
	 * is_management_different_url
	 * 管理画面作成・個別URLのときtrueを返す
	 * @return	Boolean
	 */
	protected function is_management_different_url() {
		if ( !empty( $this->options_by_formkey ) && ( $this->input !== $this->complete || $this->preview !== $this->complete ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * _meta_mwform
	 * [mwform〜]を解析し、プロパティを設定
	 * @param	Array	( input, preview, complete, key )
	 */
	public function _meta_mwform( $atts ) {
		$atts = shortcode_atts( array(
			'input' => '',
			'preview' => '',
			'complete' => '',
			'key' => 'mwform'
		), $atts );
		$this->key = $atts['key'];
		$this->input = $this->parse_url( $atts['input'] );
		$this->preview = $this->parse_url( $atts['preview'] );
		$this->complete = $this->parse_url( $atts['complete'] );
	}

	/**
	 * _meta_mwform_formkey
	 * formkeyをもとにフォームの設定を取得
	 */
	public function _meta_mwform_formkey( $atts ) {
		global $post;
		$atts = shortcode_atts( array(
			'key' => ''
		), $atts );
		$post = get_post( $atts['key'] );
		if ( !empty( $post ) ) {
			setup_postdata( $post );
			if ( get_post_type() === MWF_Config::NAME ) {
				$this->options_by_formkey = get_post_meta( $post->ID, MWF_Config::NAME, true );
				$this->options_by_formkey['post_id'] = $post->ID;
				$this->key = MWF_Config::NAME.'-'.$atts['key'];
				$this->input = $this->parse_url( $this->options_by_formkey['input_url'] );
				$this->preview = $this->parse_url( $this->options_by_formkey['confirmation_url'] );
				$this->complete = $this->parse_url( $this->options_by_formkey['complete_url'] );
			}
		}
		wp_reset_postdata();
	}

	/**
	 * apply_filters_mwform_validation
	 * バリデーション用フィルタ。フィルタの実行結果としてValidationオブジェクトが返ってこなければエラー
	 * 各バリデーションメソッドの詳細は /system/mw_validation.php を参照
	 * @example
	 * 		// hoge識別子のフォームのバリデーションを行う場合
	 * 		add_filters( 'mwform_validation_hoge', 'mwform_validation_hoge' );
	 * 		function mwform_validation_hoge( $v ) {
	 * 			$v->setRule( 'key', 'noEmpty' );
	 * 			return $V;
	 * 		}
	 */
	protected function apply_filters_mwform_validation() {
		$filterName = 'mwform_validation_'.$this->key;

		if ( $this->options_by_formkey ) {
			foreach ( $this->options_by_formkey['validation'] as $validation ) {
				foreach ( $validation as $key => $value ) {
					if ( $key == 'target' ) continue;
					if ( is_array( $value ) ) {
						$this->Validation->setRule( $validation['target'], $key, $value );
					} else {
						$this->Validation->setRule( $validation['target'], $key );
					}
				}
			}
		}

		if ( $this->akismet_check() ) {
			$this->Validation->setRule( MWF_Config::AKISMET, 'akismet_check' );
		}

		$this->Validation = apply_filters( $filterName, $this->Validation );
		if ( !is_a( $this->Validation, 'MW_Validation' ) ) {
			exit( __( 'Validation Object is not a MW Validation Class.', MWF_Config::DOMAIN ) );
		}
	}

	protected function akismet_check() {
		global $akismet_api_host, $akismet_api_port;
		if ( ! function_exists( 'akismet_get_key' ) || ! akismet_get_key() )
			return false;
		$doAkismet = false;
		$author = '';
		$author_email = '';
		$author_url = '';
		$content = '';
		if ( isset( $this->options_by_formkey['akismet_author'] ) ) {
			if ( $author = $this->Data->getValue( $this->options_by_formkey['akismet_author'] ) )
				$doAkismet = true;
		}
		if ( isset( $this->options_by_formkey['akismet_author_email'] ) ) {
			if ( $author_email = $this->Data->getValue( $this->options_by_formkey['akismet_author_email'] ) )
				$doAkismet = true;
		}
		if ( isset( $this->options_by_formkey['akismet_author_url'] ) ) {
			if ( $author_url = $this->Data->getValue( $this->options_by_formkey['akismet_author_url'] ) )
				$doAkismet = true;
		}
		if ( $doAkismet ) {
			foreach ( $this->Data->getValues() as $value ) {
				$content .= $value . "\n\n";
			}
			$permalink = get_permalink();
			$akismet = array();
			$akismet['blog']         = get_option( 'home' );
			$akismet['blog_lang']    = get_locale();
			$akismet['blog_charset'] = get_option( 'blog_charset' );
			$akismet['user_ip']      = preg_replace( '/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR'] );
			$akismet['user_agent']   = $_SERVER['HTTP_USER_AGENT'];
			$akismet['referrer']     = $_SERVER['HTTP_REFERER'];
			$akismet['comment_type'] = MWF_Config::NAME;
			if ( $permalink )    $akismet['permalink']            = $permalink;
			if ( $author )       $akismet['comment_author']       = $author;
			if ( $author_email ) $akismet['comment_author_email'] = $author_email;
			if ( $author_url )   $akismet['comment_author_url']   = $author_url;
			if ( $content )      $akismet['comment_content']      = $content;

			foreach ( $_SERVER as $key => $value ) {
				if ( !in_array( $key, array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' ) ) )
					$akismet["$key"] = $value;
			}

			$query_string = http_build_query( $akismet, null, '&' );
			$response = akismet_http_post( $query_string, $akismet_api_host, '/1.1/comment-check', $akismet_api_port );
			$response = apply_filters( 'mwform_akismet_responce', $response );

			return ( $response[1] == 'true' ) ? true : false;
		}
	}

	/**
	 * apply_filters_mwform_mail
	 * メール送信フィルター
	 * @example
	 * 		// hoge識別子のフォームのメール送信を行う場合
	 * 		// $dataにフォームから送信された内容が配列で格納されている。
	 * 		add_filters( 'mwform_mail_hoge', 'mwform_mail_hoge', 10, 2 );
	 * 		function mwform_mail_hoge( $m, $data ) {
	 * 			$m->to = $data['your_email'];	// 宛先
	 * 			$m->from = 'inc@2inc.org';		// 送信元
	 * 			$m->sender = 'kitajima'			// 送信者
	 * 			$m->subject = '送信ありがとうございます。';		// 題名
	 * 			$m->body = '本文';							// 本文
	 * 			$m->send();						// 送信
	 *			return $m;
	 * 		}
	 */
	protected function apply_filters_mwform_mail() {
		$Mail = new MW_Mail();

		$admin_mail_subject = $this->options_by_formkey['mail_subject'];
		if ( !empty( $this->options_by_formkey['admin_mail_subject'] ) )
			$admin_mail_subject = $this->options_by_formkey['admin_mail_subject'];

		$admin_mail_content = $this->options_by_formkey['mail_content'];
		if ( !empty( $this->options_by_formkey['admin_mail_content'] ) )
			$admin_mail_content = $this->options_by_formkey['admin_mail_content'];

		// 添付ファイルのデータをためた配列を作成
		// $Mail->attachments を設定（メールにファイルを添付）
		$attachments = array();
		$upload_file_keys = $this->Data->getValue( MWF_Config::UPLOAD_FILE_KEYS );
		if ( $upload_file_keys !== null ) {
			if ( is_array( $upload_file_keys ) ) {
				foreach ( $upload_file_keys as $key ) {
					$upload_file_url = $this->Data->getValue( $key );
					if ( !$upload_file_url ) continue;
					$wp_upload_dir = wp_upload_dir();
					$filepath = str_replace(
						$wp_upload_dir['url'],
						$wp_upload_dir['path'],
						$upload_file_url
					);
					if ( file_exists( $filepath ) ) {
						$attachments[$key] = $filepath;
					}
				}
				$Mail->attachments = $attachments;
			}
		}

		if ( $this->options_by_formkey ) {
			// 送信先を指定
			if ( $mailto = $this->options_by_formkey['mail_to'] ) {
				$Mail->to = $mailto;
			} else {
				$Mail->to = get_bloginfo( 'admin_email' );
			}
			// 送信元を指定
			$Mail->from = get_bloginfo( 'admin_email' );
			// 送信者を指定
			$Mail->sender = get_bloginfo( 'name' );
			// タイトルを指定
			$Mail->subject = $admin_mail_subject;
			// 本文を指定
			$Mail->body = preg_replace_callback(
				'/{(.+?)}/',
				array( $this, 'create_mail_body' ),
				$admin_mail_content
			);
		}

		$actionName = 'mwform_mail_'.$this->key;
		$Mail = apply_filters( $actionName, $Mail, $this->Data->getValues() );

		if ( $this->options_by_formkey && !empty( $Mail ) ) {
			$Mail->send();

			if ( isset( $this->options_by_formkey['automatic_reply_email'] ) ) {
				$automatic_reply_email = $this->Data->getValue( $this->options_by_formkey['automatic_reply_email'] );
				if ( $automatic_reply_email && !$this->Validation->mail( $automatic_reply_email ) ) {
					// 送信先を指定
					$Mail->to = $this->Data->getValue( $this->options_by_formkey['automatic_reply_email'] );
					// タイトルを指定
					$Mail->subject = $this->options_by_formkey['mail_subject'];
					// 本文を指定
					$Mail->body = preg_replace_callback(
						'/{(.+?)}/',
						array( $this, 'create_mail_body' ),
						$this->options_by_formkey['mail_content']
					);
					// 自動返信メールからは添付ファイルを削除
					$Mail->attachments = array();
					$Mail->send();
				}
			}

			// DB保存時
			if ( !empty( $this->options_by_formkey['usedb'] ) ) {
				// save_mail_body で登録されないように
				foreach ( $attachments as $key => $filepath ) {
					$this->Data->clearValue( $key );
				}

				// $this->insert_id を設定 ( save_mail_body で 使用 )
				$this->insert_id = wp_insert_post( array(
					'post_title' => $admin_mail_subject,
					'post_status' => 'publish',
					'post_type' => MWF_Config::DBDATA . $this->options_by_formkey['post_id'],
				) );
				// 保存
				preg_replace_callback(
					'/{(.+?)}/',
					array( $this, 'save_mail_body' ),
					$admin_mail_content
				);

				// 添付ファイルをメディアに保存
				if ( !empty( $this->insert_id ) ) {
					foreach ( $attachments as $key => $filepath ) {
						// WordPress( get_allowed_mime_types ) で許可されたファイルタイプ限定
						$wp_check_filetype = wp_check_filetype( $filepath );
						if ( file_exists( $filepath ) && !empty( $wp_check_filetype['type'] ) ) {
							$post_type = get_post_type_object( MWF_Config::DBDATA . $this->options_by_formkey['post_id'] );
							$attachment = array(
								'post_mime_type' => $wp_check_filetype['type'],
								'post_title'     => $key,
								'post_status'    => 'inherit',
								'post_content'   => __( 'Uploaded from ' ) . $post_type->label,
							);
							$attach_id = wp_insert_attachment( $attachment, $filepath, $this->insert_id );
							require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
							$attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
							$update_attachment_flg = wp_update_attachment_metadata( $attach_id, $attach_data );
							if ( $update_attachment_flg ) {
								// 代わりにここで attachment_id を保存
								update_post_meta( $this->insert_id, $key, $attach_id );
								// $key が 添付ファイルのキーであるとわかるように隠し設定を保存
								update_post_meta( $this->insert_id, '_' . MWF_Config::UPLOAD_FILE_KEYS, $key );
							}
						}
					}
				}
			}
			// DB非保存時
			else {
				foreach ( $attachments as $filepath ) {
					if ( file_exists( $filepath ) )
						unlink( $filepath );
				}
			}
		}
	}

	/**
	 * create_mail_body
	 * メール本文用に {$postのプロパティ} を置換
	 */
	public function create_mail_body( $matches ) {
		return $this->parse_mail_body( $matches, false );
	}

	/**
	 * save_mail_body
	 * DB保存用に {$postのプロパティ} を置換、保存
	 */
	public function save_mail_body( $matches ) {
		return $this->parse_mail_body( $matches, true );
	}

	/**
	 * parse_mail_body
	 * $this->create_mail_body(), $this->save_mail_body の本体
	 * 第2引数でDB保存するか判定
	 */
	protected function parse_mail_body( $matches, $doUpdate = false ) {
		$match = $this->Data->getValue( $matches[1] );
		if ( $match === null )
			return;
		if ( is_array( $match ) ) {
			if ( !array_key_exists( 'data', $match ) )
				return;
			if ( is_array( $match['data'] ) ) {
				$value = $this->Form->getZipValue( $matches[1] );
				if ( $doUpdate )
					update_post_meta( $this->insert_id, $matches[1], $value );
				return $value;
			}
			if ( $doUpdate )
				update_post_meta( $this->insert_id, $matches[1], $match['data'] );
			return $match['data'];
		} else {
			if ( $doUpdate )
				update_post_meta( $this->insert_id, $matches[1], $match );
			return $match;
		}
	}

	/**
	 * redirect
	 * 現在のURLと引数で渡されたリダイレクトURLが同じであればリダイレクトしない
	 * @param	String	リダイレクトURL
	 */
	private function redirect( $url ) {
		$redirect = ( empty( $url ) ) ? $_SERVER['REQUEST_URI'] : $url;
		$redirect = $this->parse_url( $redirect );
		$REQUEST_URI = $this->parse_url( $_SERVER['REQUEST_URI'] );
		if ( $redirect != $REQUEST_URI || $this->Form->isInput() && !empty( $_POST ) ) {
			wp_redirect( $redirect );
			exit();
		}
	}

	/**
	 * parse_url
	 * http:// からはじまるURLに変換する
	 * @param	String	URL
	 * @return	String	URL
	 */
	protected function parse_url( $url ) {
		if ( empty( $url ) )
			return '';

		preg_match( '/(\?.*)$/', $url, $reg );
		if ( !empty( $reg[1] ) ) {
			$url = str_replace( $reg[1], '', $url );
		}
		if ( !preg_match( '/^https?:\/\//', $url ) ) {
			$protocol = ( is_ssl() ) ? 'https://' : 'http://';
			$home_url = untrailingslashit( $protocol.$_SERVER['HTTP_HOST'] );
			$url = $home_url . $url;
		}
		$url = preg_replace( '/([^:])\/+/', '$1/', $url );
		$url = trailingslashit( $url );
		if ( !empty( $this->options_by_formkey['querystring'] ) && MWF_Functions::is_numeric( $_GET['post_id'] ) ) {
			$url = $url . '?post_id=' . $_GET['post_id'];
		}
		return $url;
	}

	/**
	 * _mwform_formkey
	 * 管理画面で作成したフォームを出力
	 * @example
	 * 		[mwform_formkey keys="post_id"]
	 */
	public function _mwform_formkey( $atts ) {
		global $post;
		$atts = shortcode_atts( array(
			'key' => ''
		), $atts );
		$_mwform = '[mwform key="'.$this->key.'" input="'.$this->input.'" preview="'.$this->preview.'" complete="'.$this->complete.'"]';
		$post = get_post( $atts['key'] );
		setup_postdata( $post );

		// 入力画面・確認画面
		if ( $this->viewFlg == 'input' || $this->viewFlg == 'preview' ) {
			$_ret = do_shortcode( $_mwform . get_the_content() . '[/mwform]' );
		}
		// 完了画面
		elseif( $this->viewFlg == 'complete' ) {
			$_ret = do_shortcode( '[mwform_complete_message]'.$this->options_by_formkey['complete_message'].'[/mwform_complete_message]' );
		}
		wp_reset_postdata();
		return $_ret;
	}

	/**
	 * _mwform
	 * @example
	 * 		同一画面変遷の場合
	 * 			[mwform key="hoge"]〜[/mwform]
	 * 		別ページ画面変遷の場合
	 * 			確認画面ありの場合
	 * 				入力画面 : [mwform preview="/form_preview/" key="hoge"]〜[/mwform]
	 * 				確認画面 : [mwform input="/form_input/" complete="/form_complete/" key="hoge"]〜[/mwform]
	 * 			確認画面なしの場合
	 * 				入力画面 : [mwform complete="/form_complete/" key="hoge"]〜[/mwform]
	 */
	public function _mwform( $atts, $content = '' ) {
		if ( $this->viewFlg == 'input' || $this->viewFlg == 'preview' ) {
			$this->Error = $this->Validation->Error();
			do_action( 'mwform_add_shortcode', $this->Form, $this->viewFlg, $this->Error );

			// ユーザー情報取得
			$user = wp_get_current_user();
			if ( !empty( $user ) ) {
				$search = array(
					'{user_id}',
					'{user_login}',
					'{user_email}',
					'{user_url}',
					'{user_registered}',
					'{display_name}',
				);
				$content = str_replace( $search, array(
					$user->get( 'ID' ),
					$user->get( 'user_login' ),
					$user->get( 'user_email' ),
					$user->get( 'user_url' ),
					$user->get( 'user_registered' ),
					$user->get( 'display_name' ),
				), $content );
			}

			// 投稿情報取得
			if ( isset( $this->options_by_formkey['querystring'] ) )
				$querystring = $this->options_by_formkey['querystring'];
			if ( !empty( $querystring ) ) {
				$content = preg_replace_callback( '/{(.+?)}/', array( $this, 'get_post_propery' ), $content );
			}

			$upload_file_keys = $this->Form->getValue( MWF_Config::UPLOAD_FILE_KEYS );
			$upload_file_hidden = '';
			if ( is_array( $upload_file_keys ) ) {
				foreach ( $upload_file_keys as $value ) {
					$upload_file_hidden .= $this->Form->hidden( MWF_Config::UPLOAD_FILE_KEYS . '[]', $value );
				}
			}
			return
				'<div id="mw_wp_form_' . $this->key . '" class="mw_wp_form mw_wp_form_' . $this->viewFlg . '">' .
				$this->Form->start() .
				do_shortcode( $content ) .
				$upload_file_hidden .
				$this->Form->end() .
				'<!-- end .mw_wp_form --></div>';
		}
	}

	/**
	 * get_post_propery
	 * 引数 post_id が有効の場合、ユーザー情報を取得するために preg_replace_callback から呼び出される。
	 * @param	Array	$matches
	 * @return	String
	 */
	public function get_post_propery( $matches ) {
		if ( isset( $this->options_by_formkey['querystring'] ) )
			$querystring = $this->options_by_formkey['querystring'];
		if ( !empty( $querystring ) && MWF_Functions::is_numeric( $_GET['post_id'] ) ) {
			$_post = get_post( $_GET['post_id'] );
			if ( empty( $_post->ID ) )
				return $matches[0];
			if ( isset( $_post->$matches[1] ) ) {
				return $_post->$matches[1];
			} else {
				// post_meta の処理
				$pm = get_post_meta( $_post->ID, $matches[1], true );
				if ( !empty( $pm ) )
					return $pm;
			}
		}
		return $matches[0];
	}

	/**
	 * _mwform_complete_message
	 * 完了後のメッセージ。同一ページで画面変遷したときだけ実行する
	 * @example
	 * 		[mwform …]〜[/mwform]
	 * 		[mwform_complete_message]ここに完了後に表示するメッセージ[/mwform_complete_message]
	 */
	public function _mwform_complete_message( $atts, $content = '' ) {
		if ( $this->viewFlg == 'complete' ) {
			return $content;
		}
	 }

	/**
	 * fileUpload
	 * ファイルアップロード処理。$this->data[$key] にファイルの URL を入れる
	 */
	protected function fileUpload() {
		foreach ( $_FILES as $key => $file ) {
			if ( empty( $file['tmp_name'] ) )
				continue;
			$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$uploadfile = $this->setUploadFileName( $extension );
			// WordPress( get_allowed_mime_types ) で許可されたファイルタイプ限定
			$wp_check_filetype = wp_check_filetype( $uploadfile['file'] );
			if ( !( $file['error'] == UPLOAD_ERR_OK
				 && is_uploaded_file( $file['tmp_name'] )
				 && !empty( $wp_check_filetype['type'] ) ) )
				 continue;
			$this->Data->setValue( $key, $uploadfile['url'] );
			$upload_file_keys = $this->Data->getValue( MWF_Config::UPLOAD_FILE_KEYS );
			if ( !( is_array( $upload_file_keys ) && in_array( $key, $upload_file_keys ) ) ) {
				$this->Data->pushValue( MWF_Config::UPLOAD_FILE_KEYS, $key );
			}
			$this->Form = new MW_Form( $this->Data->getValues(), $this->key );
			move_uploaded_file( $file['tmp_name'], $uploadfile['file'] );
		}
	}

	/**
	 * setUploadFileName
	 * ファイルパスとファイルURL を返す
	 * @param  String  拡張子 ( ex: jpg )
	 * @return Array   ( file =>, url => )
	 */
	private function setUploadFileName( $extension ) {
		$count      = 0;
		$filename   = date( 'Ymdhis' ) . '.' . $extension;
		$wp_upload_dir = wp_upload_dir();
		$upload_dir = realpath( $wp_upload_dir['path'] );
		$upload_url = $wp_upload_dir['url'];
		$uploadfile['file'] = $upload_dir . '/' . $filename;
		$uploadfile['url']  = $upload_url . '/' . $filename;
		$slugname = preg_replace( '/\.[^.]+$/', '', basename( $uploadfile['file'] ) );
		while ( file_exists( $uploadfile['file'] ) ) {
			$count ++;
			$uploadfile['file'] = $upload_dir . '/' . $slugname . '-' . $count . '.' . $extension;
			$uploadfile['url']  = $upload_url . '/' . $slugname . '-' . $count . '.' . $extension;
		}
		return $uploadfile;
	}
}

/**
 * mw_wp_form_data
 * mw_wp_form のデータ操作用
 * Version: 1.0
 * Created: May 29, 2013
 */
class mw_wp_form_data {
	private $data;
	private $Session;

	/**
	 * __construct
	 * @param    String    $key    データのキー
	 */
	public function __construct( $key ) {
		$this->Session = MW_Session::start( $key );
	}

	/**
	 * getValue
	 * データを取得
	 * @param    String    $key    データのキー
	 * @return   String    データ
	 */
	public function getValue( $key ) {
		if ( isset( $this->data[$key] ) )
			return $this->data[$key];
	}

	/**
	 * getValues
	 * 全てのデータを取得
	 * @return   Array   データ
	 */
	public function getValues() {
		if ( $this->data === null)
			return array();
		return $this->data;
	}

	/**
	 * setValue
	 * データを追加
	 * @param    String    $key    データのキー
	 * @param    String    $value  値
	 */
	public function setValue( $key, $value ){
		$this->data[$key] = $value;
		$this->Session->setValue( $key, $value );
	}

	/**
	 * setValue
	 * 複数のデータを一括で追加
	 * @param    Array    値
	 */
	public function setValues( Array $array ) {
		foreach ( $array as $key => $value ) {
			$this->data[$key] = $value;
			$this->Session->setValue( $key, $value );
		}
	}

	/**
	 * clearValue
	 * データを消す
	 * @param    String    $key    データのキー
	 */
	public function clearValue( $key ) {
		unset( $this->data[$key] );
		$this->Session->clearValue( $key );
	}

	/**
	 * pushValue
	 * 指定した $key をキーと配列にデータを追加
	 * @param    String    $key    データのキー
	 * @param    String    $value  値
	 */
	public function pushValue( $key, $value ) {
		$this->data[$key][] = $value;
		$this->Session->pushValue( $key, $value );
	}
}
