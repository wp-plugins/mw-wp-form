<?php
/**
 * Name: MW Form Field
 * URI: http://2inc.org
 * Description: フォームフィールドの抽象クラス
 * Version: 1.3.6
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : December 14, 2012
 * Modified: December 29, 2013
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
abstract class mw_form_field {

	/**
	 * string $shortcode_name
	 */
	protected $shortcode_name;

	/**
	 * Form $Form
	 */
	protected $Form;

	/**
	 * array $defaults 属性値等初期値
	 */
	protected $defaults = array();

	/**
	 * array $atts 属性値
	 */
	protected $atts = array();

	/**
	 * Error $Error エラーオブジェクト
	 */
	protected $Error;

	/**
	 * string $key フォーム識別子
	 */
	protected $key;

	/**
	 * array $qtags qtagsの引数
	 */
	protected $qtags = array(
		'id' => '',
		'display' => '',
		'arg1' => '',
		'arg2' => '',
	);

	/**
	 * __construct
	 */
	public function __construct() {
		$this->defaults = $this->setDefaults();
		add_action( 'mwform_add_shortcode', array( $this, 'add_shortcode' ), 10, 4 );
		add_action( 'mwform_add_qtags', array( $this, '_add_qtags' ) );
	}

	/**
	 * get_qtags
	 * @return array $qtags
	 */
	public function get_qtags() {
		return $this->qtags;
	}

	/**
	 * set_qtags
	 * @param string $id
	 * @param string $display
	 * @param string $arg1 開始タグ（ショートコード）
	 * @param string $arg2 終了タグ（ショートコード）
	 */
	protected function set_qtags( $id, $display, $arg1, $arg2 = '' ) {
		$this->qtags = array(
		'id' => $id,
		'display' => $display,
		'arg1' => $arg1,
		'arg2' => $arg2,
		);
	}

	/**
	 * getError
	 * @param  string $key name属性
	 * @return string エラーHTML
	 */
	protected function getError( $key ) {
		$_ret = '';
		if ( is_array( $this->Error->getError( $key ) ) ) {
			$start_tag = '<span class="error">';
			$end_tag   = '</span>';
			foreach ( $this->Error->getError( $key ) as $rule => $error ) {
				$error = apply_filters( 'mwform_error_message_' . $this->key, $error, $key, $rule );
				$error_html = apply_filters( 'mwform_error_message_html',
					$start_tag . esc_html( $error ) . $end_tag,
					$error,
					$start_tag,
					$end_tag,
					$this->key,
					$key,
					$rule
				);
				$_ret .= $error_html;
			}
		}
		return apply_filters( 'mwform_error_message_wrapper', $_ret, $this->key );
	}

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	abstract protected function setDefaults();

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	abstract protected function inputPage();
	public function _inputPage( $atts ) {
		if ( isset( $this->defaults['value'], $atts['name'] ) && !isset( $atts['value'] ) ) {
			$atts['value'] = apply_filters( 'mwform_value_' . $this->key, $this->defaults['value'], $atts['name'] );
		}
		$this->atts = shortcode_atts( $this->defaults, $atts );
		return $this->inputPage();
	}

	/**
	 * confirmPage
	 * 確認ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	abstract protected function confirmPage();
	public function _confirmPage( $atts ) {
		$this->atts = shortcode_atts( $this->defaults, $atts );
		return $this->confirmPage();
	}

	/**
	 * add_short_code
	 * フォーム項目を返す
	 * @param	MW_Form		$Form
	 * 			String		$viewFlg
	 * 			MW_Error	$Error
	 * 			String		$key
	 */
	public function add_shortcode( mw_form $Form, $viewFlg, mw_error $Error, $key ) {
		if ( !empty( $this->shortcode_name ) ) {
			$this->Form = $Form;
			$this->Error = $Error;
			$this->key = $key;
			switch( $viewFlg ) {
				case 'input' :
					add_shortcode( $this->shortcode_name, array( $this, '_inputPage' ) );
					break;
				case 'confirm' :
					add_shortcode( $this->shortcode_name, array( $this, '_confirmPage' ) );
					break;
				default :
					exit( '$viewFlg is not right value.' );
			}
		}
	}

	/**
	 * getChildren
	 * 選択肢の配列を返す
	 * @param string $_children
	 * @return array $children
	 */
	protected function getChildren( $_children ) {
		$children = array();
		if ( !empty( $_children) && !is_array( $_children ) ) {
			$_children = explode( ',', $_children );
		}
		if ( is_array( $_children ) ) {
			foreach ( $_children as $child ) {
				$children[$child] = $child;
			}
		}
		if ( $this->key ) {
			$children = apply_filters( 'mwform_choices_' . $this->key, $children, $this->atts );
		}
		return $children;
	}

	/**
	 * _add_qtags
	 * QTags.addButton を出力
	 */
	public function _add_qtags() {
		?>
		QTags.addButton(
			'<?php echo $this->qtags['id']; ?>',
			'<?php echo $this->qtags['display']; ?>',
			'[<?php echo $this->qtags['arg1']; ?>]',
			'<?php echo $this->qtags['arg2']; ?>'
		);
		<?php
	}
}



