<?php

class news_category_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'news_category';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file news_category_model.php */
/* Location: ./application/models/news_category_model.php */	