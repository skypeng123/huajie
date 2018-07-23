<?php

class members_model extends MY_Model{
	protected $_pk = 'uid';
	protected $_table = 'members';
	
	public function fetch_info_by_username($username){
		return $this -> db -> where('username', $username) -> get($this -> get_table()) -> row_array();
	}

	public function fetch_list_by_uids($uids){
		return $this -> db -> where_in('uid', $uids) -> get($this -> get_table()) -> result_array();
	}

}

/* End of file members_model.php */
/* Location: ./application/models/members_model.php */	