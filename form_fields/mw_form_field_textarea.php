<?php
/**
 * Name: MW Form Field TextArea
 * URI: http://2inc.org
 * Description: テキストエリアを出力。
 * Version: 1.2.3
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : December 14, 2012
 * Modified: December 22, 2013
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
class mw_form_field_textarea extends mw_form_field {

	/**
	 * String $shortcode_name
	 */
	protected $shortcode_name = 'mwform_textarea';

	/**
	 * __construct
	 */
	public function __construct() {
		parent::__construct();
		$this->set_qtags(
			$this->shortcode_name,
			'Textarea',
			$this->shortcode_name . ' name=""'
		);
	}

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return array
	 */
	protected function setDefaults() {
		return array(
			'name'        => '',
			'cols'        => 50,
			'rows'        => 5,
			'value'       => '',
			'placeholder' => '',
			'show_error'  => 'true',
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return string html
	 */
	protected function inputPage() {
		$_ret = $this->Form->textarea( $this->atts['name'], array(
			'cols' => $this->atts['cols'],
			'rows' => $this->atts['rows'],
			'value' => $this->atts['value'],
			'placeholder' => $this->atts['placeholder'],
		) );
		if ( $this->atts['show_error'] !== 'false' )
			$_ret .= $this->getError( $this->atts['name'] );
		return $_ret;
	}

	/**
	 * confirmPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function confirmPage() {
		$value = $this->Form->getValue( $this->atts['name'] );
		$_ret  = nl2br( $value );
		$_ret .= $this->Form->hidden( $this->atts['name'], $value );
		return $_ret;
	}
}
