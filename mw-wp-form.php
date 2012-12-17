<?php
/**
 * Plugin Name: MW WP Form
 * Plugin URI: http://2inc.org/blog/category/products/wordpress_plugins/mw-wp-form/
 * Description: Plug-in which can create mail form using short code. E-mail sending and validation can be specified at functions.php.
 * Version: 0.5
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: September 25, 2012
 * Modified: December 14, 2012
 * Text Domain: mw-wp-form
 * Domain Path: /languages/
 * License: GPL2
 *
 * Copyright 2012 Takashi Kitajima (email : inc@2inc.org)
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
$mw_wp_form = new mw_wp_form();
class mw_wp_form {

	const NAME = 'mw-wp-form';
	const DOMAIN = 'mw-wp-form';
	const CUSTOM_FIELD_NAME = '_mwform';
	protected $key;
	protected $input;
	protected $preview;
	protected $complete;
	protected $data;
	protected $Form;
	protected $Validation;
	protected $Error;
	protected $Session;
	protected $viewFlg = 'input';
	
	/**
	 * __construct
	 */
	public function __construct() {
		// ファイルロード
		load_plugin_textdomain( self::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_error.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_form.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_mail.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_session.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_validation.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'system/mw_form_field.php' );
		foreach( glob( plugin_dir_path( __FILE__ ) . 'form_fields/*.php' ) as $form_field ) {
			include_once $form_field;
		}
		add_action( 'get_header', array( $this, 'init' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'wp_print_styles', array( $this, 'original_style' ) );
	}
	
	/**
	 * original_style
	 * CSS適用
	 */
	public function original_style() {
		$url = plugin_dir_url( __FILE__ );
		wp_register_style( self::DOMAIN, $url.'css/style.css' );
		wp_enqueue_style( self::DOMAIN );
	}
	
	/**
	 * save_post
	 * 編集・保存時にカスタムフィールドに[mwform〜]を保存
	 * @param	Int	投稿ID
	 */
	public function save_post( $id ) {
		$post = get_post( $id );
		preg_match( '/(\[mwform .+?\])/msi', $post->post_content, $reg );
		if ( !empty( $reg[1] ) ) {
			delete_post_meta( $id, self::CUSTOM_FIELD_NAME );
			add_post_meta( $id, self::CUSTOM_FIELD_NAME, $reg[1], true );
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
		$this->input = $atts['input'];
		$this->preview = $atts['preview'];
		$this->complete = $atts['complete'];
	}
	
	/**
	 * init
	 * 表示画面でのプラグインの初期化処理等。カスタムフィールド_mwformが無いときはショートコードの有効化、処理をしない。
	 */
	public function init() {
		global $post;
		if ( empty( $post->ID ) ) return;
		$_mwform = get_post_meta( $post->ID, self::CUSTOM_FIELD_NAME, true );
		// カスタムフィールドに値がなければ終了
		if ( empty( $_mwform ) ) return;
		// カスタムフィールドから設定を取得
		add_shortcode( 'mwform', array( $this, '_meta_mwform' ) );
		do_shortcode( $_mwform );
		remove_shortcode( 'mwform' );
		// ショートコード有効化
		add_shortcode( 'mwform', array( $this, '_mwform' ) );
		add_shortcode( 'mwform_complete_message', array( $this, '_mwform_complete_message' ) );
		add_shortcode( 'mwform_error', array( $this, '_mwform_error' ) );
		add_shortcode( 'mwform_text', array( $this, '_mwform_text' ) );
		add_shortcode( 'mwform_submitButton', array( $this, '_mwform_submitButton' ) );
		add_shortcode( 'mwform_submit', array( $this, '_mwform_submit' ) );
		add_shortcode( 'mwform_previewButton', array( $this, '_mwform_previewButton' ) );
		add_shortcode( 'mwform_backButton', array( $this, '_mwform_backButton' ) );
		add_shortcode( 'mwform_button', array( $this, '_mwform_button' ) );
		add_shortcode( 'mwform_hidden', array( $this, '_mwform_hidden' ) );
		add_shortcode( 'mwform_password', array( $this, '_mwform_password' ) );
		add_shortcode( 'mwform_zip', array( $this, '_mwform_zip' ) );
		add_shortcode( 'mwform_tel', array( $this, '_mwform_tel' ) );
		add_shortcode( 'mwform_textarea', array( $this, '_mwform_textarea' ) );
		add_shortcode( 'mwform_select', array( $this, '_mwform_select' ) );
		add_shortcode( 'mwform_radio', array( $this, '_mwform_radio' ) );
		add_shortcode( 'mwform_checkbox', array( $this, '_mwform_checkbox' ) );
		add_shortcode( 'mwform_datepicker', array( $this, '_mwform_datepicker' ) );
		
		// セッション初期化
		$this->Session = MW_Session::start( $this->key );
		// $_POSTがあるときは$_POST、無いときは$this->Session->getValues()
		$this->data = ( !empty( $_POST ) ) ? $_POST : $this->Session->getValues();
		// フォームオブジェクト生成
		$this->Form = new MW_Form( $this->data, $this->key );
		// セッションデータ設定
		$this->Session->clearValues();
		if ( !empty( $_POST ) ) $this->Session->save( $_POST );
		
		// バリデーションオブジェクト生成
		$this->Validation = new MW_Validation( $this->data );
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
				$this->redirect( $this->preview );
			} else {
				$this->redirect( $this->input );
			}
		}
		// 完了画面のとき
		elseif ( $this->Form->isComplete() ) {
			if ( $this->Validation->check() ) {
				$this->viewFlg = 'complete';
				$this->do_action_mwform_mail();
				$this->Form->clearToken();
				$this->redirect( $this->complete );
			} else {
				$this->redirect( $this->input );
			}
		}
		$this->Session->clearValues();
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
		$this->Validation = apply_filters( $filterName, $this->Validation );
		if ( !is_a( $this->Validation, 'MW_Validation' ) ) {
			exit( __( 'Validation Object is not a MW Validation Class.', self::DOMAIN ) );
		}
	}
	
	/**
	 * do_action_mwform_mail
	 * メール送信アクション
	 * @example
	 * 		// hoge識別子のフォームのメール送信を行う場合
	 * 		// $dataにフォームから送信された内容が配列で格納されている。
	 * 		add_action( 'mwform_mail_hoge', 'mwform_mail_hoge', 10, 2 );
	 * 		function mwform_mail_hoge( $m, $data ) {
	 * 			$m->to = $data['your_email'];	// 宛先
	 * 			$m->from = 'inc@2inc.org';		// 送信元
	 * 			$m->sender = 'kitajima'			// 送信者
	 * 			$m->subject = '送信ありがとうございます。';		// 題名
	 * 			$m->body = '本文';							// 本文
	 * 			$m->send();						// 送信
	 * 		}
	 */
	protected function do_action_mwform_mail() {
		$Mail = new MW_Mail();
		$actionName = 'mwform_mail_'.$this->key;
		do_action( $actionName, $Mail, $this->data );
	}
	
	/**
	 * redirect
	 * 現在のURLと引数で渡されたリダイレクトURLが同じであればリダイレクトしない
	 * @param	String	リダイレクトURL
	 */
	private function redirect( $url ) {
		$redirect = ( empty( $url ) ) ? $_SERVER['REQUEST_URI'] : $url;
		if ( $redirect != $_SERVER['REQUEST_URI'] || $this->Form->isInput() && !empty( $_POST ) ) {
			wp_redirect( $redirect );
			exit();
		}
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
		// 完了画面ではフォームは表示しない
		$atts = shortcode_atts( array(
			'input' => '',
			'preview' => '',
			'complete' => '',
			'key' => 'mwform'
		), $atts );
		
		if ( $this->viewFlg == 'input' || $this->viewFlg == 'preview' ) {
			$this->Error = $this->Validation->Error();
			return
				'<div id="mw_wp_form_' . $atts['key'] . '" class="mw_wp_form">' .
				$this->Form->start() .
				do_shortcode( $content ) .
				$this->Form->end() .
				'<!-- end .mw_wp_form --></div>';
		}
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
	 * _mwform_error
	 * エラーを出力。
	 * @example
	 * 		[mwform_error keys="hoge,fuga"]
	 */
	public function _mwform_error( $atts ) {
		$form_field = new mw_form_field_error( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_text
	 * テキストフィールドを出力。
	 * @example
	 * 		[mwform_text name="hoge" size="60" maxlength="255" value="" show_error="true"]
	 */
	public function _mwform_text( $atts ) {
		$form_field = new mw_form_field_text( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_submitButton
	 * 確認ボタンと送信ボタンを自動出力。同一ページ変遷の場合に利用。
	 * @example
	 * 		[mwform_submitButton name="hoge" preview_value="確認画面へ" submit_value="送信する"]
	 *
	 * viewFlg == inputで
	 *   preview_valueあるときは確認ボタン
	 *   preview_valueないときは送信ボタン
	 * viewFlg == preview
	 *   送信ボタン
	 * viewFlg == complete
	 *   非表示
	 */
	public function _mwform_submitButton( $atts ) {
		$form_field = new mw_form_field_submit_button( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_submit
	 * サブミットボタンを出力。
	 * @example
	 * 		[mwform_submit name="hoge" value="送信する"]
	 */
	public function _mwform_submit( $atts ) {
		$form_field = new mw_form_field_submit( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_previewButton
	 * 確認ボタンを出力。
	 * @example
	 * 		[mwform_previewButton value="確認画面へ"]
	 */
	public function _mwform_previewButton( $atts ) {
		$form_field = new mw_form_field_preview_button( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_backButton
	 * 戻るボタンを出力。
	 * @example
	 * 		[mwform_backButton value="戻る"]
	 */
	public function _mwform_backButton( $atts ) {
		$form_field = new mw_form_field_back_button( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_button
	 * ボタンを出力。
	 * @example
	 * 		[mwform_button name="hoge" value="hugaボタン"]
	 */
	public function _mwform_button( $atts ) {
		$form_field = new mw_form_field_button( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_hidden
	 * hiddenフィールドを出力。
	 * @example
	 * 		[mwform_hidden name="hoge" value="fuga"]
	 */
	public function _mwform_hidden( $atts ) {
		$form_field = new mw_form_field_hidden( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_password
	 * パスワードフィールドを出力。
	 * @example
	 * 		[mwform_password name="hoge" size="60" maxlength="255" value="" show_error=""]
	 */
	public function _mwform_password( $atts ) {
		$form_field = new mw_form_field_password( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_zip
	 * 郵便番号フィールドを出力。確認画面からの送信時は-区切りに変換されるが、
	 * 入力画面からの送信時は配列で送信されるので注意
	 * @example
	 * 		[mwform_zip name="hoge" show_error=""]
	 */
	public function _mwform_zip( $atts ) {
		$form_field = new mw_form_field_zip( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_tel
	 * 電話番号フィールドを出力。確認画面からの送信時は-区切りに返還されるが、
	 * 入力画面からの送信時は配列で送信されるので注意
	 * @example
	 * 		[mwform_tel name="hoge" show_error=""]
	 */
	public function _mwform_tel( $atts ) {
		$form_field = new mw_form_field_tel( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_textarea
	 * テキストエリアを出力。
	 * @example
	 * 		[mwform_textarea name="hoge" cols="50" rows="5" value="" show_error=""]
	 */
	public function _mwform_textarea( $atts ) {
		$form_field = new mw_form_field_textarea( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_select
	 * セレクトボックスを出力。
	 * @example
	 * 		[mwform_select name="hoge" children="日本,アメリカ,中国" value="初期値として選択状態にしたい値があれば指定" show_error=""]
	 */
	public function _mwform_select( $atts ) {
		$form_field = new mw_form_field_select( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_radio
	 * ラジオボタンを出力。
	 * @example
	 * 		[mwform_radio name="hoge" children="日本,アメリカ,中国" value="初期値として選択状態にしたい値があれば指定" show_error=""]
	 */
	public function _mwform_radio( $atts ) {
		$form_field = new mw_form_field_radio( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_checkbox
	 * チェックボックスを出力。
	 * @example
	 * 		[mwform_checkbox name="hoge" children="日本,アメリカ,中国" value="初期値として選択状態にしたい値があれば,区切りで指定" show_error=""]
	 */
	public function _mwform_checkbox( $atts ) {
		$form_field = new mw_form_field_checkbox( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
	
	/**
	 * _mwform_datepicker
	 * datepickerを出力。
	 * @example
	 * 		[mwform_checkbox name="hoge" children="日本,アメリカ,中国" value="初期値として選択状態にしたい値があれば,区切りで指定" show_error=""]
	 */
	public function _mwform_datepicker( $atts ) {
		$form_field = new mw_form_field_datepicker( $this->Form, $atts, $this->viewFlg, $this->Error );
		return $form_field->getField();
	}
}
