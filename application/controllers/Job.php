<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Job extends MY_Controller {
    protected $data = [];

    public function __construct() {
        parent::__construct();
        $this->data = self::common_data();
        $this -> load -> model('jobs_model');
        $this -> load -> model('job_department_model');
    }

    public function index($page = 1){
        //查询数据列表
        $condition = [];
        $condition['select'] = "id,title,department_id,address,description,addtime,url";
        $condition['order'] = "id DESC";
        $condition['where'][] = ['state'=>1];
        $pageinfo = ['page_size'=>10,'page_index'=>$page,'uri_segment'=>2];
        $data_list = $this->jobs_model->fetch_list($condition,$pageinfo);
        $this->data['job_list'] = [];
        if(!empty($data_list)){
            $category_list = $this->job_department_model->fetch_all();
            $category_list = array_group($category_list,'id',true);
            foreach($data_list as $val){
                $this->data['job_list'][] = [
                    'id'=>$val['id'],
                    'department_name'=>isset($category_list[$val['department_id']]) ? $category_list[$val['department_id']]['name'] : '',
                    'title'=>$val['title'],
                    'address'=>$val['address'],
                    'date'=>date('Y-m-d',$val['addtime']),
                    'description'=>$val['description'],
                    'url'=>$val['url']

                ];
            }
        }
        $this->data['page_info'] = $pageinfo;
        //$this->data['page_html'] = $this->get_page($pageinfo);
        $this->data['page_title'] = '诚聘英才';

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/job_list.html',$this->data);
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