<?php
/**
 * Name: MW Form Field Preview Button
 * URI: http://2inc.org
 * Description: 確認ボタンを出力。
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
class mw_form_field_preview_button extends mw_form_field {

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'value' => __( 'Confirm', self::DOMAIN ),
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function inputPage() {
		return $this->Form->submit( $this->Form->getPreviewButtonName(), $this->atts['value'] );
	}

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function previewPage() {
	}
}
