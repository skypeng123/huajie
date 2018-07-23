<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * 封装Redis的Hash类型,List类型,Zset类型的操作方法.
 * 为了性能舍弃GET SET等操作，用Hash类型的方法去代替
 * 主从配置:主写，从读
 * @author jip
 */
class Iredis {
	private $_redis;
	private $_redis_conf = array('host' => '', 'port' => '', 'timeout' => 0, 'socket_type' => 0, 'serializer' => 0);
	private $db = 0;

	function __construct() {
		$CI = &get_instance();
		$config = $CI->config->item('redis');		
		$this->reset_config($config);		
	}
	
	/**
	 * 重新设置redis配置信息
	 *
	 * @param array $config
	 */
	public function reset_config($config) {
		if (!$this -> is_supported()) {
			return FALSE;
		}
		if ($config) {
			$this -> _redis_conf = array(
				'host' => $config['host'], 
				'port' => $config['port'], 
				'timeout' => $config['timeout'], 
				'socket_type' => $config['socket_type']
			);
		}else{
			return FALSE;
		}
		if(isset($config['serializer'])){
				$this -> _redis_conf['serializer'] = $config['serializer'];
		}
		if(isset($config['db'])){
			$this->db=$config['db'];
		}
	}
	/**
	 * 选择一个数据库
	 *
	 * @param int $key
	 */
	public function select($index = 0) {
		$this -> db = $index;
		if ($this -> _redis) {
			return $this -> _redis -> select($this -> db);
		}
	}
	/**
	 * 查看现在数据库有多少key
	 *
	 * @param int $key
	 */
	public function dbsize() {
		$this -> _open();
		return $this -> _redis -> dbsize();
	}

	/**
	 * 统计数量
	 */
	public function count($key) {
		$this -> _open();
		return count($this -> _redis -> keys($key));
	}

	/**
	 * 将字符串值 value 关联到 key
	 *
	 * @param string $key
	 */
	public function set($key, $value, $ttl = 0) {
		$this -> _open();
		if ($ttl) {
			return $this -> _redis -> setex($key, $ttl, $value);
		} else {
			return $this -> _redis -> set($key, $value);
		}
	}

	/**
	 * 设置多个缓存
	 *
	 * @param array $arr
	 * @param int $ttl
	 * @return boolean
	 */
	public function mset($arr) {
		$this -> _open();
		return self::mset($arr);
	}

	/**
	 * 获取缓存值。
	 *
	 * @param string $key
	 */
	public function get($key) {
		$this -> _open();
		return $this -> _redis -> get($key);
	}

	/**
	 * 一次获取多个缓存值
	 * @param array $arr key数组
	 */
	public function mget($arr) {
		$this -> _open();
		return $this -> _redis -> getMultiple($arr);
	}

	/**
	 * 删除给定的一个或多个 key 。
	 *
	 * @param string $key
	 */
	public function del($key) {
		$this -> _open();
		return $this -> _redis -> del($key);
	}

	/**
	 * 检查给定 key 是否存在
	 *
	 * @param string $key
	 */
	public function exists($key) {
		$this -> _open();
		return $this -> _redis -> exists($key);
	}

	/**
	 * 为key 的值加上增量 increment 。
	 *
	 * @param string $key
	 * @param string $field
	 * @param int $increment 自增量
	 */
	public function incr($key, $increment = 1) {
		$this -> _open();
		if ($increment == 1)
			$this -> _redis -> incr($key);
		else
			$this -> _redis -> incrBy($key, $increment);
	}

	/**
	 * 为key 的值加上减量 increment 。
	 *
	 * @param string $key
	 * @param string $field
	 * @param int $increment 自减量
	 */
	public function decr($key, $increment = -1) {
		$this -> _open();
		if ($increment == -1)
			$this -> _redis -> decr($key);
		else
			$this -> _redis -> decrBy($key, $increment);
	}

	/**
	 * 设置 key 缓存有效期
	 *
	 * @param string $key
	 */
	public function expire($key, $second) {
		$this -> _open();
		return $this -> _redis -> expire($key, $second);
	}

	/**
	 * Hash:向名称为$key的$field中添加元素$field—>$value
	 *
	 * @param $key string
	 * @param $field string
	 * @param $value string
	 *
	 */
	public function hash_set($key, $field, $value = '') {
		$this -> _open();
		if (is_array($field))
			$this -> _redis -> hmset($key, $field);
		else
			$this -> _redis -> hset($key, $field, $value);
	}

	/**
	 * Hash:返回名称为$key的hash中所有或者指定的键$field及其对应的$value
	 *
	 * @param $key string
	 * @param $field return array
	 */
	public function hash_get($key, $field = '') {
		$this -> _open();
		if (empty($field)) {
			return $this -> _redis -> hgetall($key);
		} else {
			return is_array($field) ? $this -> _redis -> hmget($key, $field) : $this -> _redis -> hget($key, $field);
		}
	}

	/**
	 * Hash:删除键名KEY的FIELD
	 *
	 * @param $key string
	 * @param $field object
	 *
	 */
	public function hash_del($key, $field = '') {
		$this -> _open();
		if (empty($field)) {
			$this -> _redis -> delete($key);
		} else {
			$this -> _redis -> hdel($key, $field);
		}
	}

	/**
	 * Hash:名称为$key的hash中是否存在键名字为a的值
	 *
	 * @param $key string
	 * @param $field string 字段名
	 * @return boolean
	 */
	public function hash_exists($key, $field) {
		$this -> _open();
		return $this -> _redis -> hexists($key, $field);
	}

	/**
	 * Hash:名称为$key的hash中域的数量
	 *
	 * @param $key string
	 * @param $field string 字段名
	 * @return boolean
	 */
	public function hash_len($key) {
		$this -> _open();
		return $this -> _redis -> hlen($key);
	}

	/**
	 * 为哈希表 key 中的域 field 的值加上增量 increment 。
	 *
	 * @param string $key
	 * @param string $field
	 * @param int $increment 自增量，也可为负数
	 */
	public function hash_incrby($key, $field, $increment = 1) {
		$this -> _open();
		return $this -> _redis -> hIncrBy($key, $field, $increment);
	}

	/**
	 * List:往list集合中添加数据, 默认是往后插入,也就是调用的rPush函数
	 *
	 * @param $key string
	 * @param $value object
	 * @param $tail bool
	 * @return int 如果成功返回该列表中已有的行数,包括刚刚添加成功的. 失败返回false
	 */
	public function list_push($key, $value, $tail = 'rpush') {
		$this -> _open();
		// 在名称为key的list左边(头)/右边（尾）添加一个值为value的元素,如果value已经存在，则不添加
		$mod = $tail ? 'rpush' : 'lpush';
		return $this -> _redis -> {$mod}($key, $value);
	}

	public function list_mpush($key, $arr, $tail = 'rpush') {
		$this -> _open();
		foreach ($arr as $k => $v) {
			self::list_push($key, $v, $tail);
		}
	}

	/**
	 * List:返回名称为key的list左/右的第一个元素的值，并且删除该元素
	 *
	 * @param $key string redis键值
	 * @param $tail bool 默认右(true)
	 * @param $is_array bool 返回值是不是数组, 如果是则返回序列化之后的数组,默认返回字符串类型
	 * @return object
	 */
	public function list_pop($key, $tail = true) {
		$this -> _open();
		$mod = $tail ? 'rpop' : 'lpop';
		return $this -> _redis -> {$mod}($key);
	}

	/**
	 * List:回名称为key的list有多少个元素
	 *
	 * @param $key string redis键值
	 * @return int
	 */
	public function list_size($key) {
		$this -> _open();
		return $this -> _redis -> lsize($key);
	}

	/**
	 * List:返回名称为key的list中index位置的元素
	 *
	 * @param $key string
	 * @param $index int
	 * @param $is_array bool 返回值是不是数组, 如果是则返回序列化之后的数组,默认返回字符串类型
	 * @return object 如果存在$index则返回对应的数据,不存在返回false
	 */
	public function list_get($key, $index) {
		$this -> _open();
		return $this -> _redis -> lget($key, $index);
	}

	/**
	 * List:给名称为key的list中index位置的元素赋值为value,也就是更新list中某个索引的值
	 *
	 * @param $key string
	 * @param $index int ,如果这个索引不存在,则返回false
	 * @param $value object
	 * @return bool 成功返回true,否则返回false
	 */
	public function list_set($key, $index, $value) {
		$this -> _open();
		return $this -> _redis -> lset($key, $index, $value);
	}

	/**
	 * List:返回名称为key的list中start至end之间的元素,如果需要返回所有元素则使用 ( $key , 0, -1);
	 *
	 * @param $key string
	 * @param $start int
	 * @param $end int
	 */
	public function list_range($key, $start = 0, $end = -1) {
		$this -> _open();
		return $this -> _redis -> lrange($key, $start, $end);
	}

	/**
	 * List:删除$key中值为$value的元素, 删除极限是$count个, 删除所有的话$count=0
	 *
	 * @param $key string redis键值
	 * @param $value string 值
	 * @param $count int 默认0,
	 *
	 *        count为0，删除所有值为value的元素，count>0从头至尾删除count个值为value的元素，count<0从尾到头删除|count|个值为value的元素
	 * @return int 返回删除成功的个数
	 */
	public function list_del($key, $value, $count = 0) {
		$this -> _open();
		return $this -> _redis -> lrem($key, $value, $count);
	}

	/**
	 * 返回列表中数据 的个数
	 *
	 * @param string $key
	 */
	public function list_len($key) {
		$this -> _open();
		return $this -> _redis -> llen($key);
	}
	
	/**
	 * SSet:将一个或多个 member元素加入到集合 key当中，已经存在于集合的 member元素将被忽略。
	 *
	 * @param $key string
	 * @param $value string SSet数据成员,同一个key中不能有重复的value
	 * @return int 操作结果
	 */
	public function sset_add($key, $value) {
		$this -> _open();
		return $this -> _redis -> sadd($key, $value);
	}
	
	/**
	 * SSet:返回集合 key中的所有成员
	 *
	 * @param $key string
	 */
	public function sset_members($key) {
		$this -> _open();
		return $this -> _redis -> smembers($key);
	}
	
	/**
	 * SSet:移除集合中的单个元素
	 *
	 * @param $key string
	 */
	public function sset_rem($key, $str) {
		$this -> _open();
		$this -> _redis -> srem($key, $str);
	}

	/**
	 * ZSet:向名称为key的zset中添加元素member，score用于排序。如果指定$overwrite=true并且该元素已经存在，则根据score更新该元素的顺序。
	 *
	 * @param $key string
	 * @param $value string ZSet数据成员,同一个key中不能有重复的value
	 * @param $score int
	 * @param $overwrite int 如果value存在是否覆盖其Score. 默认覆盖
	 * @return int 操作结果
	 */
	public function zset_add($key, $score, $value) {
		$this -> _open();
		return $this -> _redis -> zadd($key, $score, $value);
	}

	/**
	 * ZSet:检查一个Value在有序集中是否存在
	 *
	 * @param $key string
	 * @param $value string
	 * @return bool true:存在,false:不存在
	 */
	public function zset_exists($key, $value) {
		$this -> _open();
		$i = $this -> _redis -> zscore($key, $value);
		return !empty($i);
	}

	/**
	 * ZSet:根据元素索引返回名称为key的zset记录(注意:ZSet类型索引是根据Score排序之后的索引)
	 *
	 * @param $key string
	 * @param $start int
	 * @param $end int
	 * @param $withscores bool 是否输出socre的值，默认false，不输出
	 * @param $sort string 有序集的排序方式
	 * @return array
	 */
	public function zset_range($key, $start = 0, $end = -1, $withscores = false, $sort = 'desc') {
		$this -> _open();
		$mod = ($sort == 'desc') ? 'zrevrange' : 'zrange';	
		if($end>0) $end--;	//不这样做会在结束位置多取一条数据
		return $this -> _redis -> {$mod}($key, $start, $end, $withscores);
	}

	/**
	 * ZSet:根据value删除元素
	 *
	 * @param $key string
	 * @param $value string
	 * @return int 返回受影响行数
	 */
	public function zset_del($key, $value) {
		$this -> _open();
		return $this -> _redis -> zdelete($key, $value);
	}

	/**
	 * ZSet:返回名称为key的zset中score >= star且score <= end的所有元素
	 *
	 * @param $key string
	 * @param $start int
	 * @param $end int
	 * @param $withscores bool
	 * @param $limit int
	 * @return array 查询到的结果
	 */
	public function zset_rangescore($key, $start, $end, $withscores = false, $limit = -1, $sort = 'desc') {
		$this -> _open();	
		$mod = ($sort == 'desc') ? 'ZREVRANGEBYSCORE' : 'ZRANGEBYSCORE';	
		return $this -> _redis -> $mod($key, $start, $end, array('withscores' => $withscores, 'limit' => array(0, $limit)));
	}

	/**
	 * ZSet:返回名称为key的元素的个数
	 *
	 * @param $key string
	 */
	public function zset_len($key) {
		$this -> _open();
		return $this -> _redis -> zcard($key);
	}

	/**
	 * ZSet:返回名称为key的zset中score >= star且score <= end的所有元素的个数
	 *
	 * @param $key string
	 * @param $start int
	 * @param $end int
	 */
	public function zset_count($key, $start, $end) {
		$this -> _open();
		return $this -> _redis -> zCount($key, $start, $end);
	}

	/**
	 * ZSet:删除名称为key的zset中score >= star且score <= end的所有元素，返回删除个数
	 *
	 * @param $key string
	 * @param $start int
	 * @param $end int
	 * @return int 返回删除个数
	 */
	public function zset_delscore($key, $start, $end) {
		$this -> _open();
		return $this -> _redis -> zremrangebyscore($key, $start, $end);
	}
	
	/**
	 * 代理 执行指定命令
	 */
	public function exec($cmd){
		$this -> _open();
		//整理参数
		$args=func_get_args();
		array_splice($args,0,1);
		//动态调用
		return call_user_func_array(array($this -> _redis,$cmd),$args);
	}

	/**
	 * 打开链接
	 */
	private function _open() {
		if (!$this -> _redis) {
			$this -> _redis = new Redis();
			if ($this -> _redis_conf['socket_type'] === 'unix') {
				$connect = @$this -> _redis -> connect($this -> _redis_conf['socket']);
			}else if ($this -> _redis_conf['socket_type']=='tcp-p') {
				$connect = @$this -> _redis -> pconnect($this -> _redis_conf['host'], $this -> _redis_conf['port'], $this -> _redis_conf['timeout']);
			} else {
				$connect = @$this -> _redis -> connect($this -> _redis_conf['host'], $this -> _redis_conf['port'], $this -> _redis_conf['timeout']);
			}
			if ($connect&&isset($this -> _redis_conf['serializer'])) {
				@$this -> _redis -> setOption(Redis::OPT_SERIALIZER, $this -> _redis_conf['serializer']);
			}
			if ($this -> db) {
				$this -> _redis -> select($this -> db);
			}
		}
	}

	public function is_supported() {
		if (!extension_loaded('redis')) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 在脚本结束前调用redis对象的close方法，来关闭连接
	 */
	function __destruct() {

	}

}
