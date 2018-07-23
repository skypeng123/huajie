<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bidding extends MY_Controller {
    protected $data = [];

    public function __construct() {
        parent::__construct();
        $this->data = self::common_data();
        $this -> load -> model('bidding_model');
        $this -> load -> model('bidding_category_model');
        $this -> load -> model('bidding_submit_model');
    }

    public function index($catid = 1,$page = 1){
        //会员招标需要登录
        if($catid == 2 && !$this->uid){
            redirect($this->config->item("base_url").'login?reurl='.get_url());
        }
        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $category_map = $category_list ? array_group($category_list,'id',true) : [];
        if(!isset($category_map[$catid])){
            show_404();
        }
        $this->data['category_list'] = $category_list;
        $this->data['curr_cat'] = $category_map[$catid];

        //查询招标列表
        $condition = [];
        $condition['select'] = "id,title,content,addtime,filename,start_date,end_date";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'][] = ['catid'=>$catid];
        $condition['where'][] = ['state'=>1];
        $pageinfo = ['page_size'=>6,'page_index'=>$page,'uri_segment'=>3];
        $data_list = $this->bidding_model->fetch_list($condition,$pageinfo);
        $this->data['bidding_list'] = [];
        if(!empty($data_list)){
            foreach($data_list as $val){
                $this->data['bidding_list'][] = [
                    'id'=>$val['id'],
                    'title'=>$val['title'],
                    'addtime'=>$val['addtime'],
                    'date'=>date('Y-m-d',$val['addtime']),
                    'desc'=>mb_substr(strip_tags($val['content']),0,100),
                    'url'=>build_url('bidding','detail',array('id'=>$val['id']))

                ];
            }
        }
        $this->data['page_info'] = $pageinfo;
  

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_list.html',$this->data);
    }

    public function view($id,$page = 1){
        $id = (int)$id;
        $detail = $this->bidding_model->fetch_row("id=$id",'id,uid,title,catid,content,addtime,state,filename,fileurl,start_date,end_date');
        if(empty($detail) || $detail['state'] != 1){
            show_404();
        }

        //普通会员无权限查看会员招标
        if($detail['catid'] == 2 && !$this->uid){
            show_404();
        }

        $this->data['info'] = $detail;

        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $category_map = $category_list ? array_group($category_list,'id',true) : [];
        $this->data['category_list'] = $category_list;
        $this->data['curr_cat'] = isset($category_map[$detail['catid']]) ? $category_map[$detail['catid']] : [];

        //投标列表
        $condition = [];
        $condition['select'] = "id,company_name,contact,mobile,filename,fileurl,remark,addtime";
        $condition['order'] = "order_num ASC,id DESC";
        $condition['where'][] = ['bidding_id'=>$id];
        $pageinfo = ['page_size'=>10,'page_index'=>$page,'uri_segment'=>3];
        $bidding_submit = $this->bidding_submit_model->fetch_list($condition,$pageinfo);
        $this->data['bidding_submit'] = [];
        if($bidding_submit){
            foreach($bidding_submit as $val){
                $val['json'] = json_encode($val);
                $this->data['bidding_submit'][] = $val;
            }
        }

        $this->data['page_info'] = $pageinfo;

        $this->data['my_submit'] = [];
        if($this->uid){
            $this->data['my_submit'] = $this->bidding_submit_model->fetch_row("uid = ".$this->uid." AND bidding_id=$id");
        }


        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_detail.html',$this->data);
    }

    //发布招标
    public function create(){
        if(!$this->uid){
            redirect($this->config->item("base_url").'login?reurl='.get_url());
        }
        //管理员及评审才有权限发布招标
        if($this->rid > 2){
            show_404();
        }
        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $this->data['category_list'] = $category_list;


        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_create.html',$this->data);
    }

    public function edit($id){
        if(!$this->uid){
            redirect($this->config->item("base_url").'login?reurl='.get_url());
        }
        //管理员及评审才有权限发布招标
        if($this->rid > 2 ){
            show_404();
        }
        $id = (int)$id;
        $detail = $this->bidding_model->fetch_row("id=$id",'id,uid,title,catid,content,addtime,state,filename,fileurl,start_date,end_date');
        if(empty($detail) || $detail['state'] != 1){
            show_404();
        }

        $detail['url'] = build_url('bidding','detail',['id'=>$id]);
        $this->data['info'] = $detail;

        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $category_map = $category_list ? array_group($category_list,'id',true) : [];
        $this->data['category_list'] = $category_list;
        $this->data['curr_cat'] = isset($category_map[$detail['catid']]) ? $category_map[$detail['catid']] : [];

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_edit.html',$this->data);
    }
    //搜索供应商
    public function search(){
        if(!$this->uid){
            redirect($this->config->item("base_url").'login?reurl='.get_url());
        }
        //管理员及评审才有权限发布招标
        if($this->rid >2 ){
            show_404();
        }
        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $this->data['category_list'] = $category_list;


        $keyword = $this->input->trim_get('title',true);
        $page = $this->input->get_post_int('page');

        //投标列表
        $this->data['bidding_submit'] = [];
        $pageinfo = ['page_size'=>10,'page_index'=>$page,'uri_segment'=>3];
        if(!empty($keyword)){
            $condition = [];
            $condition['select'] = "id,bidding_id,company_name,contact,mobile,filename,fileurl,remark,addtime";
            $condition['order'] = "order_num ASC,id DESC";
            !empty($keyword) && $condition['where'][] = ['company_name like'=>$keyword];
            $bidding_submit = $this->bidding_submit_model->fetch_list($condition,$pageinfo);
            if($bidding_submit){
                $bidding_id_arr = get_array_col($bidding_submit,'bidding_id');
                $condition = [];
                $condition['where'][] = ['id'=>$bidding_id_arr];
                $bidding_list = $this->bidding_model->fetch_all($condition);
                $bidding_list && $bidding_list = array_group($bidding_list,'id',true);
                foreach($bidding_submit as $val){
                    $val['bidding_name'] = isset($bidding_list[$val['bidding_id']]) ? $bidding_list[$val['bidding_id']]['title'] : '';
                    $val['json'] = json_encode($val);
                    $this->data['bidding_submit'][] = $val;
                }
            }
        }
        $this->data['page_info'] = $pageinfo;
        $this->data['keyword'] = $keyword;


        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_search.html',$this->data);
    }

    //我的招标
    public function my(){
        if(!$this->uid){
            redirect($this->config->item("base_url").'login?reurl='.get_url());
        }

        //招标分类列表
        $category_list = $this->bidding_category_model->fetch_all();
        if($category_list){
            foreach($category_list as $key=>$cat){
                $category_list[$key]['url'] = build_url('bidding','cate',array('id'=>$cat['id']));
            }
        }
        $this->data['category_list'] = $category_list;

        $page = $this->input->get_post_int('page');
        //查询招标列表
        $condition = [];
        $condition['select'] = "id,bidding_id";
        $condition['order'] = "id DESC";
        $condition['where'][] = ['uid'=>$this->uid];
        $pageinfo = ['page_size'=>4,'page_index'=>$page,'uri_segment'=>3];
        $data_list = $this->bidding_submit_model->fetch_list($condition,$pageinfo);
        $this->data['bidding_list'] = [];
        if(!empty($data_list)){
            $bidding_id_arr = get_array_col($data_list,'bidding_id');
            $condition = [];
            $condition['select'] = "id,uid,title,content,addtime,filename,start_date,end_date";
            $condition['where'][] = ['id'=>$bidding_id_arr];
            $data_list = $this->bidding_model->fetch_all($condition);
            foreach($data_list as $val){
                $this->data['bidding_list'][] = [
                    'id'=>$val['id'],
                    'title'=>$val['title'],
                    'addtime'=>$val['addtime'],
                    'start_date'=>$val['start_date'],
                    'end_date'=>$val['end_date'],
                    'desc'=>mb_substr(strip_tags($val['content']),0,100),
                    'url'=>build_url('bidding','detail',array('id'=>$val['id']))

                ];
            }
        }
        $this->data['page_info'] = $pageinfo;

        $this->data['header'] = $this->load->view('front/header.html',$this->data,true);
        $this->data['footer'] = $this->load->view('front/footer.html',$this->data,true);
        $this->load->view('front/bidding_my.html',$this->data);
    }

    //保存招标
    public function save() {
        if(!$this->uid){
            redirect($this->config->item("base_url").'login');
        }
        $this -> load -> model('bidding_model');
        $dataset = array();
        $dataset['id'] = $this -> input -> post_int('id');
        $dataset['catid'] = $this -> input -> post_int('catid');
        $dataset['title'] = $this -> input -> trim_post('title');
        $dataset['start_date'] = $this -> input -> trim_post('start_date');
        $dataset['end_date'] = $this -> input -> trim_post('end_date');
        $dataset['content'] = $this -> input -> trim_post('content');
        $dataset['fileurl']  = $this -> input -> trim_post('fileurl');
        $dataset['filename']  = $this -> input -> trim_post('filename');

        if (empty($dataset['title'])) {
            $this -> return_data(400, "标题不能为空!");
        }
        if (empty($dataset['content'])) {
            $this -> return_data(400, "内容不能为空!");
        }
        if(empty($dataset['filename'])){
            $dataset['fileurl'] = '';
        }

        $info = array();
        if (!empty($dataset['id'])) {
            $info = $this -> bidding_model -> fetch_info($dataset['id']);
            $dataset['updatetime'] = time();
        } else {
            $dataset['uid'] = $this->uid;
            $dataset['state'] = 1;
            $dataset['addtime'] = time();
        }

        $id = $this -> bidding_model -> save($dataset);

        if ($id)
            $this -> return_data(200, "操作成功",['url'=>build_url('bidding','detail',['id'=>$id])]);
        else
            $this -> return_data(400, "操作失败");
    }

    //保存招标
    public function submit() {
        if(!$this->uid){
            $this -> return_data(401, "用户未登录!");
        }
        $this -> load -> model('bidding_submit_model');

        $dataset = array();
        $dataset['id'] = $this -> input -> post_int('id');
        $dataset['bidding_id'] = $this -> input -> post_int('bidding_id');
        $dataset['company_name'] = $this -> input -> trim_post('company_name');
        $dataset['contact'] = $this -> input -> trim_post('contact');
        $dataset['mobile'] = $this -> input -> trim_post('mobile');
        $dataset['remark'] = $this -> input -> trim_post('remark');
        $dataset['fileurl']  = $this -> input -> trim_post('fileurl');
        $dataset['filename']  = $this -> input -> trim_post('filename');

        if (empty($dataset['company_name'])) {
            $this -> return_data(400, "公司名称不能为空!");
        }
        if (empty($dataset['contact'])) {
            $this -> return_data(400, "联系人不能为空!");
        }
        if (empty($dataset['mobile'])) {
            $this -> return_data(400, "手机号码不能为空!");
        }
        if (empty($dataset['fileurl'])) {
            $this -> return_data(400, "标书不能为空!");
        }
        if(empty($dataset['filename'])){
            $dataset['fileurl'] = '';
        }

        $info = array();
        if (!empty($dataset['id'])) {
            $info = $this -> bidding_submit_model -> fetch_info($dataset['id']);
            if($info['uid'] != $this->uid){
                show_404();
            }
            $dataset['updatetime'] = time();
        } else {
            $dataset['uid'] = $this->uid;
            $dataset['state'] = 1;
            $dataset['addtime'] = time();
        }

        $id = $this -> bidding_submit_model -> save($dataset);

        if ($id)
            $this -> return_data(200, "操作成功");
        else
            $this -> return_data(400, "操作失败");
    }

    //上传文件
    public function upload() {

        if (!isset($_FILES['bidding_file']) || !is_uploaded_file($_FILES['bidding_file']["tmp_name"]) || $_FILES['bidding_file']["error"] != 0) {
            $this -> return_data(400, "无效的上传");
        }

        //上传至临时目录
        $file_path = 'bidding/' . date('Ym') . '/';
        if (!is_dir($this -> config -> item("upload_path") . $file_path)) {
            create_dir($this -> config -> item("upload_path") . $file_path,0777);
        }

        //得到文件名和扩展
        $arr = explode('.', $_FILES['bidding_file']['name']);
        $ext = '.' . end($arr);
        $filename = str_replace($ext, '', $_FILES['bidding_file']['name']);
        //生成新文件名
        $filestr = md5($_FILES['bidding_file']['name']);
        $newname = $filestr . $ext;
        //上传图片
        $newfile = $this -> config -> item("upload_path") . $file_path . $newname;
        move_uploaded_file($_FILES['bidding_file']['tmp_name'], $newfile);
        $fileurl = $this -> config -> item("upload_url") . $file_path . $newname;


        $this -> return_data(200, '上传成功', array('src' => $fileurl,'filename'=>$filename.$ext));

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