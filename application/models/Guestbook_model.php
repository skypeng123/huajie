<?php

class guestbook_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'guestbook';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file guestbook_model.php */
/* Location: ./application/models/guestbook_model.php */	