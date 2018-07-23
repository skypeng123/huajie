<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Site extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    //pv统计
    public function statistics(){
        $this->load->model('site_day_statistics_model');

        $this->site_day_statistics_model->incr_by_date(date('Y-m-d'));

        echo 'var views;';
    }



}