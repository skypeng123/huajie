<?php
/**
 * 模版类，基于ecshop修改精简，去除很多ecshop特定的代码
 * 为支持集群部署，去除单机文件缓存
 * 简化流程，去掉动态模版
 */
class Template {
	private $template_dir = '';
	private $compile_dir = '';
	private $direct_output = false;
	private $template = array();
	private $force_compile = false;
	private $_var = array();
	private $_echash = '554fcae493e564ee0dc75bdf2ebf94ca';
	private $_foreach = array();
	private $_current_file = '';
	private $_nowtime = null;
	private $_foreachmark = '';

	private $_seterror = 0;
	private $_errorlevel = 0;

	// 临时存放 foreach 里 key 的数组
	private $_temp_key = array();
	// 临时存放 foreach 里 item 的数组
	private $_temp_val = array();

	public function __construct($params = array()) {
		$this -> Template($params);
	}

	public function Template($params = array()) {
		$this -> _errorlevel = error_reporting();
		$this -> _nowtime = time();		
		if(!empty($params)){
			$config = $params;
		}else{
			$CI = &get_instance();
			$config = $CI->config->item("template");
		}
		
		if(isset($config['template_dir'])){
			$this->template_dir=$config['template_dir'];
		}
		if(isset($config['compile_dir'])){
			$this->compile_dir=$config['compile_dir'];
		}
		if(isset($config['direct_output'])){
			$this->direct_output=$config['direct_output'];
		}
	}

	/**
	 * 注册变量
	 * @access  public
	 * @param   mix      $tpl_var
	 * @param   mix      $value
	 * @return  void
	 */
	public function assign($tpl_var, $value = '') {
		if (is_array($tpl_var)) {
			//数组key递归处理
			foreach ($tpl_var AS $key => $val) {
				$this -> assign($key, $val);
			}
		} else {
			if ($tpl_var != '') {
				$this -> _var[$tpl_var] = $value;
			}
		}
	}

	/**
	 * 判断变量是否被注册并返回值
	 *
	 * @access  public
	 * @param   string     $name
	 *
	 * @return  mix
	 */
	public function & get_template_vars($name = null) {
		if (empty($name)) {
			return $this -> _var;
		} elseif (!empty($this -> _var[$name])) {
			return $this -> _var[$name];
		} else {
			$_tmp = null;

			return $_tmp;
		}
	}

	/**
	 * 显示页面函数
	 *
	 * @access  public
	 * @param   string      $filename
	 *
	 * @return  void
	 */
	public function display($filename,$charset='utf-8') {
		header('Content-type: text/html; charset='.$charset);
		$this -> _seterror++;
		//error_reporting(E_ALL ^ E_NOTICE);
		//读取模版
		$out = $this -> fetch($filename);
		//遍历模版替换项，替换动态输出值
		if (strpos($out, $this -> _echash) !== false) {
			$k = explode($this -> _echash, $out);
			foreach ($k AS $key => $val) {
				if (($key % 2) == 1) {
					$k[$key] = $this -> insert_mod($val);
				}
			}
			$out = implode('', $k);
		}		
		//error_reporting($this -> _errorlevel);
		$this -> _seterror--;
		//输出内容
		echo $out;
	}

	/**
	 * 处理模板文件
	 * @access  public
	 * @param   string      $filename
	 * @return  sring
	 */
	private function fetch($filename) {
		if (!$this -> _seterror) {
			//error_reporting(E_ALL ^ E_NOTICE);
		}
		$this -> _seterror++;
		if (strncmp($filename, 'str:', 4) == 0) {
			//字符串模版
			$out = $this -> _eval($this -> fetch_str(substr($filename, 4)));
		} else {
			//文件模版
			$path = $this -> template_dir;
			//拼装模版路径
			$filename = $path . '/' . $filename;
			if ($this -> direct_output) {
				//直接输出
				$this -> _current_file = $filename;
				//解析读取文件内容
				$out = $this -> _eval($this -> fetch_str(file_get_contents($filename)));
			} else {
				//编译输出
				if (!in_array($filename, $this -> template)) {
					$this -> template[] = $filename;
				}
				$out = $this -> make_compiled($filename);
			}
		}
		$this -> _seterror--;
		if (!$this -> _seterror) {
			//error_reporting($this -> _errorlevel);
		}
		//返回内容
		return $out;
	}

	/**
	 * 编译模板函数
	 * @access  public
	 * @param   string      $filename
	 * @return  sring        编译后内容
	 */
	private function make_compiled($filename) {
		//编译模版路径
		$name = $this -> compile_dir . '/' . md5($filename) . '.php';
		//编译文件状态
		$filestat = @stat($name);
		$expires = $filestat['mtime'];
		//源文件状态
		$filestat = @stat($filename);
		$fileTime=substr('000000000000'.$filestat['mtime'], -10);
		//未过期并且非强制编译
		if ($filestat['mtime'] <= $expires && !$this -> force_compile) {
			//编译文件存在，读取文件
			if (file_exists($name)) {
				$source = $this -> _require($name);
				if ($source == '') {
					$expires = 0;
				}else{
					//时间戳比较
					$tm=substr($source,0,17);
					if(preg_match('/^<!--(\d{10})-->$/', $tm,$match)){
						$source=substr($source, 17);
						if($match[1]!=$fileTime){
							//比较失败,重新编译
							$expires = 0;
						}
					}
				}
			} else {
				//异常流，错误，设置过期时间为0，表示已过期
				$source = '';
				$expires = 0;
			}
		}
		//强制编译或者已过期
		if ($this -> force_compile || $filestat['mtime'] > $expires) {
			//设置文件
			$this -> _current_file = $filename;
			//读取文件
			$source = $this -> fetch_str(file_get_contents($filename));
			//写入文件
			$fileSrouce="<!--{$fileTime}-->".$source;
			if (file_put_contents($name, $fileSrouce, LOCK_EX) === false) {
				//创建目录并重试
				if(@mkdir(dirname($name),0777,true)){
					if (file_put_contents($name, $fileSrouce, LOCK_EX) === false) {
						trigger_error('can\'t write:' . $name);
					}
				}else{
					trigger_error('can\'t write:' . $name);
				}
			}
			//解析
			$source = $this -> _eval($source);
		}
		//返回内容
		return $source;
	}

	/**
	 * 处理字符串函数
	 * @access  public
	 * @param   string     $source
	 * @return  sring
	 */
	private function fetch_str($source) {
		//预处理模版
		$source = $this -> smarty_prefilter_preCompile($source);
		//屏蔽php代码
		if (preg_match_all('~(<\?(?:\w+|=)?|\?>|language\s*=\s*[\"\']?php[\"\']?)~is', $source, $sp_match)) {
			$sp_match[1] = array_unique($sp_match[1]);
			for ($curr_sp = 0, $for_max2 = count($sp_match[1]); $curr_sp < $for_max2; $curr_sp++) {
				$source = str_replace($sp_match[1][$curr_sp], '%%%SMARTYSP' . $curr_sp . '%%%', $source);
			}
			for ($curr_sp = 0, $for_max2 = count($sp_match[1]); $curr_sp < $for_max2; $curr_sp++) {
				$source = str_replace('%%%SMARTYSP' . $curr_sp . '%%%', '<?php echo \'' . str_replace("'", "\'", $sp_match[1][$curr_sp]) . '\'; ?>' . "\n", $source);
			}
		}
		//返回标签替换
		$source= preg_replace_callback("/{([^\}\{\n]*)}/", array( &$this, 'select1'), $source);
		$source= preg_replace_callback("/<!--{([^\}\{\n]*)}-->/", array( &$this, 'select2'), $source);
		return $source;
	}

	/**
	 * 预过滤处理编译
	 */
	private function smarty_prefilter_preCompile($source) {
		//文件类型,后缀名
		$file_type = strtolower(strrchr($this -> _current_file, '.'));
		/* 替换文件编码头部 */
		if (strpos($source, "\xEF\xBB\xBF") !== FALSE) {
			$source = str_replace("\xEF\xBB\xBF", '', $source);
		}
		$pattern = array('/<!--[^>|\n]*?({.+?})[^<|{|\n]*?-->/', // 替换smarty注释
		'/(href=["|\'])\.\.\/(.*?)(["|\'])/i', // 替换相对链接
		'/([\'|"])\.\.\//is', // 以../开头的路径全部修正为空
		);
		$replace = array('\1', '\1\2\3', '\1');
		//返回修正的模版
		return preg_replace($pattern, $replace, $source);
	}
	
	private function select1($matchs){
		return $this->select($matchs[1], '{', '}');
	}
	
	private function select2($matchs){
		return $this->select($matchs[1], '<!--{', '}-->');
	}

	/**
	 * 处理{}标签
	 *
	 * @access  public
	 * @param   string      $tag
	 *
	 * @return  sring
	 */
	private function select($tag,$preTag,$postTag) {
		$tag = stripslashes(trim($tag));
		if (empty($tag)) {//空标签
			return $preTag.$postTag;
		} elseif ($tag{0} == '*' && substr($tag, -1) == '*'){// 注释部分
			return '';
		} elseif ($tag{0} == '$'){// 变量，替换为php输出变量的代码
			return '<?php echo ' . $this -> get_val(substr($tag, 1)) . '; ?>';
		} elseif ($tag{0} == '/'){// 结束 tag
			switch (substr($tag, 1)) {
				case 'if' :
					return '<?php endif; ?>';
					break;
				case 'foreach' :
					if ($this -> _foreachmark == 'foreachelse') {
						$output = '<?php endif; unset($_from); ?>';
					} else {
						array_pop($this -> _patchstack);
						$output = '<?php endforeach; endif; unset($_from); ?>';
					}
					$output .= "<?php \$this->pop_vars(); ?>";
					return $output;
					break;
				case 'literal' :
					return '';
					break;
				default :
					return $preTag . $tag . $postTag;
					break;
			}
		} else {
			//获取命令，空格隔开，如if xxx
			$tag_sel = array_shift(explode(' ', $tag));
			switch ($tag_sel) {
				case 'if' ://if \$abc=='dds'&&\$es<3 转为 if($this->_var['abc']=='dds'&&$this->_var['es']<3):
					return $this -> _compile_if_tag(substr($tag, 3));
					break;
				case 'else' :
					return '<?php else: ?>';
					break;
				case 'elseif' ://elseif \$abc=='dds'&&\$es<3 转为 elseif($this->_var['abc']=='dds'&&$this->_var['es']<3):
					return $this -> _compile_if_tag(substr($tag, 7), true);
					break;
				case 'foreachelse' :
					$this -> _foreachmark = 'foreachelse';
					return '<?php endforeach; else: ?>';
					break;
				case 'foreach' ://foreach from=$a key=k item=n name=dd
					$this -> _foreachmark = 'foreach';
					if (!isset($this -> _patchstack)) {
						$this -> _patchstack = array();
					}
					return $this -> _compile_foreach_start(substr($tag, 8));
					break;
				case 'assign' ://asign var=a value=$b
					$t = $this -> get_para(substr($tag, 7), 0);
					if ($t['value']{0} == '$') {
						/* 如果传进来的值是变量，就不用用引号 */
						$tmp = '$this->assign(\'' . $t['var'] . '\',' . $t['value'] . ');';
					} else {
						$tmp = '$this->assign(\'' . $t['var'] . '\',\'' . addcslashes($t['value'], "'") . '\');';
					}
					return '<?php ' . $tmp . ' ?>';
					break;
				case 'include' ://include file='fdafd.fff'，处理包含文件
					$t = $this -> get_para(substr($tag, 8), 0);
					return '<?php echo $this->fetch(' . "'$t[file]'" . '); ?>';
					break;
				case 'create_pages' :
					$t = $this -> get_para(substr($tag, 13), 0);
					return '<?php echo $this->smarty_create_pages(' . $this -> make_array($t) . '); ?>';
					break;
				case 'insert' ://动态插入,用于静态缓存
					$t = $this -> get_para(substr($tag, 7), false);
					$out = "<?php \n" . '$k = ' . preg_replace_callback("/(\'\\$[^,]+)/", function($matchs){return stripslashes(trim($matchs[1],'\''));}, var_export($t, true)) . ";\n";
					$out .= 'echo $this->_echash . $k[\'name\'] . \'|\' . serialize($k) . $this->_echash;' . "\n?>";
					return $out;
					break;
				case 'literal' :
					return '';
					break;
				case 'cycle' :
					$t = $this -> get_para(substr($tag, 6), 0);
					return '<?php echo $this->cycle(' . $this -> make_array($t) . '); ?>';
					break;
				case 'html_options' :
					$t = $this -> get_para(substr($tag, 13), 0);
					return '<?php echo $this->html_options(' . $this -> make_array($t) . '); ?>';
					break;
				case 'html_select_date' :
					$t = $this -> get_para(substr($tag, 17), 0);
					return '<?php echo $this->html_select_date(' . $this -> make_array($t) . '); ?>';
					break;
				case 'html_radios' :
					$t = $this -> get_para(substr($tag, 12), 0);
					return '<?php echo $this->html_radios(' . $this -> make_array($t) . '); ?>';
					break;
				case 'html_select_time' :
					$t = $this -> get_para(substr($tag, 12), 0);
					return '<?php echo $this->html_select_time(' . $this -> make_array($t) . '); ?>';
					break;
				default :
					return $preTag . $tag . $postTag;
					break;
			}
		}
	}

	/**
	 * 处理smarty标签中的变量标签
	 * @access  public
	 * @param   string     $val
	 * @return  bool
	 */
	private function get_val($val) {
		//数组
		if (strrpos($val, '[') !== false) {
			//数组转化为属性获取，$符变\$避免转义替换,如a[b][$c]转为a.b.\$c
			$val = preg_replace_callback("/\[([^\[\]]*)\]/is", function($matchs){return '.'.str_replace('$','\$',$matchs[1]);}, $val);
		}
		//串列处理
		if (strrpos($val, '|') !== false) {
			$moddb = explode('|', $val);
			$val = array_shift($moddb);
		}
		//空
		if (empty($val)) {
			return '';
		}
		//带.$符号转换，如：a.$b.$c转换为make_var(a)[make_var(b)][make_var(c)]
		if (strpos($val, '.$') !== false) {
			$all = explode('.$', $val);
			foreach ($all AS $key => $val) {
				$all[$key] = $key == 0 ? $this -> make_var($val) : '[' . $this -> make_var($val) . ']';
			}
			$p = implode('', $all);
		} else {
			$p = $this -> make_var($val);
		}
		//有串列处理
		if (!empty($moddb)) {
			foreach ($moddb AS $key => $mod) {
				//分解为[命令，参数]
				$s = explode(':', $mod);
				switch ($s[0]) {
					case 'escape' ://escape处理
						//删除参数1两端的引号，根据参数做escape处理
						$s[1] = trim($s[1], '"');
						if ($s[1] == 'html') {
							$p = 'htmlspecialchars(' . $p . ')';
						} elseif ($s[1] == 'url') {
							$p = 'urlencode(' . $p . ')';
						} elseif ($s[1] == 'decode_url') {
							$p = 'urldecode(' . $p . ')';
						} elseif ($s[1] == 'quotes') {
							$p = 'addslashes(' . $p . ')';
						} elseif ($s[1] == 'u8_url') {
							$p = 'urlencode(' . $p . ')';
						} else {
							$p = 'htmlspecialchars(' . $p . ')';
						}
						break;
					case 'nl2br' ://插入换行符
						$p = 'nl2br(' . $p . ')';
						break;
					case 'default' ://为空时默认值处理
						$s[1] = $s[1]{0} == '$' ? $this -> get_val(substr($s[1], 1)) : "'$s[1]'";
						$p = 'empty(' . $p . ') ? ' . $s[1] . ' : ' . $p;
						break;
					case 'truncate' ://截取utf编码下字符串长度，为ecshop特定函数
						$p = 'sub_str(' . $p . ",$s[1])";
						break;
					case 'strip_tags' ://去除html标签
						$p = 'strip_tags(' . $p . ')';
						break;
					case 'json_encode' ://
						$p = 'json_encode('.$p.')';
					default ://不处理，省略掉了smarty的大多数函数
						# code...
						break;
				}
			}
		}
		//返回代码
		return $p;
	}

	/**
	 * 处理去掉$的字符串
	 *
	 * @access  public
	 * @param   string     $val
	 *
	 * @return  bool
	 */
	private function make_var($val) {
		if (strrpos($val, '.') === false) {//单属性 如a,转为$this->_var['a']
			//堆栈里设置有$val，使用堆栈的$val的值，否则直接使用$val值;
			if (isset($this -> _var[$val]) && isset($this -> _patchstack[$val])) {
				$val = $this -> _patchstack[$val];
			}
			$p = '$this->_var[\'' . $val . '\']';			
		} else {//复属性 如a.b.c,转为$this->_var['a']['b']['c']
			$t = explode('.', $val);
			$_var_name = array_shift($t);
			//堆栈里设置有$_var_name，使用堆栈的$_var_name的值，否则直接使用$_var_name值;
			if (isset($this -> _var[$_var_name]) && isset($this -> _patchstack[$_var_name])) {
				$_var_name = $this -> _patchstack[$_var_name];
			}
			if ($_var_name == 'smarty') {
				//smarty对象
				$p = $this -> _compile_smarty_ref($t);
			} else {
				$p = '$this->_var[\'' . $_var_name . '\']';
			}
			foreach ($t AS $val) {
				//依次读取属性
				$p .= '[\'' . $val . '\']';
			}
		}
		return $p;
	}

	/**
	 * 处理if标签
	 *
	 * @access  public
	 * @param   string     $tag_args
	 * @param   bool       $elseif
	 *
	 * @return  string
	 */
	private function _compile_if_tag($tag_args, $elseif = false) {
		//拆分出符号和字符串
		preg_match_all('/\-?\d+[\.\d]+|\'[^\'|\s]*\'|"[^"|\s]*"|[\$\w\.]+|!==|===|==|!=|<>|<<|>>|<=|>=|&&|\|\||\(|\)|,|\!|\^|=|&|<|>|~|\||\%|\+|\-|\/|\*|\@|\S/', $tag_args, $match);
		$tokens = $match[0];
		// make sure we have balanced parenthesis
		//判断括号，要求双边匹配
		$token_count = array_count_values($tokens);
		if (!empty($token_count['(']) && $token_count['('] != $token_count[')']) {
			//错误，此处未输出，有可能带来隐患
			// $this->_syntax_error('unbalanced parenthesis in if statement', E_USER_ERROR, __FILE__, __LINE__);
		}
		//支持eq，ne等字符形式描述的运算符
		for ($i = 0, $count = count($tokens); $i < $count; $i++) {
			$token = &$tokens[$i];
			switch (strtolower($token)) {
				case 'eq' :
					$token = '==';
					break;
				case 'ne' :
				case 'neq' :
					$token = '!=';
					break;
				case 'lt' :
					$token = '<';
					break;
				case 'le' :
				case 'lte' :
					$token = '<=';
					break;
				case 'gt' :
					$token = '>';
					break;
				case 'ge' :
				case 'gte' :
					$token = '>=';
					break;
				case 'and' :
					$token = '&&';
					break;
				case 'or' :
					$token = '||';
					break;
				case 'not' :
					$token = '!';
					break;
				case 'mod' :
					$token = '%';
					break;
				default :
					if ($token[0] == '$') {
						$token = $this -> get_val(substr($token, 1));
					}
					break;
			}
		}
		//输出整理后的表达式
		if ($elseif) {
			return '<?php elseif (' . implode(' ', $tokens) . '): ?>';
		} else {
			return '<?php if (' . implode(' ', $tokens) . '): ?>';
		}
	}

	/**
	 * 处理foreach标签
	 *
	 * @access  public
	 * @param   string     $tag_args
	 *
	 * @return  string
	 */
	private function _compile_foreach_start($tag_args) {
		$attrs = $this -> get_para($tag_args, 0);
		$arg_list = array();
		$from = $attrs['from'];
		//如果全局存在局部变量名，修改局部变量名
		if (isset($this -> _var[$attrs['item']]) && !isset($this -> _patchstack[$attrs['item']])) {
			//生成临时变量
			$this -> _patchstack[$attrs['item']] = $attrs['item'] . '_' . str_replace(array(' ', '.'), '_', microtime());
			$attrs['item'] = $this -> _patchstack[$attrs['item']];
		} else {
			//直接设置局部变量名
			$this -> _patchstack[$attrs['item']] = $attrs['item'];
		}
		//获取值代码
		$item = $this -> get_val($attrs['item']);
		//key代码处理
		if (!empty($attrs['key'])) {
			$key = $attrs['key'];
			$key_part = $this -> get_val($key) . ' => ';
		} else {
			$key = null;
			$key_part = '';
		}
		//name代码处理
		if (!empty($attrs['name'])) {
			$name = $attrs['name'];
		} else {
			$name = null;
		}
		//php代码 $_from=来源;if(!is_array($_from)&&!is_object($_from)){settype($_from,'array);};$this->push_vars('键','键名');
		$output = '<?php ';
		@$output .= "\$_from = $from; if (!is_array(\$_from) && !is_object(\$_from)) { settype(\$_from, 'array'); }; \$this->push_vars('$attrs[key]', '$attrs[item]');";
		//$name非空
		if (!empty($name)) {//$this->_foreach['名称']=array('total'=>count(来源),'iteration'=>0);if($this->_foreach['名称']['total']>0): foreach($_from as xx=>xx): $this->_foreach['名称']['iteration']++;
			$foreach_props = "\$this->_foreach['$name']";
			$output .= "{$foreach_props} = array('total' => count(\$_from), 'iteration' => 0);\n";
			$output .= "if ({$foreach_props}['total'] > 0):\n";
			$output .= "    foreach (\$_from AS $key_part$item):\n";
			$output .= "        {$foreach_props}['iteration']++;\n";
		} else {//if(count($_from)): foreach($_from as xxx=>xxx):
			$output .= "if (count(\$_from)):\n";
			$output .= "    foreach (\$_from AS $key_part$item):\n";
		}
		return $output . '?>';
	}


	/**
	 * 处理insert外部函数/需要include运行的函数的调用数据
	 * @access  public
	 * @param   string     $val
	 * @param   int         $type
	 * @return  array
	 */
	private function get_para($val, $type = 1){// 处理insert外部函数/需要include运行的函数的调用数据
		$pa = $this -> str_trim($val);
		foreach ($pa AS $value) {
			if (strrpos($value, '=')) {
				list($a, $b) = explode('=', str_replace(array(' ', '"', "'", '&quot;'), '', $value));
				if ($b{0} == '$') {
					if ($type) {
						eval('$para[\'' . $a . '\']=' . $this -> get_val(substr($b, 1)) . ';');
					} else {
						$para[$a] = $this -> get_val(substr($b, 1));
					}
				} else {
					$para[$a] = $b;
				}
			}
		}
		return $para;
	}

	/**
	 * 将 foreach 的 key, item 放入临时数组
	 *
	 * @param  mixed    $key
	 * @param  mixed    $val
	 *
	 * @return  void
	 */
	private function push_vars($key, $val) {
		if (!empty($key) && isset($this -> _vars[$key])) {
			array_push($this -> _temp_key, "\$this->_vars['$key']='" . $this -> _vars[$key] . "';");
		}
		if (!empty($val) && isset($this -> _vars[$val])) {
			array_push($this -> _temp_val, "\$this->_vars['$val']='" . $this -> _vars[$val] . "';");
		}
	}

	/**
	 * 弹出临时数组的最后一个
	 *
	 * @return  void
	 */
	private function pop_vars() {
		$key = array_pop($this -> _temp_key);
		$val = array_pop($this -> _temp_val);
		if (!empty($key)) {
			eval($key);
		}
	}

	/**
	 * 处理smarty开头的预定义变量
	 *
	 * @access  public
	 * @param   array   $indexes
	 *
	 * @return  string
	 */
	private function _compile_smarty_ref(&$indexes) {
		/* Extract the reference name. */
		$_ref = $indexes[0];
		switch ($_ref) {
			case 'now' :
				$compiled_ref = 'time()';
				break;
			case 'foreach' :
				array_shift($indexes);
				$_var = $indexes[0];
				$_propname = $indexes[1];
				switch ($_propname) {
					case 'index' :
						array_shift($indexes);
						$compiled_ref = "(\$this->_foreach['$_var']['iteration'] - 1)";
						break;
					case 'first' :
						array_shift($indexes);
						$compiled_ref = "(\$this->_foreach['$_var']['iteration'] <= 1)";
						break;
					case 'last' :
						array_shift($indexes);
						$compiled_ref = "(\$this->_foreach['$_var']['iteration'] == \$this->_foreach['$_var']['total'])";
						break;
					case 'show' :
						array_shift($indexes);
						$compiled_ref = "(\$this->_foreach['$_var']['total'] > 0)";
						break;
					default :
						$compiled_ref = "\$this->_foreach['$_var']";
						break;
				}
				break;
			case 'get' :
				$compiled_ref = '$_GET';
				break;
			case 'post' :
				$compiled_ref = '$_POST';
				break;
			case 'cookies' :
				$compiled_ref = '$_COOKIE';
				break;
			case 'env' :
				$compiled_ref = '$_ENV';
				break;
			case 'server' :
				$compiled_ref = '$_SERVER';
				break;
			case 'request' :
				$compiled_ref = '$_REQUEST';
				break;
			case 'session' :
				$compiled_ref = '$_SESSION';
				break;
			default :
				// $this->_syntax_error('$smarty.' . $_ref . ' is an unknown reference', E_USER_ERROR, __FILE__, __LINE__);
				break;
		}
		array_shift($indexes);
		return $compiled_ref;
	}

	private function insert_mod($name) {// 处理动态内容
		list($fun, $para) = explode('|', $name);
		$para = unserialize($para);
		$fun = 'insert_' . $fun;
		return $fun($para);
	}

	private function str_trim($str) {
		/* 处理'a=b c=d k = f '类字符串，返回数组 */
		while (strpos($str, '= ') != 0) {
			$str = str_replace('= ', '=', $str);
		}
		while (strpos($str, ' =') != 0) {
			$str = str_replace(' =', '=', $str);
		}
		return explode(' ', trim($str));
	}

	private function _eval($content) {		
		ob_start();
		eval('?' . '>' . trim($content));
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	private function _require($filename) {
		ob_start();
		include $filename;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	private function html_options($arr) {
		$selected = $arr['selected'];
		if ($arr['options']) {
			$options = (array)$arr['options'];
		} elseif ($arr['output']) {
			if ($arr['values']) {
				foreach ($arr['output'] AS $key => $val) {
					$options["{$arr[values][$key]}"] = $val;
				}
			} else {
				$options = array_values((array)$arr['output']);
			}
		}
		if ($options) {
			foreach ($options AS $key => $val) {
				$out .= $key == $selected ? "<option value=\"$key\" selected>$val</option>" : "<option value=\"$key\">$val</option>";
			}
		}

		return $out;
	}

	private function html_select_date($arr) {
		$pre = $arr['prefix'];
		if (isset($arr['time'])) {
			if (intval($arr['time']) > 10000) {
				$arr['time'] = gmdate('Y-m-d', $arr['time'] + 8 * 3600);
			}
			$t = explode('-', $arr['time']);
			$year = strval($t[0]);
			$month = strval($t[1]);
			$day = strval($t[2]);
		}
		$now = gmdate('Y', $this -> _nowtime);
		if (isset($arr['start_year'])) {
			if (abs($arr['start_year']) == $arr['start_year']) {
				$startyear = $arr['start_year'];
			} else {
				$startyear = $arr['start_year'] + $now;
			}
		} else {
			$startyear = $now - 3;
		}
		if (isset($arr['end_year'])) {
			if (strlen(abs($arr['end_year'])) == strlen($arr['end_year'])) {
				$endyear = $arr['end_year'];
			} else {
				$endyear = $arr['end_year'] + $now;
			}
		} else {
			$endyear = $now + 3;
		}
		$out = "<select name=\"{$pre}Year\">";
		for ($i = $startyear; $i <= $endyear; $i++) {
			$out .= $i == $year ? "<option value=\"$i\" selected>$i</option>" : "<option value=\"$i\">$i</option>";
		}
		if ($arr['display_months'] != 'false') {
			$out .= "</select>&nbsp;<select name=\"{$pre}Month\">";
			for ($i = 1; $i <= 12; $i++) {
				$out .= $i == $month ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
			}
		}
		if ($arr['display_days'] != 'false') {
			$out .= "</select>&nbsp;<select name=\"{$pre}Day\">";
			for ($i = 1; $i <= 31; $i++) {
				$out .= $i == $day ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
			}
		}
		return $out . '</select>';
	}

	private function html_radios($arr) {
		$name = $arr['name'];
		$checked = $arr['checked'];
		$options = $arr['options'];
		$out = '';
		foreach ($options AS $key => $val) {
			$out .= $key == $checked ? "<input type=\"radio\" name=\"$name\" value=\"$key\" checked>&nbsp;{$val}&nbsp;" : "<input type=\"radio\" name=\"$name\" value=\"$key\">&nbsp;{$val}&nbsp;";
		}

		return $out;
	}

	private function html_select_time($arr) {
		$pre = $arr['prefix'];
		if (isset($arr['time'])) {
			$arr['time'] = gmdate('H-i-s', $arr['time'] + 8 * 3600);
			$t = explode('-', $arr['time']);
			$hour = strval($t[0]);
			$minute = strval($t[1]);
			$second = strval($t[2]);
		}
		$out = '';
		if (!isset($arr['display_hours'])) {
			$out .= "<select name=\"{$pre}Hour\">";
			for ($i = 0; $i <= 23; $i++) {
				$out .= $i == $hour ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
			}

			$out .= "</select>&nbsp;";
		}
		if (!isset($arr['display_minutes'])) {
			$out .= "<select name=\"{$pre}Minute\">";
			for ($i = 0; $i <= 59; $i++) {
				$out .= $i == $minute ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>";
			}
			$out .= "</select>&nbsp;";
		}
		if (!isset($arr['display_seconds'])) {
			$out .= "<select name=\"{$pre}Second\">";
			for ($i = 0; $i <= 59; $i++) {
				$out .= $i == $second ? "<option value=\"$i\" selected>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>" : "<option value=\"$i\">$i</option>";
			}
			$out .= "</select>&nbsp;";
		}

		return $out;
	}

	private function cycle($arr) {
		static $k, $old;
		$value = explode(',', $arr['values']);
		if ($old != $value) {
			$old = $value;
			$k = 0;
		} else {
			$k++;
			if (!isset($old[$k])) {
				$k = 0;
			}
		}

		echo $old[$k];
	}

	private function make_array($arr) {
		$out = '';
		foreach ($arr AS $key => $val) {
			if ($val{0} == '$') {
				$out .= $out ? ",'$key'=>$val" : "array('$key'=>$val";
			} else {
				$out .= $out ? ",'$key'=>'$val'" : "array('$key'=>'$val'";
			}
		}
		return $out . ')';
	}

	//生成option选项
	private function smarty_create_pages($params) {
		extract($params);
		if (empty($page)) {
			$page = 1;
		}
		if (!empty($count)) {
			$str = "<option value='1'>1</option>";
			$min = min($count - 1, $page + 3);
			for ($i = $page - 3; $i <= $min; $i++) {
				if ($i < 2) {
					continue;
				}
				$str .= "<option value='$i'";
				$str .= $page == $i ? " selected='true'" : '';
				$str .= ">$i</option>";
			}
			if ($count > 1) {
				$str .= "<option value='$count'";
				$str .= $page == $count ? " selected='true'" : '';
				$str .= ">$count</option>";
			}
		} else {
			$str = '';
		}
		return $str;
	}

}
?>