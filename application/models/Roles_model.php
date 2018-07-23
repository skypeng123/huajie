<?php

class roles_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'roles';	

	
	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id',$ids) -> get($this -> get_table()) -> result_array(); 
	}
	
}

/* End of file members_model.php */
/* Location: ./application/models/members_model.php */	