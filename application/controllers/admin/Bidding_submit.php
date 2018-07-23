<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Bidding_submit extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
	    $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/bidding_submit.html',$data);
	}

	public function getlist() {
		$this -> load -> model('bidding_submit_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');
        $request['bidding_id'] = $this -> input -> get_post_int('bidding_id');

		$condition = array();

		if ($request['keyword']) {
			$condition['where'][] = " (company_name LIKE '%" . $request['keyword'] . "%' OR contact LIKE '%" . $request['keyword'] . "%'OR mobile = '" . $request['keyword'] . "')";
		}
		if(!empty($request['bidding_id'])){
            $condition['where'][] = " bidding_id = ".$request['bidding_id'];
        }

		$datalist = $this -> bidding_submit_model -> fetch_all($condition);

		$data = [];
		foreach ($datalist as $key => $value) {
			$data[] = [
			    'id'=>$value['id'],
                'company_name'=>$value['company_name'],
                'contact'=>$value['contact'],
                'mobile'=>$value['mobile'],
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
		$this -> load -> model('bidding_submit_model');	
		$info = $this -> bidding_submit_model -> fetch_info($id);
		if(!$info){
			$this -> return_data(400, '数据不存在');
		}

		//$info = $this -> bidding_submit_model ->fetch_info($uid);
		if (!empty($info)) {
			$info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
		}


		$this -> return_data(200, '', $info);
	}

	public function del() {
		$ids = $this -> input -> get_post('ids');
		if ($ids) {
		    $arr = explode(',',$ids);
			$this -> load -> model('bidding_submit_model');
			foreach ($arr as $id) {
				$info = $this -> bidding_submit_model -> fetch_info($id);
				$res = $this -> bidding_submit_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				$this -> write_log("新闻 [" . join(',', $id_arr) . "] 已被删除!", $delete_data);
				$this -> return_data(200, "操作成功");
			}
		}
		$this -> return_data(400, "操作失败");
	}


}

/* End of file members.php */
/* Location: ./application/controllers/members.php */
