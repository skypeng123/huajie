<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Guestbook extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
	    $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/guestbook.html',$data);
	}

	public function getlist() {
		$this -> load -> model('guestbook_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');

		$condition = array();

		if ($request['keyword']) {
			$condition['where'][] = " (name LIKE '%" . $request['keyword'] . "%' OR content LIKE '%" . $request['keyword'] . "%')";
		}

		$datalist = $this -> guestbook_model -> fetch_all($condition);

		$data = [];
		foreach ($datalist as $key => $value) {
			$data[] = [
			    'id'=>$value['id'],
                'name'=>$value['name'],
                'mobile'=>$value['mobile'],
                'email'=>$value['email'],
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
		$this -> load -> model('guestbook_model');	
		$info = $this -> guestbook_model -> fetch_info($id);
		if(!$info){
			$this -> return_data(400, '数据不存在');
		}

		//$info = $this -> guestbook_model ->fetch_info($uid);
		if (!empty($info)) {
			$info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
		}


		$this -> return_data(200, '', $info);
	}

	public function del() {
		$ids = $this -> input -> get_post('ids');
		if ($ids) {
		    $arr = explode(',',$ids);
			$this -> load -> model('guestbook_model');
			foreach ($arr as $id) {
				$info = $this -> guestbook_model -> fetch_info($id);
				$res = $this -> guestbook_model -> delete($id);
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
