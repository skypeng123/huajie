<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Ads extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
	    $data = self::common_data();
        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/ads_list.html',$data);
	}

    public function add() {
        $data = self::common_data();
        $this -> load -> model('ads_category_model');
        $con['order'] = 'order_num ASC,id DESC';
        $category_list = $this->ads_category_model->fetch_all($con);
        $data['category_list'] = $category_list;

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
        $this -> load -> view('admin/ads_add.html',$data);
    }

    public function edit($id) {
        $data = self::common_data();
        $this -> load -> model('ads_category_model');
        $con['order'] = 'order_num ASC,id DESC';
        $category_list = $this->ads_category_model->fetch_all($con);
        $data['category_list'] = $category_list;

        $this -> load -> model('ads_model');
        $data['ads'] = $this->ads_model->fetch_info($id);


        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
        $this -> load -> view('admin/ads_edit.html',$data);
    }

	public function getlist() {
		$this -> load -> model('ads_model');
		$request['keyword'] = $this -> input -> trim_get_post('keyword');

		$condition = array();
        $condition['order'] = "order_num ASC,id DESC";
		if ($request['keyword']) {
			$condition['where'][] = " (title LIKE '%" . $request['keyword'] . "%')";
		}

		$datalist = $this -> ads_model -> fetch_all($condition);
		$catids = get_array_col($datalist, 'catid');
		if(!empty($catids)){
			$this -> load -> model('ads_category_model');
			$catlist = $this->ads_category_model->fetch_list_by_ids($catids);
			$catlist && $catlist = array_group($catlist, 'id', true);
		}

		$data = [];
		foreach ($datalist as $key => $value) {
			$data[] = [
			    'id'=>$value['id'],
                'catname'=>isset($catlist[$value['catid']]) ? $catlist[$value['catid']]['name'] : '',
                'title'=>$value['title'],
                'pic'=>$value['pic'],
                'order_num'=>$value['order_num'],
                'state'=>$value['state'],
                'addtime'=>date('Y-m-d H:i:s',$value['addtime'])

            ];
		}

		$this -> return_data(200, '', $data);
	}

	public function getinfo() {
		$id = $this -> input -> get_post_int('id');
		if(!$id){
			$this -> return_data(400, '缺少必要参数');
		}
		$this -> load -> model('ads_model');	
		$info = $this -> ads_model -> fetch_info($id);
		if(!$info){
			$this -> return_data(400, '数据不存在');
		}

		//$info = $this -> ads_model ->fetch_info($uid);
		if (!empty($info)) {
			$info['addtime'] = date('Y-m-d H:i:s', $info['addtime']);
			$info['updatetime'] = $info['modified'] ? date('Y-m-d H:i:s', $info['updatetime']) : '';
			$info['state_str'] = $info['state'] == 1 ? '启用' : '禁用';		
		}


		$this -> return_data(200, '', $info);
	}

	public function save() {
		$this -> load -> model('ads_model');
		$dataset = array();
		$dataset['id'] = $this -> input -> post_int('id');
		$dataset['catid'] = $this -> input -> post_int('catid');	
		$dataset['title'] = $this -> input -> trim_post('title');
		$dataset['state'] = $this -> input -> post_int('state');
        $dataset['order_num'] = $this -> input -> post_int('order_num');
		$dataset['url'] = $this -> input -> trim_post('url');
		$dataset['pic']  = $this -> input -> trim_post('pic');
        $dataset['description'] = $this -> input -> trim_post('description');

		if (empty($dataset['pic'])) {
			$this -> return_data(400, "广告图片不能为空!");
		}


		$info = array();
		if (!empty($dataset['id'])) {		
			$info = $this -> ads_model -> fetch_info($dataset['id']);	
			$dataset['updatetime'] = time();
		} else {
		    empty($dataset['order_num']) && $dataset['order_num'] = 99999;
			$dataset['addtime'] = time();
		}
		$id = $this -> ads_model -> save($dataset);

		if ($id) {
			if (!empty($dataset['id'])) {		
				$event = "广告 [" . $id . "] 已被修改";
			} else {
				$event = "广告 [" . $id . "] 已被添加";
			}
			$dataset['id'] = $id;
			$this -> write_log($event, $info, $dataset);

			$this -> return_data(200, "操作成功");
		}
		$this -> return_data(400, "操作失败");
	}

	public function del() {
		$ids = $this -> input -> get_post('ids');
		if ($ids) {
		    $arr = explode(',',$ids);
			$this -> load -> model('ads_model');
			foreach ($arr as $id) {
				$info = $this -> ads_model -> fetch_info($id);
				$res = $this -> ads_model -> delete($id);
				if ($res) {
					$id_arr[] = $id;
					$delete_data[] = $info;
				}
			}
			if (!empty($id_arr)) {
				$this -> write_log("广告 [" . join(',', $id_arr) . "] 已被删除!", $delete_data);
				$this -> return_data(200, "操作成功");
			}
		}
		$this -> return_data(400, "操作失败");
	}

    /**
     * 保存图片
     * @param $pic
     * @return string
     */
	public function upload_pic($pic){
        //上传至临时目录
        $file_path = 'news/' . date('Ym') . '/';
        $full_path = $this -> config -> item("upload_path") . $file_path;
        if (!is_dir($full_path)) {
            create_dir($full_path);
        }

        if(strpos($pic,'image/gif') !== false){
            $ext = '.gif';
        }elseif(strpos($pic,'image/jpeg') !== false){
            $ext = '.jpg';
        }elseif(strpos($pic,'image/png') !== false){
            $ext = '.png';
        }else{
            return $pic;
        }

        $arr = explode(',',$pic);
        $file_data = base64_decode($arr[1]);

        $filestr = md5($pic);
        $filename = $full_path. $filestr . $ext;
        file_put_contents($filename,$file_data);

        $fileurl = $this -> config -> item("upload_url") . $file_path . $filestr . $ext;
        return $fileurl;
    }

    //上传封面
    public function upload() {
        if (!$this -> uid || md5($this -> uid) != $this -> input -> trim_post('sessid')) {
            $this -> return_data(400, "无文件上传权限");
        }
        if (!isset($_FILES['pic']) || !is_uploaded_file($_FILES['pic']["tmp_name"]) || $_FILES['pic']["error"] != 0) {
            $this -> return_data(400, "无效的上传");
        }

        //上传至临时目录
        $file_path = 'pic/' . date('Ym') . '/';
        if (!is_dir($this -> config -> item("upload_path") . $file_path)) {
            create_dir($this -> config -> item("upload_path") . $file_path);
        }

        //得到文件名和扩展
        $arr = explode('.', $_FILES['pic']['name']);
        $ext = '.' . end($arr);
        $filename = str_replace($ext, '', $_FILES['pic']['name']);
        //生成新文件名
        $filestr = md5($_FILES['pic']['name']);
        $newname = $filestr . $ext;
        //上传图片
        $newfile = $this -> config -> item("upload_path") . $file_path . $newname;
        move_uploaded_file($_FILES['pic']['tmp_name'], $newfile);
        $fileurl = $this -> config -> item("upload_url") . $file_path . $newname;


        $this -> return_data(200, '上传成功', array('fileurl' => $fileurl, 'thumburl' => $fileurl));

    }
}

/* End of file members.php */
/* Location: ./application/controllers/members.php */
