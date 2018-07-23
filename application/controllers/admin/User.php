<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class User extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
        $data = self::common_data();
        $this -> load -> model('roles_model');
		$rolelist = $this -> roles_model -> fetch_all();
		$data['role_list'] = $rolelist;

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load-> view('admin/user_list.html', $data);

	}

	public function getlist() {

		$this -> load -> model('members_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');

		$condition = array();
        $condition['order'] = 'uid DESC';
		if ($request['keyword']) {
			$condition['where'][] = " (username LIKE '%" . $request['keyword'] . "%' OR mobile LIKE '%" . $request['keyword'] . "%')";
		}

		$datalist = $this -> members_model -> fetch_all($condition);


		$this -> load -> model('roles_model');
		$rolelist = $this -> roles_model -> fetch_all();
		if (!empty($rolelist)) {
			foreach ($rolelist as $key => $value) {
				$rolemap[$value['id']] = $value['name'];
			}
		}

        $status_arr = [1=>'启用',2=>'停用'];

        $data = [];
        if(!empty($datalist)){
            foreach($datalist as $key=>$val){
                $role_name = isset($rolemap[$val['rid']]) ? $rolemap[$val['rid']] : '';
                $status_str = $status_arr[$val['status']];
                $data[] = ['uid'=>$val['uid'],'username'=>trim($val['username']),'role'=>$role_name,'mobile'=>$val['mobile'],'status'=>$status_str,'addtime'=>date('Y-m-d H:i:s',$val['addtime'])];

            }
        }
		$this -> return_data(200, '', $data);
	}

	public function getinfo() {


		$uid = $this -> input -> get_post_int('uid');
		if(!$uid){
			$this->return_data(400,'缺少必要参数');
		}
		$this -> load -> model('members_model');
		$this -> load -> model('roles_model');
		$info = $this -> members_model -> fetch_info($uid);
		if(!$info){
			$this->return_data(400,'数据不存在');
		}

		if (!empty($info)) {
			$info['addtime'] = date('Y-m-d H:i', $info['addtime']);
			$info['updatetime'] = date('Y-m-d H:i', $info['updatetime']);
			$info['lasttime'] = !empty($info['lasttime']) ? date('Y-m-d H:i:s', $info['lasttime']) : '';
			$info['status_str'] = $info['status'] == 1 ? '启用' : '停用';
			$role = $this->roles_model->fetch_info($info['rid']);
			$info['role'] = $role['name'];
		}


		$this -> return_data(200, '', $info);
	}

	public function save() {
		$this -> load -> model('members_model');

		$uid = $this -> input -> post_int('uid');
		$rid = $this -> input -> post_int('role');
		$status = $this -> input -> post_int('status');
		$mobile = $this -> input -> trim_post('mobile');
		$username = $this -> input -> trim_post('username');
		$password = $this -> input -> trim_post('password');
        $repassword = $this -> input -> trim_post('repassword');
        $oldpassword = $this -> input -> trim_post('oldpassword');

		if (empty($uid) && empty($username)) {
			$this -> return_data(400, "用户名不能为空");
		}
		if (empty($uid) && empty($password)) {
			$this -> return_data(400, "密码不能为空");
		}
		if (!empty($username) && strlen($username) < 6 || strlen($username) > 12) {
			$this -> return_data(400, "用户名长度必须为6-12位");
		}
		if (!empty($password) && (strlen($password) < 6 || strlen($password) > 12)) {
			$this -> return_data(400, "密码长度必须为6-12位");
		}
        if (!empty($repassword) && (strlen($repassword) < 6 || strlen($repassword) > 12)) {
            $this -> return_data(400, "密码长度必须为6-12位");
        }
        if(!empty($password) && $password != $repassword){
            $this -> return_data(400, "两次输入的密码不一致");
        }
		$pwdkey = random_str(8);



		$info = $dataset = [];

		if (!empty($uid)) {
			$info = $this -> members_model -> fetch_info($uid);
            //验证旧密码
            if($oldpassword && password($oldpassword,$info['pwdkey']) != $info['password']){
                $this -> return_data(400, "用户密码错误!");
            }
			//设置新密码
			if (!empty($password)) {
				$dataset['password'] = password($password, $pwdkey);
				$dataset['pwdkey'] = $pwdkey;
			}

            $dataset['uid'] = $uid;
            isset($_REQUEST['role']) && $dataset['rid'] = $rid;
            isset($_REQUEST['status']) && $dataset['status'] = $status;
            isset($_REQUEST['mobile']) && $dataset['mobile'] = $mobile;
            $dataset['updatetime'] = time();

		} else {
			$result = $this -> members_model -> fetch_row("username = '" . $username . "'",'uid');
			if ($result) {
				$this -> return_data(400, "用户名已经存在!");
			}
            isset($_REQUEST['role']) && $dataset['rid'] = $rid;
            isset($_REQUEST['status']) && $dataset['status'] = $status;
            isset($_REQUEST['mobile']) && $dataset['mobile'] = $mobile;
            $dataset['username'] = $username;
            $dataset['password'] = password($password, $pwdkey);
            $dataset['pwdkey'] = $pwdkey;
            $dataset['addtime'] = $dataset['updatetime'] = time();
		}

		$save_uid = $this -> members_model -> save($dataset);

		if ($save_uid) {
			if (!empty($uid)) {
				//修改了用户的角色，踢下线
				if (isset($dataset['rid']) && $dataset['rid'] != $info['rid'] && $this->uid == $uid) {
					$this -> input -> set_cookie('hjtk','',-86400);
				}

				$event = "帐号 [" . $uid . "] 已被修改";
			} else {
				$event = "帐号 [" . $uid . "] 已被添加";
			}
			$dataset['uid'] = $save_uid;
			$this -> write_log($event, $info, $dataset);

			$this -> return_data(200, "操作成功");
		}
		$this -> return_data(200, "操作失败");
	}

	public function del() {
		$ids = $this -> input -> get_post('ids');
		if ($ids) {
		    $arr = explode(',',$ids);
			$this -> load -> model('members_model');
			foreach ($arr as $id) {
				$info = $this -> members_model -> fetch_info($id);
				$res = $this -> members_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				$this -> write_log("帐号 [" . join(',', $id_arr) . "] 已被删除!", $delete_data);
				$this -> return_data(200, "操作成功");
			}
		}
		$this -> return_data(400, "操作失败");
	}

}

/* End of file members.php */
/* Location: ./application/controllers/members.php */
