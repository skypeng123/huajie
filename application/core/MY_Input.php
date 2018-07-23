<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * 扩展 INPUT类
 * @author jip
 *
 */
class MY_Input extends CI_Input {

	function __construct() {
		parent::__construct();
	}

	/**
	 * 获取字符串，去除空格
	 */
	function trim_get($index = NULL, $xss_clean = FALSE) {
		$get = parent::get($index, $xss_clean);
		if ($get && is_array($get)) {
			foreach ($get as $key => $val) {
				$get[$key] = self::trim($val);;
			}
			return $get;
		} else {
			return self::trim($get);
		}
	}

	function trim_post($index = NULL, $xss_clean = FALSE) {
		$post = parent::post($index, $xss_clean);
		if ($post && is_array($post)) {
			foreach ($post as $key => $val) {
				$post[$key] = self::trim($val);;
			}
			return $post;
		} else {
			return self::trim($post);
		}
	}

	function trim_get_post($index = NULL, $xss_clean = FALSE) {
		$get_post = parent::get_post($index, $xss_clean);
		if ($get_post && is_array($get_post)) {
			foreach ($get_post as $key => $val) {
				$get_post[$key] = self::trim($val);;
			}
			return $get_post;
		} else {
			return self::trim($get_post);
		}
	}

	/**
	 * 获取数值型输入值
	 *
	 * @param string $index
	 * @param bloor $xss_clean
	 */
	function get_int($index, $xss_clean = FALSE) {
		return intval(self::get($index, $xss_clean));
	}

	function post_int($index, $xss_clean = FALSE) {
		return intval(self::post($index, $xss_clean));
	}

	function get_post_int($index, $xss_clean = FALSE) {
		return intval(self::get_post($index, $xss_clean));
	}
	
	
	function trim($val){
		if ($val && is_array($val)){
			$val = array_map('trim', $val);
		}else{
			$val = trim($val);
		}
		return $val;
	}

}
