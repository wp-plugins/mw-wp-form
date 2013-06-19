<?php
/**
 * Name: MW Form Field Datepicker
 * URI: http://2inc.org
 * Description: datepickerを出力。
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
class mw_form_field_datepicker extends mw_form_field {

	/**
	 * String $short_code_name
	 */
	protected $short_code_name = 'mwform_datepicker';

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return	Array	defaults
	 */
	protected function setDefaults() {
		return array(
			'name'       => '',
			'size'       => 30,
			'js'         => '',
			'value'      => '',
			'show_error' => 'true',
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	protected function inputPage( $atts ) {
		wp_enqueue_style( 'jquery.ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css', array(), '1.9.2' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		// jsの指定がないときはデフォルトで年付き変更機能追加
		if ( empty( $atts['js'] ) ) {
			$atts['js'] = 'showMonthAfterYear: true, changeYear: true, changeMonth: true';
		}
		// 日本語の場合は日本語表記に変更
		if ( get_locale() == 'ja' ) {
			if ( !empty( $atts['js'] ) )
				$atts['js'] = $atts['js'] . ',';
			$atts['js'] .= '
				yearSuffix: "年",
				dateFormat: "yy-mm-dd",
				dayNames: ["日曜日","月曜日","火曜日","水曜日","木曜日","金曜日","土曜日"],
				dayNamesMin: ["日","月","火","水","木","金","土"],
				dayNamesShort: ["日曜","月曜","火曜","水曜","木曜","金曜","土曜"],
				monthNames: ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],
				monthNamesShort: ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"]
			';
		}
		$_ret  = '';
		$_ret .= $this->Form->datepicker( $atts['name'], array(
			'size'  => $atts['size'],
			'js'    => $atts['js'],
			'value' => $atts['value'],
		) );
		if ( $atts['show_error'] !== 'false' )
			$_ret .= $this->getError( $atts['name'] );
		return $_ret;
	}

	/**
	 * previewPage
	 * 確認ページでのフォーム項目を返す
	 * @param	Array	$atts
	 * @return	String	HTML
	 */
	protected function previewPage( $atts ) {
		$value = $this->Form->getValue( $atts['name'] );
		$_ret  = $value;
		$_ret .= $this->Form->hidden( $atts['name'], $value );
		return $_ret;
	}

	/**
	 * add_qtags
	 * QTags.addButton を出力
	 */
	protected function add_qtags() {
		?>
		'<?php echo $this->short_code_name; ?>',
		'<?php _e( 'Datepicker', MWF_Config::DOMAIN ); ?>',
		'[<?php echo $this->short_code_name; ?> name=""]',
		''
		<?php
	}
}