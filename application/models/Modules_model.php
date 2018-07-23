<?php

class modules_model extends MY_Model{
	protected $_pk = 'id';
	protected $_table = 'modules';
	
	public function fetch_info_by_tag($tag){
		return $this->fetch_info($tag,'tag');
	}
	
	public function get_parent($allow_edit = NULL){
		$condition['where'][] = array('pid'=>0);
		$condition["order"] = "display_order ASC,id ASC";
		if(!is_null($allow_edit)) $condition['where'][] = "allow_edit=$allow_edit";
		$modules = $this -> modules_model -> fetch_all($condition);
		if($modules){
			foreach ($modules as $key => $value) {
				$modules[$key]['permissions'] = $this->format_permissions($value['permissions']);
				
				$condition = array();
				$condition['where'][] = array('pid'=>$value['id']);
				if(!is_null($allow_edit)) $condition['where'][] = "allow_edit=$allow_edit";
				$condition['order'] = "display_order ASC,id ASC";
				$childrens = $this -> modules_model -> fetch_all($condition);
				foreach ($childrens as $k => $v) {
					$childrens[$k]['permissions'] = $this->format_permissions($v['permissions']);					
				}
				$modules[$key]['childrens'] = $childrens;
			}
		}
		return $modules;
	}
	
	public function get_parent_by_tag($tag){
		$row = $this->fetch_info_by_tag($tag);
		if($row){
			$result = $this->fetch_info($row['pid']);
			return $result;
		}
		return;
	}
	
	public function get_tree($allow_edit = NULL){		
		$condition['where'][] = array('pid'=>0);
		if(!is_null($allow_edit)) $condition['where'][] = "allow_edit=$allow_edit";
		$condition['order'] = "display_order ASC,id ASC";
		$modules = $this -> modules_model -> fetch_all($condition);
		if($modules){
			foreach ($modules as $key => $value) {
				$modules[$key]['permissions'] = $this->format_permissions($value['permissions']);
				
				$condition = array();
				$condition['where'][] = array('pid'=>$value['id']);
				if(!is_null($allow_edit)) $condition['where'][] = "allow_edit=$allow_edit";
				$condition['order'] = "display_order ASC,id ASC";
				$childrens = $this -> modules_model -> fetch_all($condition);
				foreach ($childrens as $k => $v) {
					$childrens[$k]['permissions'] = $this->format_permissions($v['permissions']);
					
					$condition = array();
					$condition['where'][] = array('pid'=>$v['id']);
					if(!is_null($allow_edit)) $condition['where'][] = "allow_edit=$allow_edit";
					$condition['order'] = "display_order ASC,id ASC";
					$crens = $this -> modules_model -> fetch_all($condition);
					foreach ($crens as $n => $m) {
						$crens[$n]['permissions'] = $this->format_permissions($m['permissions']);
						$crens[$n]["childrens"] = array();
					}
					$childrens[$k]["childrens"] = $crens;
				}
				$modules[$key]['childrens'] = $childrens;
			}
		}
		return $modules;
	}
	
	function format_permissions($module_permissions){
		$permissions_conf = $this->config->item('permissions');		
		$result = array();
		if(!empty($module_permissions)){
			$module_permissions = explode(',', $module_permissions);
			//var_dump($module_permissions);
			foreach ($module_permissions as $value) {
				$result[$value] = array('name'=>$permissions_conf[$value],'perm'=>$value);
			}
		}
		return $result;
		
	}
	
}

/* End of file modules_model.php */
/* Location: ./application/models/modules_model.php */	