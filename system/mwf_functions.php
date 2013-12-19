<?php
/**
 * Name: MWF Functions
 * URI: http://2inc.org
 * Description: 関数
 * Version: 1.1.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created: May 29, 2013
 * Modified:December 19, 2013
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
class MWF_Functions {

	/**
	 * is_numeric
	 * 引数で渡された変数が存在し、かつ数値であるなら true
	 * @param string $value 参照渡し
	 * @return bool
	 */
	public static function is_numeric( &$value ) {
		if ( isset( $value ) && preg_match( '/^\d+$/', $value ) ) {
			return true;
		}
		return false;
	}

	/**
	 * fileurl_to_path
	 * ファイルURLをファイルパスに変換
	 * @param string $fileurl
	 * @return string
	 */
	public static function fileurl_to_path( $fileurl ) {
		$wp_upload_dir = wp_upload_dir();
		$filepath = str_replace(
			$wp_upload_dir['baseurl'],
			realpath( $wp_upload_dir['basedir'] ),
			$fileurl
		);
		return $filepath;
	}

	/**
	 * filepath_to_url
	 * ファイルパスをURLに変換
	 * @param string $filepath
	 * @return string
	 */
	public static function filepath_to_url( $filepath ) {
		$wp_upload_dir = wp_upload_dir();
		$fileurl = str_replace(
			realpath( $wp_upload_dir['basedir'] ),
			$wp_upload_dir['baseurl'],
			$filepath
		);
		return $fileurl;
	}
}