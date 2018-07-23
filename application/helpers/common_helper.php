<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * 公共函数库
 *
 * author:jip 2015.3.26
 */

function get_ip() {
	if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		} else {
			$ip = $_SERVER["REMOTE_ADDR"];
		}
	}

	if ($ip == "") {
		$ip = "127.0.0.1";
	}

	return $ip;
}
/**
 * 获取当前URL
 *
 * @return mixed
 */
function get_url() {
	$url = "http://" . $_SERVER["HTTP_HOST"];
	if (isset ($_SERVER["REQUEST_URI"]))
		$url .= $_SERVER["REQUEST_URI"];
	else {
		$url .= $_SERVER["PHP_SELF"];
		if (!empty ($_SERVER["QUERY_STRING"]))
			$url .= "?" .$_SERVER["QUERY_STRING"];
	}
	return $url;
}
//获取随机字符
function random_str($length, $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	$hash = '';
	$max = strlen($chars) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}

/**
 * 获取二维数组的一列数据
 * @param $array $arr
 * @param string $field
 * @return unknown
 */
function get_array_col($arr, $field) {
	$result = array();
	foreach ($arr as $v) {
		if (isset($v[$field]))
			$result[] = $v[$field];
	}
	return $result;
}

/**
 * 数组分组 用某个元素的值来充当索引，方便定位元素
 *
 * @param array $arr
 * @param string $keystr
 * @param bloor $limit
 * @return array
 */
function array_group($arr, $keystr, $limit = false) {
	if (empty($arr))
		return $arr;

	$tmp = $_result = array();
	foreach ($arr as $key => $item) {
		$sub_keys = array_keys($item);

		if (in_array($keystr, $sub_keys)) {
			$tmp = $item;
			$_result[$item[$keystr]][] = $item;
		} else {
			$_result[count($_result)][] = $item;
		}
	}

	$result = array();
	if ($limit) {
		foreach ($_result as $key => $item) {
			$result[$key] = $item[0];
		}
	} else {
		$result = $_result;
	}
	return $result;
}

//des+base64加密
function des_encrypt($str, $key) {
	$block = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB);
	$pad = $block - (strlen($str) % $block);
	$str .= str_repeat(chr($pad), $pad);
	$passcrypt = mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
	return base64_encode($passcrypt);
}

//base64_des解密
function des_decrypt($str, $key) {
	$str = mcrypt_decrypt(MCRYPT_DES, $key, base64_decode($str), MCRYPT_MODE_ECB);
	$pad = ord($str[strlen($str) - 1]);

	return substr($str, 0, strlen($str) - $pad);
}

/**
 * 创建目录，支持多级目录自动创建
 *
 * @param string $dir
 *        	目录路径
 * @param int $mode
 *        	目录权限
 */
function create_dir($dir, $mode = 0755) {
	$arr = explode('/', $dir);
	$pathname = '';
	foreach ($arr as $val) {
		$pathname .= ($val . '/');
		if (!is_dir($pathname))
			@mkdir($pathname, $mode);
	}
	return $dir;
}

/**
 * 使用POST方法发送HTTP请求
 * $constr 是否将参数转换为字符串
 */

function call_service($url, $send_data = array (), $method = 'post', $isImg = FALSE) {
	
	/**/
	if(empty($isImg)){
		
		$send_data_str = '';
		if(!empty($send_data)){
			foreach ($send_data as $key => $val) {
				$send_data_str .= "&$key=" . $val;
			}
			$send_data = ltrim($send_data_str, '&');
		}
	}
	$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if($method == 'post'){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data);
	}
	if ($ssl) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	}
	$return_data = curl_exec($ch);
	curl_close($ch);
//var_dump($url,$send_data,$return_data);
	//if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
	openlog($url, LOG_PID | LOG_ODELAY, LOG_LOCAL2);
	@syslog(LOG_DEBUG, json_encode($send_data) . "|||||" . json_encode($return_data) . "\r\n");
	closelog();
	//}
	if ($return_data === FALSE) {
		return;
	}
	return $return_data;
}


function return_data($status = 0, $msg = '', $data = array(), $returnData = FALSE) {
	$return = array();
	$return['code'] = $status;		
	$return['msg'] = $msg;
	$return['data'] = $data;	
	if (!$returnData) {
		exit(json_encode($return));
	} else {
		return json_encode($return);
	}
}


//生成密码
function password($pwd, $key) {
	return md5(md5($pwd) . $key);
}

//生成token
function token() {
	//长度为20位的唯 一字符串
	return uniqid() . random_str(7, '0123456789abcdefghijklmnopqrstuvwxyz');
}
/**
 * 构造URL
 * @param string $mod 模块
 * @param string $act 动作 
 * @param string $param 参数
 */
function build_url($mod,$act='',$param=array()){

	$url = config_item('base_url');
	if($mod){
		$url .= $mod.'/';
	}
	if ($act == 'detail') {
		$url .= 'view/'.$param['id'].'.html';
	}elseif($act == 'cate'){
	    $url .= $param['id'];
	}
	return $url;
}
/**
 * 邮件发送
 * $mailtype 邮件类型 :  TXT , HTML
 */
function send_mail($to,$title,$content,$mailtype='TXT',$attachment = ''){
	$CI = &get_instance();
	$smtpconf = config_item('smtpserver');
	
	
	require_once(FCPATH.APPPATH.'libraries/PHPMailer/PHPMailerAutoload.php');
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Debugoutput = 'html';
	$mail->Host = $smtpconf['server'];
	$mail->Port = $smtpconf['port'];
	$mail->SMTPAuth = true;
	$mail->Username = $smtpconf['user'];
	$mail->Password = $smtpconf['password'];
	$mail->setFrom($smtpconf['sender'], 'WebMaster');
	$mail->addReplyTo($smtpconf['sender'], 'WebMaster');
	$mail->addAddress($to, current(explode('@',$to)));
	$mail->Subject = $title;
	$mail->msgHTML($content);
	//$mail->AltBody = $content;
	if($attachment)
		$mail->addAttachment($attachment);	
	
	if (!$mail->send()) {
		$return = array('code'=>1,'error'=>$mail->ErrorInfo);	   
	} else {
		$return = array('code'=>0);	    
	}
	/**/
	return $return;
}

//调试输出
function vdump() {
	$args = func_get_args();
	echo '<pre style="background: #e5e5e5;padding:10px;">';
	foreach ($args as $value) {
		var_dump($value);
	}
	echo '</pre>';

	echo "<pre>";
	//debug_zval_dump($args);
	debug_print_backtrace();
	echo '</pre>';
	die();
}




