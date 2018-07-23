<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Project extends MY_Controller {
    protected $data = [];

    public function __construct() {
        parent::__construct();
        $this->data = self::common_data();
        $this -> load -> model('projects_model');
        $this -> load -> model('project_category_model');
    }

    public function index($page = 1){
        //查询项目列表
        $condition = [];
        $condition['select'] = "id,title,pic,content,addtime,catid";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'][] = ['state'=>1];
        $pageinfo = ['page_size'=>8,'page_index'=>$page,'uri_segment'=>3];
        $data_list = $this->projects_model->fetch_list($condition,$pageinfo);
        $this->data['project_list'] = [];
        if(!empty($data_list)){
            $category_list = $this->project_category_model->fetch_all();
            $category_list = array_group($category_list,'id',true);
            foreach($data_list as $val){
                $pic_arr = explode(',',$val['pic']);
                $this->data['project_list'][] = [
                    'id'=>$val['id'],
                    'catname'=>isset($category_list[$val['catid']]) ? $category_list[$val['catid']]['name'] : '',
                    'title'=>$val['title'],
                    'pic'=>$pic_arr,
                    'date'=>date('Y-m-d',$val['addtime']),
                    'desc'=>mb_substr(strip_tags($val['content']),0,200),
                    'url'=>build_url('project','detail',array('id'=>$val['id']))

                ];
            }
        }
        $this->data['page_info'] = $pageinfo;
        //$this->data['page_html'] = $this->get_page($pageinfo);
        $this->data['page_title'] = '参建项目';

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/project_list.html',$this->data);
    }

    public function view($id){
        //查询详情
        $id = (int)$id;
        $detail = $this->projects_model->fetch_row("id=$id",'id,title,catid,pic,content,addtime,state');
        if(empty($detail) || $detail['state'] != 1){
            show_404();
        }
        $detail['date'] = date('Y-m-d',$detail['addtime']);
        $detail['pic'] = explode(',',$detail['pic']);

        //查询分类名
        $category_info = $this->project_category_model->fetch_info($detail['catid']);
        $detail['catname'] = isset($category_info['name']) ? $category_info['name'] : '';

        $prev_news = $this->projects_model->get_prev($id);
        if($prev_news){
            $prev_news['url'] = build_url('project','detail',array('id'=>$prev_news['id']));
        }
        $next_news = $this->projects_model->get_next($id);
        if($next_news){
            $next_news['url'] = build_url('project','detail',array('id'=>$next_news['id']));
        }

        //推荐项目
        $condition = [];
        $condition['select'] = "id,title,catid,pic,addtime,content";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'] = "recommend=1 AND state = 1 ";
        $pageinfo = ['page_size'=>4];
        $data_list = $this->projects_model->fetch_list($condition,$pageinfo);
        $this->data['project_list'] = [];
        if(!empty($data_list)){
            $category_list = $this->project_category_model->fetch_all();
            $category_list = array_group($category_list,'id',true);
            foreach($data_list as $val){
                $pic_arr = explode(',',$val['pic']);
                $this->data['project_list'][] = [
                    'id'=>$val['id'],
                    'catname'=>isset($category_list[$val['catid']]) ? $category_list[$val['catid']]['name'] : '',
                    'title'=>$val['title'],
                    'pic'=>$pic_arr,
                    'url'=>build_url('project','detail',array('id'=>$val['id'])),
                    'desc'=>mb_substr(strip_tags($val['content']),0,200)
                ];
            }
        }

        $this->data['prev_record'] = $prev_news ? array('title'=>$prev_news['title'],'url'=>$prev_news['url']) : array();
        $this->data['next_record'] = $next_news ? array('title'=>$next_news['title'],'url'=>$next_news['url']) : array();
        $this->data['info'] = $detail;
        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/project_detail.html',$this->data);
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