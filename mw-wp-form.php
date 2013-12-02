<?php
/**
 * Plugin Name: MW WP Form
 * Plugin URI: http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/
 * Description: MW WP Form can create mail form with a confirmation screen.
 * Version: 1.1.5
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : September 25, 2012
 * Modified: December 2, 2013
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
	protected $confirm;
	protected $complete;
	protected $validation_error;
	protected $Data;
	protected $Form;
	protected $Validation;
	protected $Error;
	protected $File;
	protected $viewFlg = 'input';
	protected $MW_WP_Form_Admin_Page;
	protected $MW_WP_Form_Contact_Data_Page;
	protected $options_by_formkey;
	protected $insert_id;
	private $defaults = array(
		'mail_subject' => '',
		'mail_from' => '',
		'mail_sender' => '',
		'mail_content' => '',
		'automatic_reply_email' => '',
		'mail_to' => '',
		'admin_mail_subject' => '',
		'admin_mail_from' => '',
		'admin_mail_sender' => '',
		'admin_mail_content' => '',
		'querystring' => null,
		'usedb' => null,
		'akismet_author' => '',
		'akismet_author_email' => '',
		'akismet_author_url' => '',
		'complete_message' => '',
		'input_url' => '',
		'confirmation_url' => '',
		'complete_url' => '',
		'validation_error_url' => '',
		'validation' => array(),
	);

	/**
	 * __construct
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		// 有効化した時の処理
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );
		// アンインストールした時の処理
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * init
	 * ファイルの読み込み等
	 */
	public function init() {
		load_plugin_textdomain( MWF_Config::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );

		// 管理画面の実行
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_admin_page.php' );
		$this->MW_WP_Form_Admin_Page = new MW_WP_Form_Admin_Page();
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_contact_data_page.php' );
		$this->MW_WP_Form_Contact_Data_Page = new MW_WP_Form_Contact_Data_Page();
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
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_data.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_file.php' );
		add_action( 'wp', array( $this, 'main' ) );
		add_action( 'wp_print_styles', array( $this, 'original_style' ) );
		add_action( 'parse_request', array( $this, 'remote_query_vars_from_post' ) );
	}

	/**
	 * remote_query_vars_from_post
	 * WordPressへのリクエストに含まれている、$_POSTの値を削除
	 */
	public function remote_query_vars_from_post( $query ) {
		if ( strtolower( $_SERVER['REQUEST_METHOD'] ) === 'post' && isset( $_POST['token'] ) ) {
			foreach ( $_POST as $key => $value ) {
				if ( $key == 'token' )
					continue;
				if ( isset( $query->query_vars[$key] ) && $query->query_vars[$key] === $value && !empty( $value ) ) {
					$query->query_vars[$key] = '';
				}
			}
		}
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

		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_wp_form_file.php' );
		$File = new MW_WP_Form_File();
		$File->removeTempDir();
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
	 * main
	 * 表示画面でのプラグインの処理等。
	 */
	public function main() {
		global $post;
		if ( !is_singular() ) return;
		if ( empty( $post->ID ) ) return;

		// URL設定を取得
		add_shortcode( 'mwform', array( $this, '_meta_mwform' ) );
		// formkeyでのフォーム生成の場合はそれをもとに設定を取得
		add_shortcode( 'mwform_formkey', array( $this, '_meta_mwform_formkey' ) );
		preg_match_all( '/' . get_shortcode_regex() . '/s', $post->post_content, $matches, PREG_SET_ORDER );
		if ( !empty( $matches ) ) {
			foreach ( $matches as $shortcode ) {
				if ( in_array( $shortcode[2], array( 'mwform', 'mwform_formkey' ) ) ) {
					do_shortcode( $shortcode[0] );
					break;
				}
			}
		}
		remove_shortcode( 'mwform' );
		remove_shortcode( 'mwform_formkey' );

		// フォームが定義されていない場合は終了
		if ( is_null( $this->key ) ||
			 is_null( $this->input ) ||
			 is_null( $this->confirm ) ||
			 is_null( $this->complete ) ||
			 is_null( $this->validation_error ) )
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

		// ファイル操作オブジェクト生成
		$this->File = new MW_WP_Form_File();

		// 入力画面（戻る）のとき
		if ( $this->Form->isInput() ) {
			$this->redirect( $this->input );
		}
		// 確認画面のとき
		elseif ( $this->Form->isConfirm() ) {
			if ( $this->Validation->check() ) {
				$this->viewFlg = 'confirm';
				$this->fileUpload();
				$this->redirect( $this->confirm );
			} else {
				if ( !empty( $this->validation_error ) ) {
					$this->redirect( $this->validation_error );
				} else {
					$this->redirect( $this->input );
				}
			}
		}
		// 完了画面のとき
		elseif ( $this->Form->isComplete() ) {
			if ( $this->Validation->check() ) {
				$this->viewFlg = 'complete';
				$this->fileUpload();

				if ( $this->Data->getValue( $this->Form->getTokenName() ) ) {
					$this->apply_filters_mwform_mail();
					$this->Data->clearValue( $this->Form->getTokenName() );

					// 手動フォーム対応
					$REQUEST_URI = $this->parse_url( $_SERVER['REQUEST_URI'] );
					$input = $this->parse_url( $this->input );
					$complete = $this->parse_url( $this->complete );
					if ( !$this->options_by_formkey && $REQUEST_URI !== $complete && $input !== $complete ) {
						$this->Data->clearValues();
					}
				}

				$this->redirect( $this->complete );
			} else {
				if ( !empty( $this->validation_error ) ) {
					$this->redirect( $this->validation_error );
				} else {
					$this->redirect( $this->input );
				}
			}
		}

		add_shortcode( 'mwform_formkey', array( $this, '_mwform_formkey' ) );
		add_shortcode( 'mwform', array( $this, '_mwform' ) );
		add_shortcode( 'mwform_complete_message', array( $this, '_mwform_complete_message' ) );
		add_action( 'wp_footer', array( $this->Data, 'clearValues' ) );
	}

	/**
	 * _meta_mwform
	 * [mwform〜]を解析し、プロパティを設定
	 * @param	Array	( input, preview, confirm complete, key )
	 * @example
	 * 		同一画面変遷の場合
	 * 			[mwform key="hoge"]〜[/mwform]
	 * 		別ページ画面変遷の場合
	 * 			確認画面ありの場合
	 * 				入力画面 : [mwform confirm="/form_confirm/" key="hoge"]〜[/mwform]
	 * 				確認画面 : [mwform input="/form_input/" complete="/form_complete/" key="hoge"]〜[/mwform]
	 * 			確認画面なしの場合
	 * 				入力画面 : [mwform complete="/form_complete/" key="hoge"]〜[/mwform]
	 */
	public function _meta_mwform( $atts ) {
		$atts = shortcode_atts( array(
			'input' => '',
			'preview' => '',
			'confirm' => '',
			'complete' => '',
			'validation_error' => '',
			'key' => 'mwform'
		), $atts );
		$this->key = $atts['key'];
		$this->input = $this->parse_url( $atts['input'] );
		if ( $atts['confirm'] ) {
			$this->confirm = $this->parse_url( $atts['confirm'] );
		} elseif ( $atts['preview'] ) {
			$this->confirm = $this->parse_url( $atts['preview'] );
		} else {
			$this->confirm = $this->parse_url( $atts['confirm'] );
		}
		$this->complete = $this->parse_url( $atts['complete'] );
		$this->validation_error = $this->parse_url( $atts['validation_error'] );
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
				$this->options_by_formkey = array_merge(
					$this->defaults,
					( array )get_post_meta( $post->ID, MWF_Config::NAME, true )
				);
				$this->options_by_formkey['post_id'] = $post->ID;
				$this->key = MWF_Config::NAME . '-' . $atts['key'];
				$this->input = $this->parse_url( $this->options_by_formkey['input_url'] );
				$this->confirm = $this->parse_url( $this->options_by_formkey['confirmation_url'] );
				$this->complete = $this->parse_url( $this->options_by_formkey['complete_url'] );
				$this->validation_error = $this->parse_url( $this->options_by_formkey['validation_error_url'] );
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
		$filterName = 'mwform_validation_' . $this->key;

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

	/**
	 * akismet_check
	 * Akismetチェックを実行
	 * @return  Boolean
	 */
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

		if ( $this->options_by_formkey ) {
			$admin_mail_subject = $this->options_by_formkey['mail_subject'];
			if ( !empty( $this->options_by_formkey['admin_mail_subject'] ) )
				$admin_mail_subject = $this->options_by_formkey['admin_mail_subject'];

			$admin_mail_content = $this->options_by_formkey['mail_content'];
			if ( !empty( $this->options_by_formkey['admin_mail_content'] ) )
				$admin_mail_content = $this->options_by_formkey['admin_mail_content'];

			// 添付ファイルのデータをためた配列を作成
			$attachments = array();
			// $Mail->attachments を設定（メールにファイルを添付）
			$upload_file_keys = $this->Data->getValue( MWF_Config::UPLOAD_FILE_KEYS );
			if ( $upload_file_keys !== null ) {
				if ( is_array( $upload_file_keys ) ) {
					$wp_upload_dir = wp_upload_dir();
					foreach ( $upload_file_keys as $key ) {
						$upload_file_url = $this->Data->getValue( $key );
						if ( !$upload_file_url )
							continue;
						$filepath = str_replace(
							$wp_upload_dir['baseurl'],
							realpath( $wp_upload_dir['basedir'] ),
							$upload_file_url
						);
						if ( file_exists( $filepath ) ) {
							$filepath = $this->File->moveTempFileToUploadDir( $filepath );
							$new_upload_file_url = str_replace(
								realpath( $wp_upload_dir['basedir'] ),
								$wp_upload_dir['baseurl'],
								$filepath
							);
							$attachments[$key] = $filepath;
							$this->Data->setValue( $key, $new_upload_file_url );
							$this->Form = new MW_Form( $this->Data->getValues(), $this->key );
						}
					}
					$Mail->attachments = $attachments;
				}
			}

			// 送信先を指定
			$Mail->to = get_bloginfo( 'admin_email' );
			if ( $mailto = $this->options_by_formkey['mail_to'] )
				$Mail->to = $mailto;
			// 送信元を指定
			$from = get_bloginfo( 'admin_email' );
			if ( !empty( $this->options_by_formkey['admin_mail_from'] ) )
				$from = $this->options_by_formkey['admin_mail_from'];
			$Mail->from = $from;
			// 送信者を指定
			$sender = get_bloginfo( 'name' );
			if ( !empty( $this->options_by_formkey['admin_mail_sender'] ) )
				$sender = $this->options_by_formkey['admin_mail_sender'];
			$Mail->sender = $sender;
			// タイトルを指定
			$Mail->subject = $admin_mail_subject;
			// 本文を指定
			$Mail->body = preg_replace_callback(
				'/{(.+?)}/',
				array( $this, 'create_mail_body' ),
				$admin_mail_content
			);
		}

		$filter_name = 'mwform_mail_' . $this->key;
		$Mail = apply_filters( $filter_name, $Mail, $this->Data->getValues() );
		if ( $this->options_by_formkey && is_a( $Mail, 'MW_Mail' ) ) {

			// メール送信前にファイルのリネームをしないと、tempファイル名をメールで送信してしまう。
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
					$this->File->saveAttachmentsInMedia(
						$this->insert_id,
						$attachments,
						$this->options_by_formkey['post_id']
					);
				}
			}

			$filter_name = 'mwform_admin_mail_' . $this->key;
			$Mail = apply_filters( $filter_name, $Mail, $this->Data->getValues() );
			if ( !is_a( $Mail, 'MW_Mail' ) )
				return;
			$Mail->send();

			// DB非保存時は管理者メール送信後、ファイルを削除
			if ( empty( $this->options_by_formkey['usedb'] ) ) {
				foreach ( $attachments as $filepath ) {
					if ( file_exists( $filepath ) )
						unlink( $filepath );
				}
			}

			if ( isset( $this->options_by_formkey['automatic_reply_email'] ) ) {
				$automatic_reply_email = $this->Data->getValue( $this->options_by_formkey['automatic_reply_email'] );
				if ( $automatic_reply_email && !$this->Validation->mail( $automatic_reply_email ) ) {
					// 送信先を指定
					$Mail->to = $this->Data->getValue( $this->options_by_formkey['automatic_reply_email'] );
					// 送信元を指定
					$from = get_bloginfo( 'admin_email' );
					if ( !empty( $this->options_by_formkey['mail_from'] ) )
						$from = $this->options_by_formkey['mail_from'];
					$Mail->from = $from;
					// 送信者を指定
					$sender = get_bloginfo( 'name' );
					if ( !empty( $this->options_by_formkey['mail_sender'] ) )
						$sender = $this->options_by_formkey['mail_sender'];
					$Mail->sender = $sender;
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

					$filter_name = 'mwform_auto_mail_' . $this->key;
					$Mail = apply_filters( $filter_name, $Mail, $this->Data->getValues() );
					if ( !is_a( $Mail, 'MW_Mail' ) )
						return;
					$Mail->send();
				}
			}
		}
	}

	/**
	 * create_mail_body
	 * メール本文用に {name属性} を置換
	 */
	public function create_mail_body( $matches ) {
		return $this->parse_mail_body( $matches, false );
	}

	/**
	 * save_mail_body
	 * DB保存用に {name属性} を置換、保存
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
		if ( !empty( $_POST ) || $redirect != $REQUEST_URI ) {
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

		$query_string = array();
		preg_match( '/\?(.*)$/', $url, $reg );
		if ( !empty( $reg[1] ) ) {
			$url = str_replace( '?', '', $url );
			$url = str_replace( $reg[1], '', $url );
			parse_str( $reg[1], $query_string );
		}
		if ( !preg_match( '/^https?:\/\//', $url ) ) {
			$protocol = ( is_ssl() ) ? 'https://' : 'http://';
			$home_url = untrailingslashit( $protocol . $_SERVER['HTTP_HOST'] );
			$url = $home_url . $url;
		}
		$url = preg_replace( '/([^:])\/+/', '$1/', $url );

		// url引数が無効の場合、URL設定 で ?post_id が使われている場合はそれが使用される
		// url引数が有効の場合は URL設定 で ?post_id が使われていても $_GET['post_id'] で上書きされる
		$query_string = array_merge( $_GET, $query_string );
		if ( !empty( $this->options_by_formkey['querystring'] )
			 && isset( $_GET['post_id'] )
			 && MWF_Functions::is_numeric( $_GET['post_id'] ) ) {

			$query_string['post_id'] = $_GET['post_id'];
		}
		if ( !empty( $query_string ) )
			$url = $url . '?' . http_build_query( $query_string, null, '&' );
		return $url;
	}

	/**
	 * _mwform_formkey
	 * 管理画面で作成したフォームを出力（実際の出力は _mwform ）
	 * @example
	 * 		[mwform_formkey key="post_id"]
	 */
	public function _mwform_formkey( $atts ) {
		global $post;
		$atts = shortcode_atts( array(
			'key' => ''
		), $atts );
		$post = get_post( $atts['key'] );
		setup_postdata( $post );

		// 入力画面・確認画面
		if ( $this->viewFlg == 'input' || $this->viewFlg == 'confirm' ) {
			$_ret = do_shortcode( '[mwform]' . get_the_content() . '[/mwform]' );
		}
		// 完了画面
		elseif( $this->viewFlg == 'complete' ) {
			$_ret = do_shortcode( '[mwform_complete_message]' . $this->options_by_formkey['complete_message'] . '[/mwform_complete_message]' );
		}
		wp_reset_postdata();
		return $_ret;
	}

	/**
	 * _mwform
	 * フォームを出力
	 */
	public function _mwform( $atts, $content = '' ) {
		if ( $this->viewFlg == 'input' || $this->viewFlg == 'confirm' ) {
			$this->Error = $this->Validation->Error();
			do_action( 'mwform_add_shortcode', $this->Form, $this->viewFlg, $this->Error, $this->key );

			// ユーザー情報取得
			$content = $this->replace_user_property( $content );

			// 投稿情報取得
			if ( isset( $this->options_by_formkey['querystring'] ) )
				$querystring = $this->options_by_formkey['querystring'];
			if ( !empty( $querystring ) ) {
				$content = preg_replace_callback( '/{(.+?)}/', array( $this, 'get_post_property' ), $content );
			} else {
				$content = preg_replace( '/{(.+?)}/', '', $content );
			}

			$upload_file_keys = $this->Form->getValue( MWF_Config::UPLOAD_FILE_KEYS );
			$upload_file_hidden = '';
			if ( is_array( $upload_file_keys ) ) {
				foreach ( $upload_file_keys as $value ) {
					$upload_file_hidden .= $this->Form->hidden( MWF_Config::UPLOAD_FILE_KEYS . '[]', $value );
				}
			}
			$_preview_class = ( $this->viewFlg === 'confirm' ) ? ' mw_wp_form_preview' : '';
			return
				'<div id="mw_wp_form_' . $this->key . '" class="mw_wp_form mw_wp_form_' . $this->viewFlg . $_preview_class . '">' .
				$this->Form->start() .
				do_shortcode( $content ) .
				$upload_file_hidden .
				$this->Form->end() .
				'<!-- end .mw_wp_form --></div>';
		}
	}

	/**
	 * replace_user_property
	 * ユーザーがログイン中の場合、{ユーザー情報のプロパティ}を置換する。
	 * @param	String	フォーム内容
	 * @return	String	フォーム内容
	 */
	protected function replace_user_property( $content ) {
		$user = wp_get_current_user();
		$search = array(
			'{user_id}',
			'{user_login}',
			'{user_email}',
			'{user_url}',
			'{user_registered}',
			'{display_name}',
		);
		if ( !empty( $user ) ) {
			$content = str_replace( $search, array(
				$user->get( 'ID' ),
				$user->get( 'user_login' ),
				$user->get( 'user_email' ),
				$user->get( 'user_url' ),
				$user->get( 'user_registered' ),
				$user->get( 'display_name' ),
			), $content );
		} else {
			$content = str_replace( $search, '', $content );
		}
		return $content;
	}

	/**
	 * get_post_property
	 * 引数 post_id が有効の場合、投稿情報を取得するために preg_replace_callback から呼び出される。
	 * @param	Array	$matches
	 * @return	String
	 */
	public function get_post_property( $matches ) {
		if ( isset( $this->options_by_formkey['querystring'] ) )
			$querystring = $this->options_by_formkey['querystring'];
		if ( !empty( $querystring ) && isset( $_GET['post_id'] ) && MWF_Functions::is_numeric( $_GET['post_id'] ) ) {
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
		return;
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
	 * fileupload
	 * ファイルアップロード処理。実際のアップロード状況に合わせてフォームデータも再生成する。
	 */
	protected function fileupload() {
		$uploadedFiles = $this->File->fileupload();
		$excludedFiles = array_diff_key( $_FILES, $uploadedFiles );
		$upload_file_keys = $this->Data->getValue( MWF_Config::UPLOAD_FILE_KEYS );
		if ( !$upload_file_keys )
			$upload_file_keys = array();

		// 確認 => 入力 => 確認のときに空の $_FILES が送られアップ済みのも $excludesFiles に入ってしまうので消す
		$wp_upload_dir = wp_upload_dir();
		foreach ( $upload_file_keys as $upload_file_key ) {
			$upload_file_url = $this->Data->getValue( $upload_file_key );
			if ( $upload_file_url ) {
				$filepath = str_replace(
					$wp_upload_dir['baseurl'],
					realpath( $wp_upload_dir['basedir'] ),
					$upload_file_url
				);
				if ( file_exists( $filepath ) ) {
					unset( $excludedFiles[$upload_file_key] );
				}
			}
		}

		// アップロードに失敗したファイルのキーは削除
		foreach ( $excludedFiles as $key => $excludedFile ) {
			$this->Data->clearValue( $key );
			$delete_key = array_search( $key, $upload_file_keys );
			if ( $delete_key !== false )
				unset( $upload_file_keys[$delete_key] );
		}
		$this->Data->setValue( MWF_Config::UPLOAD_FILE_KEYS, $upload_file_keys );

		// アップロードに成功したファイルをフォームデータに格納
		foreach ( $uploadedFiles as $key => $uploadfile ) {
			$this->Data->setValue( $key, $uploadfile );
			if ( !in_array( $key, $upload_file_keys ) ) {
				$this->Data->pushValue( MWF_Config::UPLOAD_FILE_KEYS, $key );
			}
		}
		$this->Form = new MW_Form( $this->Data->getValues(), $this->key );
	}
}
