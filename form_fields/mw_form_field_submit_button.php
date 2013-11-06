<?php
/**
 * Name: MW Form Field Submit
 * URI: http://2inc.org
 * Description: サブミットボタンを出力。
 * Description: 確認ボタンと送信ボタンを自動出力。
 * Version: 1.2
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: December 14, 2012
 * Modified: Septermber 19, 2013
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
class mw_form_field_submit_button extends mw_form_field {

	/**
	 * String $short_code_name
	 */
	protected $short_code_name = 'mwform_submitButton';

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'name' => '',
			'preview_value' => __( 'Confirm', MWF_Config::DOMAIN ),
			'submit_value'  => __( 'Send', MWF_Config::DOMAIN ),
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

	/**
	 * add_qtags
	 * QTags.addButton を出力
	 */
	protected function add_qtags() {
		?>
		'<?php echo $this->short_code_name; ?>',
		'<?php _e( 'Confirm &amp; Submit', MWF_Config::DOMAIN ); ?>',
		'[<?php echo $this->short_code_name; ?>]',
		''
		<?php
	}
}
