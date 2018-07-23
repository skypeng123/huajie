<?php

class pages_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'pages';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file pages_model.php */
/* Location: ./application/models/pages_model.php */	