<?php

class project_category_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'project_category';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file project_category_model.php */
/* Location: ./application/models/project_category_model.php */