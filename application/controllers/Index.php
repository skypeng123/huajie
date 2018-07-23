<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(){
        $data = self::common_data();

        $this -> load -> model('ads_model');
        $ads_catid = 1; //首页bannar广告
        $condition = [];
        $condition['select'] = "id,title,pic,description,addtime,url";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'] = "catid=$ads_catid AND state = 1";
        $pageinfo = ['page_size'=>4];
        $data['ads_list'] = $this->ads_model->fetch_list($condition,$pageinfo);


        $this -> load -> model('pages_model');
        $data['profile'] = $this->pages_model->fetch_row("tag = 'profile'",'id,title,tag,content');
        if(!empty($data['profile'])){
            $data['profile']['desc'] = trim(str_replace('集团简介','',strip_tags($data['profile']['content'])));
        }

        $this -> load -> model('news_model');
        //查询4条图片新闻
        $condition = [];
        $condition['select'] = "id,title,pic,content,addtime,url";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'] = "recommend=1 AND state = 1 AND pic!=''";
        $pageinfo = ['page_size'=>4];
        $data_list = $this->news_model->fetch_list($condition,$pageinfo);
        $data['img_news_list'] = [];
        $news_id_arr = [];
        if(!empty($data_list)){
            foreach($data_list as $val){
                $data['img_news_list'][] = [
                    'id'=>$val['id'],
                    'title'=>$val['title'],
                    'pic'=>$val['pic'],
                    'date'=>date('Y-m-d',$val['addtime']),
                    'addtime'=>$val['addtime'],
                    'url'=>!empty($val['url']) ? $val['url'] : build_url('news','detail',array('id'=>$val['id'])),
                    'desc'=>mb_substr(strip_tags($val['content']),0,100)
                ];

                $news_id_arr[] = $val['id'];
            }
        }
        //查询3条文字新闻
        $news_id_str = join(',',$news_id_arr);
        $condition = [];
        $condition['select'] = "id,title,pic,content,addtime,url";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'] = "recommend=1 AND state = 1".($news_id_str ? " AND id not in ($news_id_str) " : "");
        $pageinfo = ['page_size'=>3];
        $data_list = $this->news_model->fetch_list($condition,$pageinfo);
        $data['text_news_list'] = [];
        if(!empty($data_list)){
            foreach($data_list as $val){
                $data['text_news_list'][] = [
                    'id'=>$val['id'],
                    'title'=>$val['title'],
                    'pic'=>$val['pic'],
                    'url'=>!empty($val['url']) ? $val['url'] : build_url('news','detail',array('id'=>$val['id'])),
                    'date'=>date('Y-m-d',$val['addtime']),
                    'addtime'=>$val['addtime'],
                    'desc'=>mb_substr(strip_tags($val['content']),0,100)
                ];
            }
        }

        //参建项目
        $condition = [];
        $condition['select'] = "id,catid,title,pic,addtime,content";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'] = "recommend=1 AND state = 1 AND pic!=''";
        $pageinfo = ['page_size'=>8];
        $this -> load -> model('projects_model');
        $this -> load -> model('project_category_model');
        $data_list = $this->projects_model->fetch_list($condition,$pageinfo);
        $data['project_list'] = [];
        if(!empty($data_list)){
            $category_list = $this->project_category_model->fetch_all();
            $category_list = array_group($category_list,'id',true);
            foreach($data_list as $val){
                $pic_arr = explode(',',$val['pic']);
                $data['project_list'][] = [
                    'id'=>$val['id'],
                    'catname'=>isset($category_list[$val['catid']]) ? $category_list[$val['catid']]['name'] : '',
                    'title'=>$val['title'],
                    'pic'=>$pic_arr[0],
                    'url'=>build_url('project','detail',array('id'=>$val['id'])),
                    'desc'=>mb_substr(strip_tags($val['content']),0,100)
                ];
            }
        }
        $data['header'] = $this->load->view('front/header.html',$data,true);
        $data['footer'] = $this->load->view('front/footer.html',$data,true);
        $this->load->view('front/index.html',$data);
    }

}