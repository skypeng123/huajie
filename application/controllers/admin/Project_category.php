<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Project_category extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
        $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
        $this -> load -> view('admin/project_category.html',$data);
	}

	public function getlist() {
		$this -> load -> model('project_category_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');		

		$condition = array();

		if ($request['keyword']) {
			$condition['where'][] = " (name LIKE '%" . $request['keyword'] . "%')";
		}
		$datalist = $this -> project_category_model -> fetch_all($condition);
        $data = [];
        foreach ($datalist as $key => $value) {
            $data[] = [
                'id'=>$value['id'],
                'name'=>$value['name'],
                'order_num'=>$value['order_num'],
                'state'=>$value['state'],
                'addtime'=>date('Y-m-d H:i:s',$value['addtime'])
            ];
        }

		$this -> return_data(200, '', $data);
	}

	public function getinfo() {
		$id = $this -> input -> get_post_int('id');
		if(!$id){
			$this -> return_data(400, '缺少必要参数');
		}
		$this -> load -> model('project_category_model');
		$info = $this -> project_category_model -> fetch_info($id);
		if(!$info){
			$this -> return_data(400, '数据不存在');
		}


		$this -> return_data(200, '', $info);
	}

	public function save() {
		$this -> load -> model('project_category_model');
		$dataset = array();
		$dataset['id'] = $this -> input -> post_int('id');	
		$dataset['name'] = $this -> input -> trim_post('name');
		$dataset['state'] = $this -> input -> post_int('state');
		$dataset['order_num'] = $this -> input -> post_int('order_num');
	
		if (empty($dataset['name'])) {
			$this -> return_data(400, "标题不能为空!");
		}
	

		$info = array();
		if (!empty($dataset['id'])) {		
			$info = $this -> project_category_model -> fetch_info($dataset['id']);
		} else{
            $dataset['addtime'] = time();
        }
		$id = $this -> project_category_model -> save($dataset);
		if ($id) {
			if (!empty($dataset['id'])) {		
				$event = "项目分类 [" . $id . "] 已被修改";
			} else {
				$event = "项目分类 [" . $id . "] 已被添加";
			}
			$dataset['id'] = $id;
			$this -> write_log($event, $info, $dataset);

			$this -> return_data(200, "操作成功");
		}
		$this -> return_data(400, "操作失败");
	}


	public function del() {
		$ids = $this -> input -> get_post('ids');
		if ($ids) {
		    $arr = explode(',',$ids);
			$this -> load -> model('project_category_model');
			foreach ($arr as $id) {
				$info = $this -> project_category_model -> fetch_info($id);
				$res = $this -> project_category_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				$this -> write_log("项目分类 [" . join(',', $id_arr) . "] 已被删除", $delete_data);
				$this -> return_data(200, "项目分类删除成功");
			}
		}
		$this -> return_data(400, "项目分类删除失败");
	}

}

/* End of file members.php */
/* Location: ./application/controllers/members.php */
