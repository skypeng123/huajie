<?php

class ads_category_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'ads_category';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file ads_category_model.php */
/* Location: ./application/models/ads_category_model.php */