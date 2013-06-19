<?php
/**
 * Name: MW Session
 * URI: http://2inc.org
 * Description: セッションクラス
 * Version: 1.2
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: July 17, 2012
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
class MW_Session {

	private static $session;	// セッションフラグ
	private static $sessionName = 'nw_session';	// セッション名
	private $name;				// 擬似セッション名

	private function __construct( $name ) {
		$this->setSessionName( $name );
	}

	/**
	 * start
	 * インスタンス化
	 * @param	String	擬似セッション名
	 * @return	Session	Sessionオブジェクト
	 */
	public static function start( $name ) {
		if ( self::$session == null ){
			session_name( self::$sessionName );
			session_set_cookie_params( 0, '/' );
			session_start();
			self::$session = 1;
		}
		$Session = new MW_Session( $name );
		return $Session;
	}

	/**
	 * setSessionName
	 * 疑似セッション名を設定
	 * @param	String	擬似セッション名
	 */
	private function setSessionName( $name ) {
		$this->name = $name;
	}

	/**
	 * save
	 * セッション変数にセット
	 * @param	Array	( キー => 値, … )
	 */
	public function save( Array $data ) {
		foreach ( $data as $key => $value ) {
			$_SESSION[$this->name][$key] = $value;
		}
	}

	/**
	 * setValue
	 * セッション変数にセット
	 * @param	String	キー
	 * 			Mixed	値
	 */
	public function setValue( $key, $value ) {
		$_SESSION[$this->name][$key] = $value;
	}

	/**
	 * pushValue
	 * セッション変数にセット
	 * @param	String	キー
	 * 			Mixed	値
	 */
	public function pushValue( $key, $value ) {
		$_SESSION[$this->name][$key][] = $value;
	}

	/**
	 * getValue
	 * セッション変数から取得
	 * @param	String	キー
	 * @return	Mixed	セッション値
	 */
	public function getValue( $key ) {
		$_ret = null;
		if ( isset( $_SESSION[$this->name][$key] ) ) {
			$_ret = $_SESSION[$this->name][$key];
		}
		return $_ret;
	}

	/**
	 * getValues
	 * セッション変数から取得
	 * @param	Array	( キー => 値, … )
	 */
	public function getValues() {
		$_ret = array();
		if ( isset( $_SESSION[$this->name] ) ) {
			$_ret = $_SESSION[$this->name];
		}
		return $_ret;
	}

	/**
	 * clearValue
	 * セッション変数を空に
	 * @param	String	キー
	 */
	public function clearValue( $key ) {
		if ( isset( $_SESSION[$this->name][$key] ) ) {
			unset( $_SESSION[$this->name][$key] );
		}
	}

	/**
	 * clearValues
	 * セッション変数を空に
	 */
	public function clearValues() {
		if ( isset( $_SESSION[$this->name] ) ) {
			unset( $_SESSION[$this->name] );
		}
	}

	/**
	 * destroy
	 * $_SESSIONを破壊
	 */
	public function destroy(){
		$_SESSION = array();
		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', time()-42000, '/' );
		}
		session_destroy();
	}

	/**
	 * session_regenerate_id
	 * 現在のセッションIDを新しく生成したものと置き換える
	 */
	public function session_regenerate_id(){
		session_regenerate_id( TRUE );
	}
}