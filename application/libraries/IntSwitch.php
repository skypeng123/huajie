<?php
/*
 * ===========================================
 * 进制转换
 * 功能: 10进制转换n进制
 n进制转换10进制
 * ===========================================
 */
class IntSwitch {

	public function __construct() {
		$this -> key = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		//默认密钥
		$this -> carry = 1;
		//默认进位
	}

	//普通整数N进制转换，将$raw转换为$ary，文本长度为$len
	public function changeInt($raw, $ary, $len) {
		//变量初始化
		$result = "";
		//结果
		$variable = 1;
		//临时变量
		$residue = 1;
		//余数
		$median = 1;
		//文本长度
		$verify = $raw;
		//原数值
		if ($raw == 0)
			$result = substr($this -> key, 0, $this -> carry);
		while ($raw != 0) {
			$variable = intval($raw / $ary);
			$residue = $raw % $ary;
			$result = substr($this -> key, $residue * $this -> carry, $this -> carry) . $result;
			$raw = $variable;
		}
		$median = strlen($result);
		//取结果文本长度
		if ($median < $len)//如果不够位数则补短
			$result = $this -> fillPlace($len - $median) . $result;
		if ($this -> revertInt($ary, $result) != $verify)
			return -1;
		return $result;

	}

	//普通整数N进制反转换
	public function revertInt($ary, $value) {
		//变量初始化
		$result = "";
		$median = intval(strlen($value) / $this -> carry);
		$character = "";
		for ($i = 1; $i <= $median; $i++) {
			if ($this -> carry > 1) {//多进位进制转换
				$character = substr($value, $i * $this -> carry - ($this -> carry), $this -> carry);
				$result += (intval(strpos($this -> key, $character) / $this -> carry)) * pow($ary, $median - $i);
			} else {//单进位进制转换
				$character = substr($value, $i * $this -> carry - 1, $this -> carry);
				$result += intval(strpos($this -> key, $character)) * pow($ary, $median - $i);
			}
		}
		return $result;
	}

	//大整数N进制转换，将$raw转换为$ary，文本长度为$len
	public function changeBigInt($raw, $ary, $len) {
		//变量初始化
		bcscale(0);
		//设置没有小数位。
		$result = "";
		//结果
		$variable = 1;
		//临时变量
		$residue = 1;
		//余数
		$median = 1;
		//文本长度
		$verify = $raw;
		//原数值
		if ($raw == "0")
			$result = substr($this -> key, 0, $this -> carry);
		while ($raw != "0") {
			$variable = bcdiv($raw, $ary);
			$residue = bcmod($raw, $ary);
			$result = substr($this -> key, $residue * $this -> carry, $this -> carry) . $result;
			$raw = $variable;
		}
		$median = strlen($result);
		//取结果文本长度
		if ($median < $len)//如果不够位数则补短
			$result = $this -> fillPlace($len - $median) . $result;
		if ($this -> revertBigInt($ary, $result) != $verify)
			return -1;
		return $result;

	}

	//大整数N进制反转换
	public function revertBigInt($ary, $value) {
		//变量初始化
		bcscale(0);
		//设置没有小数位。
		$result = "";
		$median = bcdiv(strlen($value), $this -> carry);
		$character = "";
		for ($i = 1; $i <= $median; $i++) {
			if ($this -> carry > 1) {//多进位进制转换
				$character = substr($value, $i * $this -> carry - ($this -> carry), $this -> carry);
				$result = bcadd(bcmul(bcdiv(strpos($this -> key, $character), $this -> carry), bcpow($ary, $median - $i)), $result);
			} else {//单进位进制转换
				$character = substr($value, $i * $this -> carry - 1, $this -> carry);
				$result = bcadd(bcmul(strpos($this -> key, $character), bcpow($ary, $median - $i)), $result);
			}
		}
		return $result;
	}

	//补位函数
	public function fillPlace($number) {
		$character = substr($this -> key, 0, $this -> carry);
		//取默认为0的字符。
		$result = $character;
		for ($i = 1; $i <= $number - 1; $i++)
			$result .= $character;
		return $result;
	}

}

/*
 $raw = "19999999999";//数据
 $ary = 62;//进制
 //$len = 6;//位数，0为自动
 $len=0;
 $is = new IntSwitch;
 $result = $is->changeBigInt($raw,$ary,$len);
 $revert=$is->revertBigInt($ary,$result);
 echo "$raw 转换为 $ary 进制 = $result\n";
 echo "实际长度：".strlen($result)."\n";
 echo "还原数据：".$revert."\n";
 */
