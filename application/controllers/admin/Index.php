<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends Admin_Controller {
	
	public function __construct() {
		parent::__construct();

	}
	
	public function index()
	{
        $data = self::common_data();
		$this->load->model('news_model');
		$this->load->model('projects_model');
		$this->load->model('bidding_model');
		$this->load->model('guestbook_model');
        /*        $this->load->model('site_day_statistics_model');
                $this->load->model('site_week_statistics_model');
                $this->load->model('site_month_statistics_model');*/

		
		$data['news_total'] = $this->news_model->fetch_count();
		$data['project_total'] = $this->projects_model->fetch_count();
		$data['bidding_total'] = $this->bidding_model->fetch_count();
		$data['guestbook_total'] = $this->guestbook_model->fetch_count();

        /*
                $condition = [];
                $date = date('Y-m-d',strtotime('-10 day'));
                $condition['where'][] = "date > '$date'";
                $data['day_views'] = $this->site_day_statistics_model->fetch_all($condition);

                $condition = [];
                $week = date('Y-W',strtotime('-10 week'));
                $condition['where'][] = "week > '$week'";
                $data['week_views'] = $this->site_week_statistics_model->fetch_all($condition);

                $condition = [];
                $month = date('Y-m',strtotime('-10 month'));
                $condition['where'][] = "month > '$month'";
                $data['month_views'] = $this->site_month_statistics_model->fetch_all($condition);*/

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
        $this->load->view('admin/home.html',$data);

	}

	public function getSiteStatistics()
    {
        $this->load->model('site_day_statistics_model');
        $condition = [];
        $date = date('Y-m-d',strtotime('-500 day'));
        $condition['order'] = "date ASC";
        $condition['where'][] = "date > '$date'";
        $datalist = $this->site_day_statistics_model->fetch_all($condition);
        $data = [];
        if($datalist){
            foreach($datalist as $val){
                $data[] = ['date'=>$val['date'],'value'=>$val['views']];
            }
        }
        $this -> return_data(200, '', $data);
    }

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */