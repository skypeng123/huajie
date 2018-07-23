<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Pages extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index($page) {
	    $data = self::common_data();

        $this -> load -> model('pages_model');
        $data['page'] = $this->pages_model->fetch_row("tag = '$page'");

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/pages.html',$data);
	}



	public function save() {
		$this -> load -> model('pages_model');
		$dataset = array();
		$dataset['title'] = $this -> input -> trim_post('title');
		$dataset['tag'] = $this -> input -> trim_post('tag');
		$dataset['content'] = $this -> input -> trim_post('content');

        if (empty($dataset['tag'])) {
            $this -> return_data(400, "tag不能为空!");
        }
		if (empty($dataset['title'])) {
			$this -> return_data(400, "标题不能为空!");
		}
		if (empty($dataset['content'])) {
			$this -> return_data(400, "内容不能为空!");
		}

        $info = $this -> pages_model -> fetch_row("tag='".$dataset['tag']."'");
        if($info){
            $dataset['updatetime'] = time();
            $rs = $this -> pages_model -> update($dataset,['tag'=>$dataset['tag']]);
        }else{
            $dataset['addtime'] = time();
            $rs = $this -> pages_model -> insert($dataset);
        }

		if ($rs) {
            $event = "页面 [" . $dataset['title'] . "] 已被编辑";
			$this -> write_log($event, $info, $dataset);

			$this -> return_data(200, "操作成功");
		}
		$this -> return_data(400, "操作失败");
	}


    /**
     * 重新定义方法的调用规则
     * @param string $method 调用方法名称
     * @param string $params 调用参数
     */
    public function _remap($method, $params) {
        if (method_exists($this, $method)) {
            //如果存在对应的控制器，则调用其控制器
            return call_user_func_array(array($this, $method), $params);
        } else {
            array_unshift($params, $method);
            //调用详情控制器
            return call_user_func_array(array($this, 'index'), $params);
        }
    }

}

/* End of file pages.php */
/* Location: ./application/controllers/pages.php */
