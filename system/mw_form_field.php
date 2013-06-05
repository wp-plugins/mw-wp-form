<?php
/**
 * Name: MW Form Field
 * URI: http://2inc.org
 * Description: フォームフィールドの抽象クラス
 * Version: 1.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: December 14, 2012
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
abstract class mw_form_field {

	/**
	 * Form $Form
	 */
	const DOMAIN = 'mw-wp-form';

	/**
	 * Form $Form
	 */
	protected $Form;

	/**
	 * Array	$defaults	属性値等初期値
	 */
	protected $defaults;

	/**
	 * Array	$atts	属性値等
	 */
	protected $atts;

	/**
	 * String	$viewFlg	表示ページ種別( input, preview )
	 */
	protected $viewFlg;
	
	/**
	 * Error	$Error	エラーオブジェクト
	 */
	protected $Error;

	/**
	 * __construct
	 * @param	mw_form	$Form
	 * 			Array	$atts		属性値等
	 * 			String	$viewFlg	表示ページ種別( input, preview )
	 */
	public function __construct( mw_form $Form, $atts, $viewFlg, $Error ) {
		$this->Form = $Form;
		$this->defaults = $this->setDefaults();
		$this->atts = shortcode_atts( $this->defaults, $atts );
		$this->viewFlg = $viewFlg;
		$this->Error = $Error;
	}

	/**
	 * getField
	 * フォーム項目を返す
	 * @return	String	HTML
	 */
	public function getField() {
		switch( $this->viewFlg ) {
			case 'input' :
				return $this->inputPage();
				break;
			case 'preview' :
				return $this->previewPage();
				break;
			default :
				exit( '$viewFlg is not right value.' );
		}
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
	 * @return	String	HTML
	 */
	abstract protected function inputPage();

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	abstract protected function previewPage();

	/**
	 * getChildren
	 * 選択肢の配列を返す
	 * @return	Array	$children
	 */
	protected function getChildren() {
		$children = array();
		if ( !is_array( $this->atts['children'] ) )
			$_children = explode( ',', $this->atts['children'] );
		foreach ( $_children as $child ) {
			$children[$child] = $child;
		}
		return $children;
	}
}
