<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * CI_Model扩展
 */

class MY_Model extends CI_Model {

	protected $_pk = 'id';	

	/**
	 * 魔术方法，定义db连接
	 */
	function __get($property) {
		switch ($property) {
			case 'db' :
				if (!isset($this -> db)) {
					$this -> db = $this -> load -> database('default', TRUE);
					$this -> db -> initialize();
				}
				return $this -> db;
				break;
			default :
				return parent::__get($property);
				break;
		}
	}
	public function __construct(){
		parent::__construct();
		//$this -> load -> library('Iredis');	
	}

	/**
	 * 获取表名
	 */
	public function get_table() {
		return $this -> _table;
	}

	/**
	 * 设置表名
	 */
	public function set_table($tablename) {
		$this -> _table = $tablename;
	}

	/**
	 * 查询一行数据
	 *
	 * @param $id string
	 * @param $field string
	 */
	public function fetch_info($id, $field = NULL, $db="db") {
		if ($field == NULL) {
			$field = $this -> _pk;
		}
		return $this -> $db -> where($field, $id) -> get($this -> get_table()) -> row_array();
	}

    /**
     * 查询一行数据
     *
     * @param $id string
     * @param $field string
     */
    public function fetch_row($condition, $field = "*", $db="db") {

        return $this -> $db -> select($field) -> where($condition) -> get($this -> get_table()) -> row_array();
    }

	/**
	 * 查询满足条件的所有数据列表
	 * @param array $condition
	 *
	 */
	public function fetch_all($condition = array(), $db="db") {
		$useOrder = isset($condition['order']) && !empty($condition['order']) ? TRUE : FALSE;
		self::init_sql($condition, $useOrder,$db);
		return $this -> $db -> get($this -> get_table())-> result_array();		
	}

	/**
	 * 查询列表
	 * @param array $condition
	 * @param array $page_info
	 */
	public function fetch_list($condition = array(), &$page_info = NULL, $db="db") {
		self::init_sql($condition, false,$db);
		$page_info['total_rows'] = $this -> $db -> count_all_results($this -> get_table());

		self::init_page($page_info);
		self::init_sql($condition, true,$db);
	
		$result = $this -> $db -> get($this -> get_table(), $page_info['page_size'], $page_info['start'])-> result_array();
		
		return $result;
	}
	
	public function fetch_count($condition = array(), $db="db"){
		self::init_sql($condition, false,$db);
		return $this -> $db -> count_all_results($this -> get_table());
	}

	/**
	 * 保存
	 */
	public function save($data, $db="db") {
		$datacp = array();
		$id = 0;
		foreach ($data as $key => $value) {
			if ($key != $this -> _pk) {
				$datacp[$key] = $value;
			} else {
				$id = $value;
			}
		}
		if ($id) {//更新

			$rs = self::update($datacp, array($this -> _pk => $id), $db);
			$id = $rs ? $id : 0;
		} else {//插入
			$id = self::insert($datacp, $db);
		}
		return $id;
	}
	//删除数据
	public function delete($id, $db="db"){
		$where = is_array($id) ? 'where_in' : 'where';
		return $this -> $db -> $where($this->_pk,$id) -> delete($this -> get_table());
	}
	/**
	 * 插入数据
	 *
	 * @param $data array
	 */
	public function insert($data, $db="db") {
		if (isset($data[0]) && !empty($data[0]) && is_array($data[0])) {
			// 批量插入
			return $this -> $db -> insert_batch($this -> get_table(), $data);
		} else {
			// 插入一条
			$rs = $this -> $db -> insert($this -> get_table(), $data);
			$id = $this -> $db -> insert_id();
			return ($id ? $id : $rs);
		}

	}

	/**
	 * 更新数据
	 *
	 * @param $data array
	 * @param $where array|string
	 */
	public function update($data, $where, $db="db") {
		return $this -> $db -> update($this -> get_table(), $data, $where);
	}

	/**
	 * 将条件数组生成最终的条件查询
	 *
	 * @param $conditions array 条件查询数组
	 * @param $addwhere bloor 是否添加where字符
	 */
	public function init_sql($condition = array(), $use_order_by = true, $dbobj = 'db') {
		//选择条件，字符串
		if (isset($condition['select']) && !empty($condition['select'])) {
			$this -> $dbobj -> select($condition['select']);
		}
		//where条件，数组
		if (isset($condition['where']) && !empty($condition['where'])) {
		    if(is_array($condition['where'])){
                foreach ($condition['where'] as $where) {
                    if (is_array($where)) {
                        foreach ($where as $key => $val) {
                            if(stripos($key,'like') !== false){
                                $key = str_ireplace('like','',$key);
                                $this -> $dbobj -> like(trim($key), $val);
                            }else{
                                //值为只有一个元素的数组时，舍弃数组
                                $val = is_array($val) && count($val) == 1 ? $val[0] : $val;
                                $wheresql = is_array($val) ? 'where_in' : 'where';
                                $this -> $dbobj -> $wheresql($key, $val);
                            }

                        }
                    } else {
                        $this -> $dbobj -> where($where, NULL, FALSE);
                    }
                }
            }else{
                $this -> $dbobj -> where($condition['where'], NULL, FALSE);
            }
		}
		//order条件，字符串
		if ($use_order_by && isset($condition['order']) && !empty($condition['order'])) {
			$this -> $dbobj -> order_by($condition['order']);
		}
	}

	/**
	 * 初始化分页信息
	 *
	 * 分页信息包含以下内容
	 * page_size 分页每页数量，限制范围1-50
	 * total_rows 分页总条目数
	 * page_count 分页总页数
	 * page_index 分页当前页数
	 * start 偏移量查询用的偏移量
	 *
	 * @param $page_info array 分页信息
	 */
	public function init_page(&$page_info = array()) {
		//定义page_size（不建议在model层获取pagesize，由具体业务定）
		if (empty($page_info['page_size'])) {
			//范围为1-50
			$page_info['page_size'] = 20;
		}
        $page_info['page_index'] = isset($page_info['page_index']) ? $page_info['page_index'] : 1;
		$page_info['total_rows'] = isset($page_info['total_rows']) ? $page_info['total_rows'] : 0;

		//设置page_count,总页数
		if ($page_info['page_size']) {
			$page_info['page_count'] = ceil($page_info['total_rows'] / $page_info['page_size']);
		} else {
			$page_info['page_count'] = 0;
		}
		//page_index范围 1-page_count
		$page_info['page_index'] = max($page_info['page_index'], 1);

		//定义start
		$start = $this -> input -> get_int('start');
		if ($start) {
			$page_info['start'] = max($start, 0);
		} else {
			$page_info['start'] = ($page_info['page_index'] - 1) * $page_info['page_size'];
			$page_info['start'] = max($page_info['start'], 0);
		}

	}

}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */
