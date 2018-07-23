<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * CI_Controller扩展
 * author:jip
 */
class MY_Controller extends CI_Controller {
	protected $module = '';
	protected $action = '';
    protected $uid = 0;
    protected $username = '';
    protected $rid = 0;
	
	public function __construct() {
		
		parent::__construct();
		//当前模块
		$this->module = $this->uri->segment(1);	
		$this->module = $this->module ? $this->module : 'index';
		//当前动作
		$this->action = $this->uri->segment(2);	
		$this->action = $this->action ? $this->action : 'index';

        //登录信息解析
        $this -> _auth();

	}
    protected function common_data(){
		$data['module'] = $this->module;
        $data['action'] = $this->action;
        $data['uid'] = $this->uid;
        $data['username'] = $this->username;
        $data['rid'] = $this->rid;

        $data['site_name'] = $this -> config -> item('site_name');
        $data['site_url'] = $this -> config -> item('base_url');
        $data['statics_url'] = $this -> config -> item('static_url');
		return $data;
	}

    //登录验证
    private function _auth() {
        $hjtk = $this -> input -> cookie('hjtk');
        if(!$hjtk)
            return -1;

        //token解码
        try{
            $this -> load -> library('JWT/JWT');
            $token_info = (array)JWT::decode($hjtk, $this -> config -> item('encryption_key'), array('HS256'));
        }catch (Exception $e){
            //var_dump($e);
            if($e->getMessage() == 'Expired token'){
                return -3;
            }
            return -2;
        }

        if(!$token_info)
            return -2;

        if(!empty($token_info['rem']) && !empty($token_info['uname'])){
            $this->remember = $token_info['rem'];
            $this->username = $token_info['uname'];
        }

        //nbf 验证
        if(time() < $token_info['nbf'])
            return -3;

        //exp 过期验证
        if(time() > $token_info['exp'])
            return -3;

        if(!$token_info['uid'] || !$token_info['uname'] || !$token_info['rid'])
            return -4;

        //延长token有效期
        $token_info['exp'] = time()+$this -> config -> item('sess_expiration');
        $jwt = JWT::encode($token_info, $this -> config -> item('encryption_key'));
        $this -> input -> set_cookie('hjtk', $jwt, 2592000);


        $this->uid = $token_info['uid'];
        $this->username = $token_info['uname'];
        $this->rid = $token_info['rid'];
        $this->remember = $token_info['rem'];
        return 1;
    }
 	protected function return_data($status = 0, $msg = '', $data = array()) {
		$return = array();
		$return['code'] = $status;		
		$return['msg'] = $msg;
		$return['data'] = $data;	
		exit(json_encode($return));
	}
	
    function get_page($pageinfo)
    {
    	$url = get_url();
        if(!empty($pageinfo['uri_segment'])){	            
            $page = $this->uri->segment($pageinfo['uri_segment']);
            if($page && is_numeric($page))
                $url = rtrim($url,$page);         	            
        } 
        $this->config->load('pagination');
        $config = $this->config->item('pagination') ;
        $config['total_rows'] = $pageinfo['total_rows'];
        $config['per_page'] = $pageinfo['page_size'];		
        $this->load->library('CPage');
        $this->cpage->initialize($config); 
		$this->cpage->base_url = $url;   
		$this->cpage->uri_segment = $pageinfo['uri_segment'];   
        return  $this->cpage->create_links();
    }
}

class Admin_Controller extends CI_Controller {
	protected $uid = 0;
	protected $username = '';
	protected $rid = 0;
	protected $remember = 0;
	protected $module = '';
	protected $action = '';
	

	public function __construct() {
		
		parent::__construct();
		//当前模块
		$this->module = $this->uri->segment(2);
		if($this->module == 'pages'){
		    $this->module = $this->uri->segment(3);
        }
		//当前动作
		$this->action = $this->uri->segment(3);	
		$this->action = $this->action ? $this->action : 'index';		

		//登录信息解析
		$this -> _auth();

		$need_auth = 1;
		//不需要验证登录信息及权限的模块
		$filter_module_arr = array('login');
		if(!empty($filter_module_arr) && in_array($this->module, $filter_module_arr)){
			$need_auth = 0;
		}
		
		/*检测到未登录则跳转至登录页*/
		if($need_auth){
			$is_ajax = $this->input->is_ajax_request();
			if($is_ajax){
				if (!$this -> uid) {					
					$this->return_data(401,'您的登录信息已经过期，请重新登录!',['reurl'=>$this->config->item('admin_url').$this->module]);
				}			
				if(!$this->_check_permissions()){
					$this->return_data(403,'您没有权限访问此页面!');
				}	
			}else{				
				if (!$this -> uid) {
					header("Location:".config_item("admin_url")."login?reurl=".get_url());
				}			
				if(!$this->_check_permissions()){
					header("Location:".config_item("admin_url")."login");
				}	
			}
		}
		
	}

	//登录验证
	private function _auth() {	
		$hjtk = $this -> input -> cookie('hjtk');
        if(!$hjtk)
            return -1;

        //token解码
        try{
            $this -> load -> library('JWT/JWT');
            $token_info = (array)JWT::decode($hjtk, $this -> config -> item('encryption_key'), array('HS256'));
        }catch (Exception $e){
            //var_dump($e);
            if($e->getMessage() == 'Expired token'){
                return -3;
            }
            return -2;
        }

        if(!$token_info)
            return -2;

        if(!empty($token_info['rem']) && !empty($token_info['uname'])){
            $this->remember = $token_info['rem'];
            $this->username = $token_info['uname'];
        }

        //nbf 验证
        if(time() < $token_info['nbf'])
            return -3;

        //exp 过期验证
        if(time() > $token_info['exp'])
            return -3;

        if(!$token_info['uid'] || !$token_info['uname'] || !$token_info['rid'])
            return -4;

        //延长token有效期
        $token_info['exp'] = time()+$this -> config -> item('sess_expiration');
        $jwt = JWT::encode($token_info, $this -> config -> item('encryption_key'));
        $this -> input -> set_cookie('hjtk', $jwt, 2592000);


        $this->uid = $token_info['uid'];
        $this->username = $token_info['uname'];
        $this->rid = $token_info['rid'];
        $this->remember = $token_info['rem'];
        return 1;
	}
	/**
	 * 检查用户对模块的权限
	 */
	private function _check_permissions(){	
		if(!$this->module || !$this->rid || !$this->uid) return TRUE;
		//接下来检查权限
		$this->load->model('roles_model');
		$role = $this->roles_model->fetch_info($this->rid);
		if($role && empty($role['isadmin'])){	
			$this->load->model('role_modules_model');
			$role_modules = $this->role_modules_model->get_permissions_by_rid($this->rid);			
			//$role_mids = get_array_col($role_modules, 'mid');
			$this->load->model('modules_model');
			$curmodule = $this->modules_model->fetch_info($this->module,'tag');
			
			if(!empty($curmodule)){
				if(!isset($role_modules[$curmodule['id']])){
					//此角色对当前模块无权限
					return FALSE;
				}
				//检查模块动作权限
				if($this->action){
					//动作和对应的权限关系
					$actions = $this->config->item('action_permissions');
					foreach ($actions as $key => $value) {
						if(in_array($this->action, $value) && !in_array($key, $role_modules[$curmodule['id']])){
							//没有该模块此动作的权限
							return FALSE;
						}
					}	
				}								
			}			
		}
		return TRUE;
	}

	
	protected function common_data(){
		$modules = array();
		$this->load->model('modules_model');
		$modules_list = array();	
		if(!empty($this->uid)){			
			$this->load->model('roles_model');
			$this->load->model('role_modules_model');
			$modules = $this -> modules_model -> get_tree();
			$role = $this->roles_model->fetch_info($this->rid);		
			
			$role_modules = $this->role_modules_model->get_permissions_by_rid($this->rid);					
			foreach($modules as $key=>&$val){
				foreach ($val['childrens'] as $k => &$v) {
					if($role['isadmin'] != 1 && !isset($role_modules[$v['id']])){
						unset($val['childrens'][$k]);
					}
				}
				if($role['isadmin'] == 1 || isset($role_modules[$val['id']])){
					$modules_list[] = $val;
				}

			}
			
		}
		
		$data['modules'] = $modules_list;
		$data['uid'] = $this->uid;
		$data['sessid'] = md5($this->uid);
		$data['username'] = $this->username;		
		$data['cmodule'] = $this->module;
		$info = $this->modules_model->fetch_info($this->module,"tag");
		$data['cmodule_name'] = $info ? $info["name"] : '后台首页';
		$data['cmodule_url'] = !empty($info['url']) ? $info["url"] : $info["tag"];
        $data['cmodule_tag'] = $info ? $info["tag"] : '';
		$parent = $this->modules_model->get_parent_by_tag($this->module);
		$data['cmodule_parent'] = $parent ? $parent['tag'] : $this->module;

        $data['site_name'] = $this -> config -> item('site_name');
        $data['site_url'] = $this -> config -> item('base_url');
        $data['statics_url'] = $this -> config -> item('static_url');
        $data['admin_url'] = $this -> config -> item('admin_url');
		return $data;
	}

	
	protected function return_data($status = 0, $msg = '', $data = array()) {
	    header("Content-Type:application/json; charset=UTF-8");
		$return = array();
		$return['code'] = $status;		
		$return['msg'] = $msg;
		$return['data'] = $data;	
		echo json_encode($return);
		die();
	}

	

	//记录日志
	protected function write_log($message,$olddata = array(),$newdata = array()){
		$this->load->model('logs_model');
		$dataset = array(
			'uid'=>$this->uid,
			'module'=>$this->module,
            'username'=>$this->username,
			'message'=>$message,
			'newdata'=>($newdata?json_encode($newdata):''),
			'olddata'=>($olddata?json_encode($olddata):''),
			'addtime'=>time()
		);
		return $this->logs_model->insert($dataset);
	}

	
    function get_page($pageinfo)
    {
        $url = get_url();
		if (preg_match("/page\=/is", $url)) {
			$url = preg_replace("/\?page=\d*(&|$)|\&page=\d*(&|$)/", "", $url);
		}       
        $this->config->load('pagination');
        $config = $this->config->item('pagination') ;	
        $config['total_rows'] = $pageinfo['total_rows'];
        $config['per_page'] = $pageinfo['page_size'];		
        $this->load->library('CPage');
        $this->cpage->initialize($config); 
		$this->cpage->base_url=$url;      
        return  $this->cpage->create_links();
    }
}


/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */
