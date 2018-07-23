<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Logs extends Admin_Controller {

	public function __construct() {
		parent::__construct();
		$this -> load -> model('logs_model');
	}

	public function index() {
	    $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load->view('admin/log_list.html',$data);
	}

	public function getlist(){
		$this -> load -> model('members_model');

		$request['keyword'] = $this -> input -> trim_get_post('keyword');

		$condition = array();	
		if (!empty($request['keyword'])) {			
			$condition['where'][] = " username LIKE '%".$request['keyword']."%' OR message LIKE '%".$request['keyword']."%'";
		}
		$condition['order'] = 'id DESC';
		$logs = $this -> logs_model -> fetch_all($condition);

        $data = [];
        if($logs){
            foreach($logs as $key=>$val){
                $data[] = [
                    'id'=>$val['id'],
                    'username'=>$val['username'],
                    'message'=>$val['message'],
                    'addtime'=>date('Y-m-d H:i:s', $val['addtime'])
                ];
            }
        }

		$this->return_data(200,'',$data);
	}


	public function del() {
		$ids = $this -> input -> get_post('id');
		if ($ids) {
		    $arr = explode(',',$ids);
			foreach ((array)$arr as $id) {
			    $id = (int)$id;
				$info = $this -> logs_model -> fetch_info($id);
				$res = $this -> logs_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				//$this -> write_log("日志 [" . join(',', $id_arr) . "] 被删除!", $delete_data);
				$this -> return_data(200, "操作成功");
			}
		}
		$this -> return_data(400, "操作失败");
	}
	


}

/* End of file home.php */
/* Location: ./application/controllers/home.php */
