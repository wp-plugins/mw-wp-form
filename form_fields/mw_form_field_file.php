<?php
/**
 * Name: MW Form Field File
 * URI: http://2inc.org
 * Description: 画像アップロードフィールドを出力。
 * Version: 1.2
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: May 17, 2013
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
class mw_form_field_file extends mw_form_field {

	/**
	 * String $short_code_name
	 */
	protected $short_code_name = 'mwform_file';

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'name' => '',
			'size' => 60,
			'show_error' => 'true',
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function inputPage() {
		$_ret = $this->Form->file( $this->atts['name'], array(
			'size' => $this->atts['size'],
		) );
		$value = $this->Form->getValue( $this->atts['name'] );
		$upload_file_keys = $this->Form->getValue( MWF_Config::UPLOAD_FILE_KEYS );
		if ( !empty( $value ) && is_array( $upload_file_keys ) && in_array( $this->atts['name'], $upload_file_keys ) ) {
			$_ret .= '<div class="' . MWF_Config::NAME . '_file">';
			$_ret .= '<a href="' . esc_attr( $value ) . '" target="_blank">' . __( 'Uploaded.', MWF_Config::DOMAIN ) . '</a>';
			$_ret .= $this->Form->hidden( $this->atts['name'], $value );
			$_ret .= '</div>';
		}
		if ( $this->atts['show_error'] !== 'false' )
			$_ret .= $this->getError( $this->atts['name'] );
		return $_ret;
	}

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function previewPage() {
		$value = $this->Form->getValue( $this->atts['name'] );
		if ( $value ) {
			$_ret  = '<div class="' . MWF_Config::NAME . '_file">';
			$_ret .= '<a href="' . esc_attr( $value ) . '" target="_blank">' . __( 'Uploaded.', MWF_Config::DOMAIN ) . '</a>';
			$_ret .= '</div>';
			$_ret .= $this->Form->hidden( $this->atts['name'], $value );
			return $_ret;
		}
	}

	/**
	 * add_qtags
	 * QTags.addButton を出力
	 */
	protected function add_qtags() {
		?>
		'<?php echo $this->short_code_name; ?>',
		'<?php _e( 'File', MWF_Config::DOMAIN ); ?>',
		'[<?php echo $this->short_code_name; ?> name=""]',
		''
		<?php
	}
}
