<?php
/**
 * Name: MW Form Field Submit Button
 * URI: http://2inc.org
 * Description: 確認ボタンと送信ボタンを自動出力。同一ページ変遷の場合に利用。
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
class mw_form_field_submit_button extends mw_form_field {

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'name' => '',
			'preview_value' => __( 'Confirm', self::DOMAIN ),
			'submit_value'  => __( 'Send', self::DOMAIN ),
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function inputPage() {
		if ( !empty( $this->atts['preview_value'] ) ) {
			return $this->Form->submit( $this->Form->getPreviewButtonName(), $this->atts['preview_value'] );
		}
		return $this->Form->submit( $this->atts['name'], $this->atts['submit_value'] );
	}

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function previewPage() {
		return $this->Form->submit( $this->atts['name'], $this->atts['submit_value'] );
	}
}
