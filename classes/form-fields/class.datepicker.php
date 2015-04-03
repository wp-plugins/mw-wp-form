<?php
/**
 * Name       : MW WP Form Field Datepicker
 * Description: datepickerを出力
 * Version    : 1.5.2
 * Author     : Takashi Kitajima
 * Author URI : http://2inc.org
 * Created    : December 14, 2012
 * Modified   : April 1, 2015
 * License    : GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_WP_Form_Field_Datepicker extends MW_WP_Form_Abstract_Form_Field {

	/**
	 * $type
	 * フォームタグの種類 input|select|button|error|other
	 * @var string
	 */
	public $type = 'input';

	/**
	 * set_names
	 * shortcode_name、display_nameを定義。各子クラスで上書きする。
	 * @return array shortcode_name, display_name
	 */
	protected function set_names() {
		return array(
			'shortcode_name' => 'mwform_datepicker',
			'display_name'   => __( 'Datepicker', MWF_Config::DOMAIN ),
		);
	}

	/**
	 * set_defaults
	 * $this->defaultsを設定し返す
	 * @return array defaults
	 */
	protected function set_defaults() {
		return array(
			'name'       => '',
			'id'         => null,
			'size'       => 30,
			'js'         => '',
			'value'      => '',
			'show_error' => 'true',
		);
	}

	/**
	 * input_page
	 * 入力ページでのフォーム項目を返す
	 * @return string HTML
	 */
	protected function input_page() {
		global $wp_scripts;
		$ui = $wp_scripts->query( 'jquery-ui-core' );
		wp_enqueue_style( 'jquery.ui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $ui->ver . '/themes/smoothness/jquery-ui.min.css', array(), $ui->ver );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		// jsの指定がないときはデフォルトで年付き変更機能追加
		if ( empty( $this->atts['js'] ) ) {
			$this->atts['js'] = 'showMonthAfterYear: true, changeYear: true, changeMonth: true';
		}
		// 日本語の場合は日本語表記に変更
		if ( get_locale() == 'ja' ) {
			if ( !empty( $this->atts['js'] ) ) {
				$this->atts['js'] = $this->atts['js'] . ',';
			}
			$this->atts['js'] .= '
				yearSuffix: "年",
				dateFormat: "yy年mm月dd日",
				dayNames: ["日曜日","月曜日","火曜日","水曜日","木曜日","金曜日","土曜日"],
				dayNamesMin: ["日","月","火","水","木","金","土"],
				dayNamesShort: ["日曜","月曜","火曜","水曜","木曜","金曜","土曜"],
				monthNames: ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],
				monthNamesShort: ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"]
			';
		}
		$value = $this->Data->get_raw( $this->atts['name'] );
		if ( is_null( $value ) ) {
			$value = $this->atts['value'];
		}

		$_ret  = '';
		$_ret .= $this->Form->datepicker( $this->atts['name'], array(
			'id'    => $this->atts['id'],
			'size'  => $this->atts['size'],
			'js'    => $this->atts['js'],
			'value' => $value,
		) );
		if ( $this->atts['show_error'] !== 'false' ) {
			$_ret .= $this->get_error( $this->atts['name'] );
		}
		return $_ret;
	}

	/**
	 * confirm_page
	 * 確認ページでのフォーム項目を返す
	 * @return string HTML
	 */
	protected function confirm_page() {
		$value = $this->Data->get_raw( $this->atts['name'] );
		$_ret  = esc_html( $value );
		$_ret .= $this->Form->hidden( $this->atts['name'], $value );
		return $_ret;
	}

	/**
	 * add_mwform_tag_generator
	 * フォームタグジェネレーター
	 */
	public function mwform_tag_generator_dialog( array $options = array() ) {
		?>
		<p>
			<strong>name<span class="mwf_require">*</span></strong>
			<?php $name = $this->get_value_for_generator( 'name', $options ); ?>
			<input type="text" name="name" value="<?php echo esc_attr( $name ); ?>" />
		</p>
		<p>
			<strong>id</strong>
			<?php $id = $this->get_value_for_generator( 'id', $options ); ?>
			<input type="text" name="id" value="<?php echo esc_attr( $id ); ?>" />
		</p>
		<p>
			<strong>size</strong>
			<?php $size = $this->get_value_for_generator( 'size', $options ); ?>
			<input type="text" name="size" value="<?php echo esc_attr( $size ); ?>" />
		</p>
		<p>
			<strong>JavaScript</strong>
			<?php $js = $this->get_value_for_generator( 'js', $options ); ?>
			<input type="text" name="js" value="<?php echo esc_attr( $js ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Default value', MWF_Config::DOMAIN ); ?></strong>
			<?php $value = $this->get_value_for_generator( 'value', $options ); ?>
			<input type="text" name="value" value="<?php echo esc_attr( $value ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Dsiplay error', MWF_Config::DOMAIN ); ?></strong>
			<?php $show_error = $this->get_value_for_generator( 'show_error', $options ); ?>
			<label><input type="checkbox" name="show_error" value="false" <?php checked( 'false', $show_error ); ?> /> <?php esc_html_e( 'Don\'t display error.', MWF_Config::DOMAIN ); ?></label>
		</p>
		<?php
	}
}
