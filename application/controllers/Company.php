<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Company extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index($page = 'profile'){
        if(!in_array($page,['profile','culture','structure','honor','business','contact'])){
            show_404();
        }
        $data = self::common_data();


        $this -> load -> model('pages_model');
        $data['info'] = $this->pages_model->fetch_row("tag = '$page'",'id,title,tag,content');
        if($page == 'profile'){
            $data['qualification'] = $this->pages_model->fetch_row("tag = 'qualification'",'id,title,tag,content');
            $data['talent'] = $this->pages_model->fetch_row("tag = 'talent'",'id,title,tag,content');
            $data['developing'] = $this->pages_model->fetch_row("tag = 'developing'",'id,title,tag,content');
            $data['page_title'] = '集团概况';
            $tpl = 'front/company_profile.html';
        }elseif($page == 'contact'){
            $data['page_title'] = $data['info']['title'];
            $tpl = 'front/company_contact.html';
        }else{
            $data['page_title'] = $data['info']['title'];
            $tpl = 'front/company.html';
        }

        $data['header'] = $this->load->view('front/header.html',$data,true);
        $data['footer'] = $this->load->view('front/footer.html',$data,true);
        $this->load->view($tpl,$data);

    }

    public function submitFeedback(){
        $dataset = [];
        $dataset['uid'] = $this->uid;
        $dataset['name'] = $this -> input -> trim_post('name');
        $dataset['mobile'] = $this -> input -> trim_post('mobile');
        $dataset['email'] = $this -> input -> trim_post('email');
        $dataset['content'] = $this -> input -> trim_post('content');
        $dataset['addtime'] = time();

        if(empty($dataset['name'])){
            $this -> return_data(400, "姓名不能为空");
        }
        if(empty($dataset['mobile'])){
            $this -> return_data(400, "手机号码不能为空");
        }
        if(empty($dataset['content'])){
            $this -> return_data(400, "留言内容不能为空");
        }

        $this -> load -> model('guestbook_model');
        $info = $this->guestbook_model->fetch_row("mobile='".$dataset['mobile']."' AND content='".$dataset['content']."'",'id');
        if($info){
            $this -> return_data(400, "不允许发布重复的留言内容");
        }


        $rs = $this->guestbook_model->insert($dataset);

        $this -> return_data(200, "留言提交成功");
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