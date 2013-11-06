<?php
/**
 * Name: MW Form Field
 * URI: http://2inc.org
 * Description: フォームフィールドの抽象クラス
 * Version: 1.3
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: December 14, 2012
 * Modified: December 5, 2013
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
	 * String $short_code_name
	 */
	protected $short_code_name;

	/**
	 * Form $Form
	 */
	protected $Form;

	/**
	 * Array	$defaults	属性値等初期値
	 */
	protected $defaults = array();

	/**
	 * Array	$atts	属性値
	 */
	protected $atts = array();

	/**
	 * Error	$Error	エラーオブジェクト
	 */
	protected $Error;

	/**
	 * key	$key	フォーム識別子
	 */
	protected $key;

	/**
	 * __construct
	 */
	public function __construct() {
		$this->defaults = $this->setDefaults();
		add_action( 'mwform_add_shortcode', array( $this, 'add_shortcode' ), 10, 4 );
		add_action( 'mwform_add_qtags', array( $this, '_add_qtags' ) );
	}

	/**
	 * getError
	 * @param	String	フォーム項目名
	 * @return	String	エラーHTML
	 */
	protected function getError( $key ) {
		$_ret = '';
		if ( is_array( $this->Error->getError( $key ) ) ) {
			foreach ( $this->Error->getError( $key ) as $error ) {
				$_ret .= sprintf( '<span class="error">%s</span>', htmlspecialchars( $error, ENT_QUOTES ) );
			}
		}
		return $_ret;
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
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	abstract protected function previewPage();
	public function _previewPage( $atts ) {
		$this->atts = shortcode_atts( $this->defaults, $atts );
		return $this->previewPage();
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
		if ( !empty( $this->short_code_name ) ) {
			$this->Form = $Form;
			$this->Error = $Error;
			$this->key = $key;
			switch( $viewFlg ) {
				case 'input' :
					add_shortcode( $this->short_code_name, array( $this, '_inputPage' ) );
					break;
				case 'preview' :
					add_shortcode( $this->short_code_name, array( $this, '_previewPage' ) );
					break;
				default :
					exit( '$viewFlg is not right value.' );
			}
		}
	}

	/**
	 * getChildren
	 * 選択肢の配列を返す
	 * @param	String	$_children
	 * @return	Array	$children
	 */
	protected function getChildren( $_children ) {
		$children = array();
		if ( !is_array( $_children ) )
			$_children = explode( ',', $_children );
		foreach ( $_children as $child ) {
			$children[$child] = $child;
		}
		if ( $this->key )
			$children = apply_filters( 'mwform_choices_' . $this->key, $children, $this->atts );
		return $children;
	}

	/**
	 * add_qtags
	 * QTags.addButton を出力
	 */
	abstract protected function add_qtags();
	public function _add_qtags() {
		?>
		QTags.addButton(
			<?php $this->add_qtags(); ?>
		);
		<?php
	}
}



