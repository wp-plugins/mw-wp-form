<?php
/**
 * Name       : MW WP Form Hidden
 * Description: hiddenフィールドを出力
 * Version    : 1.6.1
 * Author     : Takashi Kitajima
 * Author URI : http://2inc.org
 * Created    : December 14, 2012
 * Modified   : March 26, 2015
 * License    : GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_WP_Form_Field_Hidden extends MW_WP_Form_Abstract_Form_Field {

	/**
	 * set_names
	 * shortcode_name、display_nameを定義。各子クラスで上書きする。
	 * @return array shortcode_name, display_name
	 */
	protected function set_names() {
		return array(
			'shortcode_name' => 'mwform_hidden',
			'display_name'   => __( 'Hidden', MWF_Config::DOMAIN ),
		);
	}

	/**
	 * set_defaults
	 * $this->defaultsを設定し返す
	 * @return array defaults
	 */
	protected function set_defaults() {
		return array(
			'name'  => '',
			'value' => '',
			'echo'  => 'false',
		);
	}

	/**
	 * input_page
	 * 入力ページでのフォーム項目を返す
	 * @return string HTML
	 */
	protected function input_page() {
		$value = '';
		if ( $this->atts['echo'] === 'true' ) {
			$value = $this->atts['value'];
		}
		if ( is_null( $value ) ) {
			$value = $this->get_raw( $this->atts['name'] );
		}
		return esc_html( $value ) . $this->Form->hidden( $this->atts['name'], $value );
	}

	/**
	 * confirm_page
	 * 確認ページでのフォーム項目を返す
	 * @return string HTML
	 */
	protected function confirm_page() {
		$value = $this->Data->get_raw( $this->atts['name'] );
		$echo_value = '';
		if ( $this->atts['echo'] === 'true' ) {
			$echo_value = $value;
		}
		return $echo_value . $this->Form->hidden( $this->atts['name'], $value );
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
			<strong><?php esc_html_e( 'Default value', MWF_Config::DOMAIN ); ?></strong>
			<?php $value = $this->get_value_for_generator( 'value', $options ); ?>
			<input type="text" name="value" value="<?php echo esc_attr( $value ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Display', MWF_Config::DOMAIN ); ?></strong>
			<?php $echo = $this->get_value_for_generator( 'echo', $options ); ?>
			<input type="checkbox" name="echo" value="true" <?php checked( 'true', $echo ); ?> /> <?php esc_html_e( 'Display hidden value.', MWF_Config::DOMAIN ); ?>
		</p>
		<?php
	}
}
