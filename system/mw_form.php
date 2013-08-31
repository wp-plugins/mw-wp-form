<?php
/**
 * Name: MW Form
 * URI: http://2inc.org
 * Description: フォームクラス
 * Version: 1.3.3
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: September 25, 2012
 * Modified: August 28, 2013
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
class MW_Form {

	protected $key = 'form_token';		// 識別子
	public $tokenName = 'token';		// トークンタグ用のトークン名
	protected $token;					// トークンの値
	protected $data;					// データ
	protected $Session;					// sessionオブジェクト
	protected $previewButton = 'submitPreview';	// 確認ボタンの名前
	protected $backButton = 'submitBack';		// 戻るボタンの名前
	protected $modeCheck = 'input';
	protected $method = 'post';
	private $ENCODE = 'utf-8';

	/**
	 * __construct
	 * 取得データを保存、識別子とセッションIDののhash値をトークンとして利用
	 * @param	Array	リクエストデータ
	 * 			String	識別子
	 */
	public function __construct( $data, $key = '' ) {
		$this->data = $data;
		if ( !empty( $key ) ) {
			$this->key = $key.'_token';
		}
		$this->Session = MW_Session::start( $this->key );
		$this->modeCheck = $this->modeCheck();
		$this->token = sha1( $this->key . session_id() );
		if ( $this->isInput() && empty( $_POST ) ) {
			$this->Session->save( array( $this->tokenName => $this->token ) );
		}
		// 戻る、確認画面へのポスト、完了画面へのポストでないときはデータを破棄
		if ( !( isset( $this->data[$this->backButton] ) || $this->isPreview() || $this->isComplete() ) ) {
			// フォームオブジェクト再生成
			$this->data = array();
		}
	}

	/**
	 * clearToken
	 * トークン用のセッションを破棄
	 */
	public function clearToken() {
		$this->Session->clearValue( $this->tokenName );
	}

	/**
	 * isComplete
	 * 完了画面かどうか
	 * @return	Boolean
	 */
	public function isComplete() {
		$_ret = false;
		if ( !empty( $this->data ) && $this->modeCheck === 'complete' ) {
			$_ret = true;
		}
		return $_ret;
	}

	/**
	 * isPreview
	 * 確認画面かどうか
	 * @return	Boolean
	 */
	public function isPreview() {
		$_ret = false;
		if ( !empty( $this->data ) && $this->modeCheck === 'preview' ) {
			$_ret = true;
		}
		return $_ret;
	}

	/**
	 * isInput
	 * 入力画面かどうか
	 * @return	Boolean
	 */
	public function isInput() {
		$_ret = false;
		if ( $this->modeCheck === 'input' ) {
			$_ret = true;
		}
		return $_ret;
	}

	/**
	 * modeCheck
	 * 表示画面判定
	 * @return	input || preview || complete
	 */
	protected function modeCheck() {
		if ( isset( $this->data[$this->backButton] ) ) {
			$backButton = $this->data[$this->backButton];
		} elseif ( isset( $this->data[$this->previewButton] ) ) {
			$previewButton = $this->data[$this->previewButton];
		}
		$_ret = 'input';
		if ( isset( $backButton ) ) {
			$_ret = 'input';
		} elseif ( isset( $previewButton ) ) {
			$_ret = 'preview';
		} elseif ( !isset( $previewButton ) && !isset( $backButton ) && $this->check() ) {
			$_ret = 'complete';
		}
		return $_ret;
	}

	/**
	 * check
	 * トークンチェック
	 * @return	Boolean
	 */
	protected function check() {
		if ( isset( $this->data[$this->tokenName] ) )
			$requestToken = $this->data[$this->tokenName];
		$_ret = false;
		$s_token = $this->Session->getValue( $this->tokenName );
		if ( isset( $requestToken ) && !empty( $s_token ) && $requestToken == $s_token )
			$_ret = true;
		return $_ret;
	}

	/**
	 * getPreviewButtonName
	 * 確認画面への変遷用ボタンのname属性値を返す
	 * @return	String	name属性値
	 */
	public function getPreviewButtonName() {
		return $this->previewButton;
	}

	/**
	 * getBackButtonName
	 * 戻る用ボタンのname属性値を返す
	 * @return	String
	 */
	public function getBackButtonName() {
		return $this->backButton;
	}

	/**
	 * getValue
	 * データを返す
	 * @param	String	キー
	 * @return	Mixed	データ
	 */
	public function getValue( $key ) {
		$_ret = null;
		if ( isset( $this->data[$key] ) ) {
			$_ret = $this->data[$key];
			$_ret = $this->e( $_ret );
		}
		return $_ret;
	}

	/**
	 * getZipValue
	 * データを返す ( 郵便番号用 )
	 * @param	String	キー
	 * @return	Mixed	データ
	 */
	public function getZipValue( $key ) {
		$_ret = null;
		$separator = $this->getSeparatorValue( $key );
		// すべて空のからのときはimplodeしないように（---がいってしまうため）
		if ( array_key_exists( 'data', $this->data[$key] ) && is_array( $this->data[$key]['data'] ) && !empty( $separator ) ) {
			foreach ( $this->data[$key]['data'] as $value ) {
				if ( !( $value === '' || $value === null ) ) {
					$_ret = implode( $separator, $this->data[$key]['data'] );
					$_ret = $this->e( $_ret );
					break;
				}
			}
		}
		return $_ret;
	}

	/**
	 * getTelValue
	 * データを返す ( 電話番号用 )
	 * @param	String	キー
	 * @return	String	データ
	 */
	public function getTelValue( $key ) {
		return $this->getZipValue( $key );
	}

	/**
	 * getCheckedValue
	 * データを返す（ checkbox用 ）。$dataに含まれる値のみ返す
	 * @param	String	キー
	 * 			Array	データ
	 * @return	String	データ
	 */
	public function getCheckedValue( $key, Array $data ) {
		$_ret = null;
		$separator = $this->getSeparatorValue( $key );
		if ( array_key_exists( 'data', $this->data[$key] ) && is_array( $this->data[$key]['data'] ) && !empty( $separator ) ) {
			$rightData = array();
			foreach ( $this->data[$key]['data'] as $value ) {
				if ( isset( $data[$value] ) && !in_array( $data[$value], $rightData ) ) {
					$rightData[] = $data[$value];
				}
			}
			$_ret = implode( $separator, $rightData );
			$_ret = $this->e( $_ret );
		}
		return $_ret;
	}

	/**
	 * getRadioValue
	 * データを返す（ radio用 ）。$dataに含まれる値のみ返す
	 * @param	String	キー
	 * 			Array	データ
	 * @return	String	データ
	 */
	public function getRadioValue( $key, Array $data ) {
		$_ret = null;
		if ( isset( $this->data[$key] ) && !is_array( $this->data[$key] ) ) {
			if ( isset( $data[$this->data[$key]] ) ) {
				$_ret = $data[$this->data[$key]];
				$_ret = $this->e( $_ret );
			}
		}
		return $_ret;
	}

	/**
	 * getSelectedValue
	 * データを返す（ selectbox用 ）。$dataに含まれる値のみ返す
	 * @param	String	キー
	 * 			Array	データ
	 * @return	String	データ
	 */
	public function getSelectedValue( $key, Array $data ) {
		return $this->getRadioValue( $key, $data );
	}

	/**
	 * separator
	 * separatorを設定するためのhiddenを返す
	 * @param	String	キー
	 * 			String	区切り文字
	 * @return	String	HTML
	 */
	public function separator( $key, $separator = '' ) {
		if ( !$separator && $post_separator = $this->getSeparatorValue( $key ) )
			$separator = $post_separator;
		if ( $separator )
			return $this->hidden( $key.'[separator]', $separator );
	}

	/**
	 * getSeparatorValue
	 * 送られてきたseparatorを返す
	 * @param	String	キー
	 * 			Array	データ
	 * @return	String	データ
	 */
	public function getSeparatorValue( $key ) {
		if ( isset( $this->data[$key]['separator'] ) )
			return $this->data[$key]['separator'];
	}

	/**
	 * start
	 * フォームタグ生成
	 * @param	Array	( 'action' =>, 'enctype' => )
	 * @return	String	form開始タグ
	 */
	public function start( $options = array() ) {
		$defaults = array(
			'action' => '',
			//'enctype' => 'application/x-www-form-urlencoded',
			'enctype' => 'multipart/form-data',
		);
		$options = array_merge( $defaults, $options );
		$_ret = sprintf( '<form method="%s" action="%s" enctype="%s">',
				$this->method, $this->e( $options['action'] ), $this->e( $options['enctype'] ) );
		return $_ret;
	}

	/**
	 * end
	 * トークンタグ、閉じタグ生成
	 * @return	String	input[type=hidden]
	 */
	public function end() {
		$_ret = '';
		if ( $this->method == 'post' )
			$_ret .= $this->hidden( $this->tokenName, $this->token );
		$_ret .= '</form>';
		return $_ret;
	}

	/**
	 * text
	 * input[type=text]タグ生成
	 * @param	String	name属性
	 * 			Array	( 'size' =>, 'maxlength' =>, 'value' => )
	 * @return	String	htmlタグ
	 */
	public function text( $name, $options = array() ) {
		$defaults = array(
			'size' => 60,
			'maxlength' => 255,
			'value' => '',
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = sprintf( '<input type="text" name="%s" value="%s" size="%d" maxlength="%d" />',
			$this->e( $name ), $this->e( $value ), $this->e( $options['size'] ), $this->e( $options['maxlength'] )
		);
		return $_ret;
	}

	/**
	 * hidden
	 * input[type=hidden]タグ生成
	 * @param	String	name属性
	 * 			String	値
	 * @return	String	htmlタグ
	 */
	public function hidden( $name, $value ) {
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $value;
		if ( is_array( $value ) )
			$value = $this->getZipValue( $name );
		$_ret = sprintf( '<input type="hidden" name="%s" value="%s" />', $this->e( $name ), $this->e( $value ) );
		return $_ret;
	}

	/**
	 * password
	 * input[type=password]タグ生成
	 * @param	String	name属性
	 * 			Array	( 'size' =>, 'maxlength' =>, 'value' => )
	 * @return	String	htmlタグ
	 */
	public function password( $name, $options = array() ) {
		$defaults = array(
			'size' => 60,
			'maxlength' => 255,
			'value' => '',
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = sprintf( '<input type="password" name="%s" value="%s" size="%d" maxlength="%d" />',
			$this->e( $name ), $this->e( $value ), $this->e( $options['size'] ), $this->e( $options['maxlength'] )
		);
		return $_ret;
	}

	/**
	 * zip
	 * 郵便番号フィールド生成
	 * @param	String	name属性
	 * @return	String	htmlタグ
	 */
	public function zip( $name ) {
		$separator = '-';
		if ( isset( $this->data[$name]['data'] ) ) {
			if ( is_array( $this->data[$name]['data'] ) ) {
				$value = $this->data[$name]['data'];
			} else {
				$value = explode( $separator, $this->data[$name]['data'] );
			}
		}
		$value0 = ( isset( $value[0] ) )? $value[0] : '';
		$value1 = ( isset( $value[1] ) )? $value[1] : '';
		$_ret = '〒';
		$_ret .= $this->text( $name.'[data][0]', array( 'size' => 4, 'maxlength' => 3, 'value' => $value0 ) );
		$_ret .= ' '.$separator.' ';
		$_ret .= $this->text( $name.'[data][1]', array( 'size' => 5, 'maxlength' => 4, 'value' => $value1 ) );
		$_ret .= $this->separator( $name, $separator );
		return $_ret;
	}

	/**
	 * tel
	 * 電話番号フィールド生成
	 * @param	String	name属性
	 * @return	String	htmlタグ
	 */
	public function tel( $name ) {
		$separator = '-';
		if ( isset( $this->data[$name]['data'] ) ) {
			if ( is_array( $this->data[$name]['data'] ) ) {
				$value = $this->data[$name]['data'];
			} else {
				$value = explode( $separator, $this->data[$name]['data'] );
			}
		}
		$value0 = ( isset( $value[0] ) )? $value[0] : '';
		$value1 = ( isset( $value[1] ) )? $value[1] : '';
		$value2 = ( isset( $value[2] ) )? $value[2] : '';
		$_ret = '';
		$_ret .= $this->text( $name.'[data][0]', array( 'size' => 6, 'maxlength' => 5, 'value' => $value0 ) );
		$_ret .= ' '.$separator.' ';
		$_ret .= $this->text( $name.'[data][1]', array( 'size' => 5, 'maxlength' => 4, 'value' => $value1 ) );
		$_ret .= ' '.$separator.' ';
		$_ret .= $this->text( $name.'[data][2]', array( 'size' => 5, 'maxlength' => 4, 'value' => $value2 ) );
		$_ret .= $this->separator( $name, $separator );
		return $_ret;
	}

	/**
	 * textarea
	 * textareaタグ生成
	 * @param	String	name属性
	 * 			Array	( 'cols' =>, 'rows' =>, 'value' => )
	 * @return	String	htmlタグ
	 */
	public function textarea( $name, $options = array() ) {
		$defaults = array(
			'cols' => 50,
			'rows' => 5,
			'value' => ''
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = sprintf( '<textarea name="%s" cols="%d" rows="%d">%s</textarea>',
			$this->e( $name ), $this->e( $options['cols'] ), $this->e( $options['rows'] ), $this->e( $value )
		);
		return $_ret;
	}

	/**
	 * select
	 * selectタグ生成
	 * @param	String	name属性
	 * 			Array	( キー => 値, … )
	 * 			Array	( 'value' => )
	 * @return	String	htmlタグ
	 */
	public function select( $name, $children = array(), $options = array() ) {
		$defaults = array(
			'value' => ''
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = sprintf( '<select name="%s">', $this->e( $name ) );
		foreach ( $children as $key => $_value ) {
			$selected = ( $key == $value )? ' selected="selected"' : '';
			$_ret .= sprintf( '<option value="%s"%s>%s</option>',
				$this->e( $key ), $selected, $this->e( $_value )
			);
		}
		$_ret .= '</select>';
		return $_ret;
	}

	/**
	 * radio
	 * radioタグ生成
	 * @param	String name属性
	 * 			Array	( キー => 値, … )
	 * 			Array	( 'value' => )
	 * @return	String	htmlタグ
	 */
	public function radio( $name, $children = array(), $options = array() ) {
		$defaults = array(
			'value' => ''
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = '';
		foreach ( $children as $key => $_value ) {
			$checked = ( $key == $value )? ' checked="checked"' : '';
			$_ret .= sprintf( '<label><input type="radio" name="%s" value="%s"%s />%s</label>',
				$this->e( $name ), $this->e( $key ), $checked, $this->e( $_value )
			);
		}
		return $_ret;
	}

	/**
	 * checkbox
	 * checkboxタグ生成
	 * @param	String	name属性
	 * 			Array	( キー => 値, … )
	 * 			Array	( 'value' => Mixed )
	 * 			String	区切り文字
	 * @return	String	htmlタグ
	 */
	public function checkbox( $name, $children = array(), $options = array(), $separator = ',' ) {
		$defaults = array(
			'value' => array()
		);
		$options = array_merge( $defaults, $options );

		$value = $options['value'];
		if ( isset( $this->data[$name]['data'] ) ) {
			$value = $this->data[$name]['data'];
		}
		if ( !is_array( $value ) ) {
			$value = explode( $separator, $value );
		}
		$_ret = '';
		foreach ( $children as $key => $_value ) {
			$checked = ( is_array( $value ) && in_array( $key, $value ) )? ' checked="checked"' : '';
			$_ret .= sprintf( '<label><input type="checkbox" name="%s" value="%s"%s />%s</label>',
				$this->e( $name.'[data][]' ), $this->e( $key ), $checked, $this->e( $_value )
			);
		}
		$_ret .= $this->separator( $name, $separator );
		return $_ret;
	}

	/**
	 * submit
	 * submitボタン生成
	 * @param	String	name属性
	 * 			String	value属性
	 * @return	String	submitボタン
	 */
	public function submit( $name, $value ) {
		$_ret = sprintf( '<input type="submit" name="%s" value="%s" />', $this->e( $name ), $this->e( $value ) );
		return $_ret;
	}

	/**
	 * button
	 * ボタン生成
	 * @param	String	name属性
	 * 			String	value属性
	 * @return	String	ボタン
	 */
	public function button( $name, $value ) {
		$_ret = sprintf( '<input type="button" name="%s" value="%s" />', $this->e( $name ), $this->e( $value ) );
		return $_ret;
	}

	/**
	 * datepicker
	 * datepicker生成
	 * @param	String	name属性
	 * 			String	size属性
	 * 			String	value属性
	 * 			String	js	datepickerの引数
	 * @return	String	ボタン
	 */
	public function datepicker( $name, $options = array() ) {
		$defaults = array(
			'size' => 30,
			'js' => '',
			'value' => '',
		);
		$options = array_merge( $defaults, $options );
		$value = ( isset( $this->data[$name] ) )? $this->data[$name] : $options['value'];
		$_ret = sprintf( '<input type="text" name="%s" value="%s" size="%d" />',
			$this->e( $name ), $this->e( $value ), $this->e( $options['size'] )
		);
		$_ret .= sprintf( '
			<script type="text/javascript">
			jQuery( function( $ ) {
				$("input[name=\'%s\']").datepicker({%s});
			} );
			</script>
		',$this->e( $name ), $options['js'] );
		return $_ret;
	}

	/**
	 * file
	 * input[type=file]タグ生成
	 * @param	String	name属性
	 * 			Array	( 'size' => )
	 * @return	String	htmlタグ
	 */
	public function file( $name, $options = array() ) {
		$defaults = array(
			'size' => 60,
		);
		$options = array_merge( $defaults, $options );
		$_ret = sprintf( '<input type="file" name="%s" size="%d" />',
			$this->e( $name ), $this->e( $options['size'] )
		);
		return $_ret;
	}

	/**
	 * e
	 * htmlサニタイズ
	 * @param	Mixed
	 * @return	Mixed
	 */
	public function e( $str ){
		if ( is_null( $str ) ) {
			return null;
		} elseif ( is_array( $str ) ) {
			return array_map( array( $this, 'e' ), $str );
		} else {
			$str = stripslashes( $str );
			return htmlspecialchars( $str, ENT_QUOTES, $this->ENCODE );
		}
	}
}
?>