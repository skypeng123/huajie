<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Jobs extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
	    $data = self::common_data();

        $this -> load -> model('job_department_model');
        $rolelist = $this -> job_department_model -> fetch_all();
        $data['department_list'] = $rolelist;


        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/job_list.html',$data);
	}



	public function getlist() {
		$this -> load -> model('jobs_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');

		$condition = array();

		if ($request['keyword']) {
			$condition['where'][] = " (title LIKE '%" . $request['keyword'] . "%')";
		}

		$datalist = $this -> jobs_model -> fetch_all($condition);
		$catids = get_array_col($datalist, 'department_id');
		if(!empty($catids)){
			$this -> load -> model('job_department_model');
			$catlist = $this->job_department_model->fetch_list_by_ids($catids);
			$catlist && $catlist = array_group($catlist, 'id', true);
		}

		$data = array();
		foreach ($datalist as $key => $value) {
			$data[] = array(
			    'id'=>$value['id'],
                'title'=>$value['title'],
                'department_name'=>isset($catlist[$value['department_id']]) ? $catlist[$value['department_id']]['name'] : '',
                'address'=>$value['address'],
                'state'=>$value['state'],
                'addtime'=>date('Y-m-d H:i:s',$value['addtime'])
            );
		}

		$this -> return_data(200, '', $data);
	}

	public function getinfo() {
		$id = $this -> input -> get_post_int('id');
		if(!$id){
			$this -> return_data(400, '缺少必要参数');
		}
		$this -> load -> model('jobs_model');	
		$info = $this -> jobs_model -> fetch_info($id);
		if(!$info){
			$this -> return_data(400, '数据不存在');
		}

		//$info = $this -> jobs_model ->fetch_info($uid);
		if (!empty($info)) {
			$info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
			$info['updatetime'] = $info['updatetime'] ? date('Y-m-d H:i:s', $info['updatetime']) : '';
			$info['state_str'] = $info['state'] == 1 ? '启用' : '禁用';		
		}


		$this -> return_data(200, '', $info);
	}

	public function save() {
		$this -> load -> model('jobs_model');
		$dataset = array();
        $dataset['id'] = $this -> input -> post_int('id');
        $dataset['title'] = $this -> input -> trim_post('title');
        $dataset['state'] = $this -> input -> post_int('state');
        $dataset['department_id'] = $this -> input -> post_int('department_id');
        $dataset['address'] = $this -> input -> trim_post('address');
        $dataset['number'] = $this -> input -> trim_post('number');
        $dataset['salary'] = $this -> input -> trim_post('salary');
        $dataset['age'] = $this -> input -> trim_post('age');
        $dataset['education'] = $this -> input -> trim_post('education');
        $dataset['experience'] = $this -> input -> trim_post('experience');
        $dataset['sex'] = $this -> input -> trim_post('sex');
        $dataset['url'] = $this -> input -> trim_post('url');
        $dataset['description'] = $this -> input -> trim_post('description');
		if (empty($dataset['title'])) {
			$this -> return_data(400, "职位名称不能为空!");
		}
		if (empty($dataset['description'])) {
			$this -> return_data(400, "职位描述不能为空!");
		}

		$info = array();
		if (!empty($dataset['id'])) {		
			$info = $this -> jobs_model -> fetch_info($dataset['id']);	
			$dataset['updatetime'] = time();
		} else {
			$dataset['addtime'] = time();
		}
		$id = $this -> jobs_model -> save($dataset);

		if ($id) {
			if (!empty($dataset['id'])) {		
				$event = "招聘职位 [" . $id . "] 已被修改";
			} else {
				$event = "招聘职位 [" . $id . "] 已被添加";
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
			$this -> load -> model('jobs_model');
			foreach ($arr as $id) {
				$info = $this -> jobs_model -> fetch_info($id);
				$res = $this -> jobs_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				$this -> write_log("招聘职位 [" . join(',', $id_arr) . "] 已被删除!", $delete_data);
				$this -> return_data(200, "操作成功");
			}
		}
		$this -> return_data(400, "操作失败");
	}


}

/* End of file members.php */
/* Location: ./application/controllers/members.php */
