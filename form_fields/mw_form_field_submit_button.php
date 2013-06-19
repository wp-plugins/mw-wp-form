<?php
/**
 * Name: MW Form Field Submit
 * URI: http://2inc.org
 * Description: サブミットボタンを出力。
 * Version: 1.1
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: December 14, 2012
 * Modified: May 29, 2013
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
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	protected function inputPage( $atts ) {
		if ( !empty( $atts['preview_value'] ) ) {
			return $this->Form->submit( $this->Form->getPreviewButtonName(), $atts['preview_value'] );
		}
		return $this->Form->submit( $atts['name'], $atts['submit_value'] );
	}

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	protected function previewPage( $atts ) {
		return $this->Form->submit( $atts['name'], $atts['submit_value'] );
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
