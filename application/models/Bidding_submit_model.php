<?php

class bidding_submit_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'bidding_submit';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file bidding_submit_model.php */
/* Location: ./application/models/bidding_submit_model.php */	