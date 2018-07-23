<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Logout extends Admin_Controller {


	//退出登录
	public function index(){
        $hjtk = $this -> input -> cookie('hjtk');
		
		if (!$hjtk) {
			//$this->return_data(-3);
		}

		if(!empty($this->uid)){
			$this -> load -> model('members_model');
			$userinfo = $this -> members_model -> fetch_info($this->uid);
			$this->write_log("[".$userinfo['username']."] 注销成功", $userinfo);
		}		
		$this -> input -> set_cookie('hjtk','',-86400);
		
		redirect($this -> config -> item('admin_url').'login');
	}
	


}

/* End of file login.php */
/* Location: ./application/controllers/login.php */
