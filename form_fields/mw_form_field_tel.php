<?php
/**
 * Name: MW Form Field Tel
 * URI: http://2inc.org
 * Description: 電話番号フィールドを出力。
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
class mw_form_field_tel extends mw_form_field {

	/**
	 * string $shortcode_name
	 */
	protected $shortcode_name = 'mwform_tel';

	/**
	 * __construct
	 */
	public function __construct() {
		parent::__construct();
		$this->set_qtags(
			$this->shortcode_name,
			'Tel',
			$this->shortcode_name .' name=""'
		);
	}

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'name'       => '',
			'show_error' => 'true',
			'conv_half_alphanumeric' => 'true',
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function inputPage() {
		$conv_half_alphanumeric = false;
		if ( $this->atts['conv_half_alphanumeric'] === 'true' ) {
			$conv_half_alphanumeric = true;
		}
		$_ret = $this->Form->tel( $this->atts['name'], array( 'conv-half-alphanumeric' => $conv_half_alphanumeric ) );
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
		$value = $this->Form->getTelValue( $this->atts['name'] );
		$_ret  = $value;
		$_ret .= $this->Form->hidden( $this->atts['name'].'[data]', $value );
		$_ret .= $this->Form->separator( $this->atts['name'] );
		return $_ret;
	}
}
