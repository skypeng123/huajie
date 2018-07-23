<?php

class job_department_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'job_department';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file job_department_model.php */
/* Location: ./application/models/job_department_model.php */