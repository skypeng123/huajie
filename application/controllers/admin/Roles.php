<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Roles extends Admin_Controller {

	public function __construct() {
		parent::__construct();

		$this -> load -> model('roles_model');
		$this -> load -> model('modules_model');
	}

	public function index() {
        $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load-> view('admin/role_list.html',$data);
	}

	public function save() {
		$dataset = array();
		$dataset['id'] = $this -> input -> post_int('id');
		$dataset['name'] = $this -> input -> trim_post('name');
		$dataset['desc'] = $this -> input -> trim_post('desc');
		$mids = $this -> input -> trim_post('mids');

		if (empty($dataset['name'])) {
			$this->return_data(400, "缺少必要参数");
		}
		$info = array();
		if (!empty($dataset['id'])) {
			$info = $this -> roles_model -> fetch_info($dataset['id']);
			$dataset['updatetime'] = time();
		} else {
			$dataset['addtime'] = $dataset['updatetime'] = time();
			$dataset['state'] = 1;
		}
		$id = $this -> roles_model -> save($dataset);
		if ($id) {

			//更新权限
			$this -> load -> model('role_modules_model');
			$info['permissions'] = $this -> role_modules_model -> get_permissions_by_rid($id);

			$permdata = $modulesdata = array();
			if (!empty($mids)) {
                $modules = explode(',',$mids);
				foreach ($modules as $key => $value) {
				    if(strpos($value,'_') !== false){
                        list($moduleid, $perm) = explode('_', $value);
                        $modulesdata[$moduleid][] = $perm;
                    }
				}
				if ($modulesdata) {
					foreach ($modulesdata as $key => $value) {
						$permdata[] = array('rid' => $id, 'mid' => $key, 'permissions' => join(',', $value));
					}
					$rs = $this -> role_modules_model -> update_permissions_by_rid($permdata, $id);

					$dataset['permissions'] = $permdata;
				}
			} else {
				if (!empty($dataset['id'])) {
					$rs = $this -> role_modules_model -> delete_by_rid($id);
				}
			}
			//更新权限 end
			$event = '角色 '." [".$id."] 已被 ".(!empty($dataset['id'])?"修改":"添加");			
			$dataset['id'] = $id;
			$this -> write_log($event, $info, $dataset);
			$this->return_data(200, "操作成功");
		}
		$this->return_data(400, "操作失败");

	}

	public function del() {
		$ids = $this -> input -> get_post('id');
		if ($ids) {
			foreach ((array)$ids as $id) {
				$info = $this -> roles_model -> fetch_info($id);
				if ($info['isadmin'] == 1) {
					$this->return_data(400, "超级管理员不能被删除!");
				}
				$this -> load -> model('role_modules_model');
				$info['permissions'] = $this -> role_modules_model -> get_permissions_by_rid($id);
				
				$res = $this -> roles_model -> delete($id);
				if ($res) {
					$this -> role_modules_model -> delete_by_rid($id);
					$id_arr[] = $id;
					$delete_data[] = $info;
				}				
			}	
			if(!empty($id_arr)){
				$this->write_log("角色 [".join(',',$id_arr)."] 已被删除",$delete_data);		
				$this->return_data(200, "操作成功");
			}
		}
		$this->return_data(400, "操作失败");
	}

    public function getlist(){
        $request['keyword'] = $this -> input -> trim_get_post('keyword');
        $condition['order'] = 'id desc';
        if (!empty($request['keyword'])) {
            $condition['where'][] = " name LIKE '%".$request['keyword']."%' ";
        }
        $datalist = $this -> roles_model -> fetch_all($condition);
        $data = [];
        if($datalist){
            foreach($datalist as $key=>$val){
                $data[] = ['rid'=>$val['id'],'rname'=>trim($val['name']),'rdesc'=>trim($val['desc']),'addtime'=>date('Y-m-d H:i:s',$val['addtime'])];
            }
        }
        $this->return_data(200,'',$data);
    }

    public function getinfo(){
		$id = $this -> input -> get_post_int('id');
		if(!$id){
			$this->return_data(400,'缺少必要参数');
		}
		$data = $this -> roles_model -> fetch_info($id);
		if(!$data){
			$this->return_data(400,'数据不存在');
		}
		if (!empty($data)) {
			$moduleids = array();
			if (!empty($data['modules'])) {
				$moduleids = explode(',', $data['modules']);
			}
			$data['addtime'] = date('Y-m-d H:i', $data['addtime']);
			$data['updatetime'] = date('Y-m-d H:i', $data['updatetime']);
		}
		$this -> load -> model('role_modules_model');
		$modules_perms = $this -> role_modules_model -> get_permissions_by_rid($id);
		$permslist = array();
		if(!empty($modules_perms)){			
			foreach ($modules_perms as $key => $value) {				
				foreach ($value as $perm) {
					$permslist[] = $key."_$perm";
				}				
			}
		}		
	
		$data['modules_perms'] = $permslist;
		$this->return_data(200,'',$data);
	}
}

/* End of file home.php */
/* Location: ./application/controllers/home.php */
