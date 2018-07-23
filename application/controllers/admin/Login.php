<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Login extends Admin_Controller {

	/**
	 * 登录页面
	 */
	public function index() {
        $data = self::common_data();
		$data['reurl'] = $this->input->trim_get_post('reurl');
        $data['rmusername'] = $this->remember ? $this->username : "";

        $this->load->view('admin/login.html',$data);
	}

	/**
	 * 登录逻辑代码
	 */
	public function submit() {
		$username = $this -> input -> trim_post('username');
		$password = $this -> input -> trim_post('password');
		$remember = (int)$this -> input -> post_int('remember');
		$reurl = $this->input->trim_get_post('reurl');

		//check input
		if (empty($username) || empty($password)) {
			$this->return_data(400,'用户名和密码不能为空');
		}

		//db
		$this -> load -> model('members_model');
		$userinfo = $this -> members_model -> fetch_info_by_username($username);
		if(empty($userinfo)){
			$this->return_data(400,'帐号不存在');
		}
		if(!empty($userinfo) && $userinfo['status'] != 1){
			$this->return_data(400,'帐号已被禁止登录');
		}
		
		if($userinfo['password'] == password($password, $userinfo['pwdkey'])){			
			//session有效时间

            /*
             *  iss: jwt签发者
                sub: jwt所面向的用户
                aud: 接收jwt的一方
                exp: jwt的过期时间，这个过期时间必须要大于签发时间
                nbf: 定义在什么时间之前，该jwt都是不可用的.
                iat: jwt的签发时间
                jti: jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击。
             */
            $t = time();
            $token = array(
                "iss" => "huajie.com",
                "sub" => "administor",
                "iat" => $t,
                "exp" => $t+$this -> config -> item('sess_expiration'),
                "nbf" => $t,
                "jti" => uniqid(),
                "uid" => $userinfo['uid'],
                "uname" => $userinfo['username'],
                "rid" => $userinfo['rid'],
                "rem" => $remember

            );

            //保存至cookie
            $this -> load -> library('JWT/JWT');
            //JWT::decode($jwt, $key, array('HS256'))
            $jwt = JWT::encode($token, $this -> config -> item('encryption_key'));
            $this -> input -> set_cookie('hjtk', $jwt, 2592000);

			//写日志
            $this->uid = $userinfo['uid'];
            $this->username = $userinfo['username'];
			$this->write_log("[".$userinfo['username']."] 登录成功", $userinfo);
			
			//更新登录信息
			$logininfo = array('lasttime'=>time(),'lastip'=>$this->input->ip_address(),'lastua'=>$this->input->user_agent());
			$this -> members_model -> update($logininfo,array("uid"=>$userinfo['uid']));	
			
			$this->return_data(200,"登录成功",array('reurl'=>$reurl));
		}else{
			$this->return_data(400,'密码不正确');
		}
	
	}



}

/* End of file login.php */
/* Location: ./application/controllers/login.php */
