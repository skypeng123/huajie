<?php

class ads_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'ads';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file ads_model.php */
/* Location: ./application/models/ads_model.php */