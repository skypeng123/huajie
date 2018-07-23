<?php

class jobs_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'jobs';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file jobs_model.php */
/* Location: ./application/models/jobs_model.php */	