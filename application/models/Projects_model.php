<?php

class projects_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'projects';
	

	public function fetch_list_by_ids($ids){
		return $this -> db -> where_in('id', $ids) -> get($this -> get_table()) -> result_array();
	}
	
	function decr_by_id($id,$key){
		return $this -> db -> query("UPDATE ".$this->db->dbprefix($this->get_table()) . " SET $key=$key+1 WHERE id=$id");
	}
	
	function get_prev($id){
        return $this -> db -> where('id <', $id) -> order_by("id","desc") -> get($this -> get_table()) -> row_array();
	}
	function get_next($id){
        return $this -> db -> where('id >', $id) -> order_by("id","asc") -> get($this -> get_table()) -> row_array();
	}
}

/* End of file projects_model.php */
/* Location: ./application/models/projects_model.php */