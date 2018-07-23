<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Errors extends Admin_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function show404() {
	    $data = self::common_data();

        $data['header'] = $this -> load-> view('admin/header.html',$data,true);
        $data['footer'] = $this -> load-> view('admin/footer.html',$data,true);
        $data['sidebar'] = $this -> load-> view('admin/sidebar.html',$data,true);
		$this -> load -> view('admin/404.html',$data);
	}



}

/* End of file errors.php */
/* Location: ./application/controllers/errors.php */
