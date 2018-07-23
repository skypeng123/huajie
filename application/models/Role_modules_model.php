<?php

class role_modules_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'role_modules';
	
	//更新角色权限
	function update_permissions_by_rid($dataset,$rid){
		$indata = $updata = $deldata = array();
		$old_modules = $this -> db -> where_in('rid',$rid) -> get($this -> get_table()) -> result_array();
		$old_modules = array_group($old_modules, 'mid',true);
		$dataset = array_group($dataset,'mid',true);
		foreach ($old_modules as $key => $val) {
			if(!isset($dataset[$val['mid']])){
				$deldata[] = $val;
			}
		}
		foreach ($dataset as $key => $val) {
			if(isset($old_modules[$val['mid']])){
				$updata[] = $val;
			}else{
				$indata[] = $val;
			}
		}
		if($deldata){
			$ids = get_array_col($deldata, 'id');	
			$this->delete($ids);		
		}
		if($indata){
			$this->insert($indata);
		}
		if($updata){
			foreach ($updata as $key => $value) {
				$this->update_by_rid_mid($value,$value['rid'],$value['mid']);
			}	
		}
		return TRUE;
	}
	
	function update_by_rid_mid($data,$rid,$mid){
		return $this->update($data,array('rid'=>$rid,'mid'=>$mid));
	}
	
	//获取角色权限
	function get_permissions_by_rid($rid){
		$permissions = array();
		$modules = $this -> db -> where_in('rid',$rid) -> get($this -> get_table()) -> result_array();		
		$permissions = array();
		if($modules){
			foreach ($modules as $key => $value) {
				$permissions[$value['mid']] = explode(',', $value['permissions']);
			}
		}
		return $permissions;
	}
	
	function delete_by_rid($rid){
		return $this -> db -> where('rid',$rid) -> delete($this -> get_table());
	}
	
}

/* End of file role_modules_model.php */
/* Location: ./application/models/role_modules_model.php */	