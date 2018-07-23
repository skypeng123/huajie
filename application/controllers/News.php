<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class News extends MY_Controller {
    protected $data = [];

    public function __construct() {
        parent::__construct();
        $this->data = self::common_data();
        $this -> load -> model('news_model');
        $this -> load -> model('news_category_model');
    }

    public function index($catid = 1,$page = 1){
        //新闻分类列表
        $category_list = $this->news_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('news','cate',array('id'=>$cat['id']));
            }
        }
        $category_map = $category_list ? array_group($category_list,'id',true) : [];
        if(!isset($category_map[$catid])){
            show_404();
        }
        $this->data['category_list'] = $category_list;
        $this->data['curr_cat'] = $category_map[$catid];

        //查询新闻列表
        $condition = [];
        $condition['select'] = "id,title,pic,content,addtime,url,views";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'][] = ['catid'=>$catid];
        $condition['where'][] = ['state'=>1];
        $pageinfo = ['page_size'=>6,'page_index'=>$page,'uri_segment'=>3];
        $data_list = $this->news_model->fetch_list($condition,$pageinfo);
        $this->data['news_list'] = [];
        if(!empty($data_list)){
            foreach($data_list as $val){
                $this->data['news_list'][] = [
                    'id'=>$val['id'],
                    'title'=>$val['title'],
                    'pic'=>$val['pic'],
                    'addtime'=>$val['addtime'],
                    'date'=>date('Y-m-d',$val['addtime']),
                    'desc'=>mb_substr(strip_tags($val['content']),0,100),
                    'url'=>!empty($val['url']) ? $val['url'] : build_url('news','detail',array('id'=>$val['id']))

                ];
            }
        }
        $this->data['page_info'] = $pageinfo;

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/news_list.html',$this->data);
    }

    public function view($id){
        $id = (int)$id;
        $detail = $this->news_model->fetch_row("id=$id",'id,title,pic,catid,content,addtime,state,url,views');
        if(empty($detail) || $detail['state'] != 1){
            show_404();
        }
        $detail['date'] = date('Y-m-d',$detail['addtime']);

        //阅读数加1
        $this->news_model->decr_by_id($id,'views');

        $prev_news = $this->news_model->get_prev($id);
        if($prev_news){
            $prev_news['url'] = !empty($prev_news['url']) ? $prev_news['url'] : build_url('news','detail',array('id'=>$prev_news['id']));
        }
        $next_news = $this->news_model->get_next($id);
        if($next_news){
            $next_news['url'] = !empty($next_news['url']) ? $next_news['url'] : build_url('news','detail',array('id'=>$next_news['id']));
        }

        $this->data['prev_record'] = $prev_news ? array('title'=>$prev_news['title'],'url'=>$prev_news['url']) : array();
        $this->data['next_record'] = $next_news ? array('title'=>$next_news['title'],'url'=>$next_news['url']) : array();
        $this->data['info'] = $detail;

        //新闻分类列表
        $category_list = $this->news_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('news','cate',array('id'=>$cat['id']));
            }
        }
        $category_map = $category_list ? array_group($category_list,'id',true) : [];
        $this->data['category_list'] = $category_list;
        $this->data['curr_cat'] = isset($category_map[$detail['catid']]) ? $category_map[$detail['catid']] : [];

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/news_detail.html',$this->data);
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