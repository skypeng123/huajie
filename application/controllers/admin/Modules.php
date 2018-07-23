<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Modules extends Admin_Controller {

	public function __construct() {
		parent::__construct();

		$this -> load -> model('modules_model');
	}

	public function index() {
	    $data = self::common_data();
		$data['parent_modules'] = $this -> modules_model -> get_parent();
		$permissions_conf = $this->config->item('permissions'); 
		$perm_arr = array();
		foreach($permissions_conf as $k => $v) {
			$perm_arr[] = array('name'=>$v,'perm'=>$k);
		}
		
		$data['module_perms'] = $perm_arr;

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/module_list.html', $data);
	}

	public function add() {
		$data['parent_modules'] = $this -> modules_model -> get_parent();
		$permissions_list = $this->config->item('permissions'); 
		foreach ($permissions_list as $key => $value) {
			$permissions_list[$key] = array('name'=>$value,'checked'=>1);
		}
		$data['permissions_list'] = $permissions_list; 
		$this -> view('modules_add.html', $data);
	}

	public function edit() {
		$id = $this -> input -> get_int('id');
		$info = $this -> modules_model -> fetch_info($id);	
			
		$info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
		$info['updatetime'] = date('Y-m-d H:i:s', $info['updatetime']);
		$permissions = !empty($info['permissions']) ? explode(',', $info['permissions']) : array();			
		$permissions_list = $this->config->item('permissions'); 
		foreach ($permissions_list as $key => $value) {
			$permissions_list[$key] = array('name'=>$value,'checked'=>in_array($key,$permissions) ? 1 : 0);
		}
		
		$data['info'] = $info;
		$data['permissions_list'] = $permissions_list;
		$data['parent_modules'] = $this -> modules_model -> get_parent();
		$this -> view('modules_edit.html', $data);
	}
	
	public function getlist(){
        $default_mids = $this->input->trim_get_post('mids','trim');
        $mid_arr = $default_mids ? explode(',',$default_mids) : [];

        $datalist = $this -> modules_model -> get_tree();
        $data = [];
        if($datalist){
            foreach($datalist as $key=>$val){
                $row  = array(
                    "id" => $val['id'],
                    "text" => $val['name'],
                    "icon" => "fa fa-folder icon-lg icon-state-success",
                    'type' => 'root',
                    'children'=>false,
                    'state'=>['opened'=>true],
                    'a_attr'=>[]
                );
                if(!empty($val['childrens'])){
                    $row['children'] = [];
                    foreach($val['childrens'] as $child){
                        $icon = $child['state'] == 1 ? 'icon-state-warning' : 'icon-state-default';
                        $row['children'][] = [
                            "id" => $child['id'],
                            "text" => $child['name'],
                            "icon" => "fa fa-file fa-large $icon",
                            "state" => ["selected"=>in_array($child['id'],$mid_arr) ? true : false]
                        ];
                    }
                }
                $data[] = $row;
            }
        }
        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($data);exit;

		//$this->return_data(200,'',$data);
	}

    /**
     * 给角色管理用
     */
    public function gettree(){
        $default_mids = $this->input->trim_get_post('mids');
        $mid_arr = $default_mids ? explode(',',$default_mids) : [];
        $opened = $this->input->get_post_int('opened');
        $opened = $opened ? true : false;

        $datalist = $this -> modules_model -> get_tree();
        $data = [];
        if($datalist){
            foreach($datalist as $key=>$val){
                $row  = array(
                    "id" => $val['id'],
                    "text" => $val['name'],
                    "icon" => "fa fa-folder icon-lg icon-state-success",
                    'type' => 'root',
                    'children'=>false,
                    'state'=>['opened'=>true],

                );
                if(!empty($val['childrens'])){
                    $row['children'] = [];
                    foreach($val['childrens'] as $child){
                        $icon = $child['state'] == 1 ? 'icon-state-warning' : 'icon-state-error';

                        $third_children = [];
                        foreach($child['permissions'] as $perm=>$perminfo){
                            $third_children[] = [
                                "id" => $child['id'].'_'.$perm,
                                "text" => $perminfo['name'],
                                "icon" => "fa fa-file fa-large icon-state-default",
                                "state" => ["selected"=>in_array($child['id'].'_'.$perm,$mid_arr) ? true : false],
                                'children'=>false
                            ];
                        }
                        $row['children'][] = [
                            "id" => $child['id'],
                            "text" => $child['name'],
                            "icon" => "fa fa-folder fa-large $icon",
                            "state" => ['opened'=>true],
                            'children' => $third_children
                        ];
                    }
                }
                $data[] = $row;
            }
        }
        header("Content-Type:application/json; charset=UTF-8");
        echo json_encode($data);exit;

        //$this->return_data(200,'',$data);
    }

    public function getinfo(){
		$id = $this -> input -> get_post_int('id');
		if(!$id){
			$this->return_data(-3,'缺少必要参数');
		}
		$info = $this -> modules_model -> fetch_info($id);
		if(!$info){
			$this->return_data(-3,'数据不存在');
		}
		$permissions = !empty($info['permissions']) ? explode(',', $info['permissions']) : array();			
		$permissions_list = $this->config->item('permissions'); 
		foreach ($permissions_list as $key => $value) {
			$permissions_list[$key] = array('name'=>$value,'checked'=>in_array($key,$permissions) ? 1 : 0);
		}
		$info['module_perms'] = $permissions_list;
		
		
		$this->return_data(200,'',$info);
	}

	public function save() {
		$dataset = array();
		$dataset['id'] = $this->input->post_int('id');
		$dataset['pid'] = $this->input->post_int('pid');
		$dataset['name'] = $this->input->trim_post('name');
		$dataset['tag'] = $this->input->trim_post('tag');
        $dataset['url'] = $this->input->trim_post('url');
		$dataset['icon'] = $this->input->trim_post('icon');
		$dataset['url'] = $this->input->trim_post('url');
		$dataset['display_order'] = $this->input->post_int('display_order');
        $dataset['permissions'] = $this->input->trim_post('permissions');

		if(empty($dataset["icon"]) && $dataset['pid'] == 0){
			$dataset["icon"] = "icon-docs";
		}
		if(empty($dataset['id']) && $dataset['pid'] == 0){
		    //顶级模块强制设为查看权限
            $dataset['permissions'] = 'view';
        }
		if(empty($dataset['name']) || empty($dataset['tag'])){
			$this->return_data(400,"缺少必要参数");
		}
		$info = array();
		if(!empty($dataset['id'])){
			$info = $this->modules_model->fetch_info($dataset['id']);
		}
		if(empty($dataset['id']) || !empty($info) && $info['tag'] != $dataset['tag']){
			$row = $this->modules_model->fetch_row("tag='".$dataset['tag']."'",'tag');
			if($row){
				$this->return_data(400,"模块标识已被使用");
			}
		}
		
		if(!empty($dataset['id'])){			
			$dataset['updatetime'] = time();
		}else{
			$dataset['addtime'] = $dataset['updatetime'] = time();
			$dataset['state'] = 1;
		}

		$id = $this->modules_model->save($dataset);		
		if($id){			
			if (!empty($dataset['id'])) {		
				$event = "模块 [" . $id . "] 已被修改";
			} else {
				$event = "模块 [" . $id . "] 已被添加";
			}
			$dataset['id'] = $id;
			$this->write_log($event, $info,$dataset);
			$this->return_data(200,"操作成功");
		}
		$this->return_data(400,"操作失败");
			
	}

	public function del() {		
		$ids = $this->input->get_post('id');
		if($ids){
		    $id_arr = explode(',',$ids);
			foreach ($id_arr as $id) {
				$info = $this->modules_model->fetch_info($id);						
				$res = $this->modules_model->delete($id);
				if($res){
					$success[] = $id;
					$delete_data[] = $info;
				}				
			}
			if(!empty($success)){
				$this->write_log("模块 [".join(',',$id_arr)."] 已被删除",$delete_data);
				$this->return_data(200,'操作成功');
			}		
		}
		$this->return_data(400,'操作失败');
	}

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */
