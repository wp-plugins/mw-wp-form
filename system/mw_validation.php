<?php
/**
 * Name: MW Validation
 * URI: http://2inc.org
 * Description: バリデーションクラス
 * Version: 1.4.1
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: July 20, 2012
 * Modified: August 28, 2013
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
class MW_Validation {

	const DOMAIN = 'mw-wp-form';
	protected $data = array();
	protected $Error;
	public $validate = array();
	private $ENCODE = 'utf-8';

	/**
	 * __construct
	 * @param	Array	リクエストデータ
	 */
	public function __construct( Array $data = array() ) {
		$this->data = $data;
		// エラーオブジェクトを設定
		$this->Error = new MW_Error();
	}

	private function getValue( $key ) {
		$value = null;
		if ( !isset( $this->data[$key] ) ) return $value;
		if ( is_array( $this->data[$key] ) ) {
			if ( array_key_exists( 'data', $this->data[$key] ) ) {
				if ( is_array( $this->data[$key]['data'] ) ) {
					$value = $this->array_clean( $this->data[$key]['data'] );
				} else {
					$value = $this->data[$key]['data'];
				}
			}
		} else {
			$value = $this->data[$key];
		}
		return $value;
	}

	/**
	 * required
	 * 値が存在する
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function required( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( !isset( $value ) ) {
			$defaults = array(
				'message' => __( 'This is required.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * noEmpty
	 * 値が空ではない（0は許可）
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function noEmpty( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && $this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'Please enter.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * noFalse
	 * 値が空ではない（0も不可）
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function noFalse( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'message' => __( 'Please enter.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			if ( empty( $value ) ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * alpha
	 * 値がアルファベット
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function alpha( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !preg_match( '/^[A-Za-z]+$/', $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'Please enter with a half-width alphabetic character.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * numeric
	 * 値が数値
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function numeric( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !preg_match( '/^[0-9]+$/', $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'Please enter with a half-width number.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * alphaNumeric
	 * 値が英数値
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function alphaNumeric( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !preg_match( '/^[0-9A-Za-z]+$/', $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'Please enter with a half-width alphanumeric character.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * zip
	 * 値が郵便番号
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function zip( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'message' => __( 'This is not the format of a zip code.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			if ( !empty( $value ) ) {
				if ( is_array( $value ) ) {
					$value = implode( '-', $value );
				}
				if ( !preg_match( '/^\d{3}-\d{4}$/', $value ) ) {
					$_ret = $options['message'];
				}
			}
		}
		return $_ret;
	}

	/**
	 * tel
	 * 値が電話番号
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function tel( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'message' => __( 'This is not the format of a tel number.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			if ( !empty( $value ) ) {
				if ( is_array( $value ) ) {
					$value = implode( '-', $value );
				}
				if ( ! (
					preg_match( '/^\d{2}-\d{4}-\d{4}$/', $value ) ||
					preg_match( '/^\d{3}-\d{3,4}-\d{4}$/', $value ) ||
					preg_match( '/^\d{4}-\d{2}-\d{4}$/', $value ) ||
					preg_match( '/^\d{5}-\d{1}-\d{4}$/', $value )
				) ) {
					$_ret = $options['message'];
				}
			}
		}
		return $_ret;
	}

	/**
	 * mail
	 * 値がメールアドレス
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function mail( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !preg_match( '/^[^@]+@[^@]+$/', $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'This is not the format of a mail address.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * url
	 * 値がURL
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function url( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !preg_match( '/^https{0,1}:\/\//', $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'message' => __( 'This is not the format of a url.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_ret = $options['message'];
		}
		return $_ret;
	}

	/**
	 * eq
	 * 値が一致している
	 * @param	String	キー
	 *			Array	( 'target' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function eq( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'target' => null,
				'message' => __( 'This is not in agreement.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			if ( !( isset( $this->data[$options['target']] ) && $value == $this->data[$options['target']] ) ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * between
	 * 値の文字数が範囲内
	 * @param	String	キー
	 *			Array	( 'min' =>, 'max' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function between( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'min' => 0,
				// 'max' => 0,
				'message' => __( 'The number of characters is invalid.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$length = mb_strlen( $value, $this->ENCODE );
			if ( MWF_Functions::is_numeric( $options['min'] ) ) {
				if ( MWF_Functions::is_numeric( $options['max'] ) ) {
					if ( !( $options['min'] <= $length && $length <= $options['max'] ) ) {
						$_ret = $options['message'];
					}
				} else {
					if ( $options['min'] > $length ) {
						$_ret = $options['message'];
					}
				}
			} elseif ( MWF_Functions::is_numeric( $options['max'] ) ) {
				if ( $options['max'] < $length ) {
					$_ret = $options['message'];
				}
			}
		}
		return $_ret;
	}

	/**
	 * minLength
	 * 値の文字数が範囲内
	 * @param	String	キー
	 *			Array	( 'min' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function minLength( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) && !$this->isEmpty( $value ) ) {
			$defaults = array(
				'min' => 0,
				'message' => __( 'The number of characters is a few.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$length = mb_strlen( $value, $this->ENCODE );
			if ( MWF_Functions::is_numeric( $options['min'] ) && $options['min'] > $length ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * in
	 * 値が、配列で指定された中に含まれている
	 * @param	String	キー
	 *			Array	( 'options' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function in( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'options' => array(),
				'message' => __( 'This value is invalid.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			if ( !( isset( $options[ 'options' ] ) && is_array( $options[ 'options' ] ) ) ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * date
	 * 日付が正しいかどうか
	 * @param	String	キー
	 *			Array	( 'options' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function date( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( isset( $value ) ) {
			$defaults = array(
				'message' => __( 'This is not the format of a date.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$timestamp = strtotime( $value );
			$year = date( 'Y', $timestamp );
			$month = date( 'm', $timestamp );
			$day = date( 'd', $timestamp );
			$checkdate = checkdate( $month, $day, $year );
			if ( !empty( $value ) && ( !$timestamp || !$checkdate ) ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * fileType
	 * ファイル名が指定した拡張子を含む。types は , 区切り
	 * @param	String	キー
	 *			Array	( 'types' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function fileType( $key, $options = array() ) {
		$_ret = '';
		$value = $this->getValue( $key );
		if ( !empty( $value ) ) {
			$defaults = array(
				'types' => '',
				'message' => __( 'This file is invalid.', MWF_Config::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			$_types = explode( ',', $options['types'] );
			foreach ( $_types as $type ) {
				$types[] = preg_quote( trim( $type ) );
			}
			$types = implode( '|', $this->array_clean( $types ) );
			$pattern = '/\.(' . $types . ')$/';
			if ( !preg_match( $pattern, $value ) ) {
				$_ret = $options['message'];
			}
		}
		return $_ret;
	}

	/**
	 * fileSize
	 * ファイルが指定したサイズより小さい
	 * @param	String	キー
	 *			Array	( 'bytes' =>, 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function fileSize( $key, $options = array() ) {
		$_ret = '';
		if ( isset( $_FILES[$key] ) ) {
			$file = $_FILES[$key];
			if ( !empty( $file['size'] ) ) {
				$defaults = array(
					'bytes' => '0',
					'message' => __( 'This file size is too big.', MWF_Config::DOMAIN )
				);
				$options = array_merge( $defaults, $options );
				if ( !( preg_match( '/^[\d]+$/', $options['bytes'] ) && $options['bytes'] > $file['size'] ) ) {
					$_ret = $options['message'];
				}
			}
		}
		return $_ret;
	}

	/**
	 * akismet_check
	 * Akismetのエラー。常にtrue。
	 * @param	String	キー
	 *			Array	( 'message' => )
	 * @return	String	エラーメッセージ
	 */
	public function akismet_check( $key, $options = array() ) {
		$defaults = array(
			'message' => __( 'The contents which you input were judged with spam.', MWF_Config::DOMAIN )
		);
		$options = array_merge( $defaults, $options );
		return $options['message'];
	}

	/**
	 * Error
	 * エラーオブジェクトを返す
	 * @return	Error	エラーオブジェクト
	 */
	public function Error() {
		return $this->Error;
	}

	/**
	 * isValid
	 * バリデートが通っているかチェック
	 * @return	Boolean
	 */
	protected function isValid() {
		$errors = $this->Error->getErrors();
		if ( empty( $errors ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * setRule
	 * バリデートが通っているかチェック
	 * @param	String	キー
	 * 			String	バリデーションルール名
	 * 			Array	オプション
	 * @return	Boolean
	 */
	public function setRule( $key, $rule, Array $options = array() ) {
		$rules = array(
			'rule' => $rule,
			'options' =>$options
		);
		$this->validate[$key][] = $rules;
		return $this;
	}

	/**
	 * check
	 * validate実行
	 * @return	Boolean
	 */
	public function check() {
		foreach ( $this->validate as $key => $rules ) {
			foreach ( $rules as $ruleSet ) {
				if ( isset( $ruleSet['rule'] ) ) {
					$rule = $ruleSet['rule'];
					$options = array();
					if ( isset( $ruleSet['options'] ) ) {
						$options = $ruleSet['options'];
					}
					if ( method_exists( $this, $rule ) ) {
						$message = $this->$rule( $key, $options );
						if ( !empty( $message ) ) {
							$this->Error->setError( $key, $this->$rule( $key, $options ) );
						}
					}
				}
			}
		}
		return $this->isValid();
	}

	/**
	 * array_clean
	 * 配列内の値の重複を消す
	 * @param	Array
	 * @return	Array
	 */
	protected function array_clean( $array ) {
		return array_merge( array_diff( $array, array( '' ) ) );
	}

	/**
	 * isEmpty
	 * 値が空（0は許可）
	 * @param	Mixed
	 * @return	Boolean
	 */
	protected function isEmpty( $value ) {
		if ( $value == array() || $value === '' || $value === null ) {
			return true;
		} else {
			return false;
		}
	}
}
?>